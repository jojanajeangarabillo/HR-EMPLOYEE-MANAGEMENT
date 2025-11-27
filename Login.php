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
               
                "applicant" => "Applicant_Dashboard.php",

                // Employee sub-roles
                "employee" => [

                    "HR Director" => "Manager_Dashboard.php",
                    "HR Manager" => "Manager_Dashboard.php",
                    "Recruitment Manager" => "Manager_Dashboard.php",
                    "HR Officer" => "Manager_Dashboard.php",
                    "HR Assistant" => "Manager_Dashboard.php",
                    "Training and Development Coordinator" => "Manager_Dashboard.php",
                    "Human Resource (HR) Admin" => "Admin_Dashboard.php",

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
    <title>Login - Hospital Portal</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
   
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/7.0.1/css/all.min.css"
        integrity="sha512-2SwdPD6INVrV/lHTZbO2nodKhrnDdJK9/kg2XD1r9uGqPo1cUbujc+IYdlYdEErWNu69gVcYgdxlmVmzTWnetw=="
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
        }

        body {
            font-family: 'Poppins', sans-serif;
            background-color: var(--light-blue);
            margin: 0;
            padding: 0;
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

        .top-bar-text h2 {
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
            max-width: 450px;
            border-radius: 20px;
            padding: 2.5rem;
            box-shadow: var(--shadow);
            transition: var(--transition);
        }

        .login-section:hover {
            box-shadow: 0 6px 25px rgba(0, 0, 0, 0.12);
        }

        .login-header {
            text-align: center;
            margin-bottom: 2rem;
        }

        .login-header h2 {
            font-weight: 600;
            color: var(--primary-blue);
            margin-bottom: 0.5rem;
            font-size: 1.75rem;
        }

        .login-header p {
            color: var(--text-light);
            font-size: 0.9rem;
            margin: 0;
        }

        .login-input {
            width: 100%;
        }

        .form-group {
            margin-bottom: 1.5rem;
            position: relative;
        }

        .form-label {
            display: block;
            font-weight: 500;
            margin-bottom: 0.5rem;
            color: var(--text-dark);
            font-size: 0.9rem;
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
        }

        .input-container input:focus {
            outline: none;
            border-color: var(--primary-blue);
            box-shadow: 0 0 0 3px rgba(30, 58, 138, 0.1);
        }

        .input-icon {
            position: absolute;
            left: 1rem;
            top: 50%;
            transform: translateY(-50%);
            color: var(--primary-blue);
            font-size: 1rem;
        }

        .show-pass {
            position: absolute;
            right: 1rem;
            top: 50%;
            transform: translateY(-50%);
            font-size: 1rem;
            cursor: pointer;
            color: var(--text-light);
            transition: var(--transition);
        }

        .show-pass:hover {
            color: var(--primary-blue);
        }

        .login-button {
            margin: 2rem 0 1.5rem;
        }

        .login-btn {
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
        }

        .login-btn:hover {
            background-color: #172B69;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(30, 58, 138, 0.3);
        }

        .forgot-password {
            text-align: center;
        }

        .forgot-password-link {
            color: var(--primary-blue);
            text-decoration: none;
            font-size: 0.9rem;
            transition: var(--transition);
        }

        .forgot-password-link:hover {
            text-decoration: underline;
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
            color: var(--text-dark);
            margin-bottom: 1rem;
            font-weight: 600;
        }

        .modal-content p {
            color: var(--text-light);
            margin-bottom: 1.5rem;
        }

        .close-btn {
            background-color: var(--primary-blue);
            color: var(--white);
            padding: 0.75rem 1.5rem;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 500;
            transition: var(--transition);
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
            
            .top-bar-text h2 {
                font-size: 0.8rem;
            }
        }
    </style>
</head>

<body class="login-body">

    <nav class="top-bar">
        <div class="logo-header">
            <img src="Images/hospitallogo.png" alt="Hospital Logo">
            <div class="top-bar-text">
                <h1>H O S P I T A L</h1>
                <h2>This is where your journey starts!</h2>
            </div>
        </div>
    </nav>

    <!--Login Field-->
    <main class="main-content">
        <section class="login-section">
            <div class="login-header">
                <h2>Welcome Back</h2>
                <p>Sign in to access your account</p>
            </div>

            <form method="POST" class="login-input">
                <div class="form-group">
                    <label class="form-label">Email</label>
                    <div class="input-container">
                        <i class="fa-solid fa-envelope input-icon"></i>
                        <input type="email" name="email" id="email" placeholder="Enter your email" required>
                    </div>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Password</label>
                    <div class="input-container">
                        <i class="fa-solid fa-lock input-icon"></i>
                        <input type="password" name="password" id="password" placeholder="Enter your password" required>
                        <i class="fa-solid fa-eye-slash show-pass" id="togglePassword"></i>
                    </div>
                </div>

                <div class="login-button">
                    <button type="submit" name="login" class="login-btn">
                        <i class="fa-solid fa-right-from-bracket"></i>Sign In
                    </button>
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

        const toggleIcon = document.getElementById('togglePassword');
        const passwordInput = document.getElementById('password');

        toggleIcon.addEventListener('click', () => {
            const type = passwordInput.type === 'password' ? 'text' : 'password';
            passwordInput.type = type;

            // Toggle eye icon
            toggleIcon.classList.toggle('fa-eye');
            toggleIcon.classList.toggle('fa-eye-slash');
        });
    </script>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>