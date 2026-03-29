# FinPay Testing Tutorial

This guide explains how to run the different tests in FinPay and how to troubleshoot common failures.

## 1. Prerequisites

- PHP is installed and available in your terminal.
- Composer dependencies are installed (`vendor/` exists).
- MySQL is running and the `ledgercore_db` database is accessible.
- You are in the project root folder (`FinPay`).

If dependencies are missing:

```bash
composer install
```

## 2. Run all tests

From the project root:

```bash
php vendor/bin/phpunit --colors=always
```

This uses `phpunit.xml` and runs all tests inside `tests/`.

## 3. Run one test file

Run only the deposit API suite:

```bash
php vendor/bin/phpunit tests/DepositApiTest.php --colors=always
```

Run only the example test:

```bash
php vendor/bin/phpunit tests/ExampleTest.php --colors=always
```

Run only the payment process suite:

```bash
php vendor/bin/phpunit tests/PaymentApiTest.php --colors=always
```

The payment suite validates:

- Creating a payment contact and sending a payment.
- GBP wallet balance deduction after successful send.
- Transaction and withdrawal persistence for payment sends.
- Insufficient balance protection (no debit and no inserts).
- Invalid amount validation.

## 4. Run one specific test method

Use `--filter` with a method name:

```bash
php vendor/bin/phpunit --filter testCreateIsIdempotentByUserAndIdempotencyKey --colors=always
```

You can also filter by class name:

```bash
php vendor/bin/phpunit --filter DepositApiTest --colors=always
```

## 5. Common workflows

Quick smoke check before pushing code:

```bash
php vendor/bin/phpunit tests/DepositApiTest.php --colors=always
```

Full regression pass:

```bash
php vendor/bin/phpunit --colors=always
```

## 6. Troubleshooting

If you see DB connection errors:

- Verify MySQL is running.
- Confirm credentials in `includes/db_connect.php`.
- Confirm database `ledgercore_db` exists.

If tests fail due to stale data:

- Re-run the test command (the deposit tests clean up fixtures automatically).
- If needed, inspect and clean test rows manually in `users`, `wallets`, and `deposits`.

If PHPUnit command is not found:

- Run via vendor binary: `php vendor/bin/phpunit`.
- If `vendor/` is missing, run `composer install`.

## 7. Adding new tests moving forward

- Put new tests in `tests/`.
- Name files `*Test.php`.
- Extend `PHPUnit\Framework\TestCase`.
- Prefer deterministic tests (avoid live external API calls).
- For API/payment logic, test service/repository behavior first, then endpoint-level behavior if needed.

## 8. Latest deposit suite report

For a full execution report of the deposit suite (command, runtime, assertion totals, and case-by-case coverage), see `docs/deposit-test-report.md`.

## 9. CI / Pipeline Commands

Use these commands in automated pipelines depending on the runner OS.

Windows runner:

```bash
composer install --no-interaction --prefer-dist
vendor\bin\phpunit.bat --testdox
```

Linux/macOS runner:

```bash
composer install --no-interaction --prefer-dist
./vendor/bin/phpunit --testdox
```

Run only payment tests in CI:

Windows:

```bash
vendor\bin\phpunit.bat tests\PaymentApiTest.php --testdox
```

Linux/macOS:

```bash
./vendor/bin/phpunit tests/PaymentApiTest.php --testdox
```

Optional JUnit XML output (for CI test reports):

Windows:

```bash
vendor\bin\phpunit.bat --log-junit build\phpunit-report.xml
```

Linux/macOS:

```bash
./vendor/bin/phpunit --log-junit build/phpunit-report.xml
```
