<?php
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}
if ($_SERVER['REQUEST_METHOD'] !== 'POST' && $_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['status' => 'error', 'message' => 'Method Not Allowed']);
    exit;
}

$base = getenv('PAYROLL_API_BASE') ?: 'http://26.89.31.92/SIA/SIA-Payroll-System-Modules/modules/get_all_payslip.php';
$ct = $_SERVER['CONTENT_TYPE'] ?? '';
$raw = file_get_contents('php://input');
$body = (stripos($ct, 'application/json') !== false) ? json_decode($raw, true) : null;
if (!is_array($body))
    $body = ($_SERVER['REQUEST_METHOD'] === 'POST') ? $_POST : $_GET;

$params = $body ?: [];
$query = http_build_query($params);
$url = $base . (strpos($base, '?') === false ? '?' : '&') . $query;

$headers = ['Accept: application/json'];
$apiKey = getenv('PAYROLL_API_KEY') ?: '';
if ($apiKey !== '') {
    $headers[] = 'X-API-Key: ' . $apiKey;
}
$bearer = getenv('PAYROLL_API_TOKEN') ?: '';
if ($bearer !== '') {
    $headers[] = 'Authorization: Bearer ' . $bearer;
}

$connectTimeout = (int) (getenv('PAYROLL_CONNECT_TIMEOUT_MS') ?: 5000);
$timeout = (int) (getenv('PAYROLL_TIMEOUT_MS') ?: 15000);

$ch = curl_init($url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
curl_setopt($ch, CURLOPT_CONNECTTIMEOUT_MS, $connectTimeout);
curl_setopt($ch, CURLOPT_TIMEOUT_MS, $timeout);
$res = curl_exec($ch);
$err = curl_error($ch);
$code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

http_response_code($code ?: 500);
if ($err) {
    echo json_encode(['status' => 'error', 'message' => $err, 'url' => $url]);
    exit;
}

$decoded = null;
if (is_string($res)) {
    $decoded = json_decode($res, true);
}

if (is_array($decoded)) {
    echo json_encode(['status' => 'success', 'data' => $decoded]);
} else {
    echo json_encode(['status' => 'success', 'payload' => $res]);
}

