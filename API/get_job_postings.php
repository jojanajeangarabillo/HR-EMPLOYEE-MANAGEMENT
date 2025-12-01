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
    $department_id = isset($body['department_id']) ? (int) $body['department_id'] : 0;
    $employment_type_id = isset($body['employment_type_id']) ? (int) $body['employment_type_id'] : 0;
    $created_from = isset($body['created_from']) ? trim($body['created_from']) : ''; // YYYY-MM-DD
    $created_to = isset($body['created_to']) ? trim($body['created_to']) : '';       // YYYY-MM-DD

    $where = [];
    $params = [];

    if ($search !== '') {
        $where[] = "(j.job_title LIKE :q)";
        $params[':q'] = "%" . $search . "%";
    }
    if ($department_id > 0) {
        $where[] = "j.department = :dept";
        $params[':dept'] = $department_id;
    }
    if ($employment_type_id > 0) {
        $where[] = "j.employment_type = :et";
        $params[':et'] = $employment_type_id;
    }
    if ($created_from !== '' && $created_to !== '') {
        $where[] = "v.created_at BETWEEN :cf AND :ct";
        $params[':cf'] = $created_from . " 00:00:00";
        $params[':ct'] = $created_to . " 23:59:59";
    } elseif ($created_from !== '') {
        $where[] = "v.created_at >= :cf";
        $params[':cf'] = $created_from . " 00:00:00";
    } elseif ($created_to !== '') {
        $where[] = "v.created_at <= :ct";
        $params[':ct'] = $created_to . " 23:59:59";
    }

    $whereSql = count($where) ? (" WHERE " . implode(" AND ", $where)) : "";

    $sql = "SELECT 
                j.jobID,
                j.job_title,
                j.job_description,
                j.department,
                j.qualification,
                j.educational_level,
                j.skills,
                j.expected_salary,
                j.experience_years,
                j.employment_type,
                j.location,
                j.date_posted,
                j.closing_date,
                COALESCE(SUM(v.vacancy_count), 0) AS vacancy_count
            FROM job_posting j
            LEFT JOIN position p ON p.position_title = j.job_title
            LEFT JOIN vacancies v ON v.position_id = p.positionID 
                                 AND v.department_id = j.department 
                                 AND v.employment_type_id = j.employment_type
            " . $whereSql . "
            GROUP BY j.jobID, j.job_title, j.job_description, j.department, j.qualification, j.educational_level, j.skills, j.expected_salary, j.experience_years, j.employment_type, j.location, j.date_posted, j.closing_date
            ORDER BY j.date_posted DESC, j.jobID DESC";

    $stmt = $pdo->prepare($sql);
    foreach ($params as $k => $v)
        $stmt->bindValue($k, $v);
    $stmt->execute();
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        'status' => 'success',
        'jobs' => $rows,
    ]);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
