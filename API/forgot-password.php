<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit(0);
}

require_once 'Config.php';


$phpmailerPath = __DIR__ . '/../PHPMailer-master/src/';
if (!file_exists($phpmailerPath . 'PHPMailer.php')) {
    http_response_code(500);
    error_log('forgot-password: PHPMailer not found at ' . $phpmailerPath);
    echo json_encode(['status' => 'error', 'message' => 'Email service not configured']);
    exit;
}

require_once $phpmailerPath . 'PHPMailer.php';
require_once $phpmailerPath . 'SMTP.php';
require_once $phpmailerPath . 'Exception.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

function getInput()
{
    $raw = file_get_contents('php://input');
    $json = json_decode($raw, true);
    if (!is_array($json)) {
        $json = [];
    }
    $email = $json['email'] ?? ($_POST['email'] ?? ($_GET['email'] ?? ''));
    $action = $json['action'] ?? ($_POST['action'] ?? ($_GET['action'] ?? ''));
    $otp = $json['otp'] ?? ($_POST['otp'] ?? ($_GET['otp'] ?? ''));
    if (empty($otp)) {
        $otp = $json['reset_token'] ?? ($_POST['reset_token'] ?? ($_GET['reset_token'] ?? ''));
    }
    $temporary_password = $json['temporary_password'] ?? ($_POST['temporary_password'] ?? '');
    $new_password = $json['new_password'] ?? ($_POST['new_password'] ?? '');
    $confirm_password = $json['confirm_password'] ?? ($_POST['confirm_password'] ?? '');
    return compact('email', 'action', 'otp', 'temporary_password', 'new_password', 'confirm_password') + ['json' => $json];
}

function buildOtpEmail($fullname, $otp)
{
    $name = htmlspecialchars($fullname);
    $code = htmlspecialchars($otp);
    return "<html><body style='font-family: Arial, sans-serif;'><div style='max-width: 600px; margin: 0 auto; padding: 20px;'><h2>Password Reset Request</h2><p>Hello {$name},</p><p>You requested to reset your password. Use the following OTP to proceed:</p><div style='background-color: #f0f0f0; padding: 15px; text-align: center; margin: 20px 0;'><h1 style='color: #333; margin: 0;'>{$code}</h1></div><p><strong>This OTP will expire in 15 minutes.</strong></p><p>If you didn't request this, please ignore this email.</p><hr><p style='color: #888; font-size: 12px;'>Employee Management System</p></div></body></html>";
}

try {
    if (!class_exists('Database')) {
        http_response_code(500);
        error_log('forgot-password: Database class missing');
        echo json_encode(['status' => 'error', 'message' => 'Database class not found']);
        exit;
    }

    $database = new Database();
    $conn = $database->connect();
} catch (Exception $e) {
    http_response_code(500);
    error_log('forgot-password: Connection failed: ' . $e->getMessage());
    echo json_encode(['status' => 'error', 'message' => 'Connection failed']);
    exit;
}

$input = getInput();
$email = $input['email'];
$action = strtolower(trim($input['action'] ?? ''));
if ($action === '') {
    $action = 'send_otp';
}
if ($action === 'verify_otp' && empty($input['otp'])) {
    error_log('forgot-password: verify_otp requested without otp; falling back to send_otp');
    $action = 'send_otp';
}
if (empty($email)) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Email is required']);
    exit;
}

