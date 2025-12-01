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
    $department_name = isset($body['department']) ? trim($body['department']) : '';
    $employment_type_name = isset($body['employment_type']) ? trim($body['employment_type']) : '';
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
    } elseif ($department_name !== '') {
        $where[] = "d.deptName = :deptName";
        $params[':deptName'] = $department_name;
    }
    if ($employment_type_id > 0) {
        $where[] = "j.employment_type = :et";
        $params[':et'] = $employment_type_id;
    } elseif ($employment_type_name !== '') {
        $where[] = "et.typeName = :etName";
        $params[':etName'] = $employment_type_name;
    }
    if ($created_from !== '' && $created_to !== '') {
        $where[] = "j.date_posted BETWEEN :cf AND :ct";
        $params[':cf'] = $created_from;
        $params[':ct'] = $created_to;
    } elseif ($created_from !== '') {
        $where[] = "j.date_posted >= :cf";
        $params[':cf'] = $created_from;
    } elseif ($created_to !== '') {
        $where[] = "j.date_posted <= :ct";
        $params[':ct'] = $created_to;
    }

    $whereSql = count($where) ? (" WHERE " . implode(" AND ", $where)) : "";

    $sql = "SELECT 
                j.jobID,
                j.job_title,
                j.job_description,
                d.deptName AS department,
                j.qualification,
                j.educational_level,
                j.skills,
                j.expected_salary,
                j.experience_years,
                et.typeName AS employment_type,
                j.location,
                j.date_posted,
                j.closing_date,
                COALESCE(SUM(v.vacancy_count), 0) AS vacancy_count
            FROM job_posting j
            JOIN department d ON d.deptID = j.department
            JOIN employment_type et ON et.emtypeID = j.employment_type
            LEFT JOIN position p ON p.position_title = j.job_title
            LEFT JOIN vacancies v ON v.position_id = p.positionID 
                                 AND v.department_id = j.department 
                                 AND v.employment_type_id = j.employment_type
            " . $whereSql . "
            GROUP BY j.jobID, j.job_title, j.job_description, d.deptName, j.qualification, j.educational_level, j.skills, j.expected_salary, j.experience_years, et.typeName, j.location, j.date_posted, j.closing_date
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
