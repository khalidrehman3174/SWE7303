<?php

require_once __DIR__ . '/../../lib/bootstrap.php';
require_once __DIR__ . '/../../lib/payment_contacts_service.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    api_bad_request('Only POST is supported', 'invalid_method');
}

$userId = api_get_authenticated_user_id();
$body = api_get_request_json();

$contactId = isset($body['contact_id']) ? (int)$body['contact_id'] : 0;

$result = payment_contacts_delete($dbc, $userId, $contactId);

if (!$result['ok']) {
    api_json_response(422, false, $result['code'], $result['message']);
}

api_json_response(200, true, 'contact_deleted', 'Contact deleted successfully', []);
