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

// Fetch admin name
$adminnameQuery = $conn->query("SELECT fullname FROM user WHERE role = 'Admin' LIMIT 1");
$adminname = ($adminnameQuery && $row = $adminnameQuery->fetch_assoc()) ? $row['fullname'] : 'Admin';

// --- AJAX: Return Positions based on Department --- //
if (isset($_GET['ajax']) && $_GET['ajax'] === 'positions' && isset($_GET['deptID'])) {
    require 'admin/db.connect.php';
    $deptID = intval($_GET['deptID']);

    $query = $conn->prepare("SELECT positionID, position_title FROM position WHERE departmentID = ?");
    $query->bind_param("i", $deptID);
    $query->execute();
    $result = $query->get_result();

    $positions = [];
    while ($row = $result->fetch_assoc()) {
        $positions[] = $row;
    }

    echo json_encode($positions);
    exit; // STOP normal page load
}
// Handle Add User Form Submission
if (isset($_POST['action']) && $_POST['action'] === 'add_user') {
    $fullname = $_POST['fullname'];
    $email = $_POST['email'];
    $role = $_POST['role'];
    $sub_role = $_POST['sub_role'] ?? null;
    $department = $_POST['department'] ?? null;
    $status = $_POST['status'] ?? 'Active';
   $type_id = $_POST['employment_type'] ?? null; // selected emtypeID
        $type_name = null;

        if ($type_id) {
            $etypeQuery = $conn->prepare("SELECT typeName FROM employment_type WHERE emtypeID = ?");
            $etypeQuery->bind_param("i", $type_id);
            $etypeQuery->execute();
            $etypeResult = $etypeQuery->get_result();
            if ($etypeResult->num_rows > 0) {
                $etypeRow = $etypeResult->fetch_assoc();
                $type_name = $etypeRow['typeName'];
            }
        }

        // Fetch Department Name
$deptQuery = $conn->prepare("SELECT deptName FROM department WHERE deptID = ?");
$deptQuery->bind_param("i", $department);
$deptQuery->execute();
$deptResult = $deptQuery->get_result();
$departmentName = ($deptResult->num_rows > 0) ? $deptResult->fetch_assoc()['deptName'] : "Unknown Department";

// Fetch Sub-role (Position Title)
$posQuery = $conn->prepare("SELECT position_title FROM position WHERE positionID = ?");
$posQuery->bind_param("i", $sub_role);
$posQuery->execute();
$posResult = $posQuery->get_result();
$subRoleName = ($posResult->num_rows > 0) ? $posResult->fetch_assoc()['position_title'] : "Employee";


    // Generate temp password and hash
    $tempPass = bin2hex(random_bytes(4));
    $hashedPass = password_hash($tempPass, PASSWORD_DEFAULT);

    // Generate reset token
    $token = bin2hex(random_bytes(16));
    $token_expiry = date("Y-m-d H:i:s", strtotime("+1 day"));

    // Fetch last EMP ID
$result = $conn->query("SELECT applicant_employee_id FROM user WHERE applicant_employee_id LIKE 'EMP-%' ORDER BY applicant_employee_id DESC LIMIT 1");
$row = $result->fetch_assoc();

if ($row) {
    $lastID = $row['applicant_employee_id']; // e.g. EMP-007
    $num = (int) str_replace('EMP-', '', $lastID); // 7
    $num++;
} else {
    $num = 1; // first EMP ID
}

$empID = "EMP-" . str_pad($num, 3, '0', STR_PAD_LEFT);

   // Generate Unique EMP ID from BOTH user and employee tables
$query = "
    SELECT applicant_employee_id AS emp 
    FROM user 
    WHERE applicant_employee_id LIKE 'EMP-%'
    
    UNION ALL
    
    SELECT empID AS emp 
    FROM employee 
    WHERE empID LIKE 'EMP-%'
    
    ORDER BY emp DESC
    LIMIT 1
";

$result = $conn->query($query);
$row = $result->fetch_assoc();

if ($row) {
    $lastID = $row['emp']; // Example: EMP-040
    $num = (int) str_replace('EMP-', '', $lastID); // Extract 40
    $num++;
} else {
    $num = 1;
}

$empID = "EMP-" . str_pad($num, 3, '0', STR_PAD_LEFT);


    // Insert into user table
    $userStmt = $conn->prepare("INSERT INTO user (applicant_employee_id, fullname, email, password, role, sub_role, status, reset_token, token_expiry, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())");
    $userStmt->bind_param("sssssssss", $empID, $fullname, $email, $hashedPass, $role, $subRoleName, $status, $token, $token_expiry);

    if ($userStmt->execute()) {
        // Insert into employee table
        $empStmt = $conn->prepare("INSERT INTO employee (empID, fullname, email_address, department, position, type_name, hired_at) VALUES (?, ?, ?, ?, ?,?, NOW())");
        $empStmt->bind_param("ssssss", $empID, $fullname, $email,  $departmentName, $subRoleName, $type_name);
        $empStmt->execute();

        // Send email
        $mailConfig = [
            'host' => 'smtp.example.com',
            'username' => 'you@example.com',
            'password' => 'yourpassword',
            'port' => 587,
            'encryption' => 'tls',
            'from_email' => 'you@example.com',
            'from_name' => 'Employee Management System'
        ];

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

          <p>We are pleased to inform you that you are now part of our team with special system permissions 
          at the <b>$departmentName</b> as <b>$subRoleName</b>.</p>

          <p>Your Employee ID is: <b>$empID</b></p>

          <p>Your temporary password is: <b>$tempPass</b></p>

          <p>Please change your password within 24 hours using the link below:</p>
          <p><a href='$link'>$link</a></p>

          <br>
          <p>We're excited to have you onboard!</p>
      ";



            $mail->send();
            $_SESSION['success_msg'] = "User added successfully! Email sent.";
        } catch (Exception $e) {
            $_SESSION['error_msg'] = "Account created, but email failed. Error: {$mail->ErrorInfo}";
        }
    } else {
        $_SESSION['error_msg'] = "Error creating account. Please try again.";
    }

    header("Location: " . $_SERVER['PHP_SELF']);
    exit;
}

