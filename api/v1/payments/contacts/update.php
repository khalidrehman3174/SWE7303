<?php

require_once __DIR__ . '/../../lib/bootstrap.php';
require_once __DIR__ . '/../../lib/payment_contacts_service.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    api_bad_request('Only POST is supported', 'invalid_method');
}

$userId = api_get_authenticated_user_id();
$body = api_get_request_json();

$contactId = isset($body['contact_id']) ? (int)$body['contact_id'] : 0;
$recipientName = isset($body['recipient_name']) ? (string)$body['recipient_name'] : '';
$sortCode = isset($body['sort_code']) ? (string)$body['sort_code'] : '';
$accountNumber = isset($body['account_number']) ? (string)$body['account_number'] : '';

$result = payment_contacts_update($dbc, $userId, $contactId, $recipientName, $sortCode, $accountNumber);

if (!$result['ok']) {
    api_json_response(422, false, $result['code'], $result['message']);
}

api_json_response(200, true, 'contact_updated', 'Contact updated successfully', [
    'contact' => $result['contact'],
]);
