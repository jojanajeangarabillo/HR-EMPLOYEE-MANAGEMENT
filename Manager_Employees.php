<?php
session_start();
require 'admin/db.connect.php';

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

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

$config = require 'mailer-config.php';


// MENUS

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

$limit = 10;
$page = isset($_GET['page']) ? max(1, (int) $_GET['page']) : 1;
$start = ($page - 1) * $limit;

$countRes = $conn->query("SELECT COUNT(*) AS count FROM employee");
$countRow = $countRes ? $countRes->fetch_assoc() : ['count' => 0];
$totalEmployees = (int) ($countRow['count'] ?? 0);
$pages = max(1, (int) ceil($totalEmployees / $limit));

$employee = [];
$stmt = $conn->prepare("SELECT e.empID, e.fullname, e.department, e.position, e.type_name, e.email_address, u.status FROM employee e LEFT JOIN user u ON u.applicant_employee_id = e.empID ORDER BY e.fullname ASC LIMIT ?, ?");
$stmt->bind_param("ii", $start, $limit);
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
  $employee[] = $row;
}
$stmt->close();

if (isset($_POST['ajax_set_inactive'])) {
  header('Content-Type: application/json');
  $empID = $_POST['empID'] ?? null;
  if (!$empID) {
    echo json_encode(['status' => 'error', 'message' => 'Missing employee ID']);
    exit;
  }
  $upd = $conn->prepare("UPDATE user SET status = 'Inactive' WHERE applicant_employee_id = ?");
  $upd->bind_param("s", $empID);
  $ok = $upd->execute();
  echo json_encode(['status' => $ok ? 'success' : 'error']);
  exit;
}

