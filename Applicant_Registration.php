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
            (applicantID, fullName, email_address, date_applied, status) 
            VALUES (?, ?, ?, NOW(), 'Pending')");
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
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <style>
        :root {
            --primary-blue: #1E3A8A;
            --light-blue: #F5F8FF;
            --border-color: #D9D9D9;
            --text-dark: #333333;
            --white: #FFFFFF;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body.login-body {
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

        .top-bar-text h4 {
            font-weight: 300;
            font-size: 0.9rem;
            margin: 0;
            color: var(--white);
            opacity: 0.9;
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
            padding: 2.5rem;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
        }

        .login-input h1 {
            color: var(--primary-blue);
            text-align: center;
            margin-bottom: 1.5rem;
            font-weight: 700;
            font-size: 2rem;
        }

        .input-group {
            margin-bottom: 1.5rem;
        }

        .input-group label {
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
            transition: all 0.3s ease;
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

        .register-button {
            margin: 2rem 0 1rem;
        }

        .register-button button {
            width: 100%;
            background-color: var(--primary-blue);
            color: var(--white);
            border: none;
            padding: 0.875rem;
            border-radius: 10px;
            font-weight: 500;
            font-size: 1rem;
            transition: all 0.3s ease;
            cursor: pointer;
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 0.5rem;
            font-family: 'Poppins', sans-serif;
        }

        .register-button button:hover {
            background-color: #172B69;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(30, 58, 138, 0.3);
        }

        .error-msg {
            color: #DC2626;
            margin-top: 10px;
            padding: 0.75rem;
            background-color: #FEF2F2;
            border-radius: 8px;
            border-left: 4px solid #DC2626;
            font-size: 0.9rem;
            text-align: center;
        }

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
            background-color: var(--white);
            padding: 2rem;
            border-radius: 15px;
            text-align: center;
            max-width: 400px;
            width: 90%;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
        }

        .modal-content h2 {
            color: var(--primary-blue);
            margin-bottom: 1rem;
            font-weight: 600;
        }

        .modal-content p {
            color: var(--text-dark);
            margin-bottom: 1.5rem;
            line-height: 1.5;
        }

        .close-btn {
            background-color: var(--primary-blue);
            color: var(--white);
            padding: 0.75rem 1.5rem;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 500;
            transition: all 0.3s ease;
            font-family: 'Poppins', sans-serif;
        }

        .close-btn:hover {
            background-color: #172B69;
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
                <h1>Register</h1>

                <div class="input-group">
                    <label for="fullname">Full Name</label>
                    <div class="input-container">
                        <i class="fa-solid fa-user"></i>
                        <input type="text" name="fullname" id="fullname" placeholder="Enter your Full Name" required>
                    </div>
                </div>

                <div class="input-group">
                    <label for="email">Email</label>
                    <div class="input-container">
                        <i class="fa-solid fa-envelope"></i>
                        <input type="email" name="email" id="email" placeholder="Enter your Email" required>
                    </div>
                </div>

                <div class="register-button">
                    <button type="submit" name="register">
                        <i class="fa-solid fa-user-plus"></i> Register
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