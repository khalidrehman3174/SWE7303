<?php

// Simple BTC watcher CLI runner (Electrum testnet3-friendly)
// Usage: php scripts/btc_watcher.php

require_once __DIR__ . '/../includes/db_connect.php';
require_once __DIR__ . '/../api/v1/lib/config.php';
require_once __DIR__ . '/../api/v1/lib/crypto_btc_repo.php';
require_once __DIR__ . '/../api/v1/lib/crypto_btc_watcher_repo.php';
require_once __DIR__ . '/../api/v1/lib/crypto_btc_watcher_service.php';

$cfg = api_config();

crypto_btc_repo_ensure_schema($dbc);
crypto_btc_watcher_repo_ensure_schema($dbc);

$network = strtolower((string)($cfg['btc_network'] ?? 'testnet'));
if (!in_array($network, ['testnet', 'mainnet'], true)) {
    $network = 'testnet';
}

$requiredConfirmations = (int)($cfg['btc_required_confirmations'] ?? 2);
if ($requiredConfirmations < 1) {
    $requiredConfirmations = 1;
}

$maxRuntimeSeconds = (int)($cfg['btc_watcher_max_runtime_seconds'] ?? 90);
if ($maxRuntimeSeconds < 10) {
    $maxRuntimeSeconds = 10;
}
if ($maxRuntimeSeconds > 1800) {
    $maxRuntimeSeconds = 1800;
}

$maxAddressesPerRun = (int)($cfg['btc_watcher_max_addresses_per_run'] ?? 500);
if ($maxAddressesPerRun < 1) {
    $maxAddressesPerRun = 1;
}
if ($maxAddressesPerRun > 5000) {
    $maxAddressesPerRun = 5000;
}

$httpTimeout = (int)($cfg['btc_watcher_http_timeout_seconds'] ?? 12);
if ($httpTimeout < 3) {
    $httpTimeout = 3;
}

$baseUrl = trim((string)($cfg['btc_indexer_base_url'] ?? ''));
if ($baseUrl === '') {
    $baseUrl = $network === 'mainnet'
        ? 'https://blockstream.info/api'
        : 'https://blockstream.info/testnet/api';
}
$baseUrl = rtrim($baseUrl, '/');

if (function_exists('set_time_limit')) {
    @set_time_limit($maxRuntimeSeconds + 15);
}

$startedAt = microtime(true);
$runtimeExceeded = static function () use ($startedAt, $maxRuntimeSeconds): bool {
    return (microtime(true) - $startedAt) >= $maxRuntimeSeconds;
};

$stats = [
    'network' => $network,
    'provider' => 'esplora-address',
    'tip_height' => null,
    'addresses_scanned' => 0,
    'address_summaries' => [],
    'tx_confirmations' => [],
    'tx_scanned' => 0,
    'matched_outputs' => 0,
    'events_upserted' => 0,
    'credited' => 0,
    'credit_failed' => 0,
    'stopped_early' => false,
    'stop_reason' => null,
    'runtime_seconds' => 0,
    'errors' => [],
];