try {

    $stmt = $conn->prepare("SELECT * FROM user WHERE email = :email LIMIT 1");
    $stmt->bindParam(':email', $email);
    $stmt->execute();
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        http_response_code(404);
        error_log('forgot-password: User not found for email ' . $email);
        echo json_encode(['status' => 'error', 'message' => 'User not found']);
        exit;
    }


    if ($action === 'send_otp' || empty($action)) {
        $otp = strval(random_int(100000, 999999));
        $otp_expiry = date('Y-m-d H:i:s', strtotime('+15 minutes'));


        try {
            $stmt = $conn->prepare("UPDATE user SET otp = :otp, otp_expiry = :otp_expiry WHERE email = :email");
            $stmt->bindParam(':otp', $otp, PDO::PARAM_STR);
            $stmt->bindParam(':otp_expiry', $otp_expiry, PDO::PARAM_STR);
            $stmt->bindParam(':email', $email, PDO::PARAM_STR);
            $stmt->execute();
        } catch (PDOException $e) {
            http_response_code(500);
            error_log('forgot-password: Failed to store OTP: ' . $e->getMessage());
            error_log('forgot-password: Email: ' . $email . ', OTP: ' . $otp . ', Expiry: ' . $otp_expiry);
            error_log('forgot-password: DB Error Info: ' . json_encode($conn->errorInfo()));
            echo json_encode(['status' => 'error', 'message' => 'Failed to process request', 'debug' => $e->getMessage()]);
            exit;
        }


        $mail = new PHPMailer(true);
        $mailConfig = require '../mailer-config.php';

        $mail->isSMTP();
        $mail->Host = $mailConfig['host'];
        $mail->SMTPAuth = true;
        $mail->Username = $mailConfig['username'];
        $mail->Password = $mailConfig['password'];
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = $mailConfig['port'];

        $mail->setFrom($mailConfig['from_email'], $mailConfig['from_name']);
        $mail->addAddress($email, $user['fullname']);
        $mail->isHTML(true);
        $mail->Subject = 'Password Reset OTP';
        $mail->Body = buildOtpEmail($user['fullname'], $otp);

        if (!$mail->send()) {
            http_response_code(500);
            error_log('forgot-password: Email send failed: ' . $mail->ErrorInfo);
            echo json_encode(['status' => 'error', 'message' => 'Failed to send OTP email']);
            exit;
        }

        http_response_code(200);
        echo json_encode([
            'status' => 'success',
            'message' => 'OTP sent to your email',
            'email' => $email
        ]);
        exit;
    }


    if ($action === 'verify_otp') {
        $otp = $input['otp'];

        if (empty($otp)) {
            http_response_code(400);
            echo json_encode(['status' => 'error', 'message' => 'OTP is required']);
            exit;
        }

        $currentTime = date('Y-m-d H:i:s');


        if (empty($user['otp'])) {
            http_response_code(401);
            error_log('forgot-password: No OTP found for email ' . $email);
            echo json_encode(['status' => 'error', 'message' => 'No OTP request found. Please request a new OTP']);
            exit;
        }


        if ($user['otp'] !== $otp) {
            http_response_code(401);
            error_log('forgot-password: Invalid OTP for email ' . $email);
            echo json_encode(['status' => 'error', 'message' => 'Invalid OTP']);
            exit;
        }


        if (empty($user['otp_expiry'])) {
            http_response_code(401);
            error_log('forgot-password: OTP expiry not set for email ' . $email);
            echo json_encode(['status' => 'error', 'message' => 'OTP is invalid']);
            exit;
        }

        if ($currentTime > $user['otp_expiry']) {
            http_response_code(401);
            error_log('forgot-password: OTP expired for email ' . $email);
            echo json_encode(['status' => 'error', 'message' => 'OTP has expired']);
            exit;
        }


        $tempPassword = bin2hex(random_bytes(6));
        $hashedTempPassword = password_hash($tempPassword, PASSWORD_BCRYPT);


        try {
            $stmt = $conn->prepare("UPDATE user SET password = :password, otp = NULL, otp_expiry = NULL, reset_required = 1 WHERE email = :email");
            if (!$stmt) {
                throw new PDOException('Failed to prepare statement: ' . implode(', ', $conn->errorInfo()));
            }
            $stmt->bindParam(':password', $hashedTempPassword, PDO::PARAM_STR);
            $stmt->bindParam(':email', $email, PDO::PARAM_STR);
            if (!$stmt->execute()) {
                throw new PDOException('Failed to execute statement: ' . implode(', ', $stmt->errorInfo()));
            }
        } catch (PDOException $e) {
            http_response_code(500);
            error_log('forgot-password: Failed to update password: ' . $e->getMessage());
            error_log('forgot-password: Email: ' . $email . ', Temp Password Hash Length: ' . strlen($hashedTempPassword));
            error_log('forgot-password: DB Error Info: ' . json_encode($conn->errorInfo()));
            echo json_encode(['status' => 'error', 'message' => 'Failed to process request', 'debug' => $e->getMessage()]);
            exit;
        }

        http_response_code(200);
        echo json_encode([
            'status' => 'success',
            'message' => 'OTP verified successfully',
            'temporary_password' => $tempPassword,
            'note' => 'Please use this temporary password to login and update your password'
        ]);
        exit;
    }


    if ($action === 'update_password') {
        $tempPassword = $input['temporary_password'];
        $newPassword = $input['new_password'];
        $confirmPassword = $input['confirm_password'];

        if (empty($tempPassword) || empty($newPassword) || empty($confirmPassword)) {
            http_response_code(400);
            echo json_encode(['status' => 'error', 'message' => 'All fields are required']);
            exit;
        }

        if ($newPassword !== $confirmPassword) {
            http_response_code(400);
            echo json_encode(['status' => 'error', 'message' => 'Passwords do not match']);
            exit;
        }

        if (strlen($newPassword) < 6) {
            http_response_code(400);
            echo json_encode(['status' => 'error', 'message' => 'Password must be at least 6 characters']);
            exit;
        }

        $passwordValid = password_verify($tempPassword, $user['password']);

        if (!$passwordValid) {
            http_response_code(401);
            error_log('forgot-password: Invalid temporary password for email ' . $email);
            echo json_encode(['status' => 'error', 'message' => 'Invalid temporary password']);
            exit;
        }

        $hashedNewPassword = password_hash($newPassword, PASSWORD_BCRYPT);
        try {
            $stmt = $conn->prepare("UPDATE user SET password = :password, reset_required = 0 WHERE email = :email");
            $stmt->bindParam(':password', $hashedNewPassword, PDO::PARAM_STR);
            $stmt->bindParam(':email', $email, PDO::PARAM_STR);
            $stmt->execute();
        } catch (PDOException $e) {
            http_response_code(500);
            error_log('forgot-password: Failed to update new password: ' . $e->getMessage());
            error_log('forgot-password: Email: ' . $email);
            error_log('forgot-password: DB Error Info: ' . json_encode($conn->errorInfo()));
            echo json_encode(['status' => 'error', 'message' => 'Failed to update password', 'debug' => $e->getMessage()]);
            exit;
        }

        http_response_code(200);
        echo json_encode([
            'status' => 'success',
            'message' => 'Password updated successfully. Please login with your new password.'
        ]);
        exit;
    }

    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Invalid action']);

} catch (PDOException $e) {
    http_response_code(500);
    error_log('forgot-password: Database error: ' . $e->getMessage());
    echo json_encode(['status' => 'error', 'message' => 'Database error']);
    exit;
} catch (Exception $e) {
    http_response_code(500);
    error_log('forgot-password: General error: ' . $e->getMessage());
    echo json_encode(['status' => 'error', 'message' => 'An error occurred']);
    exit;
}
?>