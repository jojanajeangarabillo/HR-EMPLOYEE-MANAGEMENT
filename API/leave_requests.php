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

    $search = isset($body['search']) ? trim($body['search']) : '';
    $empID = isset($body['empID']) ? trim($body['empID']) : '';
    $status = isset($body['status']) ? trim($body['status']) : '';
    $department = isset($body['department']) ? trim($body['department']) : '';
    $leave_type = isset($body['leave_type']) ? trim($body['leave_type']) : '';
    $date_from = isset($body['date_from']) ? trim($body['date_from']) : ''; // YYYY-MM-DD
    $date_to = isset($body['date_to']) ? trim($body['date_to']) : '';       // YYYY-MM-DD

    $where = [];
    $params = [];

    if ($search !== '') {
        $where[] = "(fullname LIKE :q OR empID LIKE :q OR leave_type_name LIKE :q OR department LIKE :q OR position LIKE :q)";
        $params[':q'] = "%" . $search . "%";
    }
    if ($empID !== '') {
        $where[] = "empID = :emp";
        $params[':emp'] = $empID;
    }
    if ($status !== '') {
        $where[] = "LOWER(status) = LOWER(:st)";
        $params[':st'] = $status;
    }
    if ($department !== '') {
        $where[] = "department = :dept";
        $params[':dept'] = $department;
    }
    if ($leave_type !== '') {
        $where[] = "leave_type_name = :lt";
        $params[':lt'] = $leave_type;
    }
    if ($date_from !== '' && $date_to !== '') {
        $where[] = "requested_at BETWEEN :df AND :dt";
        $params[':df'] = $date_from . " 00:00:00";
        $params[':dt'] = $date_to . " 23:59:59";
    } elseif ($date_from !== '') {
        $where[] = "requested_at >= :df";
        $params[':df'] = $date_from . " 00:00:00";
    } elseif ($date_to !== '') {
        $where[] = "requested_at <= :dt";
        $params[':dt'] = $date_to . " 23:59:59";
    }

    $whereSql = count($where) ? (" WHERE " . implode(" AND ", $where)) : "";

    $sql = "SELECT * FROM leave_request" . $whereSql . " ORDER BY requested_at DESC";
    $stmt = $pdo->prepare($sql);
    foreach ($params as $k => $v)
        $stmt->bindValue($k, $v);
    $stmt->execute();
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        'status' => 'success',
        'requests' => $rows,
    ]);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}

