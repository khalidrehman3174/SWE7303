<?php

require_once __DIR__ . '/../lib/bootstrap.php';
require_once __DIR__ . '/../../../includes/available_balance.php';

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    api_bad_request('Only GET is supported', 'invalid_method');
}

$userId = api_get_authenticated_user_id();
$balancePayload = finpay_get_available_balance_gbp($dbc, $userId);
$limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 200;
if ($limit < 1) {
    $limit = 1;
}
if ($limit > 500) {
    $limit = 500;
}

$tableExistsResult = mysqli_query($dbc, "SHOW TABLES LIKE 'deposits'");
if (!$tableExistsResult || mysqli_num_rows($tableExistsResult) === 0) {
    api_json_response(200, true, 'deposit_activity_list', 'No deposit activity yet', [
        'all' => [],
        'recent' => [],
        'balance' => [
            'amount' => (float)($balancePayload['amount'] ?? 0.0),
            'sign' => (string)($balancePayload['sign'] ?? ''),
            'major' => (string)($balancePayload['major'] ?? '0'),
            'minor' => (string)($balancePayload['minor'] ?? '00'),
            'formatted' => (string)($balancePayload['formatted'] ?? '0.00'),
            'currency' => (string)($balancePayload['currency'] ?? 'GBP'),
            'source' => (string)($balancePayload['source'] ?? 'none'),
        ],
    ]);
}

$columnsResult = mysqli_query($dbc, 'SHOW COLUMNS FROM deposits');
$depositColumns = [];
if ($columnsResult) {
    while ($col = mysqli_fetch_assoc($columnsResult)) {
        $depositColumns[] = $col['Field'];
    }
}

$idExpr = in_array('deposit_id', $depositColumns, true)
    ? 'deposit_id'
    : (in_array('public_id', $depositColumns, true) ? 'public_id' : 'CAST(id AS CHAR)');

$netAmountExpr = in_array('net_amount', $depositColumns, true)
    ? 'net_amount'
    : (in_array('amount', $depositColumns, true) ? 'amount' : '0');

$completedAtExpr = in_array('completed_at', $depositColumns, true)
    ? 'completed_at'
    : (in_array('settled_at', $depositColumns, true) ? 'settled_at' : 'created_at');

$sql = "SELECT
            {$idExpr} AS activity_id,
            method,
            currency,
            {$netAmountExpr} AS net_amount,
            status,
            provider,
            created_at,
            {$completedAtExpr} AS completed_at
        FROM deposits
        WHERE user_id = ?
        ORDER BY created_at DESC
        LIMIT ?";

$stmt = mysqli_prepare($dbc, $sql);
if (!$stmt) {
    api_server_error('Could not prepare activity query');
}

mysqli_stmt_bind_param($stmt, 'ii', $userId, $limit);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

$activities = [];
if ($result) {
    while ($row = mysqli_fetch_assoc($result)) {
        $activities[] = $row;
    }
}
mysqli_stmt_close($stmt);

function list_activity_time_label(?string $createdAt): string
{
    if (empty($createdAt)) {
        return 'Recently';
    }

    $createdTs = strtotime($createdAt);
    if ($createdTs === false) {
        return 'Recently';
    }

    $diff = time() - $createdTs;
    if ($diff < 60) {
        return 'Just now';
    }
    if ($diff < 3600) {
        return floor($diff / 60) . ' min ago';
    }
    if ($diff < 86400) {
        return floor($diff / 3600) . ' hr ago';
    }
    if ($diff < 172800) {
        return 'Yesterday';
    }

    return date('j M', $createdTs);
}

function list_activity_datetime_label(?string $timestamp): string
{
    if (empty($timestamp)) {
        return 'N/A';
    }

    $ts = strtotime($timestamp);
    if ($ts === false) {
        return 'N/A';
    }

    return date('d M Y, H:i', $ts);
}

function list_activity_meta(string $method, string $status): array
{
    $safeMethod = strtolower($method);
    $safeStatus = strtolower($status);

    $map = [
        'bank' => ['icon_class' => 'fas fa-university', 'bg' => 'rgba(59, 130, 246, 0.12)', 'color' => '#3b82f6', 'label' => 'Bank Deposit'],
        'card' => ['icon_class' => 'fas fa-credit-card', 'bg' => 'rgba(16, 185, 129, 0.12)', 'color' => '#10b981', 'label' => 'Card Deposit'],
        'apple' => ['icon_class' => 'fab fa-apple', 'bg' => 'rgba(17, 24, 39, 0.10)', 'color' => 'var(--text-primary)', 'label' => 'Apple Pay Deposit'],
    ];

    $meta = $map[$safeMethod] ?? ['icon_class' => 'fas fa-arrow-down', 'bg' => 'var(--icon-bg-default)', 'color' => 'var(--text-primary)', 'label' => 'Deposit'];

    if ($safeStatus === 'completed') {
        $meta['sub'] = 'Completed';
    } elseif ($safeStatus === 'pending_provider' || $safeStatus === 'pending_webhook') {
        $meta['sub'] = 'Pending';
    } elseif ($safeStatus === 'failed') {
        $meta['sub'] = 'Failed';
    } elseif ($safeStatus === 'reversed') {
        $meta['sub'] = 'Reversed';
    } else {
        $meta['sub'] = ucfirst($safeStatus ?: 'Initiated');
    }

    return $meta;
}

$payload = [];
foreach ($activities as $activity) {
    $meta = list_activity_meta((string)($activity['method'] ?? ''), (string)($activity['status'] ?? ''));
    $payload[] = [
        'activity_id' => (string)($activity['activity_id'] ?? 'n/a'),
        'activity_type' => 'Deposit',
        'label' => (string)$meta['label'],
        'status_raw' => (string)($activity['status'] ?? 'unknown'),
        'status_sub' => (string)$meta['sub'],
        'method' => (string)($activity['method'] ?? 'n/a'),
        'icon_class' => (string)($meta['icon_class'] ?? 'fas fa-arrow-down'),
        'amount' => number_format((float)($activity['net_amount'] ?? 0), 2),
        'currency' => strtoupper((string)($activity['currency'] ?? 'GBP')),
        'time_label' => list_activity_time_label($activity['created_at'] ?? null),
        'created_label' => list_activity_datetime_label($activity['created_at'] ?? null),
        'completed_label' => list_activity_datetime_label($activity['completed_at'] ?? null),
    ];
}

api_json_response(200, true, 'deposit_activity_list', 'Deposit activity fetched', [
    'all' => $payload,
    'recent' => array_slice($payload, 0, 3),
    'balance' => [
        'amount' => (float)($balancePayload['amount'] ?? 0.0),
        'sign' => (string)($balancePayload['sign'] ?? ''),
        'major' => (string)($balancePayload['major'] ?? '0'),
        'minor' => (string)($balancePayload['minor'] ?? '00'),
        'formatted' => (string)($balancePayload['formatted'] ?? '0.00'),
        'currency' => (string)($balancePayload['currency'] ?? 'GBP'),
        'source' => (string)($balancePayload['source'] ?? 'none'),
    ],
]);
