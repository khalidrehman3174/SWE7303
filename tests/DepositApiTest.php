<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../api/v1/lib/config.php';
require_once __DIR__ . '/../api/v1/lib/stripe_provider.php';
require_once __DIR__ . '/../api/v1/lib/deposit_repo.php';
require_once __DIR__ . '/../api/v1/lib/deposit_service.php';

final class DepositApiTest extends TestCase
{
    private static mysqli $dbc;

    /** @var int[] */
    private array $createdUserIds = [];

    public static function setUpBeforeClass(): void
    {
        // Use env-only config so local secrets file cannot affect test determinism.
        putenv('APP_ENV=production');
        putenv('STRIPE_SECRET_KEY=');
        putenv('STRIPE_PUBLISHABLE_KEY=');
        putenv('STRIPE_WEBHOOK_SECRET=');

        self::$dbc = mysqli_connect('localhost', 'root', '', 'ledgercore_db');
        self::assertNotFalse(self::$dbc, 'Test DB connection failed');

        mysqli_set_charset(self::$dbc, 'utf8mb4');
        deposit_repo_ensure_schema(self::$dbc);
    }

    public static function tearDownAfterClass(): void
    {
        if (isset(self::$dbc) && self::$dbc instanceof mysqli) {
            mysqli_close(self::$dbc);
        }
    }

    protected function tearDown(): void
    {
        foreach ($this->createdUserIds as $userId) {
            $stmt = mysqli_prepare(self::$dbc, 'DELETE FROM deposits WHERE user_id = ?');
            if ($stmt) {
                mysqli_stmt_bind_param($stmt, 'i', $userId);
                mysqli_stmt_execute($stmt);
                mysqli_stmt_close($stmt);
            }

            $stmt = mysqli_prepare(self::$dbc, 'DELETE FROM wallets WHERE user_id = ?');
            if ($stmt) {
                mysqli_stmt_bind_param($stmt, 'i', $userId);
                mysqli_stmt_execute($stmt);
                mysqli_stmt_close($stmt);
            }

            $stmt = mysqli_prepare(self::$dbc, 'DELETE FROM users WHERE id = ?');
            if ($stmt) {
                mysqli_stmt_bind_param($stmt, 'i', $userId);
                mysqli_stmt_execute($stmt);
                mysqli_stmt_close($stmt);
            }
        }

        $this->createdUserIds = [];
    }

    public function testCreateRejectsInvalidMethod(): void
    {
        $userId = $this->createUser();

        $result = deposit_service_create(self::$dbc, $userId, 'crypto', 100.00, $this->newKey());

        $this->assertFalse($result['ok']);
        $this->assertSame('invalid_method', $result['code']);
    }

    public function testCreateRejectsInvalidAmount(): void
    {
        $userId = $this->createUser();

        $result = deposit_service_create(self::$dbc, $userId, 'bank', 0.00, $this->newKey());

        $this->assertFalse($result['ok']);
        $this->assertSame('invalid_amount', $result['code']);
    }

    public function testAppleDepositCompletesAndCreditsWallet(): void
    {
        $userId = $this->createUser();
        $before = $this->walletBalance($userId, 'GBP');

        $result = deposit_service_create(self::$dbc, $userId, 'apple', 42.50, $this->newKey());

        $this->assertTrue($result['ok']);
        $this->assertSame('completed', $result['deposit']['status']);
        $this->assertSame('sandbox', $result['provider']['mode']);

        $after = $this->walletBalance($userId, 'GBP');
        $this->assertEqualsWithDelta($before + 42.50, $after, 0.0001);
    }

    public function testBankDepositStartsAsPendingProvider(): void
    {
        $userId = $this->createUser();

        $result = deposit_service_create(self::$dbc, $userId, 'bank', 15.00, $this->newKey());

        $this->assertTrue($result['ok']);
        $this->assertSame('pending_provider', $result['deposit']['status']);
        $this->assertSame('sandbox', $result['provider']['mode']);
    }

    public function testCardDepositUsesMockProviderWithoutStripeSecret(): void
    {
        $userId = $this->createUser();

        $result = deposit_service_create(self::$dbc, $userId, 'card', 19.99, $this->newKey());

        $this->assertTrue($result['ok']);
        $this->assertSame('pending_provider', $result['deposit']['status']);
        $this->assertSame('mock', $result['provider']['mode']);
        $this->assertNull($result['provider']['external_reference']);
    }

    public function testCreateIsIdempotentByUserAndIdempotencyKey(): void
    {
        $userId = $this->createUser();
        $idempotencyKey = $this->newKey();

        $first = deposit_service_create(self::$dbc, $userId, 'bank', 50.00, $idempotencyKey);
        $second = deposit_service_create(self::$dbc, $userId, 'bank', 50.00, $idempotencyKey);

        $this->assertTrue($first['ok']);
        $this->assertTrue($second['ok']);
        $this->assertArrayHasKey('deposit', $second);
        $this->assertTrue((bool)($second['idempotent'] ?? false));
        $this->assertSame($first['deposit']['deposit_id'], $second['deposit']['deposit_id']);
    }

