<?php

require_once __DIR__ . '/../lib/bootstrap.php';

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    api_bad_request('Only GET is supported', 'invalid_method');
}

$userId = api_get_authenticated_user_id();
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

function list_table_exists(mysqli $dbc, string $table): bool
{
    $safeTable = preg_replace('/[^a-zA-Z0-9_]/', '', $table);
    if ($safeTable === '') {
        return false;
    }

    $result = mysqli_query($dbc, "SHOW TABLES LIKE '{$safeTable}'");
    return $result && mysqli_num_rows($result) > 0;
}

function list_table_columns(mysqli $dbc, string $table): array
{
    $safeTable = preg_replace('/[^a-zA-Z0-9_]/', '', $table);
    if ($safeTable === '') {
        return [];
    }

    $columns = [];
    $result = mysqli_query($dbc, "SHOW COLUMNS FROM {$safeTable}");
    if ($result) {
        while ($row = mysqli_fetch_assoc($result)) {
            $columns[] = (string)($row['Field'] ?? '');
        }
    }

    return $columns;
}

function list_first_existing_column(array $columns, array $candidates): ?string
{
    foreach ($candidates as $candidate) {
        if (in_array($candidate, $columns, true)) {
            return $candidate;
        }
    }

    return null;
}

function list_sum_completed_gbp_for_table(mysqli $dbc, int $userId, string $table, array $columnMap): float
{
    if (!list_table_exists($dbc, $table)) {
        return 0.0;
    }

    $columns = list_table_columns($dbc, $table);
    if (empty($columns)) {
        return 0.0;
    }

    $userCol = list_first_existing_column($columns, $columnMap['user']);
    $statusCol = list_first_existing_column($columns, $columnMap['status']);
    $currencyCol = list_first_existing_column($columns, $columnMap['currency']);
    $amountCol = list_first_existing_column($columns, $columnMap['amount']);

    if ($userCol === null || $statusCol === null || $currencyCol === null || $amountCol === null) {
        return 0.0;
    }

    $safeTable = preg_replace('/[^a-zA-Z0-9_]/', '', $table);
    $sql = "SELECT COALESCE(SUM({$amountCol}), 0) AS total
            FROM {$safeTable}
            WHERE {$userCol} = ?
              AND LOWER({$statusCol}) = 'completed'
              AND UPPER({$currencyCol}) = 'GBP'";

    $stmt = mysqli_prepare($dbc, $sql);
    if (!$stmt) {
        return 0.0;
    }

    mysqli_stmt_bind_param($stmt, 'i', $userId);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $row = $result ? mysqli_fetch_assoc($result) : null;
    mysqli_stmt_close($stmt);

    return (float)($row['total'] ?? 0);
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

$completedGbpDeposits = list_sum_completed_gbp_for_table($dbc, $userId, 'deposits', [
    'user' => ['user_id'],
    'status' => ['status'],
    'currency' => ['currency'],
    'amount' => ['net_amount', 'amount'],
]);

$completedGbpWithdrawals = 0.0;
$withdrawalTables = ['withdrawals', 'fiat_withdrawals', 'withdrawal_requests'];
foreach ($withdrawalTables as $withdrawalTable) {
    $tableTotal = list_sum_completed_gbp_for_table($dbc, $userId, $withdrawalTable, [
        'user' => ['user_id'],
        'status' => ['status', 'state'],
        'currency' => ['currency', 'fiat_currency', 'asset'],
        'amount' => ['net_amount', 'amount', 'withdrawal_amount'],
    ]);

    if ($tableTotal > 0) {
        $completedGbpWithdrawals += $tableTotal;
    }
}

$fiatAreaBalance = $completedGbpDeposits - $completedGbpWithdrawals;
$fiatBalanceAbsFormatted = number_format(abs($fiatAreaBalance), 2, '.', ',');
$fiatBalanceParts = explode('.', $fiatBalanceAbsFormatted);
$fiatBalanceMajor = $fiatBalanceParts[0] ?? '0';
$fiatBalanceMinor = $fiatBalanceParts[1] ?? '00';
$fiatBalanceSign = $fiatAreaBalance < 0 ? '-' : '';

api_json_response(200, true, 'deposit_activity_list', 'Deposit activity fetched', [
    'all' => $payload,
    'recent' => array_slice($payload, 0, 3),
    'balance' => [
        'amount' => $fiatAreaBalance,
        'sign' => $fiatBalanceSign,
        'major' => $fiatBalanceMajor,
        'minor' => $fiatBalanceMinor,
        'formatted' => $fiatBalanceSign . $fiatBalanceMajor . '.' . $fiatBalanceMinor,
    ],
]);
