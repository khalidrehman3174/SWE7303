<?php

function crypto_btc_watcher_service_run(mysqli $dbc, array $config): array
{
    crypto_btc_watcher_repo_ensure_schema($dbc);

    $network = strtolower((string)($config['btc_network'] ?? 'testnet'));
    if (!in_array($network, ['testnet', 'mainnet'], true)) {
        $network = 'testnet';
    }

    $requiredConfirmations = (int)($config['btc_required_confirmations'] ?? 2);
    if ($requiredConfirmations < 1) {
        $requiredConfirmations = 1;
    }

    $maxBlocksPerRun = (int)($config['btc_watcher_max_blocks_per_run'] ?? 20);
    if ($maxBlocksPerRun < 1) {
        $maxBlocksPerRun = 1;
    }
    if ($maxBlocksPerRun > 500) {
        $maxBlocksPerRun = 500;
    }

    $httpTimeout = (int)($config['btc_watcher_http_timeout_seconds'] ?? 12);
    if ($httpTimeout < 3) {
        $httpTimeout = 3;
    }

    $baseUrl = trim((string)($config['btc_indexer_base_url'] ?? ''));
    if ($baseUrl === '') {
        $baseUrl = $network === 'mainnet'
            ? 'https://mempool.space/api'
            : 'https://mempool.space/testnet/api';
    }
    $baseUrl = rtrim($baseUrl, '/');

    $stats = [
        'network' => $network,
        'tip_height' => null,
        'start_height' => null,
        'end_height' => null,
        'blocks_scanned' => 0,
        'tx_scanned' => 0,
        'matched_outputs' => 0,
        'events_upserted' => 0,
        'credited' => 0,
        'credit_failed' => 0,
        'errors' => [],
    ];

    try {
        $tipHeight = crypto_btc_watcher_http_get_text_int($baseUrl . '/blocks/tip/height', $httpTimeout);
        $stats['tip_height'] = $tipHeight;

        $lastScanned = crypto_btc_watcher_repo_get_last_scanned_height($dbc, $network);
        if ($lastScanned === null) {
            // First run: start from recent window to avoid huge backfill surprises.
            $lastScanned = max(0, $tipHeight - $maxBlocksPerRun);
        }

        $startHeight = max(0, $lastScanned + 1);
        $endHeight = min($tipHeight, $startHeight + $maxBlocksPerRun - 1);

        $stats['start_height'] = $startHeight;
        $stats['end_height'] = $endHeight;

        if ($startHeight <= $endHeight) {
            for ($height = $startHeight; $height <= $endHeight; $height++) {
                $blockHash = trim(crypto_btc_watcher_http_get_text($baseUrl . '/block-height/' . $height, $httpTimeout));
                if ($blockHash === '') {
                    throw new RuntimeException('Empty block hash at height ' . $height);
                }

                $txids = crypto_btc_watcher_http_get_json($baseUrl . '/block/' . $blockHash . '/txids', $httpTimeout);
                if (!is_array($txids)) {
                    throw new RuntimeException('Invalid txid list for block ' . $blockHash);
                }

                $stats['blocks_scanned']++;

                foreach ($txids as $txid) {
                    $txid = (string)$txid;
                    if ($txid === '') {
                        continue;
                    }

                    $tx = crypto_btc_watcher_http_get_json($baseUrl . '/tx/' . $txid, $httpTimeout);
                    $stats['tx_scanned']++;
                    if (!is_array($tx) || !isset($tx['vout']) || !is_array($tx['vout'])) {
                        continue;
                    }

                    foreach ($tx['vout'] as $voutIndex => $vout) {
                        if (!is_array($vout)) {
                            continue;
                        }

                        $address = (string)($vout['scriptpubkey_address'] ?? '');
                        $valueSats = (int)($vout['value'] ?? 0);
                        if ($address === '' || $valueSats <= 0) {
                            continue;
                        }

                        $match = crypto_btc_watcher_repo_find_user_for_address($dbc, $network, $address);
                        if (!$match) {
                            continue;
                        }

                        $confirmations = max(0, $tipHeight - $height + 1);
                        $amountBtc = round($valueSats / 100000000, 8);
                        $ok = crypto_btc_watcher_repo_upsert_event(
                            $dbc,
                            (int)$match['user_id'],
                            $network,
                            $address,
                            $txid,
                            (int)$voutIndex,
                            $amountBtc,
                            $valueSats,
                            $height,
                            $confirmations
                        );

                        if ($ok) {
                            $stats['events_upserted']++;
                        }
                        $stats['matched_outputs']++;
                    }
                }

                crypto_btc_watcher_repo_set_last_scanned_height($dbc, $network, $height);
            }
        }

        $creditStats = crypto_btc_watcher_service_credit_confirmed_events($dbc, $network, $requiredConfirmations);
        $stats['credited'] = $creditStats['credited'];
        $stats['credit_failed'] = $creditStats['failed'];
        if (!empty($creditStats['errors'])) {
            $stats['errors'] = array_merge($stats['errors'], $creditStats['errors']);
        }
    } catch (Throwable $e) {
        $stats['errors'][] = $e->getMessage();
    }

    return $stats;
}

