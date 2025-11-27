<?php
require 'admin/db.connect.php';
session_start(); 

// Initialize messages
$error_msg = "";
$success_msg = "";

if (isset($_POST['change_password'])) {
    $newPass = $_POST['newPass'];
    $confirmPass = $_POST['confirmPass'];
    $currentPass = $_POST['currentPass'] ?? null;

    $token = $_GET['token'] ?? null;

    if ($newPass !== $confirmPass) {
        $error_msg = "Passwords do not match.";
    } elseif (strlen($newPass) < 8) {
        $error_msg = "Password must be at least 8 characters.";
    } else {
        if ($token) {
            // Token-based password reset
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
                
                if ($update->execute()) {
                    $success_msg = "Password changed successfully!";
                    // Redirect after 2 seconds to show the success message
                    echo "<script>
                        setTimeout(function() {
                            window.location.href = 'Login.php';
                        }, 2000);
                    </script>";
                } else {
                    $error_msg = "Error updating password. Please try again.";
                }
            } else {
                $error_msg = "Invalid or expired reset link.";
            }
        } else {
            // Regular password change (user is logged in)
            $email = $_SESSION['email'] ?? null;

            if (!$email) {
                $error_msg = "User not logged in.";
                echo "<script>
                    setTimeout(function() {
                        window.location.href = 'Login.php';
                    }, 2000);
                </script>";
            } else {
                $check = $conn->prepare("SELECT password FROM user WHERE email=?");
                $check->bind_param("s", $email);
                $check->execute();
                $result = $check->get_result();
                $user = $result->fetch_assoc();

                if (!password_verify($currentPass, $user['password'])) {
                    $error_msg = "Current password is incorrect.";
                } else {
                    $hashed = password_hash($newPass, PASSWORD_DEFAULT);
                    $update = $conn->prepare("UPDATE user SET password=? WHERE email=?");
                    $update->bind_param("ss", $hashed, $email);
                    
                    if ($update->execute()) {
                        $success_msg = "Password changed successfully!";
                        // Redirect after 2 seconds to show the success message
                        echo "<script>
                            setTimeout(function() {
                                window.location.href = 'Login.php';
                            }, 2000);
                        </script>";
                    } else {
                        $error_msg = "Error updating password. Please try again.";
                    }
                }
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

  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/7.0.1/css/all.min.css">

  <style>
    :root {
      --primary-blue: #1E3A8A;
      --light-blue: #F5F8FF;
      --border-color: #D9D9D9;
      --text-dark: #333333;
      --white: #FFFFFF;
      --success-green: #059669;
      --success-light: #D1FAE5;
      --success-border: #10B981;
      --error-red: #DC2626;
      --error-light: #FEF2F2;
      --error-border: #EF4444;
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
      font-size: 1.75rem;
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
      padding: 0.875rem 3rem 0.875rem 1rem;
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

    .show-pass {
      position: absolute;
      right: 1rem;
      top: 50%;
      transform: translateY(-50%);
      cursor: pointer;
      color: #555;
      font-size: 1rem;
    }

    .fa-eye {
      display: none;
    }

    .fa-eye-slash {
      display: inline;
    }

    .confirm-button {
      margin: 2rem 0 1rem;
    }

    .confirm-button button {
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

    .confirm-button button:hover {
      background-color: #172B69;
      transform: translateY(-2px);
      box-shadow: 0 4px 12px rgba(30, 58, 138, 0.3);
    }

    /* Enhanced Error Message Styling */
    .error-msg {
      color: var(--error-red);
      margin: 1rem 0;
      padding: 1rem 1rem 1rem 3.5rem;
      background-color: var(--error-light);
      border-radius: 12px;
      border: 1px solid var(--error-border);
      font-size: 0.9rem;
      position: relative;
      box-shadow: 0 2px 8px rgba(220, 38, 38, 0.1);
      animation: slideIn 0.3s ease-out;
    }

    .error-msg::before {
      content: '\f06a';
      font-family: 'Font Awesome 6 Free';
      font-weight: 900;
      position: absolute;
      left: 1rem;
      top: 50%;
      transform: translateY(-50%);
      font-size: 1.2rem;
      color: var(--error-red);
    }

    /* Enhanced Success Message Styling */
    .success-msg {
      color: var(--success-green);
      margin: 1rem 0;
      padding: 1rem 1rem 1rem 3.5rem;
      background-color: var(--success-light);
      border-radius: 12px;
      border: 1px solid var(--success-border);
      font-size: 0.9rem;
      position: relative;
      box-shadow: 0 2px 8px rgba(5, 150, 105, 0.1);
      animation: slideIn 0.3s ease-out;
    }

    .success-msg::before {
      content: '\f058';
      font-family: 'Font Awesome 6 Free';
      font-weight: 900;
      position: absolute;
      left: 1rem;
      top: 50%;
      transform: translateY(-50%);
      font-size: 1.2rem;
      color: var(--success-green);
      animation: checkmark 0.5s ease-in-out;
    }

    /* Slide in animation for messages */
    @keyframes slideIn {
      from {
        opacity: 0;
        transform: translateY(-10px);
      }
      to {
        opacity: 1;
        transform: translateY(0);
      }
    }

    /* Checkmark animation */
    @keyframes checkmark {
      0% {
        transform: translateY(-50%) scale(0);
        opacity: 0;
      }
      50% {
        transform: translateY(-50%) scale(1.2);
      }
      100% {
        transform: translateY(-50%) scale(1);
        opacity: 1;
      }
    }

    /* Success message with progress bar */
    .success-msg.with-progress {
      overflow: hidden;
    }

    .success-msg.with-progress::after {
      content: '';
      position: absolute;
      bottom: 0;
      left: 0;
      height: 3px;
      background: linear-gradient(90deg, var(--success-green), #34D399);
      animation: progress 2s linear;
    }

    @keyframes progress {
      from {
        width: 100%;
      }
      to {
        width: 0%;
      }
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

      .success-msg,
      .error-msg {
        padding: 0.875rem 0.875rem 0.875rem 3rem;
        font-size: 0.85rem;
      }

      .success-msg::before,
      .error-msg::before {
        left: 0.875rem;
        font-size: 1.1rem;
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
      </div>
    </div>
  </header>

  <main class="main-content">
    <section class="login-section">
      <form class="login-input" method="POST">
        <h1>Change Password</h1>

        <!-- Current Password -->
        <div class="input-group">
          <label for="currentPass">Current Password</label>
          <div class="input-container">
            <input type="password" id="currentPass" name="currentPass" placeholder="Enter Current Password" required>
            <i class="fa-regular fa-eye show-pass" onclick="togglePassword('currentPass', this)"></i>
            <i class="fa-regular fa-eye-slash show-pass" onclick="togglePassword('currentPass', this)"></i>
          </div>
        </div>

        <!-- New Password -->
        <div class="input-group">
          <label for="newPass">New Password</label>
          <div class="input-container">
            <input type="password" id="newPass" name="newPass" placeholder="Enter New Password" required>
            <i class="fa-regular fa-eye show-pass" onclick="togglePassword('newPass', this)"></i>
            <i class="fa-regular fa-eye-slash show-pass" onclick="togglePassword('newPass', this)"></i>
          </div>
        </div>

        <!-- Confirm Password -->
        <div class="input-group">
          <label for="confirmPass">Confirm Password</label>
          <div class="input-container">
            <input type="password" id="confirmPass" name="confirmPass" placeholder="Confirm New Password" required>
            <i class="fa-regular fa-eye show-pass" onclick="togglePassword('confirmPass', this)"></i>
            <i class="fa-regular fa-eye-slash show-pass" onclick="togglePassword('confirmPass', this)"></i>
          </div>
        </div>

        <!-- Confirm Button -->
        <div class="confirm-button">
          <button type="submit" name="change_password">
            <i class="fa-solid fa-key"></i> Confirm
          </button>
        </div>

        <?php if (!empty($error_msg)): ?>
        <div class="error-msg"><?php echo $error_msg; ?></div>
        <?php endif; ?>
        
        <?php if (!empty($success_msg)): ?>
        <div class="success-msg with-progress"><?php echo $success_msg; ?></div>
        <?php endif; ?>
      </form>
    </section>
  </main>

  <!--Js for show/hide password -->
  <script>
    function togglePassword(fieldId, icon) {
      const input = document.getElementById(fieldId);
      const container = icon.parentElement;
      const eye = container.querySelector('.fa-eye');
      const eyeSlash = container.querySelector('.fa-eye-slash');

      const isPassword = input.type === "password";
      input.type = isPassword ? "text" : "password";

      if (isPassword) {
        eyeSlash.style.display = "none";
        eye.style.display = "inline";
      } else {
        eye.style.display = "none";
        eyeSlash.style.display = "inline";
      }
    }
  </script>

</body>
</html>