if (isset($_POST['ajax_archive_employee'])) {
  header('Content-Type: application/json');
  $empID = $_POST['empID'] ?? null;
  if (!$empID) {
    echo json_encode(['status' => 'error', 'message' => 'Missing employee ID']);
    exit;
  }
  $usr = $conn->prepare("SELECT user_id, applicant_employee_id, email, password, role, fullname, status, created_at, profile_pic, reset_token, token_expiry, sub_role FROM user WHERE applicant_employee_id = ? LIMIT 1");
  $usr->bind_param("s", $empID);
  $usr->execute();
  $u = $usr->get_result()->fetch_assoc();
  if (!$u) {
    echo json_encode(['status' => 'error', 'message' => 'User not found']);
    exit;
  }
  $ins = $conn->prepare("INSERT INTO user_archive (user_id, applicant_employee_id, email, password, role, fullname, status, created_at, profile_pic, reset_token, token_expiry, sub_role) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
  $ins->bind_param(
    "ssssssssssss",
    $u['user_id'],
    $u['applicant_employee_id'],
    $u['email'],
    $u['password'],
    $u['role'],
    $u['fullname'],
    $u['status'],
    $u['created_at'],
    $u['profile_pic'],
    $u['reset_token'],
    $u['token_expiry'],
    $u['sub_role']
  );
  $okIns = $ins->execute();
  if (!$okIns) {
    echo json_encode(['status' => 'error', 'message' => 'Archive insert failed']);
    exit;
  }
  $delU = $conn->prepare("DELETE FROM user WHERE applicant_employee_id = ?");
  $delU->bind_param("s", $empID);
  $okDelU = $delU->execute();
  $delE = $conn->prepare("DELETE FROM employee WHERE empID = ?");
  $delE->bind_param("s", $empID);
  $okDelE = $delE->execute();
  $ok = $okDelU && $okDelE;
  echo json_encode(['status' => $ok ? 'success' : 'error']);
  exit;
}

// Handle sending message via PHPMailer
if (isset($_POST['send_message'])) {
  require 'PHPMailer-master/src/Exception.php';
  require 'PHPMailer-master/src/PHPMailer.php';
  require 'PHPMailer-master/src/SMTP.php';


  $mail = new PHPMailer(true);

  $email = $_POST['email'];
  $subject = $_POST['subject'];
  $message = $_POST['message'];

  try {
    $mail->isSMTP();
    $mail->Host = $config['host'];
    $mail->SMTPAuth = true;
    $mail->Username = $config['username'];
    $mail->Password = $config['password'];
    $mail->SMTPSecure = $config['encryption'];
    $mail->Port = $config['port'];

    $mail->setFrom($config['from_email'], $config['from_name']);
    $mail->addAddress($email);

    $mail->isHTML(true);
    $mail->Subject = $subject;
    $mail->Body = nl2br($message);

    $mail->send();
    $_SESSION['flash_success'] = "Message sent successfully!";
  } catch (Exception $e) {
    $_SESSION['flash_error'] = "Message could not be sent. Error: " . $mail->ErrorInfo;
  }

  header("Location: Manager_Employees.php");
  exit;
}
?>




<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Manager Employees</title>

  <link rel="stylesheet" href="manager-sidebar.css">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/7.0.1/css/all.min.css">
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
  <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

  <style>
    :root {
      --primary-blue: #1E3A8A;
      --primary-light: #3B82F6;
      --primary-dark: #1E40AF;
      --success-color: #10B981;
      --danger-color: #EF4444;
      --warning-color: #F59E0B;
      --light-bg: #F8FAFC;
      --card-shadow: 0 4px 20px rgba(30, 58, 138, 0.08);
      --hover-shadow: 0 8px 30px rgba(30, 58, 138, 0.12);
    }

    body {
      font-family: 'Poppins', 'Roboto', sans-serif;
      margin: 0;
      display: flex;
      background-color: var(--light-bg);
      color: #111827;
      min-height: 100vh;
    }

    .sidebar-profile-img {
      width: 130px;
      height: 130px;
      border-radius: 50%;
      object-fit: cover;
      margin-bottom: 20px;
      border: 4px solid var(--primary-blue);
      transition: transform 0.3s ease, border-color 0.3s ease;
    }

    .sidebar-profile-img:hover {
      transform: scale(1.05);
      border-color: var(--primary-light);
    }

    .main-content {
      flex: 1;
      padding: 30px;
      margin-left: 300px;
      display: flex;
      flex-direction: column;
      transition: margin-left 0.3s ease;
      max-width: calc(100% - 300px);
    }

    @media (max-width: 992px) {
      .main-content {
        margin-left: 0;
        max-width: 100%;
        padding: 20px;
      }
    }

    /* Header Section */
    .main-content-header {
      margin-bottom: 30px;
      padding-bottom: 20px;
      border-bottom: 2px solid #EFF6FF;
    }

    .main-content-header h1 {
      font-size: 28px;
      color: var(--primary-blue);
      font-weight: 700;
      margin: 0;
      display: flex;
      align-items: center;
      gap: 12px;
    }

    .main-content-header h1 i {
      color: var(--primary-blue);
      font-size: 24px;
    }

    /* Stats Cards */
    .stats-section {
      margin-bottom: 30px;
    }

    .stats-grid {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
      gap: 24px;
      margin-bottom: 30px;
    }

    .stat-card {
      background: white;
      border-radius: 16px;
      padding: 25px;
      box-shadow: var(--card-shadow);
      border-top: 4px solid var(--primary-blue);
      transition: all 0.3s ease;
      position: relative;
      overflow: hidden;
    }

    .stat-card:hover {
      transform: translateY(-5px);
      box-shadow: var(--hover-shadow);
    }

    .stat-card::before {
      content: '';
      position: absolute;
      top: 0;
      left: 0;
      width: 100%;
      height: 4px;
      background: linear-gradient(90deg, var(--primary-blue), var(--primary-light));
    }

    .stat-icon {
      width: 50px;
      height: 50px;
      border-radius: 12px;
      background: linear-gradient(135deg, #EFF6FF, #DBEAFE);
      display: flex;
      align-items: center;
      justify-content: center;
      margin-bottom: 20px;
      color: var(--primary-blue);
      font-size: 22px;
    }

    .stat-label {
      font-size: 16px;
      color: #6B7280;
      font-weight: 500;
      margin-bottom: 8px;
      display: flex;
      align-items: center;
      gap: 8px;
    }

    .stat-value {
      font-size: 32px;
      font-weight: 700;
      color: var(--primary-blue);
      margin: 0;
    }

    .stat-change {
      font-size: 14px;
      color: var(--success-color);
      margin-top: 8px;
      display: flex;
      align-items: center;
      gap: 4px;
    }

    /* Table Container */
    .table-container {
      background: white;
      border-radius: 20px;
      box-shadow: var(--card-shadow);
      padding: 30px;
      margin-top: 20px;
      transition: all 0.3s ease;
    }

    .table-container:hover {
      box-shadow: var(--hover-shadow);
    }

    /* Controls Bar */
    .controls-bar {
      display: flex;
      justify-content: space-between;
      align-items: center;
      margin-bottom: 25px;
      gap: 20px;
      flex-wrap: wrap;
    }

    .search-box {
      flex: 1;
      min-width: 280px;
      position: relative;
    }

    .search-box input {
      width: 100%;
      padding: 14px 20px 14px 48px;
      border: 2px solid #E5E7EB;
      border-radius: 12px;
      font-size: 15px;
      font-family: 'Poppins', sans-serif;
      transition: all 0.3s ease;
      background-color: white;
    }

    .search-box input:focus {
      outline: none;
      border-color: var(--primary-blue);
      box-shadow: 0 0 0 3px rgba(30, 58, 138, 0.1);
    }

    .search-box i {
      position: absolute;
      left: 18px;
      top: 50%;
      transform: translateY(-50%);
      color: #9CA3AF;
      font-size: 16px;
    }

    .filter-box {
      min-width: 180px;
    }

    .filter-select {
      width: 100%;
      padding: 14px 20px;
      border: 2px solid #E5E7EB;
      border-radius: 12px;
      font-size: 15px;
      font-family: 'Poppins', sans-serif;
      background-color: white;
      cursor: pointer;
      transition: all 0.3s ease;
    }

    .filter-select:focus {
      outline: none;
      border-color: var(--primary-blue);
      box-shadow: 0 0 0 3px rgba(30, 58, 138, 0.1);
    }

    /* Table Styling */
    .table-wrapper {
      border-radius: 12px;
      overflow: hidden;
      border: 1px solid #E5E7EB;
    }

    .table {
      margin-bottom: 0;
      border-collapse: separate;
      border-spacing: 0;
    }

    .table thead {
      background: linear-gradient(135deg, var(--primary-blue), var(--primary-dark));
    }

    .table thead th {
      border: none;
      padding: 18px 20px;
      font-weight: 600;
      color: white;
      font-size: 15px;
      text-transform: uppercase;
      letter-spacing: 0.5px;
    }

    .table thead th:first-child {
      border-top-left-radius: 12px;
    }

    .table thead th:last-child {
      border-top-right-radius: 12px;
    }

    .table tbody td {
      padding: 18px 20px;
      border-bottom: 1px solid #F3F4F6;
      vertical-align: middle;
      font-size: 14px;
      color: #374151;
    }

    .table tbody tr:last-child td {
      border-bottom: none;
    }

    .table tbody tr:hover {
      background-color: #F8FAFC;
      transition: background-color 0.2s ease;
    }

    .table tbody tr:nth-child(even) {
      background-color: #F9FAFB;
    }

    /* Status Badges */
    .status-badge {
      display: inline-flex;
      align-items: center;
      gap: 6px;
      padding: 6px 14px;
      border-radius: 20px;
      font-size: 13px;
      font-weight: 600;
      text-transform: uppercase;
      letter-spacing: 0.3px;
    }

    .status-badge.active {
      background-color: #D1FAE5;
      color: #065F46;
    }

    .status-badge.inactive {
      background-color: #FEE2E2;
      color: #991B1B;
    }

    .status-badge.pending {
      background-color: #FEF3C7;
      color: #92400E;
    }

    .status-badge .status-dot {
      width: 8px;
      height: 8px;
      border-radius: 50%;
      display: inline-block;
    }

    .status-badge.active .status-dot {
      background-color: #10B981;
    }

    .status-badge.inactive .status-dot {
      background-color: #EF4444;
    }

    .status-badge.pending .status-dot {
      background-color: #F59E0B;
    }

    /* Action Icons */
    .action-cell {
      display: flex;
      justify-content: center;
      gap: 10px;
    }

    .action-btn {
      width: 36px;
      height: 36px;
      border-radius: 10px;
      display: flex;
      align-items: center;
      justify-content: center;
      text-decoration: none;
      transition: all 0.2s ease;
      color: white;
      border: none;
      cursor: pointer;
    }

    .action-btn.view {
      background-color: var(--primary-blue);
    }

    .action-btn.message {
      background-color: var(--success-color);
    }

    .action-btn.status {
      background-color: #8B5CF6;
    }

    .action-btn.archive {
      background-color: var(--danger-color);
    }

    .action-btn:hover {
      transform: translateY(-2px) scale(1.05);
      box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
    }

    .action-btn.view:hover {
      background-color: var(--primary-dark);
    }

    .action-btn.message:hover {
      background-color: #059669;
    }

    .action-btn.status:hover {
      background-color: #7C3AED;
    }

    .action-btn.archive:hover {
      background-color: #DC2626;
    }

    /* Pagination */
    .pagination-container {
      display: flex;
      justify-content: center;
      margin-top: 30px;
    }

    .pagination {
      background: white;
      padding: 10px;
      border-radius: 12px;
      box-shadow: 0 4px 12px rgba(30, 58, 138, 0.08);
    }

    .page-link {
      padding: 10px 18px;
      margin: 0 4px;
      border-radius: 8px;
      border: 2px solid #E5E7EB;
      color: var(--primary-blue);
      font-weight: 500;
      transition: all 0.2s ease;
    }

    .page-link:hover {
      background-color: var(--primary-blue);
      color: white;
      border-color: var(--primary-blue);
      transform: translateY(-2px);
    }

    .page-item.active .page-link {
      background-color: var(--primary-blue);
      border-color: var(--primary-blue);
      color: white;
    }

    /* Alerts */
    .alert {
      border-radius: 12px;
      border: none;
      padding: 16px 20px;
      margin-bottom: 20px;
      box-shadow: 0 4px 12px rgba(0,0,0,0.05);
      display: flex;
      align-items: center;
      gap: 12px;
    }

    .alert-success {
      background-color: #D1FAE5;
      color: #065F46;
      border-left: 4px solid #10B981;
    }

    .alert-danger {
      background-color: #FEE2E2;
      color: #991B1B;
      border-left: 4px solid #EF4444;
    }

    .alert i {
      font-size: 20px;
    }

    /* Modals */
    .modal-content {
      border-radius: 20px;
      border: none;
      box-shadow: 0 20px 60px rgba(0,0,0,0.15);
    }

    .modal-header {
      background: linear-gradient(135deg, var(--primary-blue), var(--primary-dark));
      color: white;
      border-radius: 20px 20px 0 0 !important;
      padding: 25px 30px;
      border: none;
    }

    .modal-title {
      font-weight: 600;
      font-size: 22px;
    }

    .modal-body {
      padding: 30px;
    }

    .modal-footer {
      padding: 20px 30px;
      border-top: 1px solid #E5E7EB;
    }

    /* Form Elements in Modals */
    .form-control, .form-select {
      border: 2px solid #E5E7EB;
      border-radius: 10px;
      padding: 12px 16px;
      font-size: 15px;
      transition: all 0.3s ease;
    }

    .form-control:focus, .form-select:focus {
      border-color: var(--primary-blue);
      box-shadow: 0 0 0 3px rgba(30, 58, 138, 0.1);
    }

    .btn-primary {
      background: linear-gradient(135deg, var(--primary-blue), var(--primary-dark));
      border: none;
      border-radius: 10px;
      padding: 12px 28px;
      font-weight: 600;
      transition: all 0.3s ease;
    }

    .btn-primary:hover {
      transform: translateY(-2px);
      box-shadow: 0 8px 20px rgba(30, 58, 138, 0.25);
    }

    .btn-danger {
      background: linear-gradient(135deg, #EF4444, #DC2626);
      border: none;
      border-radius: 10px;
      padding: 12px 28px;
      font-weight: 600;
      transition: all 0.3s ease;
    }

    .btn-danger:hover {
      transform: translateY(-2px);
      box-shadow: 0 8px 20px rgba(239, 68, 68, 0.25);
    }

    /* Loading Animation */
    .loading {
      display: none;
      text-align: center;
      padding: 20px;
    }

    .spinner {
      width: 40px;
      height: 40px;
      border: 4px solid #E5E7EB;
      border-top: 4px solid var(--primary-blue);
      border-radius: 50%;
      animation: spin 1s linear infinite;
      margin: 0 auto;
    }

    @keyframes spin {
      0% { transform: rotate(0deg); }
      100% { transform: rotate(360deg); }
    }
  </style>
</head>

<body>
  <!-- SIDEBAR -->
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
        <li><a href="<?php echo $link; ?>"><i class="fa-solid <?php echo $icons[$label] ?? 'fa-circle'; ?>"></i><?php echo $label; ?></a></li>
      <?php endforeach; ?>
    </ul>
  </div>

  <!-- MAIN CONTENT -->
  <div class="main-content">
    <div class="main-content-header">
      <h1><i class="fas fa-users"></i> Employee Management</h1>
    </div>

    <!-- Flash Messages -->
    <?php if (isset($_SESSION['flash_success'])): ?>
      <div class="alert alert-success alert-dismissible fade show" role="alert">
        <i class="fas fa-check-circle"></i>
        <?php
        echo $_SESSION['flash_success'];
        unset($_SESSION['flash_success']);
        ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
      </div>
    <?php endif; ?>

    <?php if (isset($_SESSION['flash_error'])): ?>
      <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <i class="fas fa-exclamation-circle"></i>
        <?php
        echo $_SESSION['flash_error'];
        unset($_SESSION['flash_error']);
        ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
      </div>
    <?php endif; ?>


    <!-- Table Container -->
    <div class="table-container">
      <!-- Controls -->
      <div class="controls-bar">
        <div class="search-box">
          <i class="fas fa-search"></i>
          <input type="text" id="searchInput" placeholder="Search employees by name, ID, department, or email...">
        </div>
        
        <div class="filter-box">
          <select id="deptFilter" class="filter-select">
            <option value="">All Departments</option>
          </select>
        </div>

        <div class="filter-box">
          <select id="statusFilter" class="filter-select">
            <option value="">All Status</option>
            <option value="Active">Active</option>
            <option value="Inactive">Inactive</option>
          </select>
        </div>
      </div>

      <!-- Loading Indicator -->
      <div class="loading" id="loadingIndicator">
        <div class="spinner"></div>
        <p class="mt-3 text-muted">Loading employees...</p>
      </div>

      <!-- Table -->
      <div class="table-wrapper">
        <div class="table-responsive">
          <table class="table table-hover">
            <thead>
              <tr>
                <th>Employee ID</th>
                <th>Full Name</th>
                <th>Department</th>
                <th>Position</th>
                <th>Employment Type</th>
                <th>Email Address</th>
                <th>Status</th>
                <th>Actions</th>
              </tr>
            </thead>
            <tbody id="employeeTable">
              <?php foreach ($employee as $emp): ?>
                <?php 
                  $status = $emp['status'] ?? 'Active';
                  $statusClass = strtolower($status) === 'inactive' ? 'inactive' : 'active';
                ?>
                <tr>
                  <td><strong><?php echo htmlspecialchars($emp['empID']); ?></strong></td>
                  <td>
                    <div class="d-flex align-items-center">
                      <div class="me-3">
                        <i class="fas fa-user-circle" style="font-size: 24px; color: var(--primary-blue);"></i>
                      </div>
                      <div>
                        <div class="fw-medium"><?php echo htmlspecialchars($emp['fullname']); ?></div>
                      </div>
                    </div>
                  </td>
                  <td><?php echo htmlspecialchars($emp['department']); ?></td>
                  <td><?php echo htmlspecialchars($emp['position']); ?></td>
                  <td>
                    <span class="badge bg-light text-dark border">
                      <?php echo htmlspecialchars($emp['type_name']); ?>
                    </span>
                  </td>
                  <td>
                    <a href="mailto:<?php echo htmlspecialchars($emp['email_address']); ?>" class="text-primary">
                      <i class="fas fa-envelope me-2"></i>
                      <?php echo htmlspecialchars($emp['email_address']); ?>
                    </a>
                  </td>
                  <td>
                    <span class="status-badge <?php echo $statusClass; ?>">
                      <span class="status-dot"></span>
                      <?php echo htmlspecialchars($status); ?>
                    </span>
                  </td>
                  <td class="action-cell">
                    <a href="#viewModal" class="action-btn view" 
                       onclick="openViewModal('<?php echo $emp['empID']; ?>',
                               '<?php echo addslashes($emp['fullname']); ?>',
                               '<?php echo addslashes($emp['department']); ?>',
                               '<?php echo addslashes($emp['position']); ?>',
                               '<?php echo addslashes($emp['type_name']); ?>',
                               '<?php echo addslashes($emp['email_address']); ?>')"
                       title="View Details">
                      <i class="fas fa-eye"></i>
                    </a>

                    <a href="#messageModal" class="action-btn message"
                       onclick="openMessageModal('<?php echo addslashes($emp['email_address']); ?>')"
                       title="Send Message">
                      <i class="fas fa-envelope"></i>
                    </a>

                    <?php if ($statusClass === 'active'): ?>
                      <a href="#inactiveModal" class="action-btn status" 
                         data-empid="<?php echo $emp['empID']; ?>"
                         title="Set to Inactive">
                        <i class="fas fa-toggle-on"></i>
                      </a>
                    <?php else: ?>
                      <a href="#archiveModal" class="action-btn archive" 
                         data-empid="<?php echo $emp['empID']; ?>"
                         title="Archive Employee">
                        <i class="fas fa-trash-alt"></i>
                      </a>
                    <?php endif; ?>
                  </td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      </div>

      <!-- No Results -->
      <div id="noResults" class="text-center py-5" style="display: none;">
        <i class="fas fa-search" style="font-size: 48px; color: #9CA3AF;"></i>
        <h4 class="mt-3 text-muted">No employees found</h4>
        <p class="text-muted">Try adjusting your search or filter</p>
      </div>

      <!-- Pagination -->
      <div class="pagination-container">
        <nav aria-label="Employee pagination">
          <ul class="pagination">
            <li class="page-item <?php echo $page <= 1 ? 'disabled' : ''; ?>">
              <a class="page-link" href="?page=<?php echo max(1, $page - 1); ?>">
                <i class="fas fa-chevron-left"></i> Previous
              </a>
            </li>
            <?php for ($p = 1; $p <= $pages; $p++): ?>
              <li class="page-item <?php echo $p === $page ? 'active' : ''; ?>">
                <a class="page-link" href="?page=<?php echo $p; ?>"><?php echo $p; ?></a>
              </li>
            <?php endfor; ?>
            <li class="page-item <?php echo $page >= $pages ? 'disabled' : ''; ?>">
              <a class="page-link" href="?page=<?php echo min($pages, $page + 1); ?>">
                Next <i class="fas fa-chevron-right"></i>
              </a>
            </li>
          </ul>
        </nav>
      </div>
    </div>
  </div>

  <!-- View Modal -->
  <div class="modal fade" id="viewModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title"><i class="fas fa-user-circle me-2"></i> Employee Details</h5>
          <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <div class="row">
            <div class="col-md-6 mb-3">
              <label class="form-label fw-semibold text-muted">Employee ID</label>
              <div class="form-control bg-light" id="v_id"></div>
            </div>
            <div class="col-md-6 mb-3">
              <label class="form-label fw-semibold text-muted">Full Name</label>
              <div class="form-control bg-light" id="v_name"></div>
            </div>
            <div class="col-md-6 mb-3">
              <label class="form-label fw-semibold text-muted">Department</label>
              <div class="form-control bg-light" id="v_dept"></div>
            </div>
            <div class="col-md-6 mb-3">
              <label class="form-label fw-semibold text-muted">Position</label>
              <div class="form-control bg-light" id="v_pos"></div>
            </div>
            <div class="col-md-6 mb-3">
              <label class="form-label fw-semibold text-muted">Employment Type</label>
              <div class="form-control bg-light" id="v_type"></div>
            </div>
            <div class="col-md-6 mb-3">
              <label class="form-label fw-semibold text-muted">Email Address</label>
              <div class="form-control bg-light" id="v_email"></div>
            </div>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
        </div>
      </div>
    </div>
  </div>

  <!-- Message Modal -->
  <div class="modal fade" id="messageModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title"><i class="fas fa-envelope me-2"></i> Send Message</h5>
          <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <form action="Manager_Employees.php" method="POST">
          <div class="modal-body">
            <input type="hidden" id="m_email" name="email">
            <input type="hidden" name="send_message" value="1">
            
            <div class="mb-3">
              <label class="form-label fw-semibold">Recipient Email</label>
              <input type="text" class="form-control bg-light" id="recipientEmail" readonly>
            </div>

            <div class="mb-3">
              <label class="form-label fw-semibold">Subject</label>
              <input type="text" name="subject" class="form-control" placeholder="Enter message subject" required>
            </div>

            <div class="mb-3">
              <label class="form-label fw-semibold">Message</label>
              <textarea name="message" class="form-control" rows="5" placeholder="Type your message here..." required></textarea>
            </div>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
            <button type="submit" class="btn btn-primary">
              <i class="fas fa-paper-plane me-2"></i> Send Email
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>

  <!-- Inactive Confirm Modal -->
  <div class="modal fade" id="inactiveModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title"><i class="fas fa-exclamation-triangle me-2 text-warning"></i> Confirm Inactive</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body text-center">
          <div class="mb-4">
            <i class="fas fa-user-slash" style="font-size: 48px; color: var(--warning-color);"></i>
          </div>
          <h4>Set Employee as Inactive?</h4>
          <p class="text-muted">This employee will no longer be able to access the system. This action can be reversed.</p>
        </div>
        <div class="modal-footer justify-content-center">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
          <button type="button" class="btn btn-warning" id="confirmInactiveBtn">Yes, Set as Inactive</button>
        </div>
      </div>
    </div>
  </div>

  <!-- Archive Confirm Modal -->
  <div class="modal fade" id="archiveModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title"><i class="fas fa-archive me-2 text-danger"></i> Confirm Archive</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body text-center">
          <div class="mb-4">
            <i class="fas fa-trash-alt" style="font-size: 48px; color: var(--danger-color);"></i>
          </div>
          <h4>Archive Employee?</h4>
          <p class="text-muted">This will permanently move the employee to archive. This action cannot be undone.</p>
        </div>
        <div class="modal-footer justify-content-center">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
          <button type="button" class="btn btn-danger" id="confirmArchiveBtn">Yes, Archive Employee</button>
        </div>
      </div>
    </div>
  </div>

  <!-- Hidden Forms for AJAX -->
  <form method="POST" id="inactiveForm" style="display:none;">
    <input type="hidden" name="empID" id="inactiveEmpID">
    <input type="hidden" name="ajax_set_inactive" value="1">
  </form>
  <form method="POST" id="archiveForm" style="display:none;">
    <input type="hidden" name="empID" id="archiveEmpID">
    <input type="hidden" name="ajax_archive_employee" value="1">
  </form>

  <!-- Bootstrap JS -->
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

  <script>
    $(document).ready(function () {
      // Initialize filter dropdowns
      let departments = new Set();
      $("#employeeTable tr").each(function () {
        let dept = $(this).find("td:nth-child(3)").text().trim();
        if (dept !== "") {
          departments.add(dept);
        }
      });

      departments.forEach(function (d) {
        $("#deptFilter").append(`<option value="${d}">${d}</option>`);
      });

      // Event listeners for filters
      $("#searchInput, #deptFilter, #statusFilter").on('input change', filterTable);
      
      // Initialize modals
      const viewModal = new bootstrap.Modal(document.getElementById('viewModal'));
      const messageModal = new bootstrap.Modal(document.getElementById('messageModal'));
      const inactiveModal = new bootstrap.Modal(document.getElementById('inactiveModal'));
      const archiveModal = new bootstrap.Modal(document.getElementById('archiveModal'));

      let currentEmpId = null;
      let currentRow = null;

      // View modal function
      window.openViewModal = function(id, name, dept, pos, type, email) {
        $("#v_id").text(id);
        $("#v_name").text(name);
        $("#v_dept").text(dept);
        $("#v_pos").text(pos);
        $("#v_type").text(type);
        $("#v_email").text(email);
        viewModal.show();
      };

      // Message modal function
      window.openMessageModal = function(email) {
        $("#m_email").val(email);
        $("#recipientEmail").val(email);
        messageModal.show();
      };

      // Inactive button click
      $(document).on('click', '.action-btn.status', function (e) {
        e.preventDefault();
        currentEmpId = $(this).data('empid');
        currentRow = $(this).closest('tr');
        inactiveModal.show();
      });

      // Archive button click
      $(document).on('click', '.action-btn.archive', function (e) {
        e.preventDefault();
        currentEmpId = $(this).data('empid');
        currentRow = $(this).closest('tr');
        archiveModal.show();
      });

      // Confirm inactive
      $('#confirmInactiveBtn').on('click', function () {
        $('#inactiveEmpID').val(currentEmpId);
        $('#loadingIndicator').show();
        
        $.post('Manager_Employees.php', $('#inactiveForm').serialize(), function (resp) {
          if (resp && resp.status === 'success') {
            // Update status badge
            currentRow.find('.status-badge')
              .removeClass('active')
              .addClass('inactive')
              .html('<span class="status-dot"></span>Inactive');
            
            // Update action button
            const actionCell = currentRow.find('.action-cell');
            actionCell.find('.action-btn.status')
              .removeClass('status')
              .addClass('archive')
              .html('<i class="fas fa-trash-alt"></i>')
              .attr('title', 'Archive Employee')
              .attr('data-empid', currentEmpId);
            
            // Show success message
            showAlert('Employee status updated to Inactive', 'success');
          } else {
            showAlert('Failed to update employee status', 'error');
          }
          $('#loadingIndicator').hide();
          inactiveModal.hide();
        }, 'json');
      });

      // Confirm archive
      $('#confirmArchiveBtn').on('click', function () {
        $('#archiveEmpID').val(currentEmpId);
        $('#loadingIndicator').show();
        
        $.post('Manager_Employees.php', $('#archiveForm').serialize(), function (resp) {
          if (resp && resp.status === 'success') {
            currentRow.fadeOut(300, function() {
              $(this).remove();
              updateStats();
              showAlert('Employee archived successfully', 'success');
            });
          } else {
            showAlert('Failed to archive employee', 'error');
          }
          $('#loadingIndicator').hide();
          archiveModal.hide();
        }, 'json');
      });

      // Initialize table filtering
      filterTable();
    });

    function filterTable() {
      const search = $("#searchInput").val().toLowerCase();
      const deptFilter = $("#deptFilter").val().toLowerCase();
      const statusFilter = $("#statusFilter").val().toLowerCase();

      let visibleRows = 0;

      $("#employeeTable tr").each(function () {
        let row = $(this);
        let textMatch = false;
        
        // Check search across all columns except Actions
        $(this).find("td").each(function (index) {
          if (index < 7) { // All columns except Actions (8th column)
            let cellText = $(this).text().toLowerCase();
            if (cellText.includes(search)) {
              textMatch = true;
            }
          }
        });

        // Check department filter
        let rowDept = row.find("td:nth-child(3)").text().toLowerCase();
        let deptMatch = deptFilter === "" || rowDept === deptFilter;

        // Check status filter
        let rowStatus = row.find(".status-badge").text().trim().toLowerCase();
        let statusMatch = statusFilter === "" || rowStatus === statusFilter.toLowerCase();

        // Show only if all match
        if (textMatch && deptMatch && statusMatch) {
          row.show();
          visibleRows++;
        } else {
          row.hide();
        }
      });

      // Show/hide no results message
      if (visibleRows === 0) {
        $("#noResults").show();
      } else {
        $("#noResults").hide();
      }
    }

    function updateStats() {
      // Update active count in stats
      const activeCount = $("#employeeTable tr:visible .status-badge.active").length;
      $(".stat-card:nth-child(2) .stat-value").text(activeCount);
      
      // Update total count
      const totalCount = $("#employeeTable tr:visible").length;
      $(".stat-card:first-child .stat-value").text(totalCount);
    }

    function showAlert(message, type) {
      const alertClass = type === 'success' ? 'alert-success' : 'alert-danger';
      const icon = type === 'success' ? 'fa-check-circle' : 'fa-exclamation-circle';
      
      const alertHtml = `
        <div class="alert ${alertClass} alert-dismissible fade show" role="alert">
          <i class="fas ${icon}"></i>
          ${message}
          <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
      `;
      
      $('.main-content-header').after(alertHtml);
      
      // Auto remove after 5 seconds
      setTimeout(() => {
        $('.alert').alert('close');
      }, 5000);
    }
  </script>

</body>

</html>