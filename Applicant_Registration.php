<?php
session_start();
require 'admin/db.connect.php';
require 'PHPMailer-master/src/Exception.php';
require 'PHPMailer-master/src/PHPMailer.php';
require 'PHPMailer-master/src/SMTP.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\SMTP;

$config = require 'mailer-config.php'; // Assuming this is 'mailer_config.php' as in the code

$error_msg = "";
$success_msg = "";

if (isset($_POST['register'])) {
    $email = $conn->real_escape_string($_POST['email']);

    $check_sql = "SELECT emailusername FROM login_table WHERE emailusername = ?";
    $check_stmt = $conn->prepare($check_sql);
    $check_stmt->bind_param("s", $email);
    $check_stmt->execute();
    $check_result = $check_stmt->get_result();

    if ($check_result->num_rows > 0) {
        $error_msg = "An account with this email already exists.";
    } else {
        $password = bin2hex(random_bytes(8));
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);

        $token = bin2hex(random_bytes(32));
        $expiry = date('Y-m-d H:i:s', strtotime('+24 hours'));

        $insert_sql = "INSERT INTO login_table (emailusername, password, reset_token, token_expiry) VALUES (?, ?, ?, ?)";
        $insert_stmt = $conn->prepare($insert_sql);
        $insert_stmt->bind_param("ssss", $email, $hashed_password, $token, $expiry);

        if ($insert_stmt->execute()) {
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

                $change_link = "localhost/HR-EMPLOYEE-MANAGEMENT/Applicant_Change-Password.php?token=$token"; // Adjust to your change password page

                $mail->isHTML(true);
                $mail->Subject = 'Welcome to the Employee Management System - Your Account Details';
                $mail->Body = "
                    <h2>Welcome to Employee Management System</h2>
                    <p>Dear User,</p>
                    <p>Your account has been created successfully. Here are your login details:</p>
                    <p><strong>Email:</strong> $email</p>
                    <p><strong>Temporary Password:</strong> $password</p>
                    <p>For security reasons, please change your password by clicking the link below:</p>
                    <p><a href=\"$change_link\">Change Your Password</a></p>
                    <p>This link will expire in 24 hours.</p>
                    <p>Thank you,<br>PLP Alumni Portal Team</p>
                ";

                $mail->send();
                $success_msg = "Registration successful! Check your email for login details.";
            } catch (Exception $e) {
                $error_msg = "Error sending email: " . $mail->ErrorInfo;
                // Optionally, delete the inserted user if email fails
                $delete_sql = "DELETE FROM login_table WHERE email = ?";
                $delete_stmt = $conn->prepare($delete_sql);
                $delete_stmt->bind_param("s", $email);
                $delete_stmt->execute();
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
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css"
        integrity="sha512-papNMv5z+YdUj4m6rKcxQZZNhpCJ3+VzYDA6kYskk5wDZqB8bJz5K5C9mEeD2iHZG5tLx4yPcXy4A4p4rA7Rqw=="
        crossorigin="anonymous" referrerpolicy="no-referrer" />
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

        .modal-content h2 {
            color: #333;
        }

        .close-btn {
            background-color: #007bff;
            color: #fff;
            padding: 10px 20px;
            border: none;
            margin-top: 15px;
            border-radius: 5px;
            cursor: pointer;
        }

        .error-msg {
            color: red;
            margin-top: 10px;
        }
    </style>
</head>

<body class="login-body">
    <!-- Topbar -->
    <header class="top-bar">
        <div class="logo-header">
            <img src="Images/hospitallogo.png" alt="Hospital Logo">
            <div class="top-bar-text">
                <h1>H O S P I T A L</h1>
                <h4>Applicant</h4>
            </div>
        </div>
    </header>

    <!-- Registration Section -->
    <main class="main-content">
        <section class="login-section">
            <form class="login-input" method="POST">
                <div class="login-email">
                    <h1 style="font-size: 50px;"><b>Register</b></h1>
                    <label for="email">Email</label>
                    <div class="input-container">
                        <i class="fa-solid fa-envelope"></i>
                        <input type="email" name="email" id="email" placeholder="Enter your Email" required>
                    </div>
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

    <!-- Modal -->
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

        // Show modal if registration was successful
        <?php if (!empty($success_msg)): ?>
            showModal();
        <?php endif; ?>
    </script>

</body>

</html>