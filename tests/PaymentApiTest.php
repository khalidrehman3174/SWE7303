<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../api/v1/lib/payment_contacts_service.php';
require_once __DIR__ . '/../includes/available_balance.php';

final class PaymentApiTest extends TestCase
{
    private static mysqli $dbc;

    /** @var int[] */
    private array $createdUserIds = [];

    public static function setUpBeforeClass(): void
    {
        self::$dbc = mysqli_connect('localhost', 'root', '', 'ledgercore_db');
        self::assertNotFalse(self::$dbc, 'Test DB connection failed');

        mysqli_set_charset(self::$dbc, 'utf8mb4');
        payment_contacts_ensure_schema(self::$dbc);
        payment_contacts_ensure_transactions_schema(self::$dbc);
        payment_contacts_ensure_withdrawals_schema(self::$dbc);
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
            $this->deleteByUser('payment_contact_transactions', $userId);
            $this->deleteByUser('payment_contacts', $userId);
            $this->deleteByUser('withdrawals', $userId);
            $this->deleteByUser('wallets', $userId);

            $stmt = mysqli_prepare(self::$dbc, 'DELETE FROM users WHERE id = ?');
            if ($stmt) {
                mysqli_stmt_bind_param($stmt, 'i', $userId);
                mysqli_stmt_execute($stmt);
                mysqli_stmt_close($stmt);
            }
        }

        $this->createdUserIds = [];
    }

    public function testCreateContactAndSendPaymentDeductsBalanceAndPersistsRecords(): void
    {
        $userId = $this->createUser();
        $this->setWalletBalance($userId, 'GBP', 150.00);

        $contact = payment_contacts_create(self::$dbc, $userId, 'John Tester', '112233', '12345678');
        $this->assertTrue($contact['ok']);

        $contactId = (int)$contact['contact']['id'];
        $result = payment_contacts_send_payment(self::$dbc, $userId, $contactId, 42.50, 'Invoice #123');

        $this->assertTrue($result['ok']);
        $this->assertSame('payment_sent', $result['code']);
        $this->assertSame($contactId, (int)$result['payment']['contact_id']);
        $this->assertSame('42.50', $result['payment']['amount']);

        $walletAfter = $this->walletBalance($userId, 'GBP');
        $this->assertEqualsWithDelta(107.50, $walletAfter, 0.0001);

        $txCount = $this->countForUserContact('payment_contact_transactions', $userId, $contactId);
        $this->assertSame(1, $txCount);

        $withdrawalsCount = $this->countForUserMethod('withdrawals', $userId, 'contact_payment');
        $this->assertSame(1, $withdrawalsCount);

        $centralBalance = finpay_get_available_balance_gbp(self::$dbc, $userId);
        $this->assertEqualsWithDelta((float)$centralBalance['amount'], (float)$result['balance']['amount'], 0.0001);
    }

    public function testSendPaymentFailsWhenInsufficientBalanceAndDoesNotPersistRecords(): void
    {
        $userId = $this->createUser();
        $this->setWalletBalance($userId, 'GBP', 10.00);

        $contact = payment_contacts_create(self::$dbc, $userId, 'Jane Receiver', '445566', '87654321');
        $this->assertTrue($contact['ok']);

        $contactId = (int)$contact['contact']['id'];
        $result = payment_contacts_send_payment(self::$dbc, $userId, $contactId, 25.00, 'Too much');

        $this->assertFalse($result['ok']);
        $this->assertSame('insufficient_balance', $result['code']);

        $walletAfter = $this->walletBalance($userId, 'GBP');
        $this->assertEqualsWithDelta(10.00, $walletAfter, 0.0001);

        $txCount = $this->countForUserContact('payment_contact_transactions', $userId, $contactId);
        $this->assertSame(0, $txCount);

        $withdrawalsCount = $this->countForUserMethod('withdrawals', $userId, 'contact_payment');
        $this->assertSame(0, $withdrawalsCount);
    }

    public function testSendPaymentValidatesPositiveAmount(): void
    {
        $userId = $this->createUser();
        $this->setWalletBalance($userId, 'GBP', 50.00);

        $contact = payment_contacts_create(self::$dbc, $userId, 'Zero Amount Contact', '001122', '10293847');
        $this->assertTrue($contact['ok']);

        $contactId = (int)$contact['contact']['id'];
        $result = payment_contacts_send_payment(self::$dbc, $userId, $contactId, 0.00, 'Invalid');

        $this->assertFalse($result['ok']);
        $this->assertSame('invalid_amount', $result['code']);
    }

    private function createUser(): int
    {
        $suffix = bin2hex(random_bytes(6));
        $username = 'pay_t_' . $suffix;
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

    private function setWalletBalance(int $userId, string $symbol, float $balance): void
    {
        $stmt = mysqli_prepare(self::$dbc, 'DELETE FROM wallets WHERE user_id = ? AND symbol = ?');
        $this->assertNotFalse($stmt, 'Failed to prepare wallet cleanup');
        mysqli_stmt_bind_param($stmt, 'is', $userId, $symbol);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);

        $stmt = mysqli_prepare(self::$dbc, 'INSERT INTO wallets (user_id, symbol, balance) VALUES (?, ?, ?)');
        $this->assertNotFalse($stmt, 'Failed to prepare wallet insert');
        mysqli_stmt_bind_param($stmt, 'isd', $userId, $symbol, $balance);
        $ok = mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);

        $this->assertTrue($ok, 'Failed to insert wallet fixture');
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

        return (float)($row['balance'] ?? 0.0);
    }

    private function countForUserContact(string $table, int $userId, int $contactId): int
    {
        $sql = "SELECT COUNT(*) AS c FROM {$table} WHERE user_id = ? AND contact_id = ?";
        $stmt = mysqli_prepare(self::$dbc, $sql);
        $this->assertNotFalse($stmt, 'Failed to prepare contact count query');

        mysqli_stmt_bind_param($stmt, 'ii', $userId, $contactId);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $row = $result ? mysqli_fetch_assoc($result) : null;
        mysqli_stmt_close($stmt);

        return (int)($row['c'] ?? 0);
    }

    private function countForUserMethod(string $table, int $userId, string $method): int
    {
        $sql = "SELECT COUNT(*) AS c FROM {$table} WHERE user_id = ? AND method = ?";
        $stmt = mysqli_prepare(self::$dbc, $sql);
        $this->assertNotFalse($stmt, 'Failed to prepare method count query');

        mysqli_stmt_bind_param($stmt, 'is', $userId, $method);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $row = $result ? mysqli_fetch_assoc($result) : null;
        mysqli_stmt_close($stmt);

        return (int)($row['c'] ?? 0);
    }

    private function deleteByUser(string $table, int $userId): void
    {
        $sql = "DELETE FROM {$table} WHERE user_id = ?";
        $stmt = mysqli_prepare(self::$dbc, $sql);
        if (!$stmt) {
            return;
        }

        mysqli_stmt_bind_param($stmt, 'i', $userId);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
    }
}
