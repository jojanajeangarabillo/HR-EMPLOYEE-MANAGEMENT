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

$base = getenv('HR_API_BASE') ?: 'http://26.137.144.53/HR-EMPLOYEE-MANAGEMENT/API';
$url = rtrim($base, '/') . '/get_users.php';

$ct = $_SERVER['CONTENT_TYPE'] ?? '';
$raw = file_get_contents('php://input');
$body = (stripos($ct, 'application/json') !== false) ? json_decode($raw, true) : null;
if (!is_array($body))
    $body = ($_SERVER['REQUEST_METHOD'] === 'POST') ? $_POST : $_GET;

$payload = json_encode($body ?: []);

$ch = curl_init($url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json', 'Accept: application/json']);
curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
$res = curl_exec($ch);
$err = curl_error($ch);
$code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

http_response_code($code ?: 500);
echo $err ? json_encode(['status' => 'error', 'message' => $err]) : ($res ?: json_encode(['status' => 'error', 'message' => 'Empty response']));
