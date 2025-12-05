<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit(0);
}

$dbFiles = [
    '../Config.php',
    'Config.php',
    '../includes/Config.php'
];
$dbLoaded = false;
foreach ($dbFiles as $file) {
    if (file_exists($file)) {
        require_once $file;
        $dbLoaded = true;
        break;
    }
}

if (!$dbLoaded) {
    http_response_code(500);
    error_log('get_users: database config not found');
    echo json_encode(['status' => 'error', 'message' => 'Database connection file not found']);
    exit;
}

try {
    if (!class_exists('Database')) {
        http_response_code(500);
        error_log('get_users: Database class missing');
        echo json_encode(['status' => 'error', 'message' => 'Database class not found']);
        exit;
    }

    $database = new Database();
    $conn = $database->connect();
} catch (Exception $e) {
    http_response_code(500);
    error_log('get_users: connection failed: ' . $e->getMessage());
    echo json_encode(['status' => 'error', 'message' => 'Connection failed']);
    exit;
}

$email = '';
$password = '';

// Retrieve input from JSON, POST, or GET
$rawInput = file_get_contents('php://input');
$jsonInput = json_decode($rawInput, true);

if (is_array($jsonInput)) {
    $email = $jsonInput['user']['email'] ?? $jsonInput['email'] ?? '';
    $password = $jsonInput['user']['password'] ?? $jsonInput['password'] ?? '';
}

if (empty($email)) {
    $email = $_POST['user']['email'] ?? $_POST['email'] ?? '';
    $password = $_POST['user']['password'] ?? $_POST['password'] ?? '';
}

if (empty($email)) {
    $email = $_GET['email'] ?? '';
    $password = $_GET['password'] ?? '';
}

if (empty($email) || empty($password)) {
    http_response_code(400);
    error_log('get_users: missing email or password field');
    echo json_encode(['status' => 'error', 'message' => 'Email and password are required']);
    exit;
}

try {
    // Fetch user from database
    $stmt = $conn->prepare("SELECT * FROM user WHERE email = :email AND status = 'Active' LIMIT 1");
    $stmt->bindParam(':email', $email);
    $stmt->execute();
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        http_response_code(404);
        error_log('get_users: user not found for email ' . $email);
        echo json_encode(['status' => 'error', 'message' => 'User not found']);
        exit;
    }

    // Verify password
    $passwordValid = password_verify($password, $user['password']);

    // Fallback for plain text passwords (not recommended in production)
    if (!$passwordValid && $password === $user['password']) {
        $passwordValid = true;
    }

    if (!$passwordValid) {
        http_response_code(401);
        error_log('get_users: invalid password for email ' . $email);
        echo json_encode(['status' => 'error', 'message' => 'Invalid password']);
        exit;
    }

    // Check if password reset is required
    if (isset($user['reset_required']) && $user['reset_required'] == 1) {
        unset($user['password']);
        http_response_code(200);
        echo json_encode([
            'status' => 'success',
            'user' => $user,
            'reset_required' => true,
            'message' => 'Please update your password'
        ]);
        exit;
    }

    // Remove sensitive data before returning
    unset($user['password'], $user['otp'], $user['otp_expiry'], $user['reset_token'], $user['token_expiry']);

    http_response_code(200);
    echo json_encode([
        'status' => 'success',
        'user' => $user,
        'users' => [$user]
    ]);

} catch (PDOException $e) {
    http_response_code(500);
    error_log('get_users: query error: ' . $e->getMessage());
    echo json_encode(['status' => 'error', 'message' => 'Database error']);
    exit;
}
?>