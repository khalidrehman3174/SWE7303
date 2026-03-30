<?php

function api_config(): array
{
    $appEnv = strtolower((string)(getenv('APP_ENV') ?: 'development'));

    $config = [
        'app_env' => $appEnv,
        'stripe_secret_key' => getenv('STRIPE_SECRET_KEY') ?: '',
        'stripe_publishable_key' => getenv('STRIPE_PUBLISHABLE_KEY') ?: '',
        'stripe_webhook_secret' => getenv('STRIPE_WEBHOOK_SECRET') ?: '',
        'api_allowed_origin' => getenv('API_ALLOWED_ORIGIN') ?: '',
        'default_currency' => 'GBP',
        'default_wallet_symbol' => 'GBP',
        'max_deposit_amount' => 100000,
        'btc_network' => strtolower((string)(getenv('BTC_NETWORK') ?: 'testnet')),
        'btc_xpub' => (string)(getenv('BTC_XPUB') ?: ''),
        'btc_required_confirmations' => (int)(getenv('BTC_REQUIRED_CONFIRMATIONS') ?: 2),
        'btc_indexer_base_url' => (string)(getenv('BTC_INDEXER_BASE_URL') ?: ''),
        'btc_watcher_max_blocks_per_run' => (int)(getenv('BTC_WATCHER_MAX_BLOCKS_PER_RUN') ?: 20),
        'btc_watcher_http_timeout_seconds' => (int)(getenv('BTC_WATCHER_HTTP_TIMEOUT_SECONDS') ?: 12),
    ];

    // In production, only environment variables should be used.
    if ($appEnv !== 'production') {
        

        $localConfigFile = __DIR__ . '/config.local.php';
        if (is_file($localConfigFile)) {
            $localConfig = require $localConfigFile;
            if (is_array($localConfig)) {
                $config = array_merge($config, $localConfig);
            }
        }
    }

    return $config;
}
