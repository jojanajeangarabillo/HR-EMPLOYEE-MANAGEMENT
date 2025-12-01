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

require __DIR__ . '/Config.php';

try {
    $ct = $_SERVER['CONTENT_TYPE'] ?? '';
    $raw = file_get_contents('php://input');
    $body = (stripos($ct, 'application/json') !== false) ? json_decode($raw, true) : null;
    if (!is_array($body))
        $body = ($_SERVER['REQUEST_METHOD'] === 'POST') ? $_POST : $_GET;
    $empID = isset($body['empID']) ? trim($body['empID']) : '';
    if ($empID === '') {
        http_response_code(400);
        echo json_encode(['status' => 'error', 'message' => 'empID is required']);
        exit;
    }

    $db = new Database();
    $pdo = $db->connect();
    if ($pdo === null) {
        throw new RuntimeException('Database connection failed');
    }

    $stmt = $pdo->prepare("SELECT * FROM employee WHERE empID = ? LIMIT 1");
    $stmt->execute([$empID]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$row) {
        http_response_code(404);
        echo json_encode(['status' => 'error', 'message' => 'Employee not found']);
        exit;
    }

    echo json_encode(['status' => 'success', 'employee' => $row]);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
