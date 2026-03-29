<?php

require_once __DIR__ . '/../../lib/bootstrap.php';
require_once __DIR__ . '/../../lib/payment_contacts_service.php';
require_once __DIR__ . '/../../../../includes/available_balance.php';

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    api_bad_request('Only GET is supported', 'invalid_method');
}

$userId = api_get_authenticated_user_id();
payment_contacts_ensure_schema($dbc);
payment_contacts_ensure_transactions_schema($dbc);

$limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 250;
if ($limit < 1) {
    $limit = 1;
}
if ($limit > 500) {
    $limit = 500;
}

$searchRaw = isset($_GET['search']) ? trim((string)$_GET['search']) : '';
$search = mb_substr($searchRaw, 0, 100);

function contacts_list_initials(string $name): string
{
    $parts = preg_split('/\s+/', trim($name));
    if (!$parts || count($parts) === 0) {
        return 'U';
    }

    $first = strtoupper(substr($parts[0], 0, 1));
    $last = count($parts) > 1 ? strtoupper(substr($parts[count($parts) - 1], 0, 1)) : '';
    return $first . $last;
}

function contacts_list_time_label(?string $createdAt): string
{
    if (empty($createdAt)) {
        return 'Recently';
    }

    $createdTs = strtotime($createdAt);
    if ($createdTs === false) {
        return 'Recently';
    }

    $today = strtotime(date('Y-m-d'));
    $entryDay = strtotime(date('Y-m-d', $createdTs));

    if ($entryDay === $today) {
        return 'Today';
    }
    if ($entryDay === strtotime('-1 day', $today)) {
        return 'Yesterday';
    }

    return date('d M', $createdTs);
}

if ($search !== '') {
    $sql = 'SELECT id, recipient_name, sort_code, account_number, created_at
            FROM payment_contacts
            WHERE user_id = ?
              AND (
                recipient_name LIKE CONCAT("%", ?, "%")
                OR sort_code LIKE CONCAT("%", ?, "%")
                OR account_number LIKE CONCAT("%", ?, "%")
              )
            ORDER BY created_at DESC
            LIMIT ?';
    $stmt = mysqli_prepare($dbc, $sql);
    if (!$stmt) {
        api_server_error('Could not prepare contacts list query');
    }
    mysqli_stmt_bind_param($stmt, 'isssi', $userId, $search, $search, $search, $limit);
} else {
    $sql = 'SELECT id, recipient_name, sort_code, account_number, created_at
            FROM payment_contacts
            WHERE user_id = ?
            ORDER BY created_at DESC
            LIMIT ?';
    $stmt = mysqli_prepare($dbc, $sql);
    if (!$stmt) {
        api_server_error('Could not prepare contacts list query');
    }
    mysqli_stmt_bind_param($stmt, 'ii', $userId, $limit);
}

mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$contacts = [];
$contactIds = [];

if ($result) {
    while ($row = mysqli_fetch_assoc($result)) {
        $contacts[] = $row;
        $contactIds[] = (int)($row['id'] ?? 0);
    }
}
mysqli_stmt_close($stmt);

$histories = [];
$contactIds = array_values(array_unique(array_filter($contactIds, static fn($id) => $id > 0)));
if (!empty($contactIds)) {
    $placeholders = implode(',', array_fill(0, count($contactIds), '?'));
    $txSql = "SELECT contact_id, direction, amount, note, created_at
              FROM payment_contact_transactions
              WHERE user_id = ? AND contact_id IN ($placeholders)
              ORDER BY created_at DESC
              LIMIT 2000";

    $txStmt = mysqli_prepare($dbc, $txSql);
    if ($txStmt) {
        $types = 'i' . str_repeat('i', count($contactIds));
        $params = array_merge([$userId], $contactIds);
        $bindRefs = [];
        foreach ($params as $k => $v) {
            $bindRefs[$k] = &$params[$k];
        }

        mysqli_stmt_bind_param($txStmt, $types, ...$bindRefs);
        mysqli_stmt_execute($txStmt);
        $txResult = mysqli_stmt_get_result($txStmt);

        if ($txResult) {
            while ($tx = mysqli_fetch_assoc($txResult)) {
                $cid = (int)($tx['contact_id'] ?? 0);
                if (!isset($histories[$cid])) {
                    $histories[$cid] = [];
                }
                if (count($histories[$cid]) >= 40) {
                    continue;
                }

                $amount = (float)($tx['amount'] ?? 0);
                $direction = (($tx['direction'] ?? 'sent') === 'received') ? 'received' : 'sent';
                $histories[$cid][] = [
                    'direction' => $direction,
                    'amount' => '£' . number_format($amount, 2),
                    'note' => (string)($tx['note'] ?? ''),
                    'timestamp' => (string)($tx['created_at'] ?? ''),
                    'time' => date('d M, H:i', strtotime((string)($tx['created_at'] ?? 'now'))),
                ];
            }
        }

        mysqli_stmt_close($txStmt);
    }
}

$balancePayload = finpay_get_available_balance_gbp($dbc, $userId);

$payload = [];
foreach ($contacts as $contact) {
    $id = (int)($contact['id'] ?? 0);
    $name = (string)($contact['recipient_name'] ?? 'Contact');
    $sortDigits = preg_replace('/[^0-9]/', '', (string)($contact['sort_code'] ?? ''));
    $acctDigits = preg_replace('/[^0-9]/', '', (string)($contact['account_number'] ?? ''));

    $sortCode = strlen($sortDigits) >= 6
        ? substr($sortDigits, 0, 2) . '-' . substr($sortDigits, 2, 2) . '-' . substr($sortDigits, 4, 2)
        : (string)($contact['sort_code'] ?? '');

    $payload[] = [
        'id' => $id,
        'name' => $name,
        'handle' => '@' . strtolower(preg_replace('/\s+/', '', $name)),
        'initials' => contacts_list_initials($name),
        'sort_code' => $sortCode,
        'account_number' => $acctDigits,
        'account_number_masked' => '****' . substr($acctDigits, -4),
        'created_at' => (string)($contact['created_at'] ?? ''),
        'time_label' => contacts_list_time_label((string)($contact['created_at'] ?? '')),
        'history' => $histories[$id] ?? [],
    ];
}

api_json_response(200, true, 'contacts_list', 'Contacts list loaded successfully', [
    'contacts' => $payload,
    'count' => count($payload),
    'wallet' => [
        'symbol' => (string)($balancePayload['currency'] ?? 'GBP'),
        'amount' => (float)($balancePayload['amount'] ?? 0.0),
        'formatted' => (string)($balancePayload['formatted'] ?? '0.00'),
        'source' => (string)($balancePayload['source'] ?? 'none'),
    ],
]);
