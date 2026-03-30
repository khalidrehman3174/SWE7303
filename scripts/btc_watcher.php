<?php

// BTC watcher CLI runner
// Usage: php scripts/btc_watcher.php

require_once __DIR__ . '/../includes/db_connect.php';
require_once __DIR__ . '/../api/v1/lib/config.php';
require_once __DIR__ . '/../api/v1/lib/crypto_btc_repo.php';
require_once __DIR__ . '/../api/v1/lib/crypto_btc_watcher_repo.php';
require_once __DIR__ . '/../api/v1/lib/crypto_btc_watcher_service.php';

$cfg = api_config();

// Ensure base BTC schema tables exist before scanning.
crypto_btc_repo_ensure_schema($dbc);
crypto_btc_watcher_repo_ensure_schema($dbc);

$result = crypto_btc_watcher_service_run($dbc, $cfg);

echo json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . PHP_EOL;

if (!empty($result['errors'])) {
    exit(1);
}

exit(0);
