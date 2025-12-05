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

    $limit = isset($body['limit']) ? (int) $body['limit'] : 25;
    if ($limit < 1)
        $limit = 25;
    if ($limit > 100)
        $limit = 100;
    $page = isset($body['page']) ? (int) $body['page'] : 1;
    if ($page < 1)
        $page = 1;
    $offset = ($page - 1) * $limit;

    $search = isset($body['search']) ? trim($body['search']) : '';
    $empID = isset($body['empID']) ? trim($body['empID']) : '';
    $department = isset($body['department']) ? trim($body['department']) : '';
    $position = isset($body['position']) ? trim($body['position']) : '';
    $status = isset($body['status']) ? trim($body['status']) : '';

    $where = [];
    $params = [];

    if ($search !== '') {
        $where[] = "(fullname LIKE :q OR empID LIKE :q OR email_address LIKE :q)";
        $params[':q'] = "%" . $search . "%";
    }
    if ($empID !== '') {
        $where[] = "empID = :empID";
        $params[':empID'] = $empID;
    }
    if ($department !== '') {
        $where[] = "department = :dept";
        $params[':dept'] = $department;
    }
    if ($position !== '') {
        $where[] = "position = :pos";
        $params[':pos'] = $position;
    }
    if ($status !== '') {
        $where[] = "EXISTS (SELECT 1 FROM user u WHERE u.applicant_employee_id = employee.empID AND LOWER(u.status) = LOWER(:st))";
        $params[':st'] = $status;
    }

    $whereSql = count($where) ? (" WHERE " . implode(" AND ", $where)) : "";

    $countSql = "SELECT COUNT(*) AS cnt FROM employee" . $whereSql;
    $countStmt = $pdo->prepare($countSql);
    foreach ($params as $k => $v)
        $countStmt->bindValue($k, $v);
    $countStmt->execute();
    $total = (int) $countStmt->fetchColumn();
    $pages = $total > 0 ? (int) ceil($total / $limit) : 1;

    $sql = "SELECT * FROM employee" . $whereSql . " ORDER BY empID ASC LIMIT :limit OFFSET :offset";
    $stmt = $pdo->prepare($sql);
    foreach ($params as $k => $v)
        $stmt->bindValue($k, $v);
    $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $stmt->execute();
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        'status' => 'success',
        'employees' => $rows,
    ]);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}

