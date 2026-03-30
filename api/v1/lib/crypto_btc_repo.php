<?php

function crypto_btc_repo_ensure_schema(mysqli $dbc): void
{
    $sourceSql = "CREATE TABLE IF NOT EXISTS crypto_wallet_sources (
        id INT AUTO_INCREMENT PRIMARY KEY,
        coin VARCHAR(16) NOT NULL,
        network VARCHAR(24) NOT NULL,
        xpub TEXT NOT NULL,
        next_index INT NOT NULL DEFAULT 0,
        active TINYINT(1) NOT NULL DEFAULT 1,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        UNIQUE KEY uniq_coin_network_active (coin, network, active)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";

    $addressSql = "CREATE TABLE IF NOT EXISTS user_crypto_addresses (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        source_id INT NOT NULL,
        coin VARCHAR(16) NOT NULL,
        network VARCHAR(24) NOT NULL,
        address VARCHAR(128) NOT NULL,
        derivation_index INT NOT NULL,
        derivation_path VARCHAR(64) NOT NULL,
        status VARCHAR(24) NOT NULL DEFAULT 'active',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        UNIQUE KEY uniq_address (address),
        UNIQUE KEY uniq_source_index (source_id, derivation_index),
        KEY idx_user_coin_network (user_id, coin, network)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";

    mysqli_query($dbc, $sourceSql);
    mysqli_query($dbc, $addressSql);
}

function crypto_btc_repo_find_active_source(mysqli $dbc, string $coin, string $network): ?array
{
    $sql = 'SELECT * FROM crypto_wallet_sources WHERE coin = ? AND network = ? AND active = 1 ORDER BY id DESC LIMIT 1';
    $stmt = mysqli_prepare($dbc, $sql);
    if (!$stmt) {
        return null;
    }

    mysqli_stmt_bind_param($stmt, 'ss', $coin, $network);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $row = $result ? mysqli_fetch_assoc($result) : null;
    mysqli_stmt_close($stmt);

    return $row ?: null;
}

function crypto_btc_repo_find_active_source_for_update(mysqli $dbc, string $coin, string $network): ?array
{
    $sql = 'SELECT * FROM crypto_wallet_sources WHERE coin = ? AND network = ? AND active = 1 ORDER BY id DESC LIMIT 1 FOR UPDATE';
    $stmt = mysqli_prepare($dbc, $sql);
    if (!$stmt) {
        return null;
    }

    mysqli_stmt_bind_param($stmt, 'ss', $coin, $network);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $row = $result ? mysqli_fetch_assoc($result) : null;
    mysqli_stmt_close($stmt);

    return $row ?: null;
}

function crypto_btc_repo_create_source(mysqli $dbc, string $coin, string $network, string $xpub): bool
{
    $sql = 'INSERT INTO crypto_wallet_sources (coin, network, xpub, next_index, active) VALUES (?, ?, ?, 0, 1)';
    $stmt = mysqli_prepare($dbc, $sql);
    if (!$stmt) {
        return false;
    }

    mysqli_stmt_bind_param($stmt, 'sss', $coin, $network, $xpub);
    $ok = mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);

    return $ok;
}

function crypto_btc_repo_find_user_address(mysqli $dbc, int $userId, string $coin, string $network): ?array
{
    $sql = 'SELECT * FROM user_crypto_addresses WHERE user_id = ? AND coin = ? AND network = ? AND status = ? ORDER BY id DESC LIMIT 1';
    $stmt = mysqli_prepare($dbc, $sql);
    if (!$stmt) {
        return null;
    }

    $active = 'active';
    mysqli_stmt_bind_param($stmt, 'isss', $userId, $coin, $network, $active);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $row = $result ? mysqli_fetch_assoc($result) : null;
    mysqli_stmt_close($stmt);

    return $row ?: null;
}

function crypto_btc_repo_insert_user_address(
    mysqli $dbc,
    int $userId,
    int $sourceId,
    string $coin,
    string $network,
    string $address,
    int $derivationIndex,
    string $derivationPath
): bool {
    $sql = 'INSERT INTO user_crypto_addresses (user_id, source_id, coin, network, address, derivation_index, derivation_path, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?)';
    $stmt = mysqli_prepare($dbc, $sql);
    if (!$stmt) {
        return false;
    }

    $active = 'active';
    mysqli_stmt_bind_param($stmt, 'iisssiss', $userId, $sourceId, $coin, $network, $address, $derivationIndex, $derivationPath, $active);
    $ok = mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);

    return $ok;
}

function crypto_btc_repo_update_source_next_index(mysqli $dbc, int $sourceId, int $nextIndex): bool
{
    $sql = 'UPDATE crypto_wallet_sources SET next_index = ? WHERE id = ? LIMIT 1';
    $stmt = mysqli_prepare($dbc, $sql);
    if (!$stmt) {
        return false;
    }

    mysqli_stmt_bind_param($stmt, 'ii', $nextIndex, $sourceId);
    $ok = mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);

    return $ok;
}

function crypto_btc_repo_update_source_xpub(mysqli $dbc, int $sourceId, string $xpub): bool
{
    $sql = 'UPDATE crypto_wallet_sources SET xpub = ? WHERE id = ? LIMIT 1';
    $stmt = mysqli_prepare($dbc, $sql);
    if (!$stmt) {
        return false;
    }

    mysqli_stmt_bind_param($stmt, 'si', $xpub, $sourceId);
    $ok = mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);

    return $ok;
}
