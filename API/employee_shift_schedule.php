<?php
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['status' => 'error', 'message' => 'Method Not Allowed']);
    exit;
}

require __DIR__ . '/Config.php';

try {
    $db = new Database();
    $pdo = $db->connect();

    $authHeader = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
    $token = '';
    if (stripos($authHeader, 'Bearer ') === 0) {
        $token = trim(substr($authHeader, 7));
    }
    $requiredToken = getenv('API_TOKEN') ?: '';
    if ($requiredToken !== '' && $token !== $requiredToken) {
        http_response_code(401);
        error_log('employee_shift_schedule: unauthorized');
        echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
        exit;
    }

    $startDate = isset($_GET['startDate']) ? trim($_GET['startDate']) : '';
    $endDate = isset($_GET['endDate']) ? trim($_GET['endDate']) : '';
    $empID = isset($_GET['empID']) ? trim($_GET['empID']) : '';
    $page = isset($_GET['page']) ? (int) $_GET['page'] : 1;
    if ($page < 1)
        $page = 1;
    $limit = isset($_GET['limit']) ? (int) $_GET['limit'] : 25;
    if ($limit < 1)
        $limit = 25;
    if ($limit > 100)
        $limit = 100;
    $offset = ($page - 1) * $limit;

    $where = [];
    $params = [];

    if ($empID !== '') {
        $where[] = "ess.empID = :emp";
        $params[':emp'] = $empID;
    }
    if ($startDate !== '' && $endDate !== '') {
        $where[] = "ess.schedule_date BETWEEN :sd AND :ed";
        $params[':sd'] = $startDate;
        $params[':ed'] = $endDate;
    } elseif ($startDate !== '') {
        $where[] = "ess.schedule_date >= :sd";
        $params[':sd'] = $startDate;
    } elseif ($endDate !== '') {
        $where[] = "ess.schedule_date <= :ed";
        $params[':ed'] = $endDate;
    }

    $whereSql = count($where) ? (" WHERE " . implode(" AND ", $where)) : "";

    $countSql = "SELECT COUNT(*) FROM employee_shift_schedule ess" . $whereSql;
    $countStmt = $pdo->prepare($countSql);
    foreach ($params as $k => $v)
        $countStmt->bindValue($k, $v);
    $countStmt->execute();
    $total = (int) $countStmt->fetchColumn();
    $pages = $total > 0 ? (int) ceil($total / $limit) : 1;

    $sql = "SELECT 
                ess.schedule_id,
                ess.empID,
                e.fullname,
                e.department,
                e.position,
                ess.shift_id,
                st.shift_name,
                st.time_in,
                st.time_out,
                ess.schedule_date,
                ess.status,
                ess.created_at
            FROM employee_shift_schedule ess
            LEFT JOIN employee e ON e.empID = ess.empID
            LEFT JOIN shift_templates st ON st.shift_id = ess.shift_id
            " . $whereSql . "
            ORDER BY ess.schedule_date ASC, ess.empID ASC
            LIMIT :limit OFFSET :offset";
    $stmt = $pdo->prepare($sql);
    foreach ($params as $k => $v)
        $stmt->bindValue($k, $v);
    $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $stmt->execute();
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    http_response_code(200);
    echo json_encode([
        'status' => 'success',
        'schedules' => $rows
    ]);
} catch (Throwable $e) {
    http_response_code(500);
    error_log('employee_shift_schedule: ' . $e->getMessage());
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
?>