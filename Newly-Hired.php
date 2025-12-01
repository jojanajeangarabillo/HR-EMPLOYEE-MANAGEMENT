<?php
session_start();
require 'admin/db.connect.php';

// PHPMailer includes
require 'PHPMailer-master/src/Exception.php';
require 'PHPMailer-master/src/PHPMailer.php';
require 'PHPMailer-master/src/SMTP.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Load mailer config
$config = require 'mailer-config.php';

// Initialize counts
$employees = $applicants = 0;

// Manager name
$managername = $_SESSION['fullname'] ?? "Manager";
$employeeID = $_SESSION['applicant_employee_id'] ?? null; // Make sure empID is stored in session
if ($employeeID) {
    $stmt = $conn->prepare("SELECT profile_pic FROM employee WHERE empID = ?");
    $stmt->bind_param("s", $employeeID);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        $profile_picture = !empty($row['profile_pic'])
            ? "uploads/employees/" . $row['profile_pic']
            : "uploads/employees/default.png";
    } else {

        $profile_picture = "uploads/employees/default.png";
    }
} else {
    $profile_picture = "uploads/employees/default.png";
}



// MENUS
$menus = [
    "HR Director" => [
        "Dashboard" => "Manager_Dashboard.php",
        "Applicants" => "Manager_Applicants.php",
        "Pending Applicants" => "Manager_PendingApplicants.php",
        "Newly Hired" => "Newly-Hired.php",
        "Employees" => "Manager_Employees.php",
        "Requests" => "Manager_Request.php",
        "Shift Scheduling"  => "Manager_Scheduling.php",
        "Vacancies" => "Manager_Vacancies.php",
        "Job Post" => "Manager-JobPosting.php",
        "Calendar" => "Manager_Calendar.php",
        "Approvals" => "Manager_Approvals.php",
        "Reports" => "Manager_Reports.php",
        "Settings" => "Manager_LeaveSettings.php",
        "Logout" => "Login.php"
    ],

    "HR Manager" => [
        "Dashboard" => "Manager_Dashboard.php",
        "Applicants" => "Manager_Applicants.php",
        "Pending Applicants" => "Manager_PendingApplicants.php",
        "Newly Hired" => "Newly-Hired.php",
        "Employees" => "Manager_Employees.php",
        "Requests" => "Manager_Request.php",
        "Shift Scheduling"  => "Manager_Scheduling.php",
        "Vacancies" => "Manager_Vacancies.php",
        "Job Post" => "Manager-JobPosting.php",
        "Calendar" => "Manager_Calendar.php",
        "Approvals" => "Manager_Approvals.php",
        "Reports" => "Manager_Reports.php",
        "Settings" => "Manager_LeaveSettings.php",
        "Logout" => "Login.php"
    ],

    "Recruitment Manager" => [
        "Dashboard" => "Manager_Dashboard.php",
        "Applicants" => "Manager_Applicants.php",
        "Pending Applicants" => "Manager_PendingApplicants.php",
        "Newly Hired" => "Newly-Hired.php",
        "Vacancies" => "Manager_Vacancies.php",
        "Logout" => "Login.php"
    ],

    "HR Officer" => [
        "Dashboard" => "Manager_Dashboard.php",
        "Applicants" => "Manager_Applicants.php",
        "Pending Applicants" => "Manager_PendingApplicants.php",
        "Newly Hired" => "Newly-Hired.php",
        "Employees" => "Manager_Employees.php",
        "Logout" => "Login.php"
    ],


];

//MENUS
$role = $_SESSION['sub_role'] ?? "HR Manager";
$icons = [
    "Dashboard" => "fa-table-columns",
    "Applicants" => "fa-user",
    "Pending Applicants" => "fa-clock",
    "Newly Hired" => "fa-user-check",
    "Employees" => "fa-users",
    "Requests" => "fa-file-lines",
    "Shift Scheduling" => "fa-clock-rotate-left",
    "Vacancies" => "fa-briefcase",
    "Job Post" => "fa-bullhorn",
    "Calendar" => "fa-calendar-days",
    "Approvals" => "fa-square-check",
    "Reports" => "fa-chart-column",
    "Settings" => "fa-gear",
    "Logout" => "fa-right-from-bracket"
];
// Count employees
$employeeQuery = $conn->query("SELECT COUNT(*) AS count FROM employee");
if ($employeeQuery && $row = $employeeQuery->fetch_assoc()) {
    $employees = $row['count'];
}

