<?php
ob_start();
session_start();
require 'admin/db.connect.php';

if (isset($_POST['login'])) {
    $email = trim($_POST['email']);
    $password = trim($_POST['password']);

    $stmt = $conn->prepare("SELECT * FROM user WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 1) {
        $row = $result->fetch_assoc();
        $hashedPassword = $row['password'];

        if (password_verify($password, $hashedPassword)) {


            $_SESSION['email'] = $row['email'];
            $_SESSION['role'] = $row['role'];
            $_SESSION['fullname'] = $row['fullname'];
            $_SESSION['sub_role'] = $row['sub_role'];
            $_SESSION['applicant_employee_id'] = $row['applicant_employee_id'];
            $_SESSION['applicantID'] = $row['applicant_employee_id'];


            $role = strtolower(trim($row['role']));
            $sub_role = trim($row['sub_role'] ?? '');

            /* REDIRECTION MAP */
            $redirects = [

                // Main roles
                "admin" => "Admin_Dashboard.php",
                "applicant" => "Applicant_Dashboard.php",

                // Employee sub-roles
                "employee" => [

                    "HR Director" => "Manager_Dashboard.php",
                    "HR Manager" => "Manager_Dashboard.php",
                    "Recruitment Manager" => "Manager_Dashboard.php",
                    "HR Officer" => "Manager_Dashboard.php",
                    "HR Assistant" => "Manager_Dashboard.php",
                    "Training and Development Coordinator" => "Manager_Dashboard.php",

                    // fallback if sub_role does not match any above
                    "default" => "Employee_Dashboard.php"
                ]
            ];

            /* REDIRECTION LOGIC */
            if (isset($redirects[$role])) {

                // EMPLOYEE ROLE → requires sub_role check
                if ($role === "employee") {

                    if (isset($redirects["employee"][$sub_role])) {
                        header("Location: " . $redirects["employee"][$sub_role]);
                        exit;
                    } else {
                        // Unknown employee sub-role → go to general employee dashboard
                        header("Location: " . $redirects["employee"]["default"]);
                        exit;
                    }

                } else {
                    // Normal roles (admin, applicant)
                    header("Location: " . $redirects[$role]);
                    exit;
                }

            } else {
                echo "<script>alert('Unknown role for this account.');</script>";
            }

            $success_msg = "Login successful!";
        } else {
            $error_msg = "Incorrect password.";
        }
    } else {
        $error_msg = "No account found with that email.";
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

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/7.0.1/css/all.min.css"
        integrity="sha512-2SwdPD6INVrV/lHTZbO2nodKhrnDdJK9/kg2XD1r9uGqPo1cUbujc+IYdlYdEErWNu69gVcYgdxlmVmzTWnetw=="
        crossorigin="anonymous" referrerpolicy="no-referrer" />
    <style>
        .login-btn:hover {
            background-color: #1E3A8A;
            cursor: pointer;
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

    <nav class="top-bar">
        <div class="logo-header">
            <img src="Images/hospitallogo.png" alt="Happy Picture">
            <div class="top-bar-text">
                <h1>H O S P I T A L</h1>
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
                    <button type="submit" name="login" class="login-btn"><i
                            class="fa-solid fa-right-from-bracket"></i>Sign In</button>
                </div>
                <div class="forgot-password">
                    <a href="Forgot-Password.php" class="forgot-password-link">Forgot password?</a>
                </div>
            </form>

        </section>
    </main>


    <div id="successModal" class="modal">
        <div class="modal-content">
            <h2>Login Successful!</h2>
            <p>You have logged in successfully.</p>
            <button class="close-btn" onclick="closeModalAndRedirect()">OK</button>
        </div>
    </div>


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