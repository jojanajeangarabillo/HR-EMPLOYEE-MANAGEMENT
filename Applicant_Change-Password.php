<?php
session_start();
include 'admin/db.connect.php';
$error_msg = "";
$success_msg = "";

if (isset($_POST['change_password'])) {
    $current_pass = $_POST['currentPass'];
    $new_pass = $_POST['newPass'];
    $confirm_pass = $_POST['confirmPass'];

    // Basic validation
    if (empty($current_pass) || empty($new_pass) || empty($confirm_pass)) {
        $error_msg = "All fields are required.";
    } elseif ($new_pass !== $confirm_pass) {
        $error_msg = "New password and confirm password do not match.";
    } elseif (strlen($new_pass) < 8) {
        $error_msg = "New password must be at least 8 characters long.";
    } else {

        if (isset($_GET['token'])) {
            $token = $_GET['token'];
            $sql = "SELECT emailusername, token_expiry FROM login_table WHERE reset_token = ? AND token_expiry > NOW()";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("s", $token);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($result->num_rows > 0) {
                $row = $result->fetch_assoc();
                $alumni_id = $row['emailusername'];
                // No need to verify current password for token-based change
                $hashed_new_pass = md5($new_pass); // Using MD5 to match existing hashing
                $update_sql = "UPDATE login_table SET password = ?, reset_token = NULL, token_expiry = NULL WHERE emailusername = ?";
                $update_stmt = $conn->prepare($update_sql);
                $update_stmt->bind_param("ss", $hashed_new_pass, $alumni_id);
                if ($update_stmt->execute()) {
                    $success_msg = "Password changed successfully. You can now log in.";

                    header("Location: Applicant_Login.php"); // Adjust to your login page
                    exit;
                } else {
                    $error_msg = "Error updating password. Please try again.";
                }
            } else {
                $error_msg = "Invalid or expired token.";
            }
        } else {

        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Applicant Change Password</title>
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
                <h4>Applicant</h4>
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