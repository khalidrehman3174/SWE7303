<?php

function crypto_btc_watcher_repo_ensure_schema(mysqli $dbc): void
{
    $eventsSql = "CREATE TABLE IF NOT EXISTS btc_deposit_events (
        id BIGINT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        coin VARCHAR(16) NOT NULL DEFAULT 'BTC',
        network VARCHAR(24) NOT NULL,
        address VARCHAR(128) NOT NULL,
        txid VARCHAR(128) NOT NULL,
        vout INT NOT NULL,
        amount_btc DECIMAL(20,8) NOT NULL,
        amount_sats BIGINT NOT NULL,
        block_height INT NULL,
        confirmations INT NOT NULL DEFAULT 0,
        detected_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        last_seen_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        credited_at TIMESTAMP NULL,
        credit_status VARCHAR(24) NOT NULL DEFAULT 'pending',
        credit_error VARCHAR(255) NULL,
        UNIQUE KEY uniq_tx_vout (txid, vout),
        KEY idx_user_network_status (user_id, network, credit_status),
        KEY idx_network_confirmations (network, confirmations)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";

    $stateSql = "CREATE TABLE IF NOT EXISTS btc_scanner_state (
        id INT AUTO_INCREMENT PRIMARY KEY,
        network VARCHAR(24) NOT NULL,
        last_scanned_height INT NOT NULL DEFAULT 0,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        UNIQUE KEY uniq_network (network)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";

    mysqli_query($dbc, $eventsSql);
    mysqli_query($dbc, $stateSql);
}

function crypto_btc_watcher_repo_get_last_scanned_height(mysqli $dbc, string $network): ?int
{
    $sql = 'SELECT last_scanned_height FROM btc_scanner_state WHERE network = ? LIMIT 1';
    $stmt = mysqli_prepare($dbc, $sql);
    if (!$stmt) {
        return null;
    }

    mysqli_stmt_bind_param($stmt, 's', $network);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $row = $result ? mysqli_fetch_assoc($result) : null;
    mysqli_stmt_close($stmt);

    if (!$row) {
        return null;
    }

    return (int)($row['last_scanned_height'] ?? 0);
}

function crypto_btc_watcher_repo_set_last_scanned_height(mysqli $dbc, string $network, int $height): bool
{
    $sql = 'INSERT INTO btc_scanner_state (network, last_scanned_height) VALUES (?, ?) ON DUPLICATE KEY UPDATE last_scanned_height = VALUES(last_scanned_height)';
    $stmt = mysqli_prepare($dbc, $sql);
    if (!$stmt) {
        return false;
    }

    mysqli_stmt_bind_param($stmt, 'si', $network, $height);
    $ok = mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);

    return $ok;
}

function crypto_btc_watcher_repo_find_user_for_address(mysqli $dbc, string $network, string $address): ?array
{
    $coin = 'BTC';
    $active = 'active';
    $sql = 'SELECT user_id, address FROM user_crypto_addresses WHERE coin = ? AND network = ? AND address = ? AND status = ? LIMIT 1';
    $stmt = mysqli_prepare($dbc, $sql);
    if (!$stmt) {
        return null;
    }

    mysqli_stmt_bind_param($stmt, 'ssss', $coin, $network, $address, $active);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $row = $result ? mysqli_fetch_assoc($result) : null;
    mysqli_stmt_close($stmt);

    return $row ?: null;
}

function crypto_btc_watcher_repo_upsert_event(
    mysqli $dbc,
    int $userId,
    string $network,
    string $address,
    string $txid,
    int $vout,
    float $amountBtc,
    int $amountSats,
    ?int $blockHeight,
    int $confirmations
): bool {
    $coin = 'BTC';
    $sql = 'INSERT INTO btc_deposit_events
        (user_id, coin, network, address, txid, vout, amount_btc, amount_sats, block_height, confirmations, credit_status)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, \"pending\")
        ON DUPLICATE KEY UPDATE
            user_id = VALUES(user_id),
            network = VALUES(network),
            address = VALUES(address),
            amount_btc = VALUES(amount_btc),
            amount_sats = VALUES(amount_sats),
            block_height = VALUES(block_height),
            confirmations = VALUES(confirmations),
            last_seen_at = CURRENT_TIMESTAMP';

    $stmt = mysqli_prepare($dbc, $sql);
    if (!$stmt) {
        return false;
    }

    mysqli_stmt_bind_param(
        $stmt,
        'issssidiii',
        $userId,
        $coin,
        $network,
        $address,
        $txid,
        $vout,
        $amountBtc,
        $amountSats,
        $blockHeight,
        $confirmations
    );

    $ok = mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);

    return $ok;
}

