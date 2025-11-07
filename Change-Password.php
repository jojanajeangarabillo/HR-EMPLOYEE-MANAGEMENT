<?php
require 'admin/db.connect.php';
session_start(); 

if (isset($_POST['change_password'])) {
    $newPass = $_POST['newPass'];
    $confirmPass = $_POST['confirmPass'];
    $currentPass = $_POST['currentPass'] ?? null;

    
    $token = $_GET['token'] ?? null;

    if ($newPass !== $confirmPass) {
        echo "<script>alert('Passwords do not match.');</script>";
    } elseif (strlen($newPass) < 8) {
        echo "<script>alert('Password must be at least 8 characters.');</script>";
    } else {
       
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
        
        else {
            
            $email = $_SESSION['email'] ?? null;

            if (!$email) {
                echo "<script>alert('User not logged in.');window.location='Login.php';</script>";
                exit;
            }

            
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
  
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/7.0.1/css/all.min.css">

  <style>
    .error-msg {
      color: red;
      margin-top: 10px;
    }

    .success-msg {
      color: green;
      margin-top: 10px;
    }

    /*  Eye icon styling */
    .input-container {
      position: relative;
    }

    .show-pass {
      position: absolute;
      right: 10px;
      top: 50%;
      transform: translateY(-50%);
      cursor: pointer;
      color: #555;
    }

   
    .fa-eye {
      display: none;
    }

    .fa-eye-slash {
      display: inline;
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
        <h1 style="font-size: 40px;"><b>Change Password</b></h1>

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
