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
