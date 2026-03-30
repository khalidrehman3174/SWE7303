<?php

require_once __DIR__ . '/../lib/bootstrap.php';

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    api_bad_request('Only GET is supported', 'invalid_method');
}

$userId = api_get_authenticated_user_id();
$asset = isset($_GET['asset']) ? (string)$_GET['asset'] : 'BTC';
$network = isset($_GET['network']) ? (string)$_GET['network'] : 'bitcoin';

$result = crypto_btc_service_get_or_create_deposit_address($dbc, $userId, $apiConfig, $asset, $network);
if (!$result['ok']) {
    api_json_response(422, false, (string)$result['code'], (string)$result['message']);
}

api_json_response(200, true, 'btc_deposit_address_ready', 'BTC deposit address ready', [
    'address' => $result['address'],
    'created' => (bool)($result['created'] ?? false),
]);
