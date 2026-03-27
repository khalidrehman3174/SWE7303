# Deposit API Test Report

Date: 2026-03-27  
Scope: Deposit API/service integration tests

## 1. Test command executed

```bash
php vendor/bin/phpunit tests/DepositApiTest.php --colors=always
```

## 2. Raw result summary

```text
PHPUnit 11.5.55 by Sebastian Bergmann and contributors.

Runtime:       PHP 8.2.12
Configuration: C:\xampp\htdocs\FinTech\FinPay\phpunit.xml

...........                                                       11 / 11 (100%)

Time: 00:01.837, Memory: 8.00 MB

OK (11 tests, 72 assertions)
```

## 3. Environment used

- OS: Windows
- PHP runtime: 8.2.12
- PHPUnit: 11.5.55
- DB target: `ledgercore_db` (MySQL via mysqli)
- Test class: `DepositApiTest`
- Stripe mode in tests: forced mock mode by setting empty Stripe env values

## 4. Coverage map (what was validated)

1. Invalid method rejected with `invalid_method`.
2. Invalid amount rejected with `invalid_amount`.
3. Apple deposit completes and credits wallet balance.
4. Bank deposit starts as `pending_provider`.
5. Card deposit in mock provider mode stays `pending_provider` initially.
6. Idempotency key returns same deposit on duplicate create.
7. Cross-user access is blocked (`not_found` for non-owner status lookup).
8. Card confirm completes mock card deposit and credits wallet.
9. Stripe-backed card with external reference returns `webhook_required` on confirm.
10. Retry rejected in invalid state, then accepted after moving to `failed`.
11. Settlement is idempotent and does not double-credit wallet.

## 5. Data safety and cleanup behavior

- Each test creates isolated fixture users with unique usernames/emails.
- `tearDown()` removes created rows from:
  - `deposits`
  - `wallets`
  - `users`
- Tests are designed to be rerun safely without manual cleanup.

## 6. Notes and limitations

- This suite validates deposit business/service behavior directly (integration against real DB), not HTTP endpoint transport formatting.
- Webhook endpoint signature verification is not exercised here; the webhook-required branch is validated via deposit/provider state simulation.
- If you need full endpoint contract validation next, add HTTP-level PHPUnit integration tests for:
  - `/api/v1/deposits/create.php`
  - `/api/v1/deposits/status.php`
  - `/api/v1/deposits/confirm.php`
  - `/api/v1/deposits/retry.php`