    public function testStatusIsNotVisibleToDifferentUser(): void
    {
        $ownerId = $this->createUser();
        $otherId = $this->createUser();

        $created = deposit_service_create(self::$dbc, $ownerId, 'bank', 22.00, $this->newKey());
        $this->assertTrue($created['ok']);

        $status = deposit_service_get_status(self::$dbc, $otherId, $created['deposit']['deposit_id']);

        $this->assertFalse($status['ok']);
        $this->assertSame('not_found', $status['code']);
    }

    public function testCardConfirmCompletesMockDepositAndCreditsWallet(): void
    {
        $userId = $this->createUser();
        $before = $this->walletBalance($userId, 'GBP');

        $created = deposit_service_create(self::$dbc, $userId, 'card', 30.00, $this->newKey());
        $this->assertTrue($created['ok']);

        $confirmed = deposit_service_confirm_card(self::$dbc, $userId, $created['deposit']['deposit_id']);

        $this->assertTrue($confirmed['ok']);
        $this->assertSame('completed', $confirmed['deposit']['status']);

        $after = $this->walletBalance($userId, 'GBP');
        $this->assertEqualsWithDelta($before + 30.00, $after, 0.0001);
    }

    public function testCardConfirmRequiresWebhookForStripeBackedDeposit(): void
    {
        $userId = $this->createUser();

        $created = deposit_service_create(self::$dbc, $userId, 'card', 25.00, $this->newKey());
        $this->assertTrue($created['ok']);

        $depositId = $created['deposit']['deposit_id'];
        $setExternal = deposit_repo_set_external_reference(self::$dbc, $depositId, 'pi_test_123', 'stripe');
        $this->assertTrue($setExternal);

        $confirmed = deposit_service_confirm_card(self::$dbc, $userId, $depositId);

        $this->assertFalse($confirmed['ok']);
        $this->assertSame('webhook_required', $confirmed['code']);
    }

    public function testRetryOnlyAllowedFromFailedOrExpired(): void
    {
        $userId = $this->createUser();

        $created = deposit_service_create(self::$dbc, $userId, 'bank', 12.34, $this->newKey());
        $this->assertTrue($created['ok']);

        $depositId = $created['deposit']['deposit_id'];

        $retryBeforeFailure = deposit_service_retry(self::$dbc, $userId, $depositId);
        $this->assertFalse($retryBeforeFailure['ok']);
        $this->assertSame('invalid_state', $retryBeforeFailure['code']);

        $updated = deposit_repo_update_status(self::$dbc, $depositId, 'failed');
        $this->assertTrue($updated);

        $retryAfterFailure = deposit_service_retry(self::$dbc, $userId, $depositId);
        $this->assertTrue($retryAfterFailure['ok']);
        $this->assertSame('pending_provider', $retryAfterFailure['deposit']['status']);
    }

    public function testSettlementIsIdempotentAndDoesNotDoubleCredit(): void
    {
        $userId = $this->createUser();
        $before = $this->walletBalance($userId, 'GBP');

        $created = deposit_service_create(self::$dbc, $userId, 'card', 60.00, $this->newKey());
        $this->assertTrue($created['ok']);

        $depositId = $created['deposit']['deposit_id'];

        $firstSettle = deposit_service_settle_completed(self::$dbc, $depositId);
        $this->assertTrue($firstSettle['ok']);

        $secondSettle = deposit_service_settle_completed(self::$dbc, $depositId);
        $this->assertTrue($secondSettle['ok']);
        $this->assertTrue((bool)($secondSettle['already_completed'] ?? false));

        $after = $this->walletBalance($userId, 'GBP');
        $this->assertEqualsWithDelta($before + 60.00, $after, 0.0001);
    }

    private function createUser(): int
    {
        $suffix = bin2hex(random_bytes(6));
        $username = 'dep_t_' . $suffix;
        $email = $username . '@example.test';
        $password = password_hash('Password123!', PASSWORD_DEFAULT);
        $refCode = strtoupper(substr('REF' . $suffix, 0, 8));

        $sql = 'INSERT INTO users (username, email, password, referral_code, role, is_verified) VALUES (?, ?, ?, ?, "user", 1)';
        $stmt = mysqli_prepare(self::$dbc, $sql);
        $this->assertNotFalse($stmt, 'Failed to prepare user insert');

        mysqli_stmt_bind_param($stmt, 'ssss', $username, $email, $password, $refCode);
        $ok = mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);

        $this->assertTrue($ok, 'Failed to insert user fixture');

        $userId = (int)mysqli_insert_id(self::$dbc);
        $this->createdUserIds[] = $userId;

        return $userId;
    }

    private function walletBalance(int $userId, string $symbol): float
    {
        $stmt = mysqli_prepare(self::$dbc, 'SELECT balance FROM wallets WHERE user_id = ? AND symbol = ? LIMIT 1');
        $this->assertNotFalse($stmt, 'Failed to prepare wallet lookup');

        mysqli_stmt_bind_param($stmt, 'is', $userId, $symbol);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $row = $result ? mysqli_fetch_assoc($result) : null;
        mysqli_stmt_close($stmt);

        if (!$row) {
            return 0.0;
        }

        return (float)$row['balance'];
    }

    private function newKey(): string
    {
        return 'idem_' . bin2hex(random_bytes(12));
    }
}