function crypto_btc_watcher_repo_list_creditable_event_ids(mysqli $dbc, string $network, int $minConfirmations, int $limit = 200): array
{
    $pending = 'pending';
    $sql = 'SELECT id FROM btc_deposit_events WHERE network = ? AND credit_status = ? AND confirmations >= ? ORDER BY id ASC LIMIT ?';
    $stmt = mysqli_prepare($dbc, $sql);
    if (!$stmt) {
        return [];
    }

    mysqli_stmt_bind_param($stmt, 'ssii', $network, $pending, $minConfirmations, $limit);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);

    $ids = [];
    if ($result) {
        while ($row = mysqli_fetch_assoc($result)) {
            $ids[] = (int)($row['id'] ?? 0);
        }
    }

    mysqli_stmt_close($stmt);
    return array_values(array_filter($ids));
}

function crypto_btc_watcher_repo_get_event_for_update(mysqli $dbc, int $eventId): ?array
{
    $sql = 'SELECT * FROM btc_deposit_events WHERE id = ? LIMIT 1 FOR UPDATE';
    $stmt = mysqli_prepare($dbc, $sql);
    if (!$stmt) {
        return null;
    }

    mysqli_stmt_bind_param($stmt, 'i', $eventId);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $row = $result ? mysqli_fetch_assoc($result) : null;
    mysqli_stmt_close($stmt);

    return $row ?: null;
}

function crypto_btc_watcher_repo_find_wallet_for_update(mysqli $dbc, int $userId, string $symbol): ?array
{
    $sql = 'SELECT id, balance FROM wallets WHERE user_id = ? AND symbol = ? LIMIT 1 FOR UPDATE';
    $stmt = mysqli_prepare($dbc, $sql);
    if (!$stmt) {
        return null;
    }

    mysqli_stmt_bind_param($stmt, 'is', $userId, $symbol);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $row = $result ? mysqli_fetch_assoc($result) : null;
    mysqli_stmt_close($stmt);

    return $row ?: null;
}

function crypto_btc_watcher_repo_create_wallet(mysqli $dbc, int $userId, string $symbol): bool
{
    $sql = 'INSERT INTO wallets (user_id, symbol, balance) VALUES (?, ?, 0)';
    $stmt = mysqli_prepare($dbc, $sql);
    if (!$stmt) {
        return false;
    }

    mysqli_stmt_bind_param($stmt, 'is', $userId, $symbol);
    $ok = mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);

    return $ok;
}

function crypto_btc_watcher_repo_increment_wallet(mysqli $dbc, int $walletId, float $amountBtc): bool
{
    $sql = 'UPDATE wallets SET balance = balance + ? WHERE id = ? LIMIT 1';
    $stmt = mysqli_prepare($dbc, $sql);
    if (!$stmt) {
        return false;
    }

    mysqli_stmt_bind_param($stmt, 'di', $amountBtc, $walletId);
    $ok = mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);

    return $ok;
}

function crypto_btc_watcher_repo_mark_event_credited(mysqli $dbc, int $eventId): bool
{
    $credited = 'credited';
    $sql = 'UPDATE btc_deposit_events SET credit_status = ?, credited_at = NOW(), credit_error = NULL WHERE id = ? LIMIT 1';
    $stmt = mysqli_prepare($dbc, $sql);
    if (!$stmt) {
        return false;
    }

    mysqli_stmt_bind_param($stmt, 'si', $credited, $eventId);
    $ok = mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);

    return $ok;
}

function crypto_btc_watcher_repo_mark_event_failed(mysqli $dbc, int $eventId, string $error): bool
{
    $failed = 'failed';
    $err = substr($error, 0, 255);
    $sql = 'UPDATE btc_deposit_events SET credit_status = ?, credit_error = ? WHERE id = ? LIMIT 1';
    $stmt = mysqli_prepare($dbc, $sql);
    if (!$stmt) {
        return false;
    }

    mysqli_stmt_bind_param($stmt, 'ssi', $failed, $err, $eventId);
    $ok = mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);

    return $ok;
}