try {
    $tipRaw = btc_watcher_http_get_text($baseUrl . '/blocks/tip/height', $httpTimeout);
    $tipHeight = (int)trim($tipRaw);
    if ($tipHeight <= 0) {
        throw new RuntimeException('Invalid tip height response');
    }
    $stats['tip_height'] = $tipHeight;

    $rows = crypto_btc_watcher_repo_list_active_addresses($dbc, $network, $maxAddressesPerRun);
    foreach ($rows as $row) {
        if ($runtimeExceeded()) {
            $stats['stopped_early'] = true;
            $stats['stop_reason'] = 'runtime_limit_reached';
            break;
        }

        $userId = (int)($row['user_id'] ?? 0);
        $address = trim((string)($row['address'] ?? ''));
        if ($userId <= 0 || $address === '') {
            continue;
        }

        $stats['addresses_scanned']++;

        try {
            $encodedAddress = rawurlencode($address);
            $summaryRaw = btc_watcher_http_get_text($baseUrl . '/address/' . $encodedAddress, $httpTimeout);
            $summary = json_decode($summaryRaw, true);
            if (!is_array($summary)) {
                throw new RuntimeException('Invalid address summary JSON');
            }

            // Include the same shape as the browser endpoint for quick verification.
            $stats['address_summaries'][$address] = [
                'address' => (string)($summary['address'] ?? $address),
                'chain_stats' => (array)($summary['chain_stats'] ?? []),
                'mempool_stats' => (array)($summary['mempool_stats'] ?? []),
            ];

            $confirmedSats = (int)($summary['chain_stats']['funded_txo_sum'] ?? 0);
            $unconfirmedSats = (int)($summary['mempool_stats']['funded_txo_sum'] ?? 0);
            if ($confirmedSats <= 0 && $unconfirmedSats <= 0) {
                continue;
            }

            $confirmedTxRaw = btc_watcher_http_get_text($baseUrl . '/address/' . $encodedAddress . '/txs', $httpTimeout);
            $mempoolTxRaw = btc_watcher_http_get_text($baseUrl . '/address/' . $encodedAddress . '/txs/mempool', $httpTimeout);

            $confirmedTxs = json_decode($confirmedTxRaw, true);
            $mempoolTxs = json_decode($mempoolTxRaw, true);
            if (!is_array($confirmedTxs)) {
                $confirmedTxs = [];
            }
            if (!is_array($mempoolTxs)) {
                $mempoolTxs = [];
            }

            $combined = [];
            foreach ($confirmedTxs as $tx) {
                if (!is_array($tx)) {
                    continue;
                }
                $txid = (string)($tx['txid'] ?? '');
                if ($txid !== '') {
                    $combined[$txid] = $tx;
                }
            }
            foreach ($mempoolTxs as $tx) {
                if (!is_array($tx)) {
                    continue;
                }
                $txid = (string)($tx['txid'] ?? '');
                if ($txid !== '') {
                    $combined[$txid] = $tx;
                }
            }

            foreach ($combined as $txid => $tx) {
                if ($runtimeExceeded()) {
                    $stats['stopped_early'] = true;
                    $stats['stop_reason'] = 'runtime_limit_reached';
                    break 2;
                }

                if (!is_array($tx)) {
                    continue;
                }

                $stats['tx_scanned']++;

                $status = is_array($tx['status'] ?? null) ? $tx['status'] : [];
                $isConfirmed = !empty($status['confirmed']);
                $blockHeight = $isConfirmed ? (int)($status['block_height'] ?? 0) : 0;
                $confirmations = ($isConfirmed && $blockHeight > 0)
                    ? max(0, $tipHeight - $blockHeight + 1)
                    : 0;

                $existing = $stats['tx_confirmations'][$txid] ?? null;
                if (!is_array($existing) || $confirmations >= (int)($existing['confirmations'] ?? -1)) {
                    $stats['tx_confirmations'][$txid] = [
                        'confirmations' => $confirmations,
                        'confirmed' => $isConfirmed,
                        'block_height' => ($blockHeight > 0 ? $blockHeight : null),
                    ];
                }

                $vout = $tx['vout'] ?? [];
                if (!is_array($vout)) {
                    $vout = [];
                }

                foreach ($vout as $voutIndex => $out) {
                    if (!is_array($out)) {
                        continue;
                    }

                    $outputAddress = (string)($out['scriptpubkey_address'] ?? '');
                    if ($outputAddress !== $address) {
                        continue;
                    }

                    $valueSats = (int)($out['value'] ?? 0);
                    if ($valueSats <= 0) {
                        continue;
                    }

                    $amountBtc = round($valueSats / 100000000, 8);
                    $ok = crypto_btc_watcher_repo_upsert_event(
                        $dbc,
                        $userId,
                        $network,
                        $address,
                        (string)$txid,
                        (int)$voutIndex,
                        $amountBtc,
                        $valueSats,
                        ($blockHeight > 0 ? $blockHeight : null),
                        $confirmations
                    );

                    if ($ok) {
                        $stats['events_upserted']++;
                    }
                    $stats['matched_outputs']++;
                }
            }
        } catch (Throwable $e) {
            $stats['errors'][] = 'address ' . $address . ': ' . $e->getMessage();
        }
    }

    $creditStats = crypto_btc_watcher_service_credit_confirmed_events($dbc, $network, $requiredConfirmations);
    $stats['credited'] = (int)($creditStats['credited'] ?? 0);
    $stats['credit_failed'] = (int)($creditStats['failed'] ?? 0);
    if (!empty($creditStats['errors']) && is_array($creditStats['errors'])) {
        $stats['errors'] = array_merge($stats['errors'], $creditStats['errors']);
    }
} catch (Throwable $e) {
    $stats['errors'][] = $e->getMessage();
}

$stats['runtime_seconds'] = round(microtime(true) - $startedAt, 3);
echo json_encode($stats, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . PHP_EOL;

if (!empty($stats['errors'])) {
    exit(1);
}

exit(0);

function btc_watcher_http_get_text(string $url, int $timeoutSeconds): string
{
    $lastError = 'unknown';

    for ($attempt = 1; $attempt <= 2; $attempt++) {
        if (function_exists('curl_init')) {
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $timeoutSeconds);
            curl_setopt($ch, CURLOPT_TIMEOUT, $timeoutSeconds);
            curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36');
            curl_setopt($ch, CURLOPT_HTTPHEADER, ['Accept: application/json', 'Cache-Control: no-cache']);
            curl_setopt($ch, CURLOPT_ENCODING, '');
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
            curl_setopt($ch, CURLOPT_MAXREDIRS, 3);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
            curl_setopt($ch, CURLOPT_IPRESOLVE, CURL_IPRESOLVE_V4);

            $response = curl_exec($ch);
            $httpCode = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);

            if ($response !== false && $httpCode < 400) {
                curl_close($ch);
                return (string)$response;
            }

            $curlErr = curl_error($ch);
            curl_close($ch);
            $lastError = $response === false
                ? ('cURL error: ' . $curlErr)
                : ('HTTP ' . $httpCode . ' from ' . $url);
        } else {
            $ctx = stream_context_create([
                'http' => [
                    'method' => 'GET',
                    'timeout' => $timeoutSeconds,
                    'ignore_errors' => true,
                    'header' => "Accept: application/json\r\nUser-Agent: Mozilla/5.0\r\n",
                ],
                'ssl' => [
                    'verify_peer' => false,
                    'verify_peer_name' => false,
                ],
            ]);

            $raw = @file_get_contents($url, false, $ctx);
            if ($raw !== false) {
                return (string)$raw;
            }

            $lastError = 'stream request failed for ' . $url;
        }

        if ($attempt < 2) {
            usleep(250000);
        }
    }

    throw new RuntimeException('HTTP request failed: ' . $url . ' (' . $lastError . ')');
}
