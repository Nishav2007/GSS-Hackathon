<?php
require_once 'config.php';

$baseUrl = getBaseUrl();
$successUrl = $baseUrl . '/payment-success.php';
$failureUrl = $baseUrl . '/payment-failed.php';

$pidx = trim((string) ($_GET['pidx'] ?? ''));
if ($pidx === '') {
    header("Location: {$failureUrl}");
    exit;
}

$khaltiSecret = getenv('KHALTI_SECRET_KEY') ?: '';
if ($khaltiSecret === '') {
    header("Location: {$failureUrl}");
    exit;
}

function curlGetJson($url, $headers) {
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPGET => true,
        CURLOPT_HTTPHEADER => $headers,
        CURLOPT_TIMEOUT => 20,
    ]);
    $body = curl_exec($ch);
    $errno = curl_errno($ch);
    $err = curl_error($ch);
    $http = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($errno) {
        return ['ok' => false, 'http' => 0, 'error' => $err, 'raw' => null];
    }

    $decoded = json_decode($body, true);
    return [
        'ok' => $http >= 200 && $http < 300,
        'http' => $http,
        'error' => ($http >= 200 && $http < 300) ? null : ($decoded['detail'] ?? $body),
        'raw' => $decoded ?? $body,
    ];
}

$stmt = $conn->prepare("
    SELECT id, user_id, purchase_order_id, amount_paisa, status
    FROM payments WHERE pidx = ? LIMIT 1
");
$stmt->bind_param('s', $pidx);
$stmt->execute();
$payment = $stmt->get_result()->fetch_assoc();

if (!$payment) {
    header("Location: {$failureUrl}");
    exit;
}

if ($payment['status'] === 'completed') {
    header("Location: {$successUrl}");
    exit;
}

if ($payment['status'] === 'failed') {
    header("Location: {$failureUrl}");
    exit;
}

$headers = [
    'Content-Type: application/json',
    'Authorization: Key ' . $khaltiSecret,
];

$lookupUrl = getKhaltiApiBase() . 'lookup/?pidx=' . urlencode($pidx);
$resp = curlGetJson($lookupUrl, $headers);
$j = json_encode($resp['raw']);

if (!$resp['ok'] || !is_array($resp['raw'])) {
    $stmt = $conn->prepare("
        UPDATE payments SET khalti_response_json = ?
        WHERE pidx = ? AND status = 'pending'
    ");
    $stmt->bind_param('ss', $j, $pidx);
    $stmt->execute();
    header("Location: {$failureUrl}");
    exit;
}

$status = $resp['raw']['status'] ?? '';
$totalAmount = $resp['raw']['total_amount'] ?? null;
$transactionId = $resp['raw']['transaction_id'] ?? null;

if (!is_numeric($totalAmount) || (int) $totalAmount !== (int) $payment['amount_paisa']) {
    $stmt = $conn->prepare("
        UPDATE payments SET status = 'failed', khalti_response_json = ?
        WHERE pidx = ? AND status = 'pending'
    ");
    $stmt->bind_param('ss', $j, $pidx);
    $stmt->execute();
    header("Location: {$failureUrl}");
    exit;
}

if ($status !== 'Completed') {
    $stmt = $conn->prepare("
        UPDATE payments SET status = 'failed', khalti_response_json = ?
        WHERE pidx = ? AND status = 'pending'
    ");
    $stmt->bind_param('ss', $j, $pidx);
    $stmt->execute();
    header("Location: {$failureUrl}");
    exit;
}

$conn->begin_transaction();
try {
    $stmt = $conn->prepare("
        UPDATE payments
        SET status = 'completed', transaction_id = ?, khalti_response_json = ?
        WHERE pidx = ? AND status = 'pending'
    ");
    $stmt->bind_param('sss', $transactionId, $j, $pidx);
    $stmt->execute();

    if ($stmt->affected_rows === 0) {
        $conn->commit();
        header("Location: {$successUrl}");
        exit;
    }

    $stmt = $conn->prepare("
        SELECT wallet_balance_paisa, service_status
        FROM users WHERE id = ? FOR UPDATE
    ");
    $stmt->bind_param('i', $payment['user_id']);
    $stmt->execute();
    $user = $stmt->get_result()->fetch_assoc();
    $newBalance = (int) ($user['wallet_balance_paisa'] ?? 0) + (int) $payment['amount_paisa'];
    $newStatus = $newBalance >= WALLET_SUSPEND_THRESHOLD_PAISA ? 'active' : $user['service_status'];

    $stmt = $conn->prepare("
        UPDATE users
        SET wallet_balance_paisa = ?, last_topup_at = NOW(), last_wallet_warning_level = 'none', service_status = ?
        WHERE id = ?
    ");
    $stmt->bind_param('isi', $newBalance, $newStatus, $payment['user_id']);
    $stmt->execute();

    $desc = 'Wallet topup via Khalti';
    $stmt = $conn->prepare("
        INSERT INTO wallet_ledger (user_id, type, amount_paisa, description, ref_type, ref_id, balance_after_paisa)
        VALUES (?, 'topup', ?, ?, 'payment', ?, ?)
    ");
    $stmt->bind_param('iisii', $payment['user_id'], $payment['amount_paisa'], $desc, $payment['id'], $newBalance);
    $stmt->execute();

    $conn->commit();
    header("Location: {$successUrl}");
    exit;
} catch (Exception $e) {
    $conn->rollback();
    header("Location: {$failureUrl}");
    exit;
}