// Fetch Employment Types
$employmentTypes = [];
$etypeQuery = $conn->query("SELECT * FROM employment_type ORDER BY emtypeID ASC");
while ($etype = $etypeQuery->fetch_assoc()) {
    $employmentTypes[] = $etype;
}

// Fetch HR positions
$hrDeptID = 10; // HR Department ID
$hrPositionsQuery = $conn->query("SELECT positionID, position_title FROM position WHERE departmentID = $hrDeptID");

$hrPositions = [];
if ($hrPositionsQuery) {
    while ($row = $hrPositionsQuery->fetch_assoc()) {
        $hrPositions[] = $row;
    }
}

// Handle Edit User
if (isset($_POST['action']) && $_POST['action'] === 'edit_user') {
    $user_id = $_POST['user_id'];
    $email = $_POST['email'] ?? null;
    $empID = $_POST['applicant_employee_id'] ?? null;
    $sub_role_id = $_POST['sub_role'] ?? null;
    $department_id = $_POST['department'] ?? null;
    $type_id = $_POST['employment_type'] ?? null;
    $type_name = null;
    $departmentName = null;
    $positionTitle = null;

    if ($type_id) {
        $etypeQuery = $conn->prepare("SELECT typeName FROM employment_type WHERE emtypeID = ?");
        $etypeQuery->bind_param("i", $type_id);
        $etypeQuery->execute();
        $etypeResult = $etypeQuery->get_result();
        if ($etypeResult->num_rows > 0) {
            $etypeRow = $etypeResult->fetch_assoc();
            $type_name = $etypeRow['typeName'];
        }
    }

    if ($department_id) {
        $deptStmt = $conn->prepare("SELECT deptName FROM department WHERE deptID = ?");
        $deptStmt->bind_param("i", $department_id);
        $deptStmt->execute();
        $deptRes = $deptStmt->get_result();
        if ($deptRes->num_rows > 0) {
            $departmentName = $deptRes->fetch_assoc()['deptName'];
        }
    }

    if ($sub_role_id) {
        $posStmt = $conn->prepare("SELECT position_title FROM position WHERE positionID = ?");
        $posStmt->bind_param("i", $sub_role_id);
        $posStmt->execute();
        $posRes = $posStmt->get_result();
        if ($posRes->num_rows > 0) {
            $positionTitle = $posRes->fetch_assoc()['position_title'];
        }
    }

    $curStmt = $conn->prepare("SELECT u.fullname, u.email, u.sub_role, e.department, e.position, e.type_name FROM user u LEFT JOIN employee e ON e.empID = u.applicant_employee_id WHERE u.applicant_employee_id = ?");
    $curStmt->bind_param("s", $empID);
    $curStmt->execute();
    $curRes = $curStmt->get_result();
    $cur = $curRes->fetch_assoc();

    $newDept = $departmentName ?: $cur['department'];
    $newPos = $positionTitle ?: $cur['position'];
    $newType = isset($type_name) ? $type_name : $cur['type_name'];

    $changed = false;
    if ($newDept !== $cur['department']) $changed = true;
    if ($newPos !== $cur['position']) $changed = true;
    if ($newType !== $cur['type_name']) $changed = true;

    if (!$changed) {
        $_SESSION['success_msg'] = "No changes to department, sub-role, or employment type.";
        header("Location: " . $_SERVER['PHP_SELF']);
        exit;
    }

    if (!$empID) {
        $_SESSION['error_msg'] = "Unable to resolve employee ID for this user.";
        header("Location: " . $_SERVER['PHP_SELF']);
        exit;
    }

    if ($positionTitle && $cur['sub_role'] !== $newPos) {
        $uStmt = $conn->prepare("UPDATE user SET sub_role=? WHERE applicant_employee_id=?");
        $uStmt->bind_param("ss", $newPos, $empID);
        $uStmt->execute();
    }

    $eStmt = $conn->prepare("UPDATE employee SET department=?, position=?, type_name=? WHERE empID=?");
    $eStmt->bind_param("ssss", $newDept, $newPos, $newType, $empID);
    $eStmt->execute();

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
        $mail->addAddress($cur['email'], $cur['fullname']);
        $mail->isHTML(true);
        $mail->Subject = "Account Information Updated";
        $mail->Body = "<h3>Hello, " . htmlspecialchars($cur['fullname']) . "</h3>"
          . "<p>Your account information has been updated by the administrator.</p>"
          . "<p><b>Department:</b> " . htmlspecialchars($newDept) . "<br>"
          . "<b>Sub-role:</b> " . htmlspecialchars($newPos) . "<br>"
          . "<b>Employment Type:</b> " . htmlspecialchars($newType) . "</p>"
          . "<p>If you did not request this change, please contact HR immediately.</p>";
        $mail->send();
        $_SESSION['success_msg'] = "User updated and email notification sent.";
    } catch (Exception $e) {
        $_SESSION['success_msg'] = "User updated successfully, but email failed to send.";
    }

    header("Location: " . $_SERVER['PHP_SELF']);
    exit;
}

