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

    $positionID = isset($body['positionID']) ? (int) $body['positionID'] : 0;
    $departmentID = isset($body['departmentID']) ? (int) $body['departmentID'] : 0;
    $search = isset($body['search']) ? trim($body['search']) : '';

    $where = [];
    $params = [];

    if ($positionID > 0) {
        $where[] = "positionID = :pid";
        $params[':pid'] = $positionID;
    }
    if ($departmentID > 0) {
        $where[] = "departmentID = :did";
        $params[':did'] = $departmentID;
    }
    if ($search !== '') {
        $where[] = "position_title LIKE :q";
        $params[':q'] = "%" . $search . "%";
    }

    $whereSql = count($where) ? (" WHERE " . implode(" AND ", $where)) : "";
    $sql = "SELECT positionID, departmentID, position_title FROM position" . $whereSql . " ORDER BY position_title ASC";
    $stmt = $pdo->prepare($sql);
    foreach ($params as $k => $v)
        $stmt->bindValue($k, $v);
    $stmt->execute();
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode(['status' => 'success', 'positions' => $rows]);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
?>
