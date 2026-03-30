# FinPay Platform Setup Guide (Cloud Engineer)

This is the one page to set up FinPay so it works in production.

## Goal

Make sure the app can:
- start without errors,
- connect to database,
- process deposits/payments,
- generate BTC deposit addresses,
- run safely and reliably after deployment.

## 1. One-Time Platform Setup

1. Provision runtime host (ECS, VM, or container host).
2. Install PHP 8.2+ with required extensions:
   - mysqli
   - pdo_mysql
   - json
   - mbstring
   - openssl
   - curl
   - gmp (required for BTC address derivation)
3. Install Composer dependencies using `composer install --no-dev --optimize-autoloader`.
4. Configure Apache or Nginx to serve the app from project root.
5. Configure TLS certificate for HTTPS.
6. Create and connect production MySQL database.

## 2. Required Environment Variables

Set these on the runtime service (not inside Git):

1. APP_ENV=production
2. API_ALLOWED_ORIGIN=https://your-frontend-domain
3. STRIPE_SECRET_KEY=...
4. STRIPE_PUBLISHABLE_KEY=...
5. STRIPE_WEBHOOK_SECRET=...
6. DB_HOST=...
7. DB_PORT=3306
8. DB_NAME=...
9. DB_USERNAME=...
10. DB_PASSWORD=...
11. BTC_NETWORK=testnet or mainnet
12. BTC_XPUB=your watch-only xpub/vpub
13. BTC_REQUIRED_CONFIRMATIONS=2 (or your policy)

Use `.env.example` as the template, but store real values in AWS Secrets Manager.
Then map them into ECS task definition environment variables (or ECS secrets).

For compatibility, the app also accepts `DB_USER` and `DB_PASS`.
If DB env vars are not set, local defaults are used automatically:

- host: localhost
- port: 3306
- user: root
- password: empty
- database: ledgercore_db

## 3. Database and Schema Checks

Before go-live:

1. Confirm app can connect to MySQL with production credentials.
2. Confirm base app tables exist (users, wallets, deposits, withdrawals, payment contacts, settings).
3. Confirm BTC tables are auto-created on API bootstrap:
   - crypto_wallet_sources
   - user_crypto_addresses
4. Run one manual deposit-address request from a test account and verify address is stored.

## 4. Web Server PHP Checks (Critical)

If BTC address generation fails with GMP error:

1. Open active php.ini.
2. Enable GMP extension:
   - extension=gmp or extension=php_gmp (depends on build)
3. Restart web server (Apache/Nginx+PHP-FPM).
4. Verify from web runtime, not only CLI.

Note: CLI PHP can have GMP enabled while web PHP does not. Always test from browser/API endpoint.

## 5. Security Baseline

1. Do not keep production secrets in repository files.
2. Store secrets in AWS Secrets Manager (or SSM Parameter Store).
3. Restrict database inbound access to app runtime only.
4. Enable HTTPS redirect and secure cookies.
5. Restrict CORS to exact frontend domain.
6. Rotate BTC_XPUB and Stripe secrets through controlled process.

## 6. Continuous Ops Checklist (Simple)

The cloud engineer should keep doing these continuously:

1. Every deployment:
   - confirm app health endpoint returns 200,
   - test login,
   - test deposit create,
   - test BTC address generation.
2. Daily:
   - check web error logs for new fatal errors,
   - check DB connectivity and latency,
   - check failed API responses.
3. Weekly:
   - verify backups for DB,
   - verify TLS certificate expiry window,
   - verify secret rotation schedule.
4. Monthly:
   - patch OS/runtime,
   - update PHP/composer packages (safe rollout),
   - review security group and firewall rules.

## 7. Fast Troubleshooting

If platform is partially broken, check in this order:

1. Is web server running?
2. Is database reachable?
3. Are environment variables present?
4. Is APP_ENV correctly set to production?
5. Is GMP enabled in web PHP runtime?
6. Do logs show missing table/column errors?

## 8. Hand-off Notes for Cloud Engineer

Use this as your minimum hand-off definition of done:

1. App boots and serves pages over HTTPS.
2. Auth works.
3. Database writes succeed.
4. Stripe keys loaded from env.
5. BTC address generation works from UI.
6. Logs and backup monitoring are in place.

## 9. BTC Auto-Detection Runner

Run the BTC watcher continuously so deposits are detected and credited:

1. Manual run:
   - `php scripts/btc_watcher.php`
2. Linux cron (every minute):
   - `* * * * * /usr/bin/php /path/to/FinPay/scripts/btc_watcher.php >> /var/log/finpay-btc-watcher.log 2>&1`
3. Windows Task Scheduler:
   - Program/script: `C:\xampp\php\php.exe`
   - Add arguments: `C:\xampp\htdocs\FinTech\FinPay\scripts\btc_watcher.php`
   - Trigger: repeat every 1 minute

Required behavior:

1. Watcher scans new BTC blocks.
2. Matches outputs to user derived addresses.
3. Stores events in `btc_deposit_events`.
4. Credits wallet only after required confirmations.
5. Credits are idempotent (same tx output cannot be credited twice).