// Get user statistics
$totalUsers = $conn->query("SELECT COUNT(*) as count FROM user")->fetch_assoc()['count'];
$activeUsers = $conn->query("SELECT COUNT(*) as count FROM user WHERE status = 'Active'")->fetch_assoc()['count'];
$inactiveUsers = $conn->query("SELECT COUNT(*) as count FROM user WHERE status = 'Inactive'")->fetch_assoc()['count'];
$employeeUsers = $conn->query("SELECT COUNT(*) as count FROM user WHERE role = 'Employee'")->fetch_assoc()['count'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Admin Dashboard - User Management</title>
<link rel="stylesheet" href="admin-sidebar.css">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<style>
:root {
    --primary: #1E3A8A;
    --primary-light: #3B82F6;
    --primary-dark: #1E40AF;
    --secondary: #64748B;
    --success: #10B981;
    --warning: #F59E0B;
    --danger: #EF4444;
    --light: #F8FAFC;
    --dark: #1E293B;
    --gray-100: #F3F4F6;
    --gray-200: #E5E7EB;
    --gray-300: #D1D5DB;
    --card-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
    --hover-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
    --smooth-shadow: 0 2px 15px -3px rgba(0, 0, 0, 0.07), 0 10px 20px -2px rgba(0, 0, 0, 0.04);
}

body { 
    font-family: 'Poppins', sans-serif; 
    margin:0; 
    display:flex; 
    background-color: var(--light); 
    color: var(--dark);
    line-height: 1.6;
}

.main-content { 
    padding: 30px; 
    margin-left: 220px; 
    display: flex; 
    flex-direction: column; 
    width: calc(100% - 220px);
    min-height: 100vh;
}

.main-content-header { 
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 30px;
    padding-bottom: 20px;
    border-bottom: 1px solid var(--gray-200);
}

.main-content-header h1 { 
    color: var(--primary);
    font-weight: 700;
    margin: 0;
    font-size: 2rem;
    position: relative;
}

.main-content-header h1::after {
    content: '';
    position: absolute;
    bottom: -10px;
    left: 0;
    width: 60px;
    height: 4px;
    background: linear-gradient(90deg, var(--primary), var(--primary-light));
    border-radius: 2px;
}

/* Stats Cards */
.stats-container {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
    gap: 20px;
    margin-bottom: 30px;
}

.stat-card {
    background: white;
    border-radius: 16px;
    padding: 24px;
    box-shadow: var(--card-shadow);
    transition: all 0.3s ease;
    border: 1px solid var(--gray-200);
    position: relative;
    overflow: hidden;
}

.stat-card::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 4px;
    background: linear-gradient(90deg, var(--primary), var(--primary-light));
}

