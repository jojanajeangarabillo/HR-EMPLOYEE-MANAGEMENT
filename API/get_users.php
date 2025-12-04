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

    $secret = getenv('HR_API_SECRET') ?: 'hr_api_secret';

    $b64url = function ($data) {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    };
    $makeToken = function ($claims) use ($secret, $b64url) {
        $header = ['alg' => 'HS256', 'typ' => 'JWT'];
        $h = $b64url(json_encode($header));
        $p = $b64url(json_encode($claims));
        $sig = hash_hmac('sha256', $h . '.' . $p, $secret, true);
        $s = $b64url($sig);
        return $h . '.' . $p . '.' . $s;
    };

    $ct = $_SERVER['CONTENT_TYPE'] ?? '';
    $raw = file_get_contents('php://input');
    $body = (stripos($ct, 'application/json') !== false) ? json_decode($raw, true) : null;
    if (!is_array($body))
        $body = ($_SERVER['REQUEST_METHOD'] === 'POST') ? $_POST : $_GET;

    $loginEmail = isset($body['email']) ? trim($body['email']) : '';
    $loginPassword = isset($body['password']) ? (string) $body['password'] : '';
    if ($loginEmail !== '' && $loginPassword !== '') {
        $stmt = $pdo->prepare("SELECT applicant_employee_id, email, password, role, fullname, status, created_at, profile_pic, sub_role FROM user WHERE email = ? LIMIT 1");
        $stmt->execute([$loginEmail]);
        $u = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$u || !password_verify($loginPassword, $u['password'])) {
            http_response_code(401);
            echo json_encode(['status' => 'error', 'message' => 'Invalid credentials']);
            return;
        }
        $now = time();
        $exp = $now + 3600;
        $claims = [
            'sub' => $u['applicant_employee_id'],
            'email' => $u['email'],
            'role' => $u['role'],
            'name' => $u['fullname'],
            'iat' => $now,
            'exp' => $exp
        ];
        $token = $makeToken($claims);
        echo json_encode([
            'status' => 'success',
            'token' => $token,
            'expires_at' => date('c', $exp),
            'user' => [
                'applicant_employee_id' => $u['applicant_employee_id'],
                'email' => $u['email'],
                'role' => $u['role'],
                'fullname' => $u['fullname'],
                'status' => $u['status'],
                'created_at' => $u['created_at'],
                'profile_pic' => $u['profile_pic'],
                'sub_role' => $u['sub_role']
            ]
        ]);
        return;
    }

    $search = isset($body['search']) ? trim($body['search']) : '';
    $role = isset($body['role']) ? trim($body['role']) : '';
    $status = isset($body['status']) ? trim($body['status']) : '';
    $sub_role = isset($body['sub_role']) ? trim($body['sub_role']) : '';
    $department = isset($body['department']) ? trim($body['department']) : '';
    $position = isset($body['position']) ? trim($body['position']) : '';
    $created_from = isset($body['created_from']) ? trim($body['created_from']) : '';
    $created_to = isset($body['created_to']) ? trim($body['created_to']) : '';

    $where = [];
    $params = [];

    if ($search !== '') {
        $where[] = "(u.fullname LIKE :q OR u.email LIKE :q OR u.applicant_employee_id LIKE :q)";
        $params[':q'] = "%" . $search . "%";
    }
    if ($role !== '') {
        $where[] = "u.role = :role";
        $params[':role'] = $role;
    }
    if ($status !== '') {
        $where[] = "u.status = :st";
        $params[':st'] = $status;
    }
    if ($sub_role !== '') {
        $where[] = "u.sub_role = :sr";
        $params[':sr'] = $sub_role;
    }
    if ($department !== '') {
        $where[] = "e.department = :dept";
        $params[':dept'] = $department;
    }
    if ($position !== '') {
        $where[] = "e.position = :pos";
        $params[':pos'] = $position;
    }
    if ($created_from !== '' && $created_to !== '') {
        $where[] = "u.created_at BETWEEN :cf AND :ct";
        $params[':cf'] = $created_from;
        $params[':ct'] = $created_to;
    } elseif ($created_from !== '') {
        $where[] = "u.created_at >= :cf";
        $params[':cf'] = $created_from;
    } elseif ($created_to !== '') {
        $where[] = "u.created_at <= :ct";
        $params[':ct'] = $created_to;
    }

    $whereSql = count($where) ? (" WHERE " . implode(" AND ", $where)) : "";

    $sql = "SELECT 
                u.applicant_employee_id,
                u.email,
                u.role,
                u.fullname,
                u.status,
                u.created_at,
                u.profile_pic,
                u.sub_role,
                e.department,
                e.position,
                e.type_name
            FROM user u
            LEFT JOIN employee e ON e.empID = u.applicant_employee_id
            " . $whereSql . "
            ORDER BY u.created_at DESC";

    $stmt = $pdo->prepare($sql);
    foreach ($params as $k => $v)
        $stmt->bindValue($k, $v);
    $stmt->execute();
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        'status' => 'success',
        'users' => $rows,
    ]);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
