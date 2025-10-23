<?php
session_start();
include 'admin/db.connect.php';

$error_msg = "";
$success_msg = "";

if (isset($_POST['login'])) {
    $email = $_POST['email'];
    $password = md5($_POST['password']);
    $sql = "SELECT * FROM login_table WHERE emailusername = ? AND password = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ss", $email, $password);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        /*$_SESSION['login_id'] = $row['id'];
        $_SESSION['login_type'] = $row['type']; // or whatever your column is */
        $success_msg = "Login successful!";
    } else {
        $error_msg = "Invalid email or password.";
    }
}


?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login</title>
    <link rel="stylesheet" href="stylesheet.css">
    <!--For icons-->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/7.0.1/css/all.min.css"
        integrity="sha512-2SwdPD6INVrV/lHTZbO2nodKhrnDdJK9/kg2XD1r9uGqPo1cUbujc+IYdlYdEErWNu69gVcYgdxlmVmzTWnetw=="
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
    </style>
</head>

<body class="login-body">
    <!--Topbar-->
    <nav class="top-bar">
        <div class="logo-header">
            <img src="Images/hospitallogo.png" alt="Happy Picture">
            <div class="top-bar-text">
                <h1>Hospital</h1>
                <h4>Human Resources</h4>
            </div>
        </div>
    </nav>

    <!--Login Field-->
    <main class="main-content">
        <section class="login-section">

            <form method="POST" class="login-input">
                <div class="login-email">
                    <label>Email</label>
                    <i class="fa-solid fa-envelope"></i>
                    <input type="email" name="email" id="email" placeholder="Enter your Email" required>
                </div>
                <div class="login-password">
                    <label>Password</label>
                    <i class="fa-solid fa-lock"></i>
                    <input type="password" name="password" id="password" placeholder="Enter your Password" required>
                </div>
                <div class="show-password">
                    <input type="checkbox" name="Show-password" id="show"> Show password
                </div>
                <div class="login-button">
                    <button type="submit" name="login"><i class="fa-solid fa-right-from-bracket"></i>Sign In</button>
                </div>
                <div class="forgot-password">
                    <a href="Admin-Forgot-Password.html" class="forgot-password-link">Forgot password?</a>
                </div>
            </form>

        </section>
    </main>

    <!-- Success Modal -->
    <div id="successModal" class="modal">
        <div class="modal-content">
            <h2>Login Successful!</h2>
            <p>You have logged in successfully.</p>
            <button class="close-btn" onclick="closeModalAndRedirect()">OK</button>
        </div>
    </div>

    <!-- Error Modal -->
    <div id="errorModal" class="modal">
        <div class="modal-content">
            <h2>Login Failed</h2>
            <p><?php echo $error_msg; ?></p>
            <button class="close-btn" onclick="closeModal()">OK</button>
        </div>
    </div>

    <script>
        function showModal(modalId) {
            document.getElementById(modalId).style.display = "flex";
        }

        function closeModal() {
            document.getElementById("successModal").style.display = "none";
            document.getElementById("errorModal").style.display = "none";
        }

        function closeModalAndRedirect() {
            closeModal();

            <?php if (!empty($success_msg)): ?>
                <?php if (isset($_SESSION['require_password_change'])): ?>
                    window.location.href = "Applicant_Change-Password.php";
                <?php elseif ($_SESSION['login_type'] == 1 || $_SESSION['login_type'] == 2): ?>
                    window.location.href = "Admin-Dashboard.php";
                <?php else: ?>
                    window.location.href = "#";
                <?php endif; ?>
            <?php endif; ?>
        }


        window.onload = function () {
            <?php if (!empty($success_msg)): ?>
                showModal("successModal");
            <?php elseif (!empty($error_msg)): ?>
                showModal("errorModal");
            <?php endif; ?>
        };

        // Show/hide password functionality
        document.getElementById('show').addEventListener('change', function () {
            var passwordField = document.getElementById('password');
            passwordField.type = this.checked ? 'text' : 'password';
        });
    </script>

</body>

</html>