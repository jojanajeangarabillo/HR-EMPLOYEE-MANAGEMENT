<?php
require 'admin/db.connect.php';
session_start(); // if you want to use logged-in session email later

if (isset($_POST['change_password'])) {
    $newPass = $_POST['newPass'];
    $confirmPass = $_POST['confirmPass'];
    $currentPass = $_POST['currentPass'] ?? null;

    // Either from email reset link or from logged-in user
    $token = $_GET['token'] ?? null;

    if ($newPass !== $confirmPass) {
        echo "<script>alert('Passwords do not match.');</script>";
    } elseif (strlen($newPass) < 8) {
        echo "<script>alert('Password must be at least 8 characters.');</script>";
    } else {
        // Check token method (reset via email)
        if ($token) {
            $stmt = $conn->prepare("SELECT email FROM user WHERE reset_token=? AND token_expiry>NOW()");
            $stmt->bind_param("s", $token);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($result->num_rows === 1) {
                $row = $result->fetch_assoc();
                $email = $row['email'];
                $hashed = password_hash($newPass, PASSWORD_DEFAULT);

                $update = $conn->prepare("UPDATE user SET password=?, reset_token=NULL, token_expiry=NULL WHERE email=?");
                $update->bind_param("ss", $hashed, $email);
                $update->execute();

                echo "<script>alert('Password changed successfully!');window.location='Login.php';</script>";
            } else {
                echo "<script>alert('Invalid or expired reset link.');</script>";
            }
        } 
        // Logged-in user changing password normally
        else {
            // Assuming logged-in user email stored in session
            $email = $_SESSION['email'] ?? null;

            if (!$email) {
                echo "<script>alert('User not logged in.');window.location='Login.php';</script>";
                exit;
            }

            // Verify current password
            $check = $conn->prepare("SELECT password FROM user WHERE email=?");
            $check->bind_param("s", $email);
            $check->execute();
            $result = $check->get_result();
            $user = $result->fetch_assoc();

            if (!password_verify($currentPass, $user['password'])) {
                echo "<script>alert('Current password is incorrect.');</script>";
            } else {
                $hashed = password_hash($newPass, PASSWORD_DEFAULT);
                $update = $conn->prepare("UPDATE user SET password=? WHERE email=?");
                $update->bind_param("ss", $hashed, $email);
                $update->execute();
                echo "<script>alert('Password changed successfully!');window.location='Login.php';</script>";
            }
        }
    }
}
?>



<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Change Password</title>
    <link rel="stylesheet" href="applicant.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css"
        integrity="sha512-papNMv5z+YdUj4m6rKcxQZZNhpCJ3+VzYDA6kYskk5wDZqB8bJz5K5C9mEeD2iHZG5tLx4yPcXy4A4p4rA7Rqw=="
        crossorigin="anonymous" referrerpolicy="no-referrer" />
    <style>
        .error-msg {
            color: red;
            margin-top: 10px;
        }

        .success-msg {
            color: green;
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
            </div>
        </div>
    </header>

    <!-- Main Content -->
    <main class="main-content">
        <section class="login-section">
            <form class="login-input" method="POST">
                <h1 style="font-size: 40px;"><b>Change Password</b></h1>

                <!-- Current Password -->
                <div class="input-group">
                    <label for="currentPass">Current Password</label>
                    <div class="input-container">
                        <i class="fa-solid fa-lock"></i>
                        <input type="password" id="currentPass" name="currentPass" placeholder="Enter Current Password"
                            required>
                    </div>
                </div>

                <!-- New Password -->
                <div class="input-group">
                    <label for="newPass">New Password</label>
                    <div class="input-container">
                        <i class="fa-solid fa-lock"></i>
                        <input type="password" id="newPass" name="newPass" placeholder="Enter New Password" required>
                    </div>
                </div>

                <!-- Confirm Password -->
                <div class="input-group">
                    <label for="confirmPass">Confirm Password</label>
                    <div class="input-container">
                        <i class="fa-solid fa-lock"></i>
                        <input type="password" id="confirmPass" name="confirmPass" placeholder="Confirm New Password"
                            required>
                    </div>
                </div>

                <!-- Confirm Button -->
                <div class="confirm-button">
                    <button type="submit" name="change_password">
                        <i class="fa-solid fa-right-to-bracket"></i> Confirm
                    </button>
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