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
    $db = new Database();
    $pdo = $db->connect();

    $ct = $_SERVER['CONTENT_TYPE'] ?? '';
    $raw = file_get_contents('php://input');
    $body = (stripos($ct, 'application/json') !== false) ? json_decode($raw, true) : null;
    if (!is_array($body))
        $body = ($_SERVER['REQUEST_METHOD'] === 'POST') ? $_POST : $_GET;

    $shiftID = isset($body['shiftID']) ? (int) $body['shiftID'] : 0;
    $search = isset($body['search']) ? trim($body['search']) : '';

    $where = [];
    $params = [];

    if ($shiftID > 0) {
        $where[] = "shift_id = :sid";
        $params[':sid'] = $shiftID;
    }
    if ($search !== '') {
        $where[] = "shift_name LIKE :q";
        $params[':q'] = "%" . $search . "%";
    }

    $whereSql = count($where) ? (" WHERE " . implode(" AND ", $where)) : "";
    $sql = "SELECT shift_id, shift_name, time_in, time_out, shift_hours, description FROM shift_templates" . $whereSql . " ORDER BY shift_id ASC";
    $stmt = $pdo->prepare($sql);
    foreach ($params as $k => $v)
        $stmt->bindValue($k, $v);
    $stmt->execute();
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode(['status' => 'success', 'shifts' => $rows]);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
?>