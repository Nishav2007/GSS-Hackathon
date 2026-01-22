<?php
require_once 'config.php';
requireLogin();

header('Content-Type: application/json; charset=utf-8');

function jsonResponse($code, $payload) {
    http_response_code($code);
    echo json_encode($payload);
    exit;
}

function curlPostJson($url, $payload, $headers) {
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_HTTPHEADER => $headers,
        CURLOPT_POSTFIELDS => json_encode($payload),
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

$raw = file_get_contents('php://input');
$data = json_decode($raw, true);
if (!is_array($data)) {
    jsonResponse(400, ['error' => 'Invalid JSON body']);
}

$name = trim((string) ($data['name'] ?? ''));
$email = trim((string) ($data['email'] ?? ''));
$phone = trim((string) ($data['phone'] ?? ''));
$amountNpr = $data['amount_npr'] ?? null;

if ($name === '' || $email === '' || $phone === '' || $amountNpr === null) {
    jsonResponse(422, ['error' => 'Required: name, email, phone, amount_npr']);
}
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    jsonResponse(422, ['error' => 'Invalid email']);
}
if (!preg_match('/^[0-9+]{7,15}$/', $phone)) {
    jsonResponse(422, ['error' => 'Invalid phone']);
}
if (!is_numeric($amountNpr) || (float) $amountNpr <= 0) {
    jsonResponse(422, ['error' => 'amount_npr must be > 0']);
}

$amountPaisa = nprToPaisa($amountNpr);
if ($amountPaisa < 1000) {
    jsonResponse(422, ['error' => 'Minimum topup is Rs 10 (1000 paisa)']);
}

$khaltiSecret = getenv('KHALTI_SECRET_KEY') ?: '';
if ($khaltiSecret === '') {
    jsonResponse(500, ['error' => 'KHALTI_SECRET_KEY not configured']);
}

$baseUrl = getBaseUrl();
$returnUrl = $baseUrl . '/khalti-callback.php';
$websiteUrl = $baseUrl;

$purchaseOrderId = 'PO-' . date('YmdHis') . '-' . bin2hex(random_bytes(4));

$stmt = $conn->prepare("
    INSERT INTO payments (user_id, purchase_order_id, amount_paisa, status)
    VALUES (?, ?, ?, 'pending')
");
$stmt->bind_param('isi', $_SESSION['user_id'], $purchaseOrderId, $amountPaisa);
if (!$stmt->execute()) {
    jsonResponse(500, ['error' => 'Failed to create payment record']);
}

$payload = [
    'return_url' => $returnUrl,
    'website_url' => $websiteUrl,
    'amount' => $amountPaisa,
    'purchase_order_id' => $purchaseOrderId,
    'purchase_order_name' => 'Astha Wallet Topup',
    'customer_info' => [
        'name' => $name,
        'email' => $email,
        'phone' => $phone,
    ],
];

$headers = [
    'Content-Type: application/json',
    'Authorization: Key ' . $khaltiSecret,
];

$resp = curlPostJson(getKhaltiApiBase() . 'initiate/', $payload, $headers);
if (!$resp['ok']) {
    $j = json_encode($resp['raw']);
    $stmt = $conn->prepare("
        UPDATE payments
        SET status = 'failed', khalti_response_json = ?
        WHERE purchase_order_id = ? AND status = 'pending'
    ");
    $stmt->bind_param('ss', $j, $purchaseOrderId);
    $stmt->execute();

    jsonResponse(502, [
        'error' => 'Khalti initiate failed',
        'http' => $resp['http'],
        'detail' => $resp['error'],
        'raw' => $resp['raw'],
    ]);
}

$pidx = $resp['raw']['pidx'] ?? null;
$paymentUrl = $resp['raw']['payment_url'] ?? null;
if (!$pidx || !$paymentUrl) {
    jsonResponse(502, ['error' => 'Unexpected Khalti response']);
}

$j = json_encode($resp['raw']);
$stmt = $conn->prepare("
    UPDATE payments SET pidx = ?, khalti_response_json = ?
    WHERE purchase_order_id = ? AND status = 'pending'
");
$stmt->bind_param('sss', $pidx, $j, $purchaseOrderId);
$stmt->execute();

jsonResponse(200, [
    'purchase_order_id' => $purchaseOrderId,
    'pidx' => $pidx,
    'payment_url' => $paymentUrl,
]);
