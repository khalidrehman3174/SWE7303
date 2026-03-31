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

    $maxBackfillBlocks = (int)($config['btc_watcher_max_backfill_blocks'] ?? 2000);
    if ($maxBackfillBlocks < 100) {
        $maxBackfillBlocks = 100;
    }

    $maxAddressesPerRun = (int)($config['btc_watcher_max_addresses_per_run'] ?? 500);
    if ($maxAddressesPerRun < 1) {
        $maxAddressesPerRun = 1;
    }
    if ($maxAddressesPerRun > 5000) {
        $maxAddressesPerRun = 5000;
    }

    $maxRuntimeSeconds = (int)($config['btc_watcher_max_runtime_seconds'] ?? 90);
    if ($maxRuntimeSeconds < 10) {
        $maxRuntimeSeconds = 10;
    }
    if ($maxRuntimeSeconds > 1800) {
        $maxRuntimeSeconds = 1800;
    }

    if (function_exists('set_time_limit')) {
        @set_time_limit($maxRuntimeSeconds + 15);
    }

    $startedAt = microtime(true);

    $baseUrl = trim((string)($config['btc_indexer_base_url'] ?? ''));
    if ($baseUrl === '') {
        $baseUrl = $network === 'mainnet'
            ? 'https://blockstream.info/api'
            : 'https://blockstream.info/testnet/api';
    }
    $baseUrl = rtrim($baseUrl, '/');
    $provider = crypto_btc_watcher_detect_provider($baseUrl);

    $stats = [
        'network' => $network,
        'provider' => $provider,
        'tip_height' => null,
        'start_height' => null,
        'end_height' => null,
        'blocks_scanned' => 0,
        'tx_scanned' => 0,
        'matched_outputs' => 0,
        'events_upserted' => 0,
        'addresses_scanned' => 0,
        'credited' => 0,
        'credit_failed' => 0,
        'stopped_early' => false,
        'stop_reason' => null,
        'state_reset' => false,
        'runtime_seconds' => 0,
        'errors' => [],
    ];

    $isRuntimeExceeded = static function () use ($startedAt, $maxRuntimeSeconds): bool {
        return (microtime(true) - $startedAt) >= $maxRuntimeSeconds;
    };

    try {
        $tipHeight = crypto_btc_watcher_get_tip_height($provider, $baseUrl, $httpTimeout);
        $stats['tip_height'] = $tipHeight;

        if ($provider === 'esplora') {
            $ingestStats = crypto_btc_watcher_service_ingest_esplora_addresses(
                $dbc,
                $network,
                $baseUrl,
                $tipHeight,
                $httpTimeout,
                $maxAddressesPerRun,
                $isRuntimeExceeded
            );

            $stats['tx_scanned'] += $ingestStats['tx_scanned'];
            $stats['matched_outputs'] += $ingestStats['matched_outputs'];
            $stats['events_upserted'] += $ingestStats['events_upserted'];
            $stats['addresses_scanned'] = $ingestStats['addresses_scanned'];

            if (!empty($ingestStats['stopped_early'])) {
                $stats['stopped_early'] = true;
                $stats['stop_reason'] = 'runtime_limit_reached';
            }
            if (!empty($ingestStats['errors'])) {
                $stats['errors'] = array_merge($stats['errors'], $ingestStats['errors']);
            }
        } else {

            $lastScanned = crypto_btc_watcher_repo_get_last_scanned_height($dbc, $network);
            if ($lastScanned === null) {
                // First run: start from recent window to avoid huge backfill surprises.
                $lastScanned = max(0, $tipHeight - $maxBlocksPerRun);
            }

            $backlog = $tipHeight - $lastScanned;
            if ($backlog > $maxBackfillBlocks) {
                $lastScanned = max(0, $tipHeight - $maxBlocksPerRun);
                crypto_btc_watcher_repo_set_last_scanned_height($dbc, $network, $lastScanned);
                $stats['state_reset'] = true;
            }

            // Auto-heal if persisted state moved ahead of chain tip (reorg/reset/manual edits).
            if ($lastScanned >= $tipHeight) {
                $lastScanned = max(0, $tipHeight - 1);
                crypto_btc_watcher_repo_set_last_scanned_height($dbc, $network, $lastScanned);
            }

            $startHeight = max(0, $lastScanned + 1);
            $endHeight = min($tipHeight, $startHeight + $maxBlocksPerRun - 1);

            $stats['start_height'] = $startHeight;
            $stats['end_height'] = $endHeight;

            if ($startHeight <= $endHeight) {
                $haltScan = false;
                for ($height = $startHeight; $height <= $endHeight; $height++) {
                    if ($isRuntimeExceeded()) {
                        $stats['stopped_early'] = true;
                        $stats['stop_reason'] = 'runtime_limit_reached';
                        break;
                    }

                    $txids = crypto_btc_watcher_get_block_txids_by_height($provider, $baseUrl, $height, $httpTimeout);
                    if (!is_array($txids)) {
                        throw new RuntimeException('Invalid txid list for height ' . $height);
                    }

                    $stats['blocks_scanned']++;

                    $completedBlock = true;

                    foreach ($txids as $txid) {
                        if ($isRuntimeExceeded()) {
                            $stats['stopped_early'] = true;
                            $stats['stop_reason'] = 'runtime_limit_reached';
                            $completedBlock = false;
                            $haltScan = true;
                            break;
                        }

                        $txid = (string)$txid;
                        if ($txid === '') {
                            continue;
                        }

                        $tx = crypto_btc_watcher_get_transaction($provider, $baseUrl, $txid, $httpTimeout);
                        $stats['tx_scanned']++;
                        if (!is_array($tx)) {
                            continue;
                        }

                        $outputs = crypto_btc_watcher_extract_outputs($provider, $tx);

                        foreach ($outputs as $output) {
                            $address = (string)($output['address'] ?? '');
                            $valueSats = (int)($output['value_sats'] ?? 0);
                            $voutIndex = (int)($output['vout_index'] ?? 0);
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

                    if (!$completedBlock) {
                        break;
                    }

                    crypto_btc_watcher_repo_set_last_scanned_height($dbc, $network, $height);

                    if ($haltScan) {
                        break;
                    }
                }
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

    $stats['runtime_seconds'] = round(microtime(true) - $startedAt, 3);

    return $stats;
}

function crypto_btc_watcher_service_ingest_esplora_addresses(
    mysqli $dbc,
    string $network,
    string $baseUrl,
    int $tipHeight,
    int $httpTimeout,
    int $maxAddressesPerRun,
    callable $isRuntimeExceeded
): array {
    $stats = [
        'addresses_scanned' => 0,
        'tx_scanned' => 0,
        'matched_outputs' => 0,
        'events_upserted' => 0,
        'stopped_early' => false,
        'errors' => [],
    ];

    $rows = crypto_btc_watcher_repo_list_active_addresses($dbc, $network, $maxAddressesPerRun);
    foreach ($rows as $row) {
        if ($isRuntimeExceeded()) {
            $stats['stopped_early'] = true;
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
            $summary = crypto_btc_watcher_http_get_json($baseUrl . '/address/' . $encodedAddress, $httpTimeout);
            $confirmedSats = (int)($summary['chain_stats']['funded_txo_sum'] ?? 0);
            $mempoolSats = (int)($summary['mempool_stats']['funded_txo_sum'] ?? 0);

            if ($confirmedSats <= 0 && $mempoolSats <= 0) {
                continue;
            }

            $confirmedTxs = crypto_btc_watcher_http_get_json($baseUrl . '/address/' . $encodedAddress . '/txs', $httpTimeout);
            $mempoolTxs = crypto_btc_watcher_http_get_json($baseUrl . '/address/' . $encodedAddress . '/txs/mempool', $httpTimeout);

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
                if ($isRuntimeExceeded()) {
                    $stats['stopped_early'] = true;
                    break 2;
                }

                $stats['tx_scanned']++;
                $outputs = crypto_btc_watcher_extract_outputs('esplora', $tx);

                $status = is_array($tx['status'] ?? null) ? $tx['status'] : [];
                $confirmed = !empty($status['confirmed']);
                $blockHeight = $confirmed ? (int)($status['block_height'] ?? 0) : 0;
                $confirmations = ($confirmed && $blockHeight > 0)
                    ? max(0, $tipHeight - $blockHeight + 1)
                    : 0;

                foreach ($outputs as $output) {
                    $outputAddress = trim((string)($output['address'] ?? ''));
                    if ($outputAddress !== $address) {
                        continue;
                    }

                    $valueSats = (int)($output['value_sats'] ?? 0);
                    if ($valueSats <= 0) {
                        continue;
                    }

                    $voutIndex = (int)($output['vout_index'] ?? 0);
                    $amountBtc = round($valueSats / 100000000, 8);
                    $ok = crypto_btc_watcher_repo_upsert_event(
                        $dbc,
                        $userId,
                        $network,
                        $address,
                        (string)$txid,
                        $voutIndex,
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

    return $stats;
}

function crypto_btc_watcher_detect_provider(string $baseUrl): string
{
    $lower = strtolower($baseUrl);
    if (strpos($lower, 'blockcypher.com') !== false) {
        return 'blockcypher';
    }

    if (strpos($lower, 'blockstream.info') !== false) {
        return 'esplora';
    }

    return 'mempool';
}

function crypto_btc_watcher_get_tip_height(string $provider, string $baseUrl, int $timeoutSeconds): int
{
    if ($provider === 'blockcypher') {
        $chain = crypto_btc_watcher_http_get_json($baseUrl, $timeoutSeconds);
        $height = (int)($chain['height'] ?? 0);
        if ($height <= 0) {
            throw new RuntimeException('Invalid tip height from BlockCypher');
        }

        return $height;
    }

    return crypto_btc_watcher_http_get_text_int($baseUrl . '/blocks/tip/height', $timeoutSeconds);
}

function crypto_btc_watcher_get_block_txids_by_height(string $provider, string $baseUrl, int $height, int $timeoutSeconds): array
{
    if ($provider === 'blockcypher') {
        return crypto_btc_watcher_blockcypher_get_block_txids_by_height($baseUrl, $height, $timeoutSeconds);
    }

    $blockHash = trim(crypto_btc_watcher_http_get_text($baseUrl . '/block-height/' . $height, $timeoutSeconds));
    if ($blockHash === '') {
        throw new RuntimeException('Empty block hash at height ' . $height);
    }

    $txids = crypto_btc_watcher_http_get_json($baseUrl . '/block/' . $blockHash . '/txids', $timeoutSeconds);
    if (!is_array($txids)) {
        throw new RuntimeException('Invalid txid list for block ' . $blockHash);
    }

    return $txids;
}

function crypto_btc_watcher_blockcypher_get_block_txids_by_height(string $baseUrl, int $height, int $timeoutSeconds): array
{
    $txids = [];
    $txStart = 0;
    $limit = 500;
    $expected = null;
    $maxPages = 1000;

    for ($page = 0; $page < $maxPages; $page++) {
        $url = $baseUrl . '/blocks/' . $height . '?txstart=' . $txStart . '&limit=' . $limit;
        $block = crypto_btc_watcher_http_get_json($url, $timeoutSeconds);
        if (!is_array($block)) {
            throw new RuntimeException('Invalid block payload at height ' . $height);
        }

        $chunk = $block['txids'] ?? [];
        if (!is_array($chunk)) {
            throw new RuntimeException('Missing txids in BlockCypher block payload at height ' . $height);
        }

        if ($expected === null) {
            $expected = (int)($block['n_tx'] ?? count($chunk));
        }

        foreach ($chunk as $id) {
            $id = (string)$id;
            if ($id !== '') {
                $txids[] = $id;
            }
        }

        if (count($chunk) < $limit) {
            break;
        }
        if ($expected !== null && count($txids) >= $expected) {
            break;
        }

        $txStart += $limit;
    }

    if (empty($txids) && $expected !== null && $expected > 0) {
        throw new RuntimeException('BlockCypher returned zero txids for non-empty block at height ' . $height);
    }

    return array_values(array_unique($txids));
}

function crypto_btc_watcher_get_transaction(string $provider, string $baseUrl, string $txid, int $timeoutSeconds): array
{
    if ($provider === 'blockcypher') {
        $tx = crypto_btc_watcher_http_get_json($baseUrl . '/txs/' . $txid, $timeoutSeconds);
        return is_array($tx) ? $tx : [];
    }

    $tx = crypto_btc_watcher_http_get_json($baseUrl . '/tx/' . $txid, $timeoutSeconds);
    return is_array($tx) ? $tx : [];
}

function crypto_btc_watcher_extract_outputs(string $provider, array $tx): array
{
    $outputs = [];

    if ($provider === 'blockcypher') {
        if (!isset($tx['outputs']) || !is_array($tx['outputs'])) {
            return $outputs;
        }

        foreach ($tx['outputs'] as $voutIndex => $vout) {
            if (!is_array($vout)) {
                continue;
            }

            $valueSats = (int)($vout['value'] ?? 0);
            $addresses = $vout['addresses'] ?? [];
            if (!is_array($addresses)) {
                $addresses = [];
            }

            foreach ($addresses as $address) {
                $address = (string)$address;
                if ($address === '' || $valueSats <= 0) {
                    continue;
                }

                $outputs[] = [
                    'vout_index' => (int)$voutIndex,
                    'address' => $address,
                    'value_sats' => $valueSats,
                ];
            }
        }

        return $outputs;
    }

    if (!isset($tx['vout']) || !is_array($tx['vout'])) {
        return $outputs;
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

        $outputs[] = [
            'vout_index' => (int)$voutIndex,
            'address' => $address,
            'value_sats' => $valueSats,
        ];
    }

    return $outputs;
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
    $userAgent = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36';
    $ctx = stream_context_create([
        'http' => [
            'method' => 'GET',
            'timeout' => $timeoutSeconds,
            'ignore_errors' => true,
            'header' => "Accept: application/json\r\nUser-Agent: {$userAgent}\r\n",
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