.stat-card:hover {
    transform: translateY(-5px);
    box-shadow: var(--hover-shadow);
}

.stat-card.total-users::before { background: linear-gradient(90deg, var(--primary), var(--primary-light)); }
.stat-card.active-users::before { background: linear-gradient(90deg, var(--success), #34D399); }
.stat-card.inactive-users::before { background: linear-gradient(90deg, var(--danger), #F87171); }
.stat-card.employee-users::before { background: linear-gradient(90deg, var(--warning), #FBBF24); }

.stat-card i {
    font-size: 2.5rem;
    margin-bottom: 15px;
    opacity: 0.9;
}

.stat-card.total-users i { color: var(--primary); }
.stat-card.active-users i { color: var(--success); }
.stat-card.inactive-users i { color: var(--danger); }
.stat-card.employee-users i { color: var(--warning); }

.stat-card h3 {
    font-size: 2rem;
    font-weight: 700;
    margin: 0;
    color: var(--dark);
}

.stat-card p {
    color: var(--secondary);
    margin: 5px 0 0 0;
    font-weight: 500;
    font-size: 0.95rem;
}

/* Main Content Card */
.content-card {
    background: white;
    border-radius: 16px;
    padding: 30px;
    box-shadow: var(--smooth-shadow);
    border: 1px solid var(--gray-200);
    margin-bottom: 30px;
}

.content-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 25px;
    padding-bottom: 20px;
    border-bottom: 1px solid var(--gray-200);
}

.content-header h3 {
    color: var(--primary);
    font-weight: 600;
    margin: 0;
    font-size: 1.5rem;
}

/* Enhanced Button Styles */
.btn {
    border-radius: 10px;
    padding: 10px 20px;
    font-weight: 500;
    transition: all 0.3s ease;
    border: none;
    display: inline-flex;
    align-items: center;
    gap: 8px;
}

.btn-primary {
    background: linear-gradient(135deg, var(--primary), var(--primary-light));
    box-shadow: 0 2px 10px rgba(30, 64, 175, 0.3);
}

.btn-primary:hover {
    background: linear-gradient(135deg, var(--primary-dark), var(--primary));
    transform: translateY(-2px);
    box-shadow: 0 4px 15px rgba(30, 64, 175, 0.4);
}

.btn-secondary {
    background: var(--gray-200);
    color: var(--dark);
}

.btn-secondary:hover {
    background: var(--gray-300);
    transform: translateY(-2px);
}

.btn-warning {
    background: var(--warning);
    color: white;
}

.btn-warning:hover {
    background: #EAB308;
    transform: translateY(-2px);
}

.btn-danger {
    background: var(--danger);
    color: white;
}

.btn-danger:hover {
    background: #DC2626;
    transform: translateY(-2px);
}

.btn-sm {
    padding: 8px 12px;
    font-size: 0.85rem;
    border-radius: 8px;
}

/* Enhanced Table Styles */
.table-container {
    border-radius: 12px;
    overflow: hidden;
    box-shadow: 0 1px 10px rgba(0, 0, 0, 0.05);
    border: 1px solid var(--gray-200);
}

.table {
    margin-bottom: 0;
    font-size: 0.95rem;
}

.table thead th {
    background: linear-gradient(135deg, var(--primary), var(--primary-light));
    color: white;
    border: none;
    padding: 16px 12px;
    font-weight: 600;
    font-size: 0.9rem;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.table tbody td {
    padding: 16px 12px;
    vertical-align: middle;
    border-color: var(--gray-200);
    color: var(--dark);
}

.table tbody tr {
    transition: all 0.2s ease;
}

.table tbody tr:hover {
    background: var(--gray-100);
    transform: scale(1.002);
}

.table tbody tr:nth-child(even) {
    background: var(--light);
}

.table tbody tr:nth-child(even):hover {
    background: var(--gray-100);
}

/* Badge Styles */
.badge {
    font-size: 0.8rem;
    padding: 6px 12px;
    border-radius: 20px;
    font-weight: 500;
}

.bg-success {
    background: linear-gradient(135deg, var(--success), #34D399) !important;
}

.bg-danger {
    background: linear-gradient(135deg, var(--danger), #F87171) !important;
}

/* Enhanced Modal Styles */
.modal-content {
    border-radius: 20px;
    border: none;
    box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
    overflow: hidden;
}

.modal-header {
    background: linear-gradient(135deg, var(--primary), var(--primary-light));
    color: white;
    border-bottom: none;
    padding: 25px 30px;
}

.modal-title {
    font-weight: 700;
    font-size: 1.4rem;
    letter-spacing: 0.5px;
}

.modal-body {
    padding: 30px;
    background: var(--light);
}

.modal-footer {
    background: var(--light);
    border-top: 1px solid var(--gray-200);
    padding: 20px 30px;
}

.form-control, .form-select {
    border-radius: 10px;
    padding: 12px 16px;
    border: 1px solid var(--gray-300);
    transition: all 0.3s ease;
    font-size: 0.95rem;
    background: white;
}

.form-control:focus, .form-select:focus {
    border-color: var(--primary-light);
    box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
    transform: translateY(-1px);
}

.form-label {
    font-weight: 600;
    color: var(--primary);
    margin-bottom: 8px;
    font-size: 0.95rem;
}

/* Alert Styles */
.alert {
    border-radius: 12px;
    border: none;
    padding: 16px 20px;
    font-weight: 500;
}

.alert-success {
    background: linear-gradient(135deg, #D1FAE5, #ECFDF5);
    color: var(--success);
    border-left: 4px solid var(--success);
}

.alert-danger {
    background: linear-gradient(135deg, #FEE2E2, #FEF2F2);
    color: var(--danger);
    border-left: 4px solid var(--danger);
}

/* Action Buttons */
.action-buttons {
    display: flex;
    gap: 8px;
    justify-content: center;
}

/* Loading Animation */
@keyframes fadeIn {
    from { opacity: 0; transform: translateY(20px); }
    to { opacity: 1; transform: translateY(0); }
}

.fade-in {
    animation: fadeIn 0.5s ease;
}

/* Responsive Design */
@media (max-width: 768px) {
    .main-content {
        margin-left: 0;
        width: 100%;
        padding: 20px 15px;
    }
    
    .stats-container {
        grid-template-columns: 1fr;
    }
    
    .content-header {
        flex-direction: column;
        align-items: flex-start;
        gap: 15px;
    }
    
    .action-buttons {
        flex-wrap: wrap;
        justify-content: flex-start;
    }
    
    .modal-body .row {
        gap: 15px;
    }
    
    .table-responsive {
        font-size: 0.85rem;
    }
}

/* Custom Scrollbar */
::-webkit-scrollbar {
    width: 8px;
}

::-webkit-scrollbar-track {
    background: var(--gray-100);
}

::-webkit-scrollbar-thumb {
    background: var(--primary-light);
    border-radius: 4px;
}

::-webkit-scrollbar-thumb:hover {
    background: var(--primary);
}
</style>
</head>
<body>

<!-- Sidebar -->
<div class="sidebar">
    <div class="sidebar-logo"><img src="Images/hospitallogo.png" alt="Hospital Logo"></div>
    <div class="sidebar-name"><p><?php echo "Welcome Admin, $adminname"; ?></p></div>
    <ul class="nav flex-column">
        <li><a href="Admin_Dashboard.php"><i class="fa-solid fa-table-columns"></i> Dashboard</a></li>
        <li class="active"><a href="Admin_UserManagement.php"><i class="fa-solid fa-users"></i> User Management</a></li>
        <li><a href="Admin_Departments.php"><i class="fa-solid fa-building-columns"></i> Departments</a></li>
        <li><a href="Admin_RequestSetting.php"><i class="fa-solid fa-clipboard-list"></i> Request Setting</a></li>
        <li><a href="Admin-Applicants.php"><i class="fa-solid fa-user-check"></i> Applicants</a></li>
        <li><a href="Admin_Reports.php"><i class="fa-solid fa-chart-simple"></i> Reports</a></li>
        <li><a href="Admin-Settings.php"><i class="fa-solid fa-gear"></i> Settings</a></li>
        <li><a href="Login.php"><i class="fa-solid fa-right-from-bracket"></i> Logout</a></li>
    </ul>
</div>

<main class="main-content">
    <div class="main-content-header">
        <h1>User Management</h1>
        <?php
        if (!empty($_SESSION['success_msg'])) {
            echo '<div class="alert alert-success fade-in">'.$_SESSION['success_msg'].'</div>';
            unset($_SESSION['success_msg']);
        } elseif (!empty($_SESSION['error_msg'])) {
            echo '<div class="alert alert-danger fade-in">'.$_SESSION['error_msg'].'</div>';
            unset($_SESSION['error_msg']);
        }
        ?>
    </div>

    <!-- Statistics Cards -->
    <div class="stats-container">
        <div class="stat-card total-users fade-in">
            <i class="fa-solid fa-users"></i>
            <h3><?php echo $totalUsers; ?></h3>
            <p>Total Users</p>
        </div>
        
        <div class="stat-card active-users fade-in">
            <i class="fa-solid fa-user-check"></i>
            <h3><?php echo $activeUsers; ?></h3>
            <p>Active Users</p>
        </div>
        
        <div class="stat-card inactive-users fade-in">
            <i class="fa-solid fa-user-slash"></i>
            <h3><?php echo $inactiveUsers; ?></h3>
            <p>Inactive Users</p>
        </div>
        
        <div class="stat-card employee-users fade-in">
            <i class="fa-solid fa-briefcase"></i>
            <h3><?php echo $employeeUsers; ?></h3>
            <p>Employee Users</p>
        </div>
    </div>

    <!-- Main Content -->
    <div class="content-card fade-in">
        <div class="content-header">
            <h3>User Accounts</h3>
            <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addUserModal">
                <i class="fa-solid fa-user-plus"></i> Add New User
            </button>
        </div>

        <div class="table-container">
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead>
                        <tr>
                            <th>Full Name</th>
                            <th>Email</th>
                            <th>Role</th>
                            <th>Sub-role</th>
                            <th>Status</th>
                            <th>Date Created</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php
                    $query = $conn->query("SELECT u.*, e.department AS emp_department, e.position AS emp_position, e.type_name AS emp_type_name FROM user u LEFT JOIN employee e ON e.empID = u.applicant_employee_id ORDER BY u.created_at DESC");
                    while ($row = $query->fetch_assoc()) {
                        $statusBadge = ($row['status'] == 'Active') ? 'bg-success' : 'bg-danger';
                        $subRole = $row['sub_role'] ?: '-';
                        echo "
                        <tr class='fade-in'>
                            <td><strong>{$row['fullname']}</strong></td>
                            <td>{$row['email']}</td>
                            <td><span class='badge bg-primary'>{$row['role']}</span></td>
                            <td>{$subRole}</td>
                            <td><span class='badge $statusBadge'>{$row['status']}</span></td>
                            <td>" . date('M j, Y', strtotime($row['created_at'])) . "</td>
                            <td>
                                <div class='action-buttons'>
                                    <button class='btn btn-sm btn-warning editBtn' 
                                        data-id='{$row['user_id']}' 
                                        data-fullname='{$row['fullname']}' 
                                        data-email='{$row['email']}' 
                                        data-role='{$row['role']}' 
                                        data-subrole='{$row['sub_role']}' 
                                        data-status='{$row['status']}' 
                                        data-departmentname='{$row['emp_department']}'
                                        data-employmenttypename='{$row['emp_type_name']}'
                                        data-empid='{$row['applicant_employee_id']}'
                                        title='Edit User'>
                                        <i class='fa-solid fa-pen'></i> Edit
                                    </button>
                                </div>
                            </td>
                        </tr>";
                    }
                    ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</main>

<!-- ADD USER MODAL -->
<div class="modal fade" id="addUserModal" tabindex="-1">
  <div class="modal-dialog modal-lg modal-dialog-scrollable">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Add New User</h5>
        <button class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <form action="" method="POST">
        <input type="hidden" name="action" value="add_user">
        <div class="modal-body">
          <div class="row g-3">
            <div class="col-12 col-md-6">
              <label class="form-label">Full Name</label>
              <input type="text" name="fullname" class="form-control" required>
            </div>
            <div class="col-12 col-md-6">
              <label class="form-label">Email</label>
              <input type="email" name="email" class="form-control" required>
            </div>
            <div class="col-12 col-md-4">
              <label class="form-label">Role</label>
              <select name="role" class="form-select" required>
                <option value="Employee">Employee</option>
              </select>
            </div>
            <div class="col-12 col-md-4">
                <label class="form-label">Subrole</label>
              <select name="sub_role" id="sub_role" class="form-select" required>
                  <option value="">-- Select Position --</option>
              </select>
              </div>
            <div class="col-12 col-md-4">
              <label class="form-label">Department</label>
             <select name="department" id="department" class="form-select" required>
                <option value="">-- Select Department --</option>
                <?php
                $deptQuery = $conn->query("SELECT deptID, deptName FROM department ORDER BY deptName ASC");
                while ($dept = $deptQuery->fetch_assoc()) {
                    echo "<option value='{$dept['deptID']}'>{$dept['deptName']}</option>";
                }
                ?>
              </select>
            </div>
            <div class="col-12 col-md-4">
              <label class="form-label">Employment Type</label>
              <select name="employment_type" class="form-select" required>
                <option value="">-- Select Employment Type --</option>
                <?php foreach ($employmentTypes as $etype): ?>
                  <option value="<?= $etype['emtypeID'] ?>"><?= $etype['typeName'] ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="col-12 col-md-4">
              <label class="form-label">Status</label>
              <select name="status" class="form-select">
                <option value="Active" selected>Active</option>
                <option value="Inactive">Inactive</option>
              </select>
            </div>
          </div>
        </div>
        <div class="modal-footer">
          <button class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
          <button class="btn btn-primary">
            <i class="fa-solid fa-save me-2"></i>Save User
          </button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- EDIT USER MODAL -->
<div class="modal fade" id="editUserModal" tabindex="-1">
  <div class="modal-dialog modal-lg modal-dialog-scrollable">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Edit User</h5>
        <button class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <form action="" method="POST">
        <input type="hidden" name="action" value="edit_user">
        <input type="hidden" name="user_id" id="edit_user_id">
        <input type="hidden" name="applicant_employee_id" id="edit_empid">
        <div class="modal-body">
          <div class="row g-3">
            <div class="col-12 col-md-6">
              <label class="form-label">Full Name</label>
              <input type="text" name="fullname" id="edit_fullname" class="form-control" required>
            </div>
            <div class="col-12 col-md-6">
              <label class="form-label">Email</label>
              <input type="email" name="email" id="edit_email" class="form-control" required>
            </div>
            <div class="col-12 col-md-4">
              <label class="form-label">Role</label>
              <select name="role" id="edit_role" class="form-select" required>
                <option value="Employee">Employee</option>
              </select>
            </div>
            <div class="col-12 col-md-4">
              <label class="form-label">Sub-role</label>
              <select name="sub_role" id="edit_sub_role" class="form-select">
                <option value="">-- Select Sub-role --</option>
              </select>
            </div>
            <div class="col-12 col-md-4">
              <label class="form-label">Department</label>
              <select name="department" id="edit_department" class="form-select" required>
                <option value="">-- Select Department --</option>
                <?php
                  $deptQuery2 = $conn->query("SELECT deptID, deptName FROM department ORDER BY deptName ASC");
                  while ($dept = $deptQuery2->fetch_assoc()) {
                    echo "<option value='" . htmlspecialchars($dept['deptID']) . "'>" . htmlspecialchars($dept['deptName']) . "</option>";
                  }
                ?>
              </select>
            </div>
            <div class="col-12 col-md-4">
  <label class="form-label">Employment Type</label>
  <select name="employment_type" id="edit_employment_type" class="form-select" required>
    <option value="">-- Select Employment Type --</option>
    <?php foreach ($employmentTypes as $etype): ?>
        <option value="<?= $etype['emtypeID'] ?>"><?= $etype['typeName'] ?></option>
    <?php endforeach; ?>
  </select>
</div>

            <div class="col-12 col-md-4">
              <label class="form-label">Status</label>
              <select name="status" id="edit_status" class="form-select">
                <option value="Active">Active</option>
                <option value="Inactive">Inactive</option>
              </select>
            </div>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
          <button class="btn btn-primary">
            <i class="fa-solid fa-save me-2"></i>Update User
          </button>
        </div>
      </form>
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
// Enhanced JavaScript with animations
document.addEventListener('DOMContentLoaded', function() {
    const editButtons = document.querySelectorAll(".editBtn");
    
    editButtons.forEach(btn => {
        btn.addEventListener("click", () => {
            const modal = new bootstrap.Modal(document.getElementById("editUserModal"));
            
            // Set form values
            document.getElementById("edit_fullname").value = btn.dataset.fullname;
            document.getElementById("edit_email").value = btn.dataset.email;
            document.getElementById("edit_role").value = btn.dataset.role;
            document.getElementById("edit_status").value = btn.dataset.status;
            document.getElementById("edit_user_id").value = btn.dataset.id;
            document.getElementById("edit_empid").value = btn.dataset.empid;

            const deptSelect = document.getElementById("edit_department");
            const subRoleSelect = document.getElementById("edit_sub_role");
            const empTypeSelect = document.getElementById("edit_employment_type");

            const deptName = btn.dataset.departmentname;
            const subRoleText = btn.dataset.subrole;
            const empTypeName = btn.dataset.employmenttypename;

            // Set department
            if (deptName) {
                Array.from(deptSelect.options).forEach(opt => {
                    if (opt.textContent === deptName) {
                        deptSelect.value = opt.value;
                    }
                });
            }

            // Load positions for selected department
            if (deptSelect.value) {
                subRoleSelect.innerHTML = "<option>Loading...</option>";
                fetch("?ajax=positions&deptID=" + deptSelect.value)
                    .then(res => res.json())
                    .then(data => {
                        subRoleSelect.innerHTML = "<option value=''>-- Select Sub-role --</option>";
                        data.forEach(pos => {
                            const opt = document.createElement('option');
                            opt.value = pos.positionID;
                            opt.textContent = pos.position_title;
                            subRoleSelect.appendChild(opt);
                        });
                        if (subRoleText) {
                            Array.from(subRoleSelect.options).forEach(o => {
                                if (o.textContent === subRoleText) subRoleSelect.value = o.value;
                            });
                        }
                    });
            }

            // Set employment type
            if (empTypeName) {
                Array.from(empTypeSelect.options).forEach(opt => {
                    if (opt.textContent === empTypeName) {
                        empTypeSelect.value = opt.value;
                    }
                });
            }
            
            modal.show();
        });
    });

    // Department change handlers
    document.getElementById('department').addEventListener('change', function () {
        let deptID = this.value;
        let subRoleDropdown = document.getElementById('sub_role');

        if (!deptID) {
            subRoleDropdown.innerHTML = "<option value=''>-- Select Position --</option>";
            return;
        }

        subRoleDropdown.innerHTML = "<option>Loading...</option>";

        fetch("?ajax=positions&deptID=" + deptID)
            .then(res => res.json())
            .then(data => {
                subRoleDropdown.innerHTML = "<option value=''>-- Select Position --</option>";
                data.forEach(pos => {
                    subRoleDropdown.innerHTML += 
                        `<option value="${pos.positionID}">${pos.position_title}</option>`;
                });
            });
    });

    document.getElementById('edit_department').addEventListener('change', function () {
        let deptID = this.value;
        let subRoleDropdown = document.getElementById('edit_sub_role');
        if (!deptID) {
            subRoleDropdown.innerHTML = "<option value=''>-- Select Sub-role --</option>";
            return;
        }
        subRoleDropdown.innerHTML = "<option>Loading...</option>";
        fetch("?ajax=positions&deptID=" + deptID)
            .then(res => res.json())
            .then(data => {
                subRoleDropdown.innerHTML = "<option value=''>-- Select Sub-role --</option>";
                data.forEach(pos => {
                    const opt = document.createElement('option');
                    opt.value = pos.positionID;
                    opt.textContent = pos.position_title;
                    subRoleDropdown.appendChild(opt);
                });
            });
    });

    // Add hover effects to all buttons
    const buttons = document.querySelectorAll('.btn');
    buttons.forEach(button => {
        button.addEventListener('mouseenter', function() {
            this.style.transform = 'translateY(-2px)';
        });
        
        button.addEventListener('mouseleave', function() {
            this.style.transform = 'translateY(0)';
        });
    });
});
</script>

</body>
</html>