// Count applicants
$applicantQuery = $conn->query("SELECT COUNT(*) AS count FROM user WHERE role='Applicant'");
if ($applicantQuery && $row = $applicantQuery->fetch_assoc()) {
    $applicants = $row['count'];
}

// Fetch newly hired applicants
$newlyHiredQuery = $conn->query("SELECT * FROM applicant WHERE status='Hired'");
$newlyHired = $newlyHiredQuery ? $newlyHiredQuery->fetch_all(MYSQLI_ASSOC) : [];

// Handle promotion to Employee
if (isset($_POST['add_employee_id'])) {

    $applicantID = $_POST['add_employee_id'];

    // Fetch applicant info
    $appStmt = $conn->prepare("SELECT * FROM applicant WHERE applicantID=? LIMIT 1");
    $appStmt->bind_param("s", $applicantID);
    $appStmt->execute();
    $appResult = $appStmt->get_result();

    if ($app = $appResult->fetch_assoc()) {

        $email = $app['email_address'];

        // BEGIN TRANSACTION (safe execution)
        $conn->begin_transaction();

        try {

            // 1. DELETE old applicant account from user table (NEW)
            $deleteUser = $conn->prepare("DELETE FROM user WHERE email=? AND role='Applicant'");
            $deleteUser->bind_param("s", $email);
            $deleteUser->execute();

            // 2. Generate unique EMP ID
            $empLastID = 'EMP-000';
            $empLastQuery = $conn->query(
                "SELECT applicant_employee_id FROM user 
                 WHERE role='Employee' AND applicant_employee_id IS NOT NULL 
                 ORDER BY applicant_employee_id DESC LIMIT 1"
            );
            if ($empLastQuery && $row = $empLastQuery->fetch_assoc()) {
                $empLastID = $row['applicant_employee_id'];
            }
            $num = intval(substr($empLastID, 4)) + 1;
            $newEmpID = 'EMP-' . str_pad($num, 3, '0', STR_PAD_LEFT);

            // 3. Generate password and reset token
            $passwordPlain = bin2hex(random_bytes(4));
            $passwordHash = password_hash($passwordPlain, PASSWORD_BCRYPT);
            $resetToken = bin2hex(random_bytes(16));
            $expiry = date('Y-m-d H:i:s', strtotime('+3 days'));

            // 4. Create NEW employee record in user table (NEW)
            $insertUser = $conn->prepare("INSERT INTO user 
                (fullname, email, role, status, applicant_employee_id, password, reset_token, token_expiry)
                VALUES (?, ?, 'Employee', 'Active', ?, ?, ?, ?)");
            $insertUser->bind_param(
                "ssssss",
                $app['fullName'],
                $email,
                $newEmpID,
                $passwordHash,
                $resetToken,
                $expiry
            );
            $insertUser->execute();

            // 5. Insert into employee table (NEW)
// Insert into employee table
            $insertEmployee = $conn->prepare("
    INSERT INTO employee (
        empID, fullname, department, position, type_name, 
        email_address, home_address, contact_number, date_of_birth, gender,
        emergency_contact, TIN_number, phil_health_number, SSS_number, 
        pagibig_number, profile_pic, hired_at
    ) VALUES (
        ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?
    )
");

            $insertEmployee->bind_param(
                "sssssssssssssssss",
                $newEmpID,
                $app['fullName'],
                $app['department'],
                $app['position_applied'],
                $app['type_name'],       // <-- just the string
                $app['email_address'],
                $app['home_address'],
                $app['contact_number'],
                $app['date_of_birth'],
                $app['gender'],
                $app['emergency_contact'],
                $app['TIN_number'],
                $app['phil_health_number'],
                $app['SSS_number'],
                $app['pagibig_number'],
                $app['profile_pic'],
                $app['hired_at']
            );

            $insertEmployee->execute();



            // 5. Archive applicant in applicant table (NEW)
            $archiveStmt = $conn->prepare("UPDATE applicant SET status='Archived' WHERE applicantID=?");
            $archiveStmt->bind_param("s", $applicantID);
            $archiveStmt->execute();

            // COMMIT changes
            $conn->commit();

            // 6. Send employment confirmation email (NEW)
            $mail = new PHPMailer(true);
            try {
                $mail->isSMTP();
                $mail->Host = $config['host'];
                $mail->SMTPAuth = true;
                $mail->Username = $config['username'];
                $mail->Password = $config['password'];
                $mail->SMTPSecure = $config['encryption'];
                $mail->Port = $config['port'];

                $mail->setFrom($config['from_email'], $config['from_name']);
                $mail->addAddress($email, $app['fullName']);

                $mail->isHTML(true);
                $mail->Subject = 'Official Employment Confirmation';

                $resetLink = "https://localhost/HR-EMPLOYEE-MANAGEMENT/Change-Password.php?token=$resetToken";

                $mail->Body = "
                    <p>Dear <strong>{$app['fullName']}</strong>,</p>
                    <p>Congratulations! You are now officially employed.</p>
                    
                    <p><b>Your Employee Credentials:</b></p>
                    <ul>
                        <li><b>Employee ID:</b> $newEmpID</li>
                        <li><b>Temporary Password:</b> $passwordPlain</li>
                    </ul>

                    <p>To secure your account, please reset your password using the link below:</p>
                    <p><a href='$resetLink'>Reset Your Password</a></p>
                    <p><b>⚠ Note:</b> This password-reset link expires in <b>3 days</b>.</p>

                    <p>Your applicant account is now closed and cannot be accessed anymore.</p>

                    <p>Welcome to the team!<br>HR Department</p>
                ";

                $mail->send();
            } catch (Exception $e) {
                error_log("Mailer Error: " . $mail->ErrorInfo);
            }

            echo "<script>alert('Employee promoted, old account deleted, email sent successfully.'); 
                  window.location='Newly-Hired.php';</script>";
            exit;

        } catch (Exception $e) {
            $conn->rollback();
            echo "<script>alert('Failed to promote employee. Transaction rolled back.');</script>";
        }
    }
}
?>


<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Newly Hired Employees</title>
    <link rel="stylesheet" href="manager-sidebar.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/7.0.1/css/all.min.css" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <style>
        :root {
            --primary: #1E3A8A;
            --primary-light: #3B82F6;
            --primary-dark: #1E40AF;
            --secondary: #10B981;
            --accent: #F59E0B;
            --danger: #EF4444;
            --warning: #F59E0B;
            --light: #F8FAFC;
            --dark: #111827;
            --gray: #6B7280;
            --gray-light: #E5E7EB;
            --card-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
            --card-shadow-hover: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
        }

        body {
            font-family: 'Poppins', 'Roboto', sans-serif;
            margin: 0;
            display: flex;
            background: #f8fbff;
            color: var(--dark);
            line-height: 1.6;
        }

        .sidebar-profile-img {
            width: 130px;
            height: 130px;
            border-radius: 50%;
            object-fit: cover;
            margin-bottom: 20px;
            transition: transform 0.3s ease;
            border: 3px solid var(--primary-light);
        }

        .sidebar-profile-img:hover {
            transform: scale(1.05);
        }

        .main-content {
            padding: 30px;
            margin-left: 220px;
            width: calc(100% - 220px);
            display: flex;
            flex-direction: column;
        }

        .main-content-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 1px solid var(--gray-light);
        }

        .main-content-header h1 {
            color: var(--primary);
            font-weight: 700;
            margin: 0;
            font-size: 28px;
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .header-actions {
            display: flex;
            gap: 15px;
            align-items: center;
        }

        .date-display {
            color: var(--gray);
            font-size: 14px;
            background: white;
            padding: 8px 15px;
            border-radius: 20px;
            box-shadow: var(--card-shadow);
        }

        .stats-summary {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .stat-card {
            background: white;
            border-radius: 12px;
            padding: 20px;
            box-shadow: var(--card-shadow);
            transition: all 0.3s ease;
            border-left: 4px solid var(--primary);
        }

        .stat-card:hover {
            transform: translateY(-3px);
            box-shadow: var(--card-shadow-hover);
        }

        .stat-card.employees { border-left-color: var(--primary); }
        .stat-card.applicants { border-left-color: var(--secondary); }
        .stat-card.new-hires { border-left-color: var(--accent); }

        .stat-card-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 10px;
        }

        .stat-card-title {
            font-size: 14px;
            color: var(--gray);
            font-weight: 500;
            margin: 0;
        }

        .stat-card-icon {
            width: 40px;
            height: 40px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 18px;
        }

        .stat-card.employees .stat-card-icon { background: var(--primary); }
        .stat-card.applicants .stat-card-icon { background: var(--secondary); }
        .stat-card.new-hires .stat-card-icon { background: var(--accent); }

        .stat-card-value {
            font-size: 28px;
            font-weight: 700;
            color: var(--dark);
            margin: 0;
        }

        /* Table Container */
        .table-container {
            background: white;
            border-radius: 15px;
            box-shadow: var(--card-shadow);
            overflow: hidden;
            margin-bottom: 30px;
        }

        .table-header {
            padding: 20px 25px;
            border-bottom: 1px solid var(--gray-light);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .table-title {
            font-size: 18px;
            font-weight: 600;
            color: var(--primary);
            margin: 0;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .table-actions {
            display: flex;
            gap: 10px;
        }

        .refresh-btn {
            background: var(--primary);
            color: white;
            border: none;
            border-radius: 8px;
            padding: 8px 15px;
            font-size: 13px;
            display: flex;
            align-items: center;
            gap: 6px;
            transition: all 0.3s;
        }

        .refresh-btn:hover {
            background: var(--primary-dark);
            transform: translateY(-2px);
        }

        /* Table Styling */
        .table-custom {
            width: 100%;
            border-collapse: collapse;
            margin: 0;
        }

        .table-custom thead {
            background: linear-gradient(135deg, var(--primary), var(--primary-dark));
        }

        .table-custom th {
            padding: 16px 20px;
            text-align: left;
            color: white;
            font-weight: 600;
            font-size: 14px;
            border: none;
        }

        .table-custom tbody tr {
            border-bottom: 1px solid var(--gray-light);
            transition: all 0.3s;
        }

        .table-custom tbody tr:hover {
            background-color: rgba(59, 130, 246, 0.05);
            transform: translateY(-1px);
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
        }

        .table-custom tbody tr:nth-child(even) {
            background-color: #fbfdff;
        }

        .table-custom td {
            padding: 16px 20px;
            vertical-align: middle;
            border: none;
            font-size: 14px;
        }

        /* Action Buttons */
        .action-btn {
            background: var(--primary);
            color: white;
            border: none;
            border-radius: 8px;
            padding: 8px 16px;
            font-size: 13px;
            font-weight: 500;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            transition: all 0.3s;
            text-decoration: none;
        }

        .action-btn:hover {
            background: var(--primary-dark);
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(30, 64, 175, 0.3);
        }

        .action-btn.success {
            background: var(--secondary);
        }

        .action-btn.success:hover {
            background: #0da271;
        }

        /* Status Badges */
        .status-badge {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 6px 12px;
            border-radius: 20px;
            font-weight: 600;
            font-size: 12px;
            text-transform: capitalize;
        }

        .status-badge.hired {
            background-color: rgba(16, 185, 129, 0.1);
            color: var(--secondary);
            border: 1px solid rgba(16, 185, 129, 0.3);
        }

        /* Empty State */
        .empty-state {
            text-align: center;
            padding: 60px 20px;
            color: var(--gray);
        }

        .empty-state i {
            font-size: 64px;
            margin-bottom: 20px;
            color: var(--gray-light);
        }

        /* Confirmation Modal Styles */
        .confirmation-modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.5);
            z-index: 9999;
            align-items: center;
            justify-content: center;
            animation: fadeIn 0.3s ease;
        }

        .confirmation-modal.active {
            display: flex;
        }

        .modal-content {
            background: white;
            border-radius: 16px;
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
            width: 90%;
            max-width: 500px;
            overflow: hidden;
            animation: slideUp 0.3s ease;
        }

        .modal-header {
            background: linear-gradient(135deg, var(--primary), var(--primary-dark));
            color: white;
            padding: 20px 25px;
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .modal-header i {
            font-size: 24px;
        }

        .modal-header h3 {
            margin: 0;
            font-weight: 600;
            font-size: 20px;
        }

        .modal-body {
            padding: 25px;
        }

        .promotion-details {
            background-color: rgba(59, 130, 246, 0.05);
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 20px;
            border-left: 4px solid var(--primary);
        }

        .detail-row {
            display: flex;
            margin-bottom: 10px;
        }

        .detail-label {
            font-weight: 600;
            color: var(--gray);
            width: 120px;
            flex-shrink: 0;
        }

        .detail-value {
            color: var(--dark);
            flex-grow: 1;
        }

        .warning-box {
            background-color: rgba(245, 158, 11, 0.1);
            border-radius: 12px;
            padding: 15px;
            margin-bottom: 20px;
            border-left: 4px solid var(--warning);
            display: flex;
            align-items: flex-start;
            gap: 10px;
        }

        .warning-box i {
            color: var(--warning);
            font-size: 18px;
            margin-top: 2px;
        }

        .warning-content h4 {
            margin: 0 0 5px 0;
            font-size: 16px;
            color: var(--dark);
        }

        .warning-content p {
            margin: 0;
            font-size: 14px;
            color: var(--gray);
        }

        .modal-footer {
            padding: 0 25px 25px;
            display: flex;
            gap: 12px;
            justify-content: flex-end;
        }

        .btn {
            padding: 10px 20px;
            border-radius: 8px;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s;
            border: none;
            font-size: 14px;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }

        .btn-cancel {
            background-color: var(--gray-light);
            color: var(--dark);
        }

        .btn-cancel:hover {
            background-color: #d1d5db;
        }

        .btn-confirm {
            background-color: var(--secondary);
            color: white;
        }

        .btn-confirm:hover {
            background-color: #0da271;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(16, 185, 129, 0.3);
        }

        .btn:disabled {
            opacity: 0.6;
            cursor: not-allowed;
            transform: none !important;
        }

        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }

        @keyframes slideUp {
            from { 
                opacity: 0;
                transform: translateY(20px);
            }
            to { 
                opacity: 1;
                transform: translateY(0);
            }
        }

        /* Loading state for the button */
        .btn-loading {
            position: relative;
            color: transparent !important;
        }

        .btn-loading::after {
            content: "";
            position: absolute;
            width: 16px;
            height: 16px;
            top: 50%;
            left: 50%;
            margin-left: -8px;
            margin-top: -8px;
            border: 2px solid rgba(255, 255, 255, 0.3);
            border-radius: 50%;
            border-top-color: white;
            animation: spin 1s linear infinite;
        }

        @keyframes spin {
            to { transform: rotate(360deg); }
        }

        /* Responsive adjustments */
        @media (max-width: 1200px) {
            .main-content {
                margin-left: 0;
                width: 100%;
            }
        }

        @media (max-width: 768px) {
            .stats-summary {
                grid-template-columns: 1fr;
            }
            
            .table-header {
                flex-direction: column;
                gap: 15px;
                align-items: flex-start;
            }
            
            .table-responsive {
                overflow-x: auto;
            }

            .modal-content {
                width: 95%;
                margin: 20px;
            }

            .detail-row {
                flex-direction: column;
                gap: 5px;
            }

            .detail-label {
                width: 100%;
            }

            .modal-footer {
                flex-direction: column;
            }
        }

        @media (max-width: 576px) {
            .main-content {
                padding: 20px 15px;
            }
            
            .main-content-header {
                flex-direction: column;
                gap: 15px;
                align-items: flex-start;
            }
        }
    </style>
</head>

<body>
    <div class="sidebar">
        <div class="sidebar-logo">
            <a href="Manager_Profile.php" class="profile">
                <img src="<?php echo htmlspecialchars($profile_picture); ?>" alt="Profile" class="sidebar-profile-img">
            </a>
        </div>

        <div class="sidebar-name">
            <p><?php echo "Welcome, $managername"; ?></p>
        </div>

        <ul class="nav">
            <?php foreach ($menus[$role] as $label => $link): ?>
                <li><a href="<?php echo $link; ?>"><i
                            class="fa-solid <?php echo $icons[$label] ?? 'fa-circle'; ?>"></i><?php echo $label; ?></a></li>
            <?php endforeach; ?>
        </ul>
    </div>

    <div class="main-content">
        <div class="main-content-header">
            <h1><i class="fas fa-user-check"></i> Newly Hired Employees</h1>
            <div class="header-actions">
                <span class="date-display">
                    <i class="far fa-calendar-alt me-2"></i>
                    <?php echo date("F j, Y"); ?>
                </span>
            </div>
        </div>

        <!-- Stats Summary -->
        <div class="stats-summary">
            <div class="stat-card employees">
                <div class="stat-card-header">
                    <h3 class="stat-card-title">Total Employees</h3>
                    <div class="stat-card-icon">
                        <i class="fas fa-users"></i>
                    </div>
                </div>
                <p class="stat-card-value"><?php echo $employees; ?></p>
            </div>

            <div class="stat-card applicants">
                <div class="stat-card-header">
                    <h3 class="stat-card-title">Total Applicants</h3>
                    <div class="stat-card-icon">
                        <i class="fas fa-user"></i>
                    </div>
                </div>
                <p class="stat-card-value"><?php echo $applicants; ?></p>
            </div>

            <div class="stat-card new-hires">
                <div class="stat-card-header">
                    <h3 class="stat-card-title">Newly Hired</h3>
                    <div class="stat-card-icon">
                        <i class="fas fa-user-plus"></i>
                    </div>
                </div>
                <p class="stat-card-value"><?php echo count($newlyHired); ?></p>
            </div>
        </div>

        <!-- Newly Hired Table -->
        <div class="table-container">
            <div class="table-header">
                <h3 class="table-title"><i class="fas fa-list-check"></i> Newly Hired Candidates</h3>
                <div class="table-actions">
                    <button class="refresh-btn" onclick="refreshPage()">
                        <i class="fas fa-sync-alt"></i> Refresh
                    </button>
                </div>
            </div>
            
            <div class="table-responsive">
                <table class="table-custom">
                    <thead>
                        <tr>
                            <th>Applicant ID</th>
                            <th>Full Name</th>
                            <th>Department</th>
                            <th>Position</th>
                            <th>Employment Type</th>
                            <th>Email Address</th>
                            <th>Hired Date</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($newlyHired)): ?>
                            <?php foreach ($newlyHired as $hire): ?>
                                <tr>
                                    <td>
                                        <span class="fw-bold text-primary"><?php echo htmlspecialchars($hire['applicantID']); ?></span>
                                    </td>
                                    <td>
                                        <strong><?php echo htmlspecialchars($hire['fullName']); ?></strong>
                                    </td>
                                    <td><?php echo htmlspecialchars($hire['department']); ?></td>
                                    <td><?php echo htmlspecialchars($hire['position_applied']); ?></td>
                                    <td><?php echo htmlspecialchars($hire['type_name']); ?></td>
                                    <td><?php echo htmlspecialchars($hire['email_address']); ?></td>
                                    <td>
                                        <?php 
                                        $hiredDate = !empty($hire['hired_at']) ? date('M j, Y', strtotime($hire['hired_at'])) : 'Not set';
                                        echo $hiredDate;
                                        ?>
                                    </td>
                                    <td>
                                        <span class="status-badge hired">
                                            <i class="fas fa-user-check"></i> Hired
                                        </span>
                                    </td>
                                    <td>
                                        <form method="POST" class="d-inline promotion-form">
                                            <input type="hidden" name="add_employee_id" value="<?php echo $hire['applicantID']; ?>">
                                            <button type="button" class="action-btn success promote-btn" 
                                                    data-applicant-id="<?php echo $hire['applicantID']; ?>"
                                                    data-fullname="<?php echo htmlspecialchars($hire['fullName']); ?>"
                                                    data-department="<?php echo htmlspecialchars($hire['department']); ?>"
                                                    data-position="<?php echo htmlspecialchars($hire['position_applied']); ?>"
                                                    data-employment-type="<?php echo htmlspecialchars($hire['type_name']); ?>"
                                                    data-email="<?php echo htmlspecialchars($hire['email_address']); ?>">
                                                <i class="fas fa-user-plus"></i> Promote to Employee
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="9">
                                    <div class="empty-state">
                                        <i class="fas fa-user-check"></i>
                                        <h4>No Newly Hired Employees</h4>
                                        <p>There are no newly hired candidates at the moment. Check back later or review pending applicants.</p>
                                    </div>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Confirmation Modal -->
    <div class="confirmation-modal" id="promotionModal">
        <div class="modal-content">
            <div class="modal-header">
                <i class="fas fa-user-plus"></i>
                <h3>Promote to Employee</h3>
            </div>
            <div class="modal-body">
                <div class="promotion-details">
                    <div class="detail-row">
                        <div class="detail-label">Applicant:</div>
                        <div class="detail-value" id="modal-fullname"></div>
                    </div>
                    <div class="detail-row">
                        <div class="detail-label">Department:</div>
                        <div class="detail-value" id="modal-department"></div>
                    </div>
                    <div class="detail-row">
                        <div class="detail-label">Position:</div>
                        <div class="detail-value" id="modal-position"></div>
                    </div>
                    <div class="detail-row">
                        <div class="detail-label">Employment Type:</div>
                        <div class="detail-value" id="modal-employment-type"></div>
                    </div>
                    <div class="detail-row">
                        <div class="detail-label">Email:</div>
                        <div class="detail-value" id="modal-email"></div>
                    </div>
                </div>
                
                <div class="warning-box">
                    <i class="fas fa-exclamation-triangle"></i>
                    <div class="warning-content">
                        <h4>Important Notice</h4>
                        <p>This action will create an employee account, send login credentials to the applicant's email, and archive their applicant record. This process cannot be undone.</p>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-cancel" id="cancelPromotion">
                    <i class="fas fa-times"></i> Cancel
                </button>
                <button type="button" class="btn btn-confirm" id="confirmPromotion">
                    <i class="fas fa-check"></i> Confirm Promotion
                </button>
            </div>
        </div>
    </div>

    <script>
        // Promotion Modal Functionality
        document.addEventListener('DOMContentLoaded', function() {
            const modal = document.getElementById('promotionModal');
            const cancelBtn = document.getElementById('cancelPromotion');
            const confirmBtn = document.getElementById('confirmPromotion');
            let currentForm = null;
            
            // Open modal when promote button is clicked
            document.querySelectorAll('.promote-btn').forEach(button => {
                button.addEventListener('click', function() {
                    // Set modal content
                    document.getElementById('modal-fullname').textContent = this.dataset.fullname;
                    document.getElementById('modal-department').textContent = this.dataset.department;
                    document.getElementById('modal-position').textContent = this.dataset.position;
                    document.getElementById('modal-employment-type').textContent = this.dataset.employmentType;
                    document.getElementById('modal-email').textContent = this.dataset.email;
                    
                    // Store reference to the form
                    currentForm = this.closest('.promotion-form');
                    
                    // Show modal
                    modal.classList.add('active');
                });
            });
            
            // Close modal when cancel button is clicked
            cancelBtn.addEventListener('click', function() {
                modal.classList.remove('active');
                currentForm = null;
            });
            
            // Close modal when clicking outside the modal content
            modal.addEventListener('click', function(e) {
                if (e.target === modal) {
                    modal.classList.remove('active');
                    currentForm = null;
                }
            });
            
            // Handle confirmation
            confirmBtn.addEventListener('click', function() {
                if (currentForm) {
                    // Disable button and show loading state
                    this.disabled = true;
                    this.classList.add('btn-loading');
                    
                    // Submit the form after a brief delay to show the loading state
                    setTimeout(() => {
                        currentForm.submit();
                    }, 500);
                }
            });
        });

        // Your existing JavaScript functions
        function refreshPage() {
            location.reload();
        }

        // Add smooth animations
        document.addEventListener('DOMContentLoaded', function() {
            // Animate table rows on load
            const tableRows = document.querySelectorAll('.table-custom tbody tr');
            tableRows.forEach((row, index) => {
                row.style.animationDelay = `${index * 0.1}s`;
                row.classList.add('animate__animated', 'animate__fadeInUp');
            });
        });

        // Success message handler
        <?php if (isset($_POST['add_employee_id'])): ?>
            setTimeout(() => {
                const toast = document.createElement('div');
                toast.className = 'alert alert-success alert-dismissible fade show position-fixed top-0 end-0 m-3';
                toast.style.zIndex = '9999';
                toast.innerHTML = `
                    <i class="fas fa-check-circle me-2"></i>
                    Employee promoted successfully! Account created and credentials sent.
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                `;
                document.body.appendChild(toast);
            }, 100);
        <?php endif; ?>
    </script>

    <!-- Add Bootstrap JS for toast functionality -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>