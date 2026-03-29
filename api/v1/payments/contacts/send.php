<?php

require_once __DIR__ . '/../../lib/bootstrap.php';
require_once __DIR__ . '/../../lib/payment_contacts_service.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    api_bad_request('Only POST is supported', 'invalid_method');
}

$userId = api_get_authenticated_user_id();
$body = api_get_request_json();

$contactId = isset($body['contact_id']) ? (int)$body['contact_id'] : 0;
$amount = isset($body['amount']) ? (float)$body['amount'] : 0.0;
$note = isset($body['note']) ? (string)$body['note'] : '';

$result = payment_contacts_send_payment($dbc, $userId, $contactId, $amount, $note);

if (!$result['ok']) {
    api_json_response(422, false, $result['code'], $result['message']);
}

api_json_response(200, true, 'payment_sent', 'Payment sent successfully', [
    'payment' => $result['payment'],
    'balance' => $result['balance'],
]);
