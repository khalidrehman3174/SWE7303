<?php

use Derive\DeriveWrapper;

function crypto_btc_service_get_or_create_deposit_address(mysqli $dbc, int $userId, array $config, string $asset, string $networkId): array
{
    $asset = strtoupper(trim($asset));
    if ($asset !== 'BTC') {
        return ['ok' => false, 'code' => 'unsupported_asset', 'message' => 'Only BTC is supported for this flow'];
    }

    $appNetwork = strtolower((string)($config['btc_network'] ?? 'testnet'));
    if (!in_array($appNetwork, ['testnet', 'mainnet'], true)) {
        $appNetwork = 'testnet';
    }

    // Only allow active BTC network choices exposed in UI.
    if (!in_array(strtolower($networkId), ['bitcoin', 'btc'], true)) {
        return ['ok' => false, 'code' => 'unsupported_network', 'message' => 'Unsupported BTC network selection'];
    }

    $coinCode = 'BTC';
    $xpub = trim((string)($config['btc_xpub'] ?? ''));
    if ($xpub === '') {
        return ['ok' => false, 'code' => 'missing_xpub', 'message' => 'BTC XPUB is not configured'];
    }

    if (!extension_loaded('gmp')) {
        return [
            'ok' => false,
            'code' => 'missing_gmp_extension',
            'message' => 'PHP GMP extension is not enabled for web runtime. Enable extension=php_gmp in php.ini and restart Apache.',
        ];
    }

    $existing = crypto_btc_repo_find_user_address($dbc, $userId, $coinCode, $appNetwork);
    if ($existing) {
        return [
            'ok' => true,
            'created' => false,
            'address' => [
                'address' => (string)$existing['address'],
                'coin' => $coinCode,
                'network' => $appNetwork,
                'derivation_index' => (int)$existing['derivation_index'],
                'derivation_path' => (string)$existing['derivation_path'],
            ],
        ];
    }

    mysqli_begin_transaction($dbc);
    try {
        $source = crypto_btc_repo_find_active_source_for_update($dbc, $coinCode, $appNetwork);
        if (!$source) {
            if (!crypto_btc_repo_create_source($dbc, $coinCode, $appNetwork, $xpub)) {
                throw new RuntimeException('Could not create BTC source row');
            }
            $source = crypto_btc_repo_find_active_source_for_update($dbc, $coinCode, $appNetwork);
        }

        if (!$source) {
            throw new RuntimeException('BTC source not available');
        }

        $sourceXpub = trim((string)($source['xpub'] ?? ''));
        // Auto-heal older rows created before XPUB was configured or after key rotation.
        if ($sourceXpub === '' || $sourceXpub !== $xpub) {
            if (!crypto_btc_repo_update_source_xpub($dbc, (int)$source['id'], $xpub)) {
                throw new RuntimeException('Could not refresh BTC source key');
            }
            $source['xpub'] = $xpub;
            $sourceXpub = $xpub;
        }

        // Re-check after row lock to avoid duplicate address assignment races.
        $existing = crypto_btc_repo_find_user_address($dbc, $userId, $coinCode, $appNetwork);
        if ($existing) {
            mysqli_commit($dbc);
            return [
                'ok' => true,
                'created' => false,
                'address' => [
                    'address' => (string)$existing['address'],
                    'coin' => $coinCode,
                    'network' => $appNetwork,
                    'derivation_index' => (int)$existing['derivation_index'],
                    'derivation_path' => (string)$existing['derivation_path'],
                ],
            ];
        }

        $derivationIndex = (int)($source['next_index'] ?? 0);
        [$keyType, $addressType] = crypto_btc_service_key_and_address_type($sourceXpub);
        $deriveCoin = $appNetwork === 'testnet' ? 'btc-test' : 'btc';

        $derived = DeriveWrapper::derive(
            key: $sourceXpub,
            coin: $deriveCoin,
            keyType: $keyType,
            addrType: $addressType,
            path: 'm/0/x',
            startindex: $derivationIndex,
            numderive: 1,
            format: 'array'
        );

        if (!is_array($derived) || !($derived['ok'] ?? false) || empty($derived['data'][0]['address'])) {
            $msg = is_array($derived) ? (string)($derived['message'] ?? 'Address derivation failed') : 'Address derivation failed';
            throw new RuntimeException($msg);
        }

        $row = $derived['data'][0];
        $address = (string)$row['address'];
        $path = (string)($row['path'] ?? ('m/0/' . $derivationIndex));

        $inserted = crypto_btc_repo_insert_user_address(
            $dbc,
            $userId,
            (int)$source['id'],
            $coinCode,
            $appNetwork,
            $address,
            $derivationIndex,
            $path
        );

        if (!$inserted) {
            throw new RuntimeException('Could not save derived BTC address');
        }

        if (!crypto_btc_repo_update_source_next_index($dbc, (int)$source['id'], $derivationIndex + 1)) {
            throw new RuntimeException('Could not update BTC source index');
        }

        mysqli_commit($dbc);

        return [
            'ok' => true,
            'created' => true,
            'address' => [
                'address' => $address,
                'coin' => $coinCode,
                'network' => $appNetwork,
                'derivation_index' => $derivationIndex,
                'derivation_path' => $path,
            ],
        ];
    } catch (Throwable $e) {
        mysqli_rollback($dbc);
        return [
            'ok' => false,
            'code' => 'address_generation_failed',
            'message' => $e->getMessage(),
        ];
    }
}

function crypto_btc_service_key_and_address_type(string $extendedKey): array
{
    $prefix = strtolower(substr(trim($extendedKey), 0, 4));

    if (in_array($prefix, ['vpub', 'zpub'], true)) {
        return ['z', 'bech32'];
    }

    if (in_array($prefix, ['upub', 'ypub'], true)) {
        return ['y', 'p2sh-segwit'];
    }

    return ['x', 'legacy'];
}
