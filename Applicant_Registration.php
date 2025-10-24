<?php
session_start();
require 'admin/db.connect.php';
require 'PHPMailer-master/src/Exception.php';
require 'PHPMailer-master/src/PHPMailer.php';
require 'PHPMailer-master/src/SMTP.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

$config = require 'mailer-config.php'; 

$error_msg = "";
$success_msg = "";

if (isset($_POST['register'])) {
    $fullname = trim($_POST['fullname']);
    $email = trim($_POST['email']);

    // Check if email already exists
    $check = $conn->prepare("SELECT email FROM user WHERE email = ?");
    $check->bind_param("s", $email);
    $check->execute();
    $check_result = $check->get_result();

    if ($check_result->num_rows > 0) {
        $error_msg = "Email already exists!";
    } else {
        // Auto-generate Applicant ID (HOS-001 format)
        $result = $conn->query("SELECT applicantID FROM applicant ORDER BY applicantID DESC LIMIT 1");
        if ($result->num_rows > 0) {
            $last = $result->fetch_assoc();
            $lastID = intval(substr($last['applicantID'], 4)) + 1;
            $newID = 'HOS-' . str_pad($lastID, 3, '0', STR_PAD_LEFT);
        } else {
            $newID = 'HOS-001';
        }

        // Generate temporary password, token, and expiry
        $tempPass = bin2hex(random_bytes(4)); // 8-char temp password
        $hashedPass = password_hash($tempPass, PASSWORD_DEFAULT);
        $token = bin2hex(random_bytes(32));
        $expiry = date('Y-m-d H:i:s', strtotime('+24 hours'));

        // Insert into user table
        $stmt = $conn->prepare("INSERT INTO user 
            (applicant_employee_id, email, password, role, fullname, status, reset_token, token_expiry) 
            VALUES (?, ?, ?, 'Applicant', ?, 'Pending', ?, ?)");
        $stmt->bind_param("ssssss", $newID, $email, $hashedPass, $fullname, $token, $expiry);

        if ($stmt->execute()) {
            // Insert into applicant table
            $stmt2 = $conn->prepare("INSERT INTO applicant 
                (applicantID, fullName, email_address, date_applied) 
                VALUES (?, ?, ?, NOW())");
            $stmt2->bind_param("sss", $newID, $fullname, $email);
            $stmt2->execute();

            // Send email
            try {
                $mail = new PHPMailer(true);
                $mail->isSMTP();
                $mail->Host = $config['host'];
                $mail->SMTPAuth = true;
                $mail->Username = $config['username'];
                $mail->Password = $config['password'];
                $mail->SMTPSecure = $config['encryption'];
                $mail->Port = $config['port'];

                $mail->setFrom($config['from_email'], $config['from_name']);
                $mail->addAddress($email);

                $link = "http://localhost/HR-EMPLOYEE-MANAGEMENT/Change-Password.php?token=$token";

                $mail->isHTML(true);
                $mail->Subject = "Welcome to the Employee Management System";
                $mail->Body = "
                    <h3>Welcome, $fullname!</h3>
                    <p>Your temporary password is: <b>$tempPass</b></p>
                    <p>Please change your password within 24 hours using this link:</p>
                    <a href='$link'>$link</a>
                ";

                $mail->send();
                $success_msg = "Registration successful! Check your email for password setup.";
            } catch (Exception $e) {
                $error_msg = "Account created, but email failed to send. Error: {$mail->ErrorInfo}";
            }
        } else {
            $error_msg = "Error creating account. Please try again.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Applicant Registration</title>
    <link rel="stylesheet" href="applicant.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <style>
        .modal {
            display: none;
            position: fixed;
            z-index: 100;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.6);
            justify-content: center;
            align-items: center;
        }
        .modal-content {
            background-color: #fff;
            padding: 30px;
            border-radius: 10px;
            text-align: center;
            max-width: 400px;
        }
        .modal-content h2 { color: #333; }
        .close-btn {
            background-color: #007bff;
            color: #fff;
            padding: 10px 20px;
            border: none;
            margin-top: 15px;
            border-radius: 5px;
            cursor: pointer;
        }
        .error-msg { color: red; margin-top: 10px; }
    </style>
</head>
<body class="login-body">
    <header class="top-bar">
        <div class="logo-header">
            <img src="Images/hospitallogo.png" alt="Hospital Logo">
            <div class="top-bar-text">
                <h1>H O S P I T A L</h1>
                <h4>Applicant</h4>
            </div>
        </div>
    </header>

    <main class="main-content">
        <section class="login-section">
            <form class="login-input" method="POST">
                <h1 style="font-size: 50px;"><b>Register</b></h1>

                <label for="fullname">Full Name</label>
                <div class="input-container">
                    <i class="fa-solid fa-user"></i>
                    <input type="fullname" name="fullname" id="fullname" placeholder="Enter your Full Name" required>
                </div>

                <label for="email">Email</label>
                <div class="input-container">
                    <i class="fa-solid fa-envelope"></i>
                    <input type="email" name="email" id="email" placeholder="Enter your Email" required>
                </div>

                <div class="register-button">
                    <button type="submit" name="register">
                        <i class="fa-solid fa-right-to-bracket"></i> Register
                    </button>
                </div>

                <?php if (!empty($error_msg)): ?>
                    <div class="error-msg"><?php echo $error_msg; ?></div>
                <?php endif; ?>
            </form>
        </section>
    </main>

    <div id="successModal" class="modal">
        <div class="modal-content">
            <h2>Check your email!</h2>
            <p>We've sent your login password and a link to change it.</p>
            <button class="close-btn" onclick="closeModal()">OK</button>
        </div>
    </div>

    <script>
    function showModal() {
    document.getElementById("successModal").style.display = "flex";
    }
    function closeModal() {
    document.getElementById("successModal").style.display = "none";
    }

    // Only show modal if registration actually succeeded and POST request was made
    <?php if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['register']) && !empty($success_msg)): ?>
    showModal();
    <?php endif; ?>
    </script>


</body>
</html>
