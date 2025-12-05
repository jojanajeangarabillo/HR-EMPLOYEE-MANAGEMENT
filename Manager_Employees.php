<?php
session_start();
require 'admin/db.connect.php';

// Manager name
$managername = $_SESSION['fullname'] ?? "Manager";
$employeeID = $_SESSION['applicant_employee_id'] ?? null;

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

// Fetch all departments for the filter dropdown
$deptRes = $conn->query("SELECT deptID, deptName FROM department ORDER BY deptName");
$allDepartments = [];
while ($dept = $deptRes->fetch_assoc()) {
    $allDepartments[] = $dept;
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

// Pagination and filtering
$limit = 10;
$page = isset($_GET['page']) ? max(1, (int) $_GET['page']) : 1;
$start = ($page - 1) * $limit;

// Get filter parameters
$search = isset($_GET['search']) ? $conn->real_escape_string($_GET['search']) : '';
$deptFilter = isset($_GET['department']) ? $conn->real_escape_string($_GET['department']) : '';
$statusFilter = isset($_GET['status']) ? $_GET['status'] : '';

// Build query conditions
$whereConditions = [];
$params = [];
$types = '';

if (!empty($search)) {
    $whereConditions[] = "(e.fullname LIKE ? OR e.empID LIKE ? OR e.department LIKE ? OR e.position LIKE ? OR e.email_address LIKE ?)";
    $searchParam = "%$search%";
    $params = array_merge($params, [$searchParam, $searchParam, $searchParam, $searchParam, $searchParam]);
    $types .= 'sssss';
}

if (!empty($deptFilter)) {
    $whereConditions[] = "e.department = ?";
    $params[] = $deptFilter;
    $types .= 's';
}

if (!empty($statusFilter)) {
    if ($statusFilter === 'Active') {
        $whereConditions[] = "(u.status IS NULL OR u.status = 'Active')";
    } else {
        $whereConditions[] = "u.status = ?";
        $params[] = $statusFilter;
        $types .= 's';
    }
}

// Build WHERE clause
$whereClause = '';
if (!empty($whereConditions)) {
    $whereClause = ' WHERE ' . implode(' AND ', $whereConditions);
}

// Count query
$countQuery = "SELECT COUNT(*) AS count FROM employee e LEFT JOIN user u ON u.applicant_employee_id = e.empID $whereClause";
$countStmt = $conn->prepare($countQuery);
if (!empty($params)) {
    $countStmt->bind_param($types, ...$params);
}
$countStmt->execute();
$countResult = $countStmt->get_result();
$countRow = $countResult->fetch_assoc();
$totalEmployees = (int) ($countRow['count'] ?? 0);
$pages = max(1, (int) ceil($totalEmployees / $limit));

// Main query
$mainQuery = "SELECT e.empID, e.fullname, e.department, e.position, e.type_name, e.email_address, 
                     COALESCE(u.status, 'Active') as status 
              FROM employee e 
              LEFT JOIN user u ON u.applicant_employee_id = e.empID 
              $whereClause 
              ORDER BY e.fullname ASC 
              LIMIT ?, ?";

$stmt = $conn->prepare($mainQuery);
if (!empty($params)) {
    $stmt->bind_param($types . 'ii', ...array_merge($params, [$start, $limit]));
} else {
    $stmt->bind_param("ii", $start, $limit);
}
$stmt->execute();
$result = $stmt->get_result();
$employee = [];
while ($row = $result->fetch_assoc()) {
    $employee[] = $row;
}
$stmt->close();

// Get stats for dashboard
$statsQuery = "SELECT 
                COUNT(*) as total,
                SUM(CASE WHEN COALESCE(u.status, 'Active') = 'Active' THEN 1 ELSE 0 END) as active,
                SUM(CASE WHEN u.status = 'Inactive' THEN 1 ELSE 0 END) as inactive
               FROM employee e 
               LEFT JOIN user u ON u.applicant_employee_id = e.empID";
$statsResult = $conn->query($statsQuery);
$stats = $statsResult->fetch_assoc();

// AJAX handlers
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

    $config = require 'mailer-config.php';
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

        * {
            box-sizing: border-box;
        }

        body {
            font-family: 'Poppins', 'Roboto', sans-serif;
            margin: 0;
            display: flex;
            background-color: var(--light-bg);
            color: #111827;
            min-height: 100vh;
            overflow-x: hidden;
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
            padding: 20px;
            margin-left: 300px;
            display: flex;
            flex-direction: column;
            transition: margin-left 0.3s ease, padding 0.3s ease;
            width: calc(100% - 300px);
            overflow: hidden;
        }

        @media (max-width: 1200px) {
            .main-content {
                margin-left: 280px;
                width: calc(100% - 280px);
                padding: 15px;
            }
        }

        @media (max-width: 992px) {
            .main-content {
                margin-left: 0;
                width: 100%;
                padding: 15px;
            }
        }

        @media (max-width: 768px) {
            .main-content {
                padding: 10px;
            }
        }

        /* Header Section */
        .main-content-header {
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 2px solid #EFF6FF;
        }

        .main-content-header h1 {
            font-size: 24px;
            color: var(--primary-blue);
            font-weight: 700;
            margin: 0;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        @media (max-width: 768px) {
            .main-content-header h1 {
                font-size: 20px;
            }
        }

        .main-content-header h1 i {
            color: var(--primary-blue);
            font-size: 22px;
        }

        /* Stats Cards */
        .stats-section {
            margin-bottom: 20px;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 15px;
            margin-bottom: 20px;
        }

        @media (max-width: 768px) {
            .stats-grid {
                grid-template-columns: 1fr;
                gap: 12px;
            }
        }

        .stat-card {
            background: white;
            border-radius: 12px;
            padding: 20px;
            box-shadow: var(--card-shadow);
            border-top: 4px solid var(--primary-blue);
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }

        .stat-card:hover {
            transform: translateY(-3px);
            box-shadow: var(--hover-shadow);
        }

        .stat-icon {
            width: 45px;
            height: 45px;
            border-radius: 10px;
            background: linear-gradient(135deg, #EFF6FF, #DBEAFE);
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 15px;
            color: var(--primary-blue);
            font-size: 20px;
        }

        .stat-label {
            font-size: 14px;
            color: #6B7280;
            font-weight: 500;
            margin-bottom: 6px;
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .stat-value {
            font-size: 28px;
            font-weight: 700;
            color: var(--primary-blue);
            margin: 0;
        }

        @media (max-width: 768px) {
            .stat-value {
                font-size: 24px;
            }
        }

        /* Table Container */
        .table-container {
            background: white;
            border-radius: 15px;
            box-shadow: var(--card-shadow);
            padding: 20px;
            margin-top: 15px;
            transition: all 0.3s ease;
            width: 100%;
            display: flex;
            flex-direction: column;
            min-height: 0;
            flex: 1;
        }

        @media (max-width: 768px) {
            .table-container {
                padding: 15px;
                border-radius: 12px;
                margin-top: 10px;
            }
        }

        /* Controls Bar */
        .controls-bar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            gap: 15px;
            flex-wrap: wrap;
            flex-shrink: 0;
        }

        @media (max-width: 768px) {
            .controls-bar {
                flex-direction: column;
                align-items: stretch;
                gap: 10px;
            }
        }

        .search-box {
            flex: 1;
            min-width: 200px;
            position: relative;
        }

        @media (max-width: 768px) {
            .search-box {
                min-width: 100%;
            }
        }

        .search-box input {
            width: 100%;
            padding: 12px 16px 12px 42px;
            border: 2px solid #E5E7EB;
            border-radius: 10px;
            font-size: 14px;
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
            left: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: #9CA3AF;
            font-size: 15px;
        }

        .filter-box {
            min-width: 160px;
        }

        @media (max-width: 768px) {
            .filter-box {
                min-width: 100%;
            }
        }

        .filter-select {
            width: 100%;
            padding: 12px 16px;
            border: 2px solid #E5E7EB;
            border-radius: 10px;
            font-size: 14px;
            font-family: 'Poppins', sans-serif;
            background-color: white;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        /* Enhanced Table Styling - Scrollable Container */
        .table-scroll-container {
            width: 100%;
            overflow-x: auto;
            -webkit-overflow-scrolling: touch;
            border-radius: 10px;
            border: 1px solid #E5E7EB;
            flex: 1;
            min-height: 0;
            position: relative;
        }

        /* Scrollbar Styling */
        .table-scroll-container::-webkit-scrollbar {
            height: 8px;
            width: 8px;
        }

        .table-scroll-container::-webkit-scrollbar-track {
            background: #f1f1f1;
            border-radius: 4px;
        }

        .table-scroll-container::-webkit-scrollbar-thumb {
            background: #c1c1c1;
            border-radius: 4px;
        }

        .table-scroll-container::-webkit-scrollbar-thumb:hover {
            background: #a8a8a8;
        }

        .table-wrapper {
            min-width: 1000px; /* Minimum width for table */
            width: 100%;
        }

        @media (max-width: 768px) {
            .table-wrapper {
                min-width: 800px; /* Reduced minimum width for mobile */
            }
        }

        .table {
            margin-bottom: 0;
            border-collapse: collapse;
            width: 100%;
            min-width: 100%;
        }

        .table thead {
            background: linear-gradient(135deg, var(--primary-blue), var(--primary-dark));
            position: sticky;
            top: 0;
            z-index: 10;
        }

        .table thead th {
            border: none;
            padding: 16px 12px;
            font-weight: 600;
            color: white;
            font-size: 13px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            white-space: nowrap;
            position: relative;
        }

        .table thead th:after {
            content: '';
            position: absolute;
            left: 0;
            right: 0;
            bottom: 0;
            height: 1px;
            background: rgba(255, 255, 255, 0.3);
        }

        @media (max-width: 768px) {
            .table thead th {
                padding: 14px 10px;
                font-size: 12px;
            }
        }

        .table tbody td {
            padding: 16px 12px;
            border-bottom: 1px solid #F3F4F6;
            vertical-align: middle;
            font-size: 13px;
            color: #374151;
            word-wrap: break-word;
            max-width: 200px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .table tbody td:hover {
            overflow: visible;
            white-space: normal;
            word-wrap: break-word;
            z-index: 5;
            position: relative;
            background: white;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }

        @media (max-width: 768px) {
            .table tbody td {
                padding: 14px 10px;
                font-size: 12px;
                max-width: 180px;
            }
        }

        /* Fixed column widths for better scrolling */
        .table tbody td:nth-child(1) { min-width: 120px; max-width: 150px; } /* Employee ID */
        .table tbody td:nth-child(2) { min-width: 180px; max-width: 200px; } /* Full Name */
        .table tbody td:nth-child(3) { min-width: 120px; max-width: 150px; } /* Department */
        .table tbody td:nth-child(4) { min-width: 120px; max-width: 150px; } /* Position */
        .table tbody td:nth-child(5) { min-width: 100px; max-width: 120px; } /* Type */
        .table tbody td:nth-child(6) { min-width: 180px; max-width: 220px; } /* Email */
        .table tbody td:nth-child(7) { min-width: 100px; max-width: 120px; } /* Status */
        .table tbody td:nth-child(8) { min-width: 140px; max-width: 160px; } /* Actions */

        .table tbody tr:last-child td {
            border-bottom: none;
        }

        .table tbody tr:hover {
            background-color: #F8FAFC;
            transition: background-color 0.2s ease;
        }

        /* Status Badges */
        .status-badge {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            padding: 6px 12px;
            border-radius: 15px;
            font-size: 12px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.3px;
            white-space: nowrap;
        }

        @media (max-width: 768px) {
            .status-badge {
                padding: 5px 10px;
                font-size: 11px;
            }
        }

        .status-badge.active {
            background-color: #D1FAE5;
            color: #065F46;
        }

        .status-badge.inactive {
            background-color: #FEE2E2;
            color: #991B1B;
        }

        .status-badge .status-dot {
            width: 6px;
            height: 6px;
            border-radius: 50%;
            display: inline-block;
        }

        /* Action Icons */
        .action-cell {
            display: flex;
            justify-content: center;
            gap: 8px;
            flex-wrap: nowrap;
        }

        @media (max-width: 768px) {
            .action-cell {
                gap: 6px;
            }
        }

        .action-btn {
            width: 32px;
            height: 32px;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            text-decoration: none;
            transition: all 0.2s ease;
            color: white;
            border: none;
            cursor: pointer;
            flex-shrink: 0;
        }

        @media (max-width: 768px) {
            .action-btn {
                width: 30px;
                height: 30px;
                border-radius: 6px;
            }
            
            .action-btn i {
                font-size: 12px;
            }
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

        /* Scroll Indicator */
        .scroll-indicator {
            position: absolute;
            right: 10px;
            top: 50%;
            transform: translateY(-50%);
            background: var(--primary-blue);
            color: white;
            width: 24px;
            height: 24px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 12px;
            opacity: 0.7;
            z-index: 100;
            cursor: pointer;
            transition: opacity 0.3s ease;
        }

        .scroll-indicator:hover {
            opacity: 1;
        }

        .scroll-indicator.left {
            left: 10px;
            right: auto;
        }

        /* Pagination */
        .pagination-container {
            display: flex;
            justify-content: center;
            margin-top: 20px;
            width: 100%;
            flex-shrink: 0;
        }

        @media (max-width: 768px) {
            .pagination-container {
                margin-top: 15px;
            }
        }

        .pagination {
            background: white;
            padding: 8px;
            border-radius: 10px;
            box-shadow: 0 4px 12px rgba(30, 58, 138, 0.08);
            display: flex;
            flex-wrap: wrap;
            justify-content: center;
        }

        .page-link {
            padding: 8px 14px;
            margin: 2px;
            border-radius: 6px;
            border: 2px solid #E5E7EB;
            color: var(--primary-blue);
            font-weight: 500;
            transition: all 0.2s ease;
            font-size: 13px;
            white-space: nowrap;
        }

        @media (max-width: 768px) {
            .page-link {
                padding: 6px 10px;
                font-size: 12px;
                margin: 1px;
            }
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
            border-radius: 10px;
            border: none;
            padding: 14px 16px;
            margin-bottom: 15px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.05);
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 14px;
        }

        @media (max-width: 768px) {
            .alert {
                padding: 12px;
                font-size: 13px;
            }
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
            font-size: 18px;
        }

        @media (max-width: 768px) {
            .alert i {
                font-size: 16px;
            }
        }

        /* Modals */
        .modal-content {
            border-radius: 15px;
            border: none;
            box-shadow: 0 20px 60px rgba(0,0,0,0.15);
        }

        @media (max-width: 768px) {
            .modal-content {
                margin: 10px;
            }
        }

        .modal-header {
            background: linear-gradient(135deg, var(--primary-blue), var(--primary-dark));
            color: white;
            border-radius: 15px 15px 0 0 !important;
            padding: 20px;
            border: none;
        }

        @media (max-width: 768px) {
            .modal-header {
                padding: 15px;
            }
        }

        .modal-title {
            font-weight: 600;
            font-size: 18px;
        }

        @media (max-width: 768px) {
            .modal-title {
                font-size: 16px;
            }
        }

        .modal-body {
            padding: 20px;
        }

        @media (max-width: 768px) {
            .modal-body {
                padding: 15px;
            }
        }

        .modal-footer {
            padding: 15px 20px;
            border-top: 1px solid #E5E7EB;
        }

        @media (max-width: 768px) {
            .modal-footer {
                padding: 12px 15px;
            }
        }

        /* Form Elements in Modals */
        .form-control, .form-select {
            border: 2px solid #E5E7EB;
            border-radius: 8px;
            padding: 10px 14px;
            font-size: 14px;
            transition: all 0.3s ease;
        }

        @media (max-width: 768px) {
            .form-control, .form-select {
                padding: 8px 12px;
                font-size: 13px;
            }
        }

        .form-control:focus, .form-select:focus {
            border-color: var(--primary-blue);
            box-shadow: 0 0 0 3px rgba(30, 58, 138, 0.1);
        }

        .btn-primary {
            background: linear-gradient(135deg, var(--primary-blue), var(--primary-dark));
            border: none;
            border-radius: 8px;
            padding: 10px 20px;
            font-weight: 600;
            transition: all 0.3s ease;
            font-size: 14px;
        }

        @media (max-width: 768px) {
            .btn-primary {
                padding: 8px 16px;
                font-size: 13px;
            }
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(30, 58, 138, 0.25);
        }

        .btn-danger {
            background: linear-gradient(135deg, #EF4444, #DC2626);
            border: none;
            border-radius: 8px;
            padding: 10px 20px;
            font-weight: 600;
            transition: all 0.3s ease;
            font-size: 14px;
        }

        /* Clear Filters Button */
        .clear-filters {
            background: linear-gradient(135deg, #6B7280, #4B5563);
            color: white;
            border: none;
            border-radius: 10px;
            padding: 12px 20px;
            font-weight: 600;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 14px;
            white-space: nowrap;
        }

        @media (max-width: 768px) {
            .clear-filters {
                width: 100%;
                justify-content: center;
                padding: 10px 16px;
                font-size: 13px;
            }
        }

        .clear-filters:hover {
            background: linear-gradient(135deg, #4B5563, #374151);
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(107, 114, 128, 0.25);
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

        /* Email link styling */
        .email-link {
            display: inline-flex;
            align-items: center;
            gap: 4px;
            text-decoration: none;
            color: var(--primary-blue);
            max-width: 100%;
        }

        .email-link:hover {
            text-decoration: underline;
        }

        /* Compact employee info for mobile */
        .employee-info-compact {
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .employee-info-compact .avatar {
            width: 32px;
            height: 32px;
            border-radius: 50%;
            background: var(--primary-blue);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            font-size: 12px;
            flex-shrink: 0;
        }

        .employee-info-compact .details {
            min-width: 0;
        }

        .employee-info-compact .name {
            font-weight: 600;
            font-size: 13px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .employee-info-compact .id {
            font-size: 11px;
            color: #6B7280;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        /* No Results */
        .no-results {
            text-align: center;
            padding: 40px 20px;
            background: white;
            border-radius: 10px;
            border: 2px dashed #E5E7EB;
        }

        .no-results i {
            font-size: 48px;
            color: #9CA3AF;
            margin-bottom: 15px;
        }

        .no-results h4 {
            color: #6B7280;
            margin-bottom: 10px;
        }

        .no-results p {
            color: #9CA3AF;
            margin-bottom: 20px;
        }

        /* Mobile optimization for table */
        @media (max-width: 576px) {
            .table-scroll-container {
                border-radius: 8px;
            }
            
            .table tbody td {
                padding: 12px 8px;
            }
            
            .status-badge {
                padding: 4px 8px;
                font-size: 10px;
            }
            
            .action-btn {
                width: 28px;
                height: 28px;
            }
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

        <!-- Stats Section -->
        <div class="stats-section">
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-users"></i>
                    </div>
                    <div class="stat-label">
                        <i class="fas fa-chart-line"></i>
                        Total Employees
                    </div>
                    <div class="stat-value"><?php echo $stats['total'] ?? 0; ?></div>
                </div>

                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-user-check"></i>
                    </div>
                    <div class="stat-label">
                        <i class="fas fa-check-circle"></i>
                        Active Employees
                    </div>
                    <div class="stat-value"><?php echo $stats['active'] ?? 0; ?></div>
                </div>

                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-user-slash"></i>
                    </div>
                    <div class="stat-label">
                        <i class="fas fa-times-circle"></i>
                        Inactive Employees
                    </div>
                    <div class="stat-value"><?php echo $stats['inactive'] ?? 0; ?></div>
                </div>
            </div>
        </div>

        <!-- Table Container -->
        <div class="table-container">
            <!-- Controls -->
            <div class="controls-bar">
                <form method="GET" action="Manager_Employees.php" class="d-flex flex-wrap gap-3 w-100" id="filterForm">
                    <div class="search-box">
                        <i class="fas fa-search"></i>
                        <input type="text" id="searchInput" name="search" 
                               value="<?php echo htmlspecialchars($search); ?>" 
                               placeholder="Search employees...">
                    </div>
                    
                    <div class="filter-box">
                        <select id="deptFilter" name="department" class="filter-select">
                            <option value="">All Departments</option>
                            <?php foreach ($allDepartments as $dept): ?>
                                <option value="<?php echo htmlspecialchars($dept['deptName']); ?>"
                                    <?php echo ($deptFilter === $dept['deptName']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($dept['deptName']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="filter-box">
                        <select id="statusFilter" name="status" class="filter-select">
                            <option value="">All Status</option>
                            <option value="Active" <?php echo ($statusFilter === 'Active') ? 'selected' : ''; ?>>Active</option>
                            <option value="Inactive" <?php echo ($statusFilter === 'Inactive') ? 'selected' : ''; ?>>Inactive</option>
                        </select>
                    </div>

                    <button type="button" class="clear-filters" onclick="clearFilters()">
                        <i class="fas fa-filter-circle-xmark"></i> Clear Filters
                    </button>
                </form>
            </div>

            <!-- Loading Indicator -->
            <div class="loading" id="loadingIndicator" style="display: none;">
                <div class="spinner"></div>
                <p class="mt-3 text-muted">Loading employees...</p>
            </div>

            <!-- Table -->
            <?php if (count($employee) > 0): ?>
                <div class="table-scroll-container">
                    <div class="table-wrapper">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Employee ID</th>
                                    <th>Full Name</th>
                                    <th>Department</th>
                                    <th>Position</th>
                                    <th>Type</th>
                                    <th>Email</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody id="employeeTable">
                                <?php foreach ($employee as $emp): ?>
                                    <?php 
                                        $status = $emp['status'] ?? 'Active';
                                        $statusClass = strtolower($status) === 'inactive' ? 'inactive' : 'active';
                                        // Get initials for avatar
                                        $initials = '';
                                        $nameParts = explode(' ', $emp['fullname']);
                                        if (count($nameParts) >= 2) {
                                            $initials = strtoupper($nameParts[0][0] . $nameParts[1][0]);
                                        } else {
                                            $initials = strtoupper(substr($emp['fullname'], 0, 2));
                                        }
                                    ?>
                                    <tr>
                                        <td>
                                            <div class="d-none d-md-block">
                                                <strong><?php echo htmlspecialchars($emp['empID']); ?></strong>
                                            </div>
                                            <div class="d-md-none employee-info-compact">
                                                <div class="avatar"><?php echo $initials; ?></div>
                                                <div class="details">
                                                    <div class="name"><?php echo htmlspecialchars($emp['fullname']); ?></div>
                                                    <div class="id"><?php echo htmlspecialchars($emp['empID']); ?></div>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="d-none d-md-table-cell">
                                            <div class="d-flex align-items-center">
                                                <div class="me-2">
                                                    <i class="fas fa-user-circle" style="font-size: 18px; color: var(--primary-blue);"></i>
                                                </div>
                                                <div>
                                                    <div class="fw-medium"><?php echo htmlspecialchars($emp['fullname']); ?></div>
                                                </div>
                                            </div>
                                        </td>
                                        <td><?php echo htmlspecialchars($emp['department']); ?></td>
                                        <td><?php echo htmlspecialchars($emp['position']); ?></td>
                                        <td>
                                            <span class="badge bg-light text-dark border" style="font-size: 11px;">
                                                <?php echo htmlspecialchars($emp['type_name']); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <a href="mailto:<?php echo htmlspecialchars($emp['email_address']); ?>" class="email-link" title="<?php echo htmlspecialchars($emp['email_address']); ?>">
                                                <i class="fas fa-envelope"></i>
                                                <span class="d-none d-md-inline"><?php echo htmlspecialchars($emp['email_address']); ?></span>
                                                <span class="d-md-none">Email</span>
                                            </a>
                                        </td>
                                        <td>
                                            <span class="status-badge <?php echo $statusClass; ?>">
                                                <span class="status-dot"></span>
                                                <span class="d-none d-md-inline"><?php echo htmlspecialchars($status); ?></span>
                                                <span class="d-md-none"><?php echo substr(htmlspecialchars($status), 0, 1); ?></span>
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
            <?php else: ?>
                <!-- No Results -->
                <div class="no-results">
                    <i class="fas fa-search"></i>
                    <h4>No employees found</h4>
                    <p>Try adjusting your search or filter</p>
                    <?php if (!empty($search) || !empty($deptFilter) || !empty($statusFilter)): ?>
                        <button class="btn btn-primary" onclick="clearFilters()">
                            <i class="fas fa-filter-circle-xmark me-2"></i>Clear All Filters
                        </button>
                    <?php endif; ?>
                </div>
            <?php endif; ?>

            <!-- Pagination -->
            <?php if ($pages > 1): ?>
                <div class="pagination-container">
                    <nav aria-label="Employee pagination">
                        <ul class="pagination">
                            <li class="page-item <?php echo $page <= 1 ? 'disabled' : ''; ?>">
                                <a class="page-link" href="<?php echo getPageLink($page - 1); ?>">
                                    <i class="fas fa-chevron-left d-none d-md-inline"></i>
                                    <span class="d-md-none">Prev</span>
                                </a>
                            </li>
                            <?php 
                            // Show limited pagination on mobile
                            $startPage = max(1, $page - 2);
                            $endPage = min($pages, $page + 2);
                            
                            for ($p = $startPage; $p <= $endPage; $p++): ?>
                                <li class="page-item <?php echo $p === $page ? 'active' : ''; ?> d-none d-md-block">
                                    <a class="page-link" href="<?php echo getPageLink($p); ?>"><?php echo $p; ?></a>
                                </li>
                            <?php endfor; ?>
                            
                            <!-- Current page indicator for mobile -->
                            <li class="page-item active d-md-none">
                                <span class="page-link"><?php echo $page; ?> of <?php echo $pages; ?></span>
                            </li>
                            
                            <li class="page-item <?php echo $page >= $pages ? 'disabled' : ''; ?>">
                                <a class="page-link" href="<?php echo getPageLink($page + 1); ?>">
                                    <span class="d-md-none">Next</span>
                                    <i class="fas fa-chevron-right d-none d-md-inline"></i>
                                </a>
                            </li>
                        </ul>
                    </nav>
                </div>
            <?php endif; ?>
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
                            <textarea name="message" class="form-control" rows="4" placeholder="Type your message here..." required></textarea>
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
                    <div class="mb-3">
                        <i class="fas fa-user-slash" style="font-size: 40px; color: var(--warning-color);"></i>
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
                    <div class="mb-3">
                        <i class="fas fa-trash-alt" style="font-size: 40px; color: var(--danger-color);"></i>
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
            // Auto-submit form on filter changes
            $('#searchInput, #deptFilter, #statusFilter').on('change keyup', function() {
                if ($(this).attr('id') === 'searchInput') {
                    clearTimeout(window.searchTimeout);
                    window.searchTimeout = setTimeout(function() {
                        $('#filterForm').submit();
                    }, 500);
                } else {
                    $('#filterForm').submit();
                }
            });

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
                            .html('<span class="status-dot"></span>' + (window.innerWidth >= 768 ? 'Inactive' : 'I'));
                        
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
                            showAlert('Employee archived successfully', 'success');
                            // Reload page to update stats
                            setTimeout(() => location.reload(), 1000);
                        });
                    } else {
                        showAlert('Failed to archive employee', 'error');
                    }
                    $('#loadingIndicator').hide();
                    archiveModal.hide();
                }, 'json');
            });

            // Add hover effect for table cells with ellipsis
            $('table tbody td').on('mouseenter', function() {
                if (this.offsetWidth < this.scrollWidth && !$(this).hasClass('action-cell')) {
                    $(this).addClass('hover-expand');
                }
            }).on('mouseleave', function() {
                $(this).removeClass('hover-expand');
            });
        });

        function clearFilters() {
            window.location.href = 'Manager_Employees.php';
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

        // Function to scroll table horizontally
        function scrollTable(direction) {
            const container = document.querySelector('.table-scroll-container');
            const scrollAmount = 200;
            
            if (direction === 'left') {
                container.scrollLeft -= scrollAmount;
            } else {
                container.scrollLeft += scrollAmount;
            }
        }

        // Add scroll indicators if table overflows
        function checkTableOverflow() {
            const container = document.querySelector('.table-scroll-container');
            if (container && container.scrollWidth > container.clientWidth) {
                // Add scroll indicators if not already present
                if (!document.querySelector('.scroll-indicator.right')) {
                    const rightIndicator = document.createElement('div');
                    rightIndicator.className = 'scroll-indicator right';
                    rightIndicator.innerHTML = '<i class="fas fa-chevron-right"></i>';
                    rightIndicator.onclick = () => scrollTable('right');
                    container.appendChild(rightIndicator);
                }
                
                if (!document.querySelector('.scroll-indicator.left')) {
                    const leftIndicator = document.createElement('div');
                    leftIndicator.className = 'scroll-indicator left';
                    leftIndicator.innerHTML = '<i class="fas fa-chevron-left"></i>';
                    leftIndicator.onclick = () => scrollTable('left');
                    container.appendChild(leftIndicator);
                }
            }
        }

        // Check table overflow on load and resize
        $(window).on('load resize', checkTableOverflow);
        setTimeout(checkTableOverflow, 100); // Check after a short delay
    </script>

</body>

</html>

<?php
// Helper function to generate pagination links with filters
function getPageLink($pageNum) {
    global $search, $deptFilter, $statusFilter;
    $params = [];
    if (!empty($search)) $params[] = "search=" . urlencode($search);
    if (!empty($deptFilter)) $params[] = "department=" . urlencode($deptFilter);
    if (!empty($statusFilter)) $params[] = "status=" . urlencode($statusFilter);
    $params[] = "page=" . $pageNum;
    
    return "Manager_Employees.php?" . implode("&", $params);
}