function crypto_btc_watcher_service_credit_confirmed_events(mysqli $dbc, string $network, int $requiredConfirmations): array
{
    $out = [
        'credited' => 0,
        'failed' => 0,
        'errors' => [],
    ];

    $ids = crypto_btc_watcher_repo_list_creditable_event_ids($dbc, $network, $requiredConfirmations, 200);
    foreach ($ids as $eventId) {
        mysqli_begin_transaction($dbc);
        try {
            $event = crypto_btc_watcher_repo_get_event_for_update($dbc, (int)$eventId);
            if (!$event) {
                mysqli_commit($dbc);
                continue;
            }

            if ((string)($event['credit_status'] ?? '') !== 'pending') {
                mysqli_commit($dbc);
                continue;
            }

            if ((int)($event['confirmations'] ?? 0) < $requiredConfirmations) {
                mysqli_commit($dbc);
                continue;
            }

            $userId = (int)($event['user_id'] ?? 0);
            $amountBtc = (float)($event['amount_btc'] ?? 0);
            if ($userId <= 0 || $amountBtc <= 0) {
                throw new RuntimeException('Invalid event payload for credit id=' . (int)$eventId);
            }

            $symbol = 'BTC';
            $wallet = crypto_btc_watcher_repo_find_wallet_for_update($dbc, $userId, $symbol);
            if (!$wallet) {
                if (!crypto_btc_watcher_repo_create_wallet($dbc, $userId, $symbol)) {
                    throw new RuntimeException('Could not create BTC wallet for user ' . $userId);
                }
                $wallet = crypto_btc_watcher_repo_find_wallet_for_update($dbc, $userId, $symbol);
            }

            if (!$wallet) {
                throw new RuntimeException('Could not lock BTC wallet for user ' . $userId);
            }

            if (!crypto_btc_watcher_repo_increment_wallet($dbc, (int)$wallet['id'], $amountBtc)) {
                throw new RuntimeException('Wallet credit update failed');
            }

            if (!crypto_btc_watcher_repo_mark_event_credited($dbc, (int)$eventId)) {
                throw new RuntimeException('Could not mark event credited');
            }

            mysqli_commit($dbc);
            $out['credited']++;
        } catch (Throwable $e) {
            mysqli_rollback($dbc);
            $out['failed']++;
            $msg = $e->getMessage();
            $out['errors'][] = 'event ' . (int)$eventId . ': ' . $msg;

            try {
                crypto_btc_watcher_repo_mark_event_failed($dbc, (int)$eventId, $msg);
            } catch (Throwable $ignore) {
                // no-op
            }
        }
    }

    return $out;
}

function crypto_btc_watcher_http_get_text_int(string $url, int $timeoutSeconds): int
{
    $text = trim(crypto_btc_watcher_http_get_text($url, $timeoutSeconds));
    if ($text === '' || !preg_match('/^-?\d+$/', $text)) {
        throw new RuntimeException('Invalid integer response from ' . $url);
    }

    return (int)$text;
}

function crypto_btc_watcher_http_get_json(string $url, int $timeoutSeconds): mixed
{
    $raw = crypto_btc_watcher_http_get_text($url, $timeoutSeconds);
    $decoded = json_decode($raw, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        throw new RuntimeException('Invalid JSON from ' . $url . ': ' . json_last_error_msg());
    }

    return $decoded;
}

function crypto_btc_watcher_http_get_text(string $url, int $timeoutSeconds): string
{
    $ctx = stream_context_create([
        'http' => [
            'method' => 'GET',
            'timeout' => $timeoutSeconds,
            'ignore_errors' => true,
            'header' => "Accept: application/json\r\n",
        ],
    ]);

    $raw = @file_get_contents($url, false, $ctx);
    if ($raw === false) {
        throw new RuntimeException('HTTP request failed: ' . $url);
    }

    $statusCode = 0;
    if (isset($http_response_header) && is_array($http_response_header) && isset($http_response_header[0])) {
        if (preg_match('/HTTP\/\d\.\d\s+(\d+)/', (string)$http_response_header[0], $m)) {
            $statusCode = (int)$m[1];
        }
    }

    if ($statusCode >= 400) {
        throw new RuntimeException('HTTP ' . $statusCode . ' from ' . $url);
    }

    return (string)$raw;
}
