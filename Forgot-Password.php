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

if (isset($_POST['send_reset'])) {
    $email = trim($_POST['email']);

    // Check if email exists
    $check = $conn->prepare("SELECT email, fullname FROM user WHERE email = ?");
    $check->bind_param("s", $email);
    $check->execute();
    $check_result = $check->get_result();

    if ($check_result->num_rows > 0) {
        $user = $check_result->fetch_assoc();
        $fullname = $user['fullname'];
        
        // Generate temporary password and reset token
        $tempPass = bin2hex(random_bytes(4)); // 8-char temporary password
        $hashedPass = password_hash($tempPass, PASSWORD_DEFAULT);
        $token = bin2hex(random_bytes(32));
        $expiry = date('Y-m-d H:i:s', strtotime('+24 hours'));

        // Update user with temporary password and reset token
        $update = $conn->prepare("UPDATE user SET password=?, reset_token=?, token_expiry=? WHERE email=?");
        $update->bind_param("ssss", $hashedPass, $token, $expiry, $email);

        if ($update->execute()) {
            // Send email with temporary password and reset link
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

                $reset_link = "http://localhost/HR-EMPLOYEE-MANAGEMENT/Change-Password.php?token=$token";

                $mail->isHTML(true);
                $mail->Subject = "Password Reset Request - Hospital Employee Management System";
                $mail->Body = "
                    <h3>Password Reset Request</h3>
                    <p>Hello $fullname,</p>
                    <p>We received a request to reset your password. Here's your temporary password:</p>
                    <p style='font-size: 18px; font-weight: bold; color: #1E3A8A;'>Temporary Password: $tempPass</p>
                    <p>Please use the link below to set your new password. This link will expire in 24 hours.</p>
                    <a href='$reset_link' style='background-color: #1E3A8A; color: white; padding: 12px 24px; text-decoration: none; border-radius: 5px; display: inline-block;'>Set New Password</a>
                    <p>If you didn't request this reset, please ignore this email.</p>
                    <br>
                    <p>Best regards,<br>Hospital Team</p>
                ";

                $mail->send();
                $success_msg = "Password reset instructions have been sent to your email!";
            } catch (Exception $e) {
                $error_msg = "Failed to send email. Please try again later.";
            }
        } else {
            $error_msg = "Error processing your request. Please try again.";
        }
    } else {
        $error_msg = "No account found with that email address.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forgot Password - Hospital Portal</title>
   
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" 
          href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css" 
          integrity="sha512-papNMv5z+YdUj4m6rKcxQZZNhpCJ3+VzYDA6kYskk5wDZqB8bJz5K5C9mEeD2iHZG5tLx4yPcXy4A4p4rA7Rqw==" 
          crossorigin="anonymous" referrerpolicy="no-referrer" />
    <style>
        :root {
            --primary-blue: #1E3A8A;
            --light-blue: #F5F8FF;
            --border-color: #D9D9D9;
            --text-dark: #333333;
            --text-light: #6B7280;
            --white: #FFFFFF;
            --shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
            --transition: all 0.3s ease;
            --error-red: #DC2626;
            --success-green: #059669;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Poppins', sans-serif;
            background-color: var(--light-blue);
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }

        .top-bar {
            background-color: var(--primary-blue);
            padding: 1.5rem 2rem;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }

        .logo-header {
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .logo-header img {
            height: 60px;
            width: auto;
        }

        .top-bar-text h1 {
            font-weight: 700;
            font-size: 1.5rem;
            margin: 0;
            color: var(--white);
            line-height: 1.2;
        }

        .main-content {
            flex: 1;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 2rem;
        }

        .login-section {
            background-color: var(--white);
            width: 100%;
            max-width: 500px;
            border-radius: 20px;
            padding: 3rem;
            box-shadow: var(--shadow);
            transition: var(--transition);
        }

        .login-section:hover {
            box-shadow: 0 6px 25px rgba(0, 0, 0, 0.12);
        }

        .login-input h1 {
            color: var(--primary-blue);
            text-align: center;
            margin-bottom: 0.5rem;
            font-weight: 700;
            font-size: 2rem;
        }

        .instruction-text {
            text-align: center;
            color: var(--text-light);
            margin-bottom: 2rem;
            font-size: 0.95rem;
            line-height: 1.5;
        }

        .form-group {
            margin-bottom: 1.5rem;
        }

        .form-label {
            display: block;
            font-weight: 500;
            margin-bottom: 0.5rem;
            color: var(--text-dark);
            font-size: 0.95rem;
        }

        .input-container {
            position: relative;
        }

        .input-container input {
            width: 100%;
            padding: 0.875rem 1rem 0.875rem 3rem;
            border: 1px solid var(--border-color);
            border-radius: 10px;
            font-size: 0.95rem;
            transition: var(--transition);
            background-color: var(--light-blue);
            font-family: 'Poppins', sans-serif;
        }

        .input-container input:focus {
            outline: none;
            border-color: var(--primary-blue);
            box-shadow: 0 0 0 3px rgba(30, 58, 138, 0.1);
        }

        .input-container .fa-solid {
            position: absolute;
            left: 1rem;
            top: 50%;
            transform: translateY(-50%);
            color: var(--primary-blue);
            font-size: 1rem;
        }

        .login-button {
            margin: 2rem 0 1.5rem;
        }

        .login-button button {
            width: 100%;
            background-color: var(--primary-blue);
            color: var(--white);
            border: none;
            padding: 0.875rem;
            border-radius: 10px;
            font-weight: 500;
            font-size: 1rem;
            transition: var(--transition);
            cursor: pointer;
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 0.5rem;
            font-family: 'Poppins', sans-serif;
        }

        .login-button button:hover {
            background-color: #172B69;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(30, 58, 138, 0.3);
        }

        .back-link {
            text-align: center;
        }

        .back-link a {
            color: var(--primary-blue);
            text-decoration: none;
            font-weight: 500;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            transition: var(--transition);
            font-size: 0.95rem;
        }

        .back-link a:hover {
            text-decoration: underline;
            gap: 0.7rem;
        }

        .error-msg {
            color: var(--error-red);
            margin-top: 10px;
            padding: 0.75rem;
            background-color: #FEF2F2;
            border-radius: 8px;
            border-left: 4px solid var(--error-red);
            font-size: 0.9rem;
            text-align: center;
        }

        .success-msg {
            color: var(--success-green);
            margin-top: 10px;
            padding: 0.75rem;
            background-color: #F0FDF4;
            border-radius: 8px;
            border-left: 4px solid var(--success-green);
            font-size: 0.9rem;
            text-align: center;
        }

        /* Responsive adjustments */
        @media (max-width: 576px) {
            .main-content {
                padding: 1rem;
            }
            
            .login-section {
                padding: 2rem 1.5rem;
            }
            
            .top-bar {
                padding: 1rem;
            }
            
            .logo-header img {
                height: 50px;
            }
            
            .top-bar-text h1 {
                font-size: 1.25rem;
            }
            
            .login-input h1 {
                font-size: 1.75rem;
            }
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
            </div>
        </div>
    </header>

    <!-- Forgot Password Section -->
    <main class="main-content">
        <section class="login-section">
            <form class="login-input" method="POST">
                <h1>Forgot Password</h1>
                <p class="instruction-text">Enter your email address and we'll send you a temporary password and reset link.</p>

                <!-- Email Input -->
                <div class="form-group">
                    <label for="email" class="form-label">Email Address</label>
                    <div class="input-container">
                        <i class="fa-solid fa-envelope"></i>
                        <input type="email" name="email" id="email" placeholder="Enter your email address" required value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>">
                    </div>
                </div>

                <!-- Send Reset Button -->
                <div class="login-button">
                    <button type="submit" name="send_reset">
                        <i class="fa-solid fa-paper-plane"></i> Send Reset Instructions
                    </button>
                </div>

                <div class="back-link">
                    <a href="Login.php"><i class="fa-solid fa-arrow-left"></i> Back to Login</a>
                </div>

                <?php if (!empty($error_msg)): ?>
                    <div class="error-msg"><?php echo $error_msg; ?></div>
                <?php endif; ?>
                
                <?php if (!empty($success_msg)): ?>
                    <div class="success-msg"><?php echo $success_msg; ?></div>
                <?php endif; ?>
            </form>
        </section>
    </main>
</body>
</html>