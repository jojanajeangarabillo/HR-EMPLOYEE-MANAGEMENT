<?php
session_start();
require 'admin/db.connect.php';
// PHPMailer includes (adjust path if needed)
require 'PHPMailer-master/src/Exception.php';
require 'PHPMailer-master/src/PHPMailer.php';
require 'PHPMailer-master/src/SMTP.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// load mailer config
$config = require 'mailer-config.php';

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

$requests = [];
$openPickupModal = false;
$pickupInfo = null;

// Build combined request list: leave_request + general_request
if ($role === "HR Manager" || $role === "HR Director") {
  $excludeEmp = $employeeID ?? '';
  $sql = "
    SELECT lr.request_id AS rid, e.empID, e.fullname, e.department, 'Leave' AS request_type_name,
           lr.reason, lr.status, lr.requested_at, 'leave' AS source, lr.from_date
    FROM leave_request lr
    JOIN employee e ON lr.empID = e.empID
    WHERE e.empID != ?
    UNION ALL
    SELECT gr.request_id AS rid, e.empID, e.fullname, e.department, tor.request_type_name,
           gr.reason, gr.status, gr.requested_at, 'general' AS source, NULL AS from_date
    FROM general_request gr
    JOIN employee e ON gr.empID = e.empID
    JOIN types_of_requests tor ON tor.id = gr.request_type_id
    WHERE e.empID != ?
    ORDER BY requested_at DESC";
  $stmt = $conn->prepare($sql);
  $stmt->bind_param('ss', $excludeEmp, $excludeEmp);
} else {
  $sql = "
    SELECT lr.request_id AS rid, e.empID, e.fullname, e.department, 'Leave' AS request_type_name,
           lr.reason, lr.status, lr.requested_at, 'leave' AS source, lr.from_date
    FROM leave_request lr
    JOIN employee e ON lr.empID = e.empID
    UNION ALL
    SELECT gr.request_id AS rid, e.empID, e.fullname, e.department, tor.request_type_name,
           gr.reason, gr.status, gr.requested_at, 'general' AS source, NULL AS from_date
    FROM general_request gr
    JOIN employee e ON gr.empID = e.empID
    JOIN types_of_requests tor ON tor.id = gr.request_type_id
    ORDER BY requested_at DESC";
  $stmt = $conn->prepare($sql);
}

$stmt->execute();
$res = $stmt->get_result();
while ($row = $res->fetch_assoc()) {
  $requests[] = $row;
}
$stmt->close();

// Prepare monthly slots lookup for Leave
$typeStmt2 = $conn->prepare("SELECT id FROM types_of_requests WHERE request_type_name = ? LIMIT 1");
$typeName2 = 'Leave';
$typeStmt2->bind_param("s", $typeName2);
$typeStmt2->execute();
$typeRes2 = $typeStmt2->get_result()->fetch_assoc();
$leave_type_id_for_lookup = $typeRes2['id'] ?? null;
$typeStmt2->close();

$limitLookupStmt = null;
if ($leave_type_id_for_lookup) {
  $limitLookupStmt = $conn->prepare("SELECT employee_limit FROM leave_settings WHERE request_type_id = ? AND month = ? ORDER BY settingID DESC LIMIT 1");
}

// --- Handle Approve/Reject Actions ---
if (isset($_GET['action'], $_GET['type'])) {
  $action = $_GET['action'];
  $source = $_GET['type'];
  $request_id = isset($_GET['id']) ? intval($_GET['id']) : null;
  $req_emp = isset($_GET['emp']) ? $_GET['emp'] : null;
  $req_ts = isset($_GET['ts']) ? $_GET['ts'] : null;

  $status = ($action === 'accept') ? 'Approved' : (($action === 'reject') ? 'Rejected' : null);

  if ($status) {
    if ($source === 'general' && $status === 'Approved') {
      $gi = $conn->prepare("SELECT gr.request_id, gr.empID, gr.fullname, gr.department, gr.position, gr.email, tor.request_type_name, e.type_name FROM general_request gr JOIN types_of_requests tor ON tor.id = gr.request_type_id LEFT JOIN employee e ON e.empID = gr.empID WHERE gr.request_id = ?");
      $gi->bind_param("i", $request_id);
      $gi->execute();
      $pickupInfo = $gi->get_result()->fetch_assoc();
      $gi->close();
      $openPickupModal = $pickupInfo ? true : false;
      if ($openPickupModal) {
        goto render_page;
      }
    }

    if ($source === 'leave') {
      $stmt = $conn->prepare("SELECT fullname, email_address, 'Leave' AS request_type_name, leave_type_name, from_date FROM leave_request WHERE empID = ? AND requested_at = ?");
      $stmt->bind_param("ss", $req_emp, $req_ts);
      $stmt->execute();
      $req = $stmt->get_result()->fetch_assoc();
      $stmt->close();
      $emp_name = $req['fullname'];
      $email = $req['email_address'];
      $requestType = $req['request_type_name'];
      $leaveType = $req['leave_type_name'];
      $leaveMonth = isset($req['from_date']) ? intval(date('n', strtotime($req['from_date']))) : intval(date('n'));
    } elseif ($source === 'general') {
      $stmt = $conn->prepare("SELECT fullname, email AS email_address, tor.request_type_name, NULL AS leave_type_name FROM general_request gr JOIN types_of_requests tor ON tor.id = gr.request_type_id WHERE gr.request_id = ?");
      $stmt->bind_param("i", $request_id);
      $stmt->execute();
      $req = $stmt->get_result()->fetch_assoc();
      $stmt->close();
      $emp_name = $req['fullname'];
      $email = $req['email_address'];
      $requestType = $req['request_type_name'];
      $leaveType = $req['leave_type_name'];
    }

    // Prepare email message
    if ($status == "Approved") {
      if ($requestType == "Leave") {
        $messageBody = "Greetings $emp_name,<br><br>
                Your request for <b>$leaveType</b> has been <b>approved</b>.<br>
                Kindly coordinate with the HR Team onsite regarding your schedule.<br><br>
                Thank you!";
      } else {
        $messageBody = "Greetings $emp_name,<br><br>
                Your request for <b>$requestType</b> has been <b>approved</b>.<br>
                You may coordinate with HR onsite for further instructions.<br><br>
                Thank you!";
      }
    } else {
      $messageBody = "Greetings $emp_name,<br><br>
            Your request for <b>$requestType</b> has been <b>rejected</b>.<br>
            For more details, kindly coordinate with the HR Team.<br><br>
            Thank you!";
    }

    // Update database
    $action_by = $role;

    if ($source === 'leave') {
      $stmt = $conn->prepare("UPDATE leave_request SET status = ?, action_by = ? WHERE empID = ? AND requested_at = ?");
      $stmt->bind_param("ssss", $status, $action_by, $req_emp, $req_ts);
      $stmt->execute();
      $stmt->close();

      if ($status === 'Approved') {
        $typeStmt = $conn->prepare("SELECT id FROM types_of_requests WHERE request_type_name = ? LIMIT 1");
        $typeName = 'Leave';
        $typeStmt->bind_param("s", $typeName);
        $typeStmt->execute();
        $typeRes = $typeStmt->get_result()->fetch_assoc();
        $leave_type_id = $typeRes['id'] ?? null;
        $typeStmt->close();

        if ($leave_type_id) {
          $upd = $conn->prepare("UPDATE leave_settings SET employee_limit = employee_limit - 1 WHERE request_type_id = ? AND month = ? AND employee_limit > 0 ORDER BY settingID DESC LIMIT 1");
          $upd->bind_param("ii", $leave_type_id, $leaveMonth);
          $upd->execute();
          if ($upd->affected_rows <= 0) {
            $_SESSION['flash_error'] = "No available slots to decrement for leave setting.";
          }
          $upd->close();
        }
      }
    } else {
      if ($status === 'Approved' && !$openPickupModal) {
        $stmt = $conn->prepare("UPDATE general_request SET status = ?, action_by = ? WHERE request_id = ?");
        $stmt->bind_param("ssi", $status, $action_by, $request_id);
        $stmt->execute();
        $stmt->close();
      }
    }

    // SEND EMAIL
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
      $mail->addAddress($email, $emp_name);

      $mail->isHTML(true);
      $mail->Subject = "Your Request Update - Employee Management System";
      $mail->Body = $messageBody;

      $mail->send();

      $_SESSION['flash_success'] = "Email sent successfully and request has been $status.";

    } catch (Exception $e) {
      error_log("Email failed: " . $mail->ErrorInfo);
      $_SESSION['flash_error'] = "Request updated but email failed to send.";
    }
  }

  if (!$openPickupModal) {
    header("Location: Manager_Approvals.php");
    exit;
  }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['schedule_pickup'])) {
  $gid = intval($_POST['general_id']);
  $pickup_date = $_POST['pickup_date'] ?? null;
  if ($gid > 0 && $pickup_date) {
    $stmt = $conn->prepare("SELECT gr.fullname, gr.email, tor.request_type_name, gr.empID FROM general_request gr JOIN types_of_requests tor ON tor.id = gr.request_type_id WHERE gr.request_id = ?");
    $stmt->bind_param("i", $gid);
    $stmt->execute();
    $r = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    $stmt = $conn->prepare("UPDATE general_request SET status='Approved', action_by=?, pickup_date=? WHERE request_id=?");
    $stmt->bind_param("ssi", $role, $pickup_date, $gid);
    $stmt->execute();
    $stmt->close();

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
      $mail->addAddress($r['email'], $r['fullname']);

      $pos = ($role === 'HR Director') ? 'Director' : 'Manager';
      $mail->isHTML(true);
      $mail->Subject = "General Request Approved";
      $mail->Body = "Greetings {$r['fullname']},<br><br>" .
        "Your request for <b>{$r['request_type_name']}</b> has been <b>Approved</b>.<br>" .
        "Pickup Date: <b>" . htmlspecialchars($pickup_date) . "</b><br>" .
        "Approved by: <b>" . htmlspecialchars($managername) . "</b> (" . htmlspecialchars($pos) . ").<br><br>Thank you!";
      $mail->send();
      $_SESSION['success_modal_text'] = "Approval email sent and pickup scheduled on " . htmlspecialchars($pickup_date) . ".";
    } catch (Exception $e) {
      $_SESSION['success_modal_text'] = "Pickup scheduled, but email failed to send.";
    }
  }
  header("Location: Manager_Approvals.php");
  exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ajax_get_request_detail'])) {
  header('Content-Type: application/json');
  $source = $_POST['source'] ?? '';
  if ($source === 'leave') {
    $emp = $_POST['emp'] ?? '';
    $ts = $_POST['ts'] ?? '';
    $s = $conn->prepare("SELECT empID, fullname, department, position, type_name, email_address, request_type_name, leave_type_name, reason, from_date, to_date, duration, status, requested_at FROM leave_request WHERE empID = ? AND requested_at = ? LIMIT 1");
    $s->bind_param('ss', $emp, $ts);
    $s->execute();
    $row = $s->get_result()->fetch_assoc();
    $s->close();
    echo json_encode(['ok' => $row ? 1 : 0, 'data' => $row]);
    exit;
  } elseif ($source === 'general') {
    $rid = isset($_POST['id']) ? intval($_POST['id']) : 0;
    $s = $conn->prepare("SELECT gr.request_id, gr.empID, gr.fullname, gr.department, gr.position, gr.email, tor.request_type_name AS request_type_name, gr.reason, gr.status, gr.requested_at FROM general_request gr JOIN types_of_requests tor ON tor.id = gr.request_type_id WHERE gr.request_id = ? LIMIT 1");
    $s->bind_param('i', $rid);
    $s->execute();
    $row = $s->get_result()->fetch_assoc();
    $s->close();
    echo json_encode(['ok' => $row ? 1 : 0, 'data' => $row]);
    exit;
  }
  echo json_encode(['ok' => 0]);
  exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ajax_reject_request'])) {
  header('Content-Type: application/json');
  $source = $_POST['source'] ?? '';
  $reasonMsg = trim($_POST['reject_reason'] ?? '');
  if ($source === 'leave') {
    $emp = $_POST['emp'] ?? '';
    $ts = $_POST['ts'] ?? '';
    $stmt = $conn->prepare("SELECT fullname, email_address, request_type_name, leave_type_name FROM leave_request WHERE empID = ? AND requested_at = ? LIMIT 1");
    $stmt->bind_param('ss', $emp, $ts);
    $stmt->execute();
    $req = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    if ($req) {
      $stmt = $conn->prepare("UPDATE leave_request SET status='Rejected', action_by=? WHERE empID=? AND requested_at=?");
      $stmt->bind_param('sss', $role, $emp, $ts);
      $okUpd = $stmt->execute();
      $stmt->close();
      if ($okUpd) {
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
          $mail->addAddress($req['email_address'], $req['fullname']);
          $mail->isHTML(true);
          $mail->Subject = "Your Request Update - Employee Management System";
          $mail->Body = "Greetings " . htmlspecialchars($req['fullname']) . ",<br><br>" .
            "Your request for <b>" . htmlspecialchars($req['leave_type_name'] ?: $req['request_type_name']) . "</b> has been <b>rejected</b>.<br>" .
            "Reason: " . nl2br(htmlspecialchars($reasonMsg)) . "<br><br>Thank you!";
          $mail->send();
        } catch (Exception $e) {
        }
        echo json_encode(['ok' => 1]);
        exit;
      }
    }
    echo json_encode(['ok' => 0]);
    exit;
  } elseif ($source === 'general') {
    $rid = isset($_POST['id']) ? intval($_POST['id']) : 0;
    $stmt = $conn->prepare("SELECT gr.fullname, gr.email, tor.request_type_name FROM general_request gr JOIN types_of_requests tor ON tor.id = gr.request_type_id WHERE gr.request_id = ? LIMIT 1");
    $stmt->bind_param('i', $rid);
    $stmt->execute();
    $req = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    if ($req) {
      $stmt = $conn->prepare("UPDATE general_request SET status='Rejected', action_by=? WHERE request_id=?");
      $stmt->bind_param('si', $role, $rid);
      $okUpd = $stmt->execute();
      $stmt->close();
      if ($okUpd) {
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
          $mail->addAddress($req['email'], $req['fullname']);
          $mail->isHTML(true);
          $mail->Subject = "Your Request Update - Employee Management System";
          $mail->Body = "Greetings " . htmlspecialchars($req['fullname']) . ",<br><br>" .
            "Your request for <b>" . htmlspecialchars($req['request_type_name']) . "</b> has been <b>rejected</b>.<br>" .
            "Reason: " . nl2br(htmlspecialchars($reasonMsg)) . "<br><br>Thank you!";
          $mail->send();
        } catch (Exception $e) {
        }
        echo json_encode(['ok' => 1]);
        exit;
      }
    }
    echo json_encode(['ok' => 0]);
    exit;
  }
  echo json_encode(['ok' => 0]);
  exit;
}

render_page:

// Calculate statistics
$pendingCount = 0;
$approvedCount = 0;
$rejectedCount = 0;
$leaveRequests = 0;
$generalRequests = 0;

foreach ($requests as $req) {
  if ($req['status'] === 'Pending') $pendingCount++;
  elseif ($req['status'] === 'Approved') $approvedCount++;
  elseif ($req['status'] === 'Rejected') $rejectedCount++;
  
  if ($req['source'] === 'leave') $leaveRequests++;
  else $generalRequests++;
}

$totalRequests = count($requests);
?>

<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Manager Approvals</title>

  <link rel="stylesheet" href="manager-sidebar.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/7.0.1/css/all.min.css">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

  <style>
    body {
      font-family: 'Poppins', 'Roboto', sans-serif;
      margin: 0;
      display: flex;
      background-color: #f8fafc;
      color: #1f2937;
      min-height: 100vh;
    }

    /* Sidebar styling */
    .sidebar {
      position: fixed;
      height: 100vh;
      overflow-y: auto;
      z-index: 1000;
    }

    .sidebar-profile-img {
      width: 130px;
      height: 130px;
      border-radius: 50%;
      object-fit: cover;
      margin-bottom: 20px;
      transition: transform 0.3s ease;
      border: 4px solid #1E3A8A;
    }

    .sidebar-profile-img:hover {
      transform: scale(1.05);
    }

    .sidebar-name {
      text-align: center;
      color: white;
      padding: 10px;
      margin-bottom: 30px;
      font-size: 16px;
      font-weight: 500;
    }

    /* Main Content Area */
    .main-content {
      flex: 1;
      padding: 30px;
      margin-left: 220px;
      width: calc(100% - 220px);
    }

    /* Page Header */
    .page-header {
      background: linear-gradient(135deg, #1E3A8A 0%, #3B82F6 100%);
      color: white;
      padding: 25px 30px;
      border-radius: 12px;
      margin-bottom: 30px;
      box-shadow: 0 4px 12px rgba(30, 58, 138, 0.15);
    }

    .page-header h1 {
      color: white;
      margin: 0;
      font-size: 28px;
      font-weight: 700;
      display: flex;
      align-items: center;
      gap: 12px;
    }

    .page-header .subtitle {
      opacity: 0.9;
      font-size: 14px;
      margin-top: 8px;
      margin-left: 5px;
    }

    /* Statistics Cards */
    .stats-grid {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
      gap: 20px;
      margin-bottom: 30px;
    }

    .stat-card {
      background: white;
      border-radius: 12px;
      padding: 25px;
      box-shadow: 0 2px 10px rgba(0,0,0,0.06);
      border-left: 5px solid #1E3A8A;
      transition: all 0.3s ease;
      position: relative;
      overflow: hidden;
    }

    .stat-card:hover {
      transform: translateY(-3px);
      box-shadow: 0 6px 20px rgba(0,0,0,0.1);
    }

    .stat-card h6 {
      color: #6b7280;
      font-size: 13px;
      text-transform: uppercase;
      letter-spacing: 0.5px;
      margin-bottom: 10px;
      font-weight: 600;
    }

    .stat-card .stat-number {
      font-size: 32px;
      font-weight: 700;
      color: #1E3A8A;
      margin-bottom: 5px;
    }

    .stat-card .stat-sub {
      color: #9ca3af;
      font-size: 13px;
      font-weight: 500;
    }

    .stat-card.pending {
      border-left-color: #f59e0b;
    }

    .stat-card.pending .stat-number {
      color: #f59e0b;
    }

    .stat-card.approved {
      border-left-color: #10b981;
    }

    .stat-card.approved .stat-number {
      color: #10b981;
    }

    .stat-card.rejected {
      border-left-color: #ef4444;
    }

    .stat-card.rejected .stat-number {
      color: #ef4444;
    }

    /* Filters Section */
    .filters-section {
      background: white;
      border-radius: 12px;
      padding: 25px;
      margin-bottom: 30px;
      box-shadow: 0 2px 10px rgba(0,0,0,0.06);
    }

    .search-box {
      position: relative;
      flex: 1;
      min-width: 300px;
    }

    .search-box input {
      width: 100%;
      padding: 12px 45px 12px 20px;
      border: 1px solid #e5e7eb;
      border-radius: 8px;
      font-size: 14px;
      transition: all 0.3s ease;
      background: #f9fafb;
    }

    .search-box input:focus {
      outline: none;
      border-color: #1E3A8A;
      background: white;
      box-shadow: 0 0 0 3px rgba(30, 58, 138, 0.1);
    }

    .search-box i {
      position: absolute;
      right: 15px;
      top: 50%;
      transform: translateY(-50%);
      color: #9ca3af;
    }

    /* Table Container */
    .table-container {
      background: white;
      border-radius: 12px;
      overflow: hidden;
      box-shadow: 0 2px 10px rgba(0,0,0,0.06);
      margin-bottom: 40px;
    }

    .table-header {
      background: linear-gradient(135deg, #1E3A8A 0%, #3B82F6 100%);
      color: white;
      padding: 20px 25px;
      display: flex;
      justify-content: space-between;
      align-items: center;
    }

    .table-header h5 {
      margin: 0;
      font-weight: 600;
      display: flex;
      align-items: center;
      gap: 10px;
    }

    .table-controls {
      display: flex;
      gap: 10px;
      align-items: center;
    }

    /* Table Styling */
    .table-custom {
      margin: 0;
      border-collapse: separate;
      border-spacing: 0;
    }

    .table-custom thead th {
      background: #f8fafc;
      color: #4b5563;
      font-weight: 600;
      padding: 16px 20px;
      border: none;
      border-bottom: 2px solid #e5e7eb;
      text-align: left;
    }

    .table-custom tbody td {
      padding: 18px 20px;
      border-bottom: 1px solid #f3f4f6;
      vertical-align: middle;
    }

    .table-custom tbody tr {
      transition: all 0.2s ease;
    }

    .table-custom tbody tr:hover {
      background-color: #f9fafb;
    }

    .table-custom tbody tr:nth-child(even) {
      background-color: #fcfdfe;
    }

    /* Status Badges */
    .badge-status {
      padding: 6px 14px;
      border-radius: 20px;
      font-size: 12px;
      font-weight: 600;
      text-transform: uppercase;
      letter-spacing: 0.3px;
    }

    .badge-pending {
      background: linear-gradient(135deg, #fef3c7 0%, #fde68a 100%);
      color: #92400e;
      border: 1px solid #fbbf24;
    }

    .badge-approved {
      background: linear-gradient(135deg, #d1fae5 0%, #a7f3d0 100%);
      color: #065f46;
      border: 1px solid #10b981;
    }

    .badge-rejected {
      background: linear-gradient(135deg, #fee2e2 0%, #fecaca 100%);
      color: #991b1b;
      border: 1px solid #ef4444;
    }

    /* Action Buttons */
    .action-buttons {
      display: flex;
      gap: 8px;
      align-items: center;
    }

    .btn-action {
      width: 36px;
      height: 36px;
      border-radius: 8px;
      display: flex;
      align-items: center;
      justify-content: center;
      border: none;
      cursor: pointer;
      transition: all 0.2s ease;
      font-size: 14px;
    }

    .btn-view {
      background: #e0e7ff;
      color: #4f46e5;
    }

    .btn-view:hover {
      background: #4f46e5;
      color: white;
      transform: translateY(-2px);
    }

    .btn-approve {
      background: #d1fae5;
      color: #10b981;
    }

    .btn-approve:hover {
      background: #10b981;
      color: white;
      transform: translateY(-2px);
    }

    .btn-reject {
      background: #fee2e2;
      color: #ef4444;
    }

    .btn-reject:hover {
      background: #ef4444;
      color: white;
      transform: translateY(-2px);
    }

    /* Slot Badge */
    .slot-badge {
      background: #dbeafe;
      color: #1e40af;
      padding: 3px 8px;
      border-radius: 6px;
      font-size: 11px;
      font-weight: 600;
      margin-left: 6px;
      border: 1px solid #93c5fd;
    }

    /* Empty State */
    .empty-state {
      text-align: center;
      padding: 60px 20px;
      color: #9ca3af;
    }

    .empty-state i {
      font-size: 48px;
      margin-bottom: 20px;
      opacity: 0.5;
    }

    .empty-state h4 {
      color: #6b7280;
      margin-bottom: 10px;
      font-weight: 600;
    }

    .empty-state p {
      color: #9ca3af;
      max-width: 400px;
      margin: 0 auto;
    }

    /* Modal Styling */
    .modal-content {
      border-radius: 12px;
      border: none;
      box-shadow: 0 10px 40px rgba(0,0,0,0.15);
    }

    .modal-header {
      background: linear-gradient(135deg, #1E3A8A 0%, #3B82F6 100%);
      color: white;
      border-radius: 12px 12px 0 0;
      padding: 20px 25px;
      border: none;
    }

    .modal-header .btn-close {
      filter: brightness(0) invert(1);
      opacity: 0.8;
    }

    /* Alert Styling */
    .alert-container {
      max-width: 800px;
      margin: 0 auto 25px auto;
    }

    .alert {
      border-radius: 8px;
      border: none;
      box-shadow: 0 2px 8px rgba(0,0,0,0.1);
    }

    /* Responsive Design */
    @media (max-width: 768px) {
      .main-content {
        margin-left: 0;
        width: 100%;
        padding: 20px;
      }
      
      .sidebar {
        transform: translateX(-100%);
        transition: transform 0.3s ease;
      }
      
      .sidebar.active {
        transform: translateX(0);
      }
      
      .stats-grid {
        grid-template-columns: 1fr;
      }
      
      .search-box {
        min-width: 100%;
      }
      
      .table-header {
        flex-direction: column;
        gap: 15px;
        align-items: flex-start;
      }
    }

    /* Scrollbar Styling */
    ::-webkit-scrollbar {
      width: 8px;
    }

    ::-webkit-scrollbar-track {
      background: #f1f5f9;
      border-radius: 4px;
    }

    ::-webkit-scrollbar-thumb {
      background: #cbd5e1;
      border-radius: 4px;
    }

    ::-webkit-scrollbar-thumb:hover {
      background: #94a3b8;
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
    <!-- Page Header -->
    <div class="page-header">
      <h1><i class="fa-solid fa-clipboard-check"></i> Employee Requests Approval</h1>
      <div class="subtitle">Review and manage employee leave and general requests</div>
    </div>

    <!-- Statistics -->
    <div class="stats-grid">
      <div class="stat-card">
        <h6>Total Requests</h6>
        <div class="stat-number"><?= $totalRequests ?></div>
        <div class="stat-sub">All requests combined</div>
      </div>
      <div class="stat-card pending">
        <h6>Pending Requests</h6>
        <div class="stat-number"><?= $pendingCount ?></div>
        <div class="stat-sub">Awaiting your approval</div>
      </div>
      <div class="stat-card approved">
        <h6>Approved</h6>
        <div class="stat-number"><?= $approvedCount ?></div>
        <div class="stat-sub">Successfully approved</div>
      </div>
      <div class="stat-card rejected">
        <h6>Rejected</h6>
        <div class="stat-number"><?= $rejectedCount ?></div>
        <div class="stat-sub">Requests declined</div>
      </div>
    </div>

    <!-- Flash Messages -->
    <div class="alert-container">
      <?php if (isset($_SESSION['flash_success'])): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
          <i class="fa-solid fa-circle-check me-2"></i>
          <?= $_SESSION['flash_success']; ?>
          <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
        <?php unset($_SESSION['flash_success']); ?>
      <?php endif; ?>

      <?php if (isset($_SESSION['flash_error'])): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
          <i class="fa-solid fa-circle-exclamation me-2"></i>
          <?= $_SESSION['flash_error']; ?>
          <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
        <?php unset($_SESSION['flash_error']); ?>
      <?php endif; ?>
    </div>

    <!-- Filters -->
    <div class="filters-section">
      <div class="d-flex align-items-center gap-3">
        <div class="search-box">
          <input type="text" id="searchInput" placeholder="Search requests by employee, department, or type...">
          <i class="fa-solid fa-search"></i>
        </div>
        <button class="btn btn-outline-primary" onclick="filterTable()">
          <i class="fa-solid fa-filter me-2"></i>Filter
        </button>
      </div>
    </div>

    <!-- Table Container -->
    <div class="table-container">
      <div class="table-header">
        <h5><i class="fa-solid fa-list-check"></i> All Requests</h5>
        <div class="table-controls">
          <span class="text-white opacity-75">Showing <?= count($requests) ?> requests</span>
        </div>
      </div>
      
      <div class="table-responsive">
        <table class="table table-custom">
          <thead>
            <tr>
              <th style="width: 120px;">Employee ID</th>
              <th style="width: 200px;">Employee Name</th>
              <th style="width: 150px;">Department</th>
              <th style="width: 150px;">Request Type</th>
              <th style="width: 250px;">Reason</th>
              <th style="width: 180px;">Date Submitted</th>
              <th style="width: 120px;">Status</th>
              <th style="width: 140px;">Actions</th>
            </tr>
          </thead>
          <tbody id="employeeTable">
            <?php if (!empty($requests)): ?>
              <?php foreach ($requests as $req): ?>
                <?php 
                  $statusClass = '';
                  if ($req['status'] === 'Pending') $statusClass = 'badge-pending';
                  elseif ($req['status'] === 'Approved') $statusClass = 'badge-approved';
                  elseif ($req['status'] === 'Rejected') $statusClass = 'badge-rejected';
                ?>
                <tr>
                  <td>
                    <div class="fw-semibold text-primary"><?= htmlspecialchars($req['empID']) ?></div>
                  </td>
                  <td>
                    <div class="fw-semibold"><?= htmlspecialchars($req['fullname']) ?></div>
                  </td>
                  <td>
                    <span class="text-muted"><?= htmlspecialchars($req['department'] ?? 'N/A') ?></span>
                  </td>
                  <td>
                    <div class="d-flex align-items-center">
                      <span class="fw-medium"><?= htmlspecialchars($req['request_type_name']) ?></span>
                      <?php if ($req['source'] === 'leave' && $limitLookupStmt && !empty($req['from_date'])): ?>
                        <?php
                          $monthNum = intval(date('n', strtotime($req['from_date'])));
                          $limitLookupStmt->bind_param('ii', $leave_type_id_for_lookup, $monthNum);
                          $limitLookupStmt->execute();
                          $lr = $limitLookupStmt->get_result()->fetch_assoc();
                          $slotsLeft = $lr['employee_limit'] ?? null;
                        ?>
                        <?php if ($slotsLeft !== null): ?>
                          <span class="slot-badge"><?= (int) $slotsLeft ?> slots left</span>
                        <?php endif; ?>
                      <?php endif; ?>
                    </div>
                  </td>
                  <td>
                    <div class="text-truncate" style="max-width: 240px;" title="<?= htmlspecialchars($req['reason']) ?>">
                      <?= htmlspecialchars($req['reason']) ?>
                    </div>
                  </td>
                  <td>
                    <div class="text-muted">
                      <i class="fa-regular fa-calendar me-1"></i>
                      <?= date('M d, Y', strtotime($req['requested_at'])) ?>
                    </div>
                    <small class="text-muted">
                      <?= date('h:i A', strtotime($req['requested_at'])) ?>
                    </small>
                  </td>
                  <td>
                    <span class="badge-status <?= $statusClass ?>">
                      <?= htmlspecialchars($req['status']) ?>
                    </span>
                  </td>
                  <td>
                    <?php if ($req['status'] === 'Approved'): ?>
                      <span class="text-success fw-semibold">Approved</span>
                    <?php elseif ($req['status'] === 'Rejected'): ?>
                      <span class="text-danger fw-semibold">Rejected</span>
                    <?php else: ?>
                      <div class="action-buttons">
                        <button class="btn-action btn-view view" 
                                data-source="<?= $req['source'] ?>" 
                                <?php if ($req['source'] === 'leave'): ?>
                                  data-emp="<?= htmlspecialchars($req['empID']) ?>"
                                  data-ts="<?= htmlspecialchars($req['requested_at']) ?>"
                                <?php else: ?>
                                  data-id="<?= (int) $req['rid'] ?>"
                                <?php endif; ?>
                                title="View Details">
                          <i class="fa-solid fa-eye"></i>
                        </button>
                        
                        <?php if ($req['source'] === 'leave'): ?>
                          <a href="?type=leave&action=accept&emp=<?= urlencode($req['empID']) ?>&ts=<?= urlencode($req['requested_at']) ?>"
                             class="btn-action btn-approve" title="Approve">
                            <i class="fa-solid fa-check"></i>
                          </a>
                          <button class="btn-action btn-reject reject" 
                                  data-source="leave"
                                  data-emp="<?= htmlspecialchars($req['empID']) ?>"
                                  data-ts="<?= htmlspecialchars($req['requested_at']) ?>"
                                  title="Reject">
                            <i class="fa-solid fa-xmark"></i>
                          </button>
                        <?php else: ?>
                          <a href="?id=<?= (int) $req['rid'] ?>&type=general&action=accept"
                             class="btn-action btn-approve" title="Approve">
                            <i class="fa-solid fa-check"></i>
                          </a>
                          <button class="btn-action btn-reject reject" 
                                  data-source="general"
                                  data-id="<?= (int) $req['rid'] ?>"
                                  title="Reject">
                            <i class="fa-solid fa-xmark"></i>
                          </button>
                        <?php endif; ?>
                      </div>
                    <?php endif; ?>
                  </td>
                </tr>
              <?php endforeach; ?>
            <?php else: ?>
              <tr>
                <td colspan="8">
                  <div class="empty-state">
                    <i class="fa-solid fa-inbox"></i>
                    <h4>No Requests Found</h4>
                    <p>There are currently no pending requests for approval.</p>
                  </div>
                </td>
              </tr>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>

  <!-- Pickup Schedule Modal -->
  <div class="modal fade" id="pickupModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title">Schedule Pickup</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <form method="POST" action="Manager_Approvals.php">
          <div class="modal-body">
            <?php if ($pickupInfo): ?>
              <div class="row mb-3">
                <div class="col-6">
                  <label class="form-label text-muted small">Fullname</label>
                  <div class="fw-semibold"><?= htmlspecialchars($pickupInfo['fullname']) ?></div>
                </div>
                <div class="col-6">
                  <label class="form-label text-muted small">Department</label>
                  <div class="fw-semibold"><?= htmlspecialchars($pickupInfo['department']) ?></div>
                </div>
              </div>
              <div class="row mb-3">
                <div class="col-6">
                  <label class="form-label text-muted small">Position</label>
                  <div class="fw-semibold"><?= htmlspecialchars($pickupInfo['position']) ?></div>
                </div>
                <div class="col-6">
                  <label class="form-label text-muted small">Employment Type</label>
                  <div class="fw-semibold"><?= htmlspecialchars($pickupInfo['type_name'] ?? 'N/A') ?></div>
                </div>
              </div>
              <div class="row mb-3">
                <div class="col-12">
                  <label class="form-label text-muted small">Request Type</label>
                  <div class="fw-semibold"><?= htmlspecialchars($pickupInfo['request_type_name']) ?></div>
                </div>
              </div>
            <?php endif; ?>
            <input type="hidden" name="general_id" value="<?= $pickupInfo['request_id'] ?? '' ?>">
            <div class="mb-3">
              <label class="form-label fw-semibold">Pickup Date</label>
              <input type="date" name="pickup_date" class="form-control" required>
              <div class="form-text">Select the scheduled pickup date for the employee.</div>
            </div>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
            <button type="submit" name="schedule_pickup" class="btn btn-primary">
              <i class="fa-solid fa-paper-plane me-2"></i>Send Approval
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>

  <!-- View Details Modal -->
  <div class="modal fade" id="viewModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered modal-lg">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title">Request Details</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <div class="row mb-3">
            <div class="col-md-6">
              <label class="form-label text-muted small">Employee ID</label>
              <div class="fw-semibold" id="vEmpID"></div>
            </div>
            <div class="col-md-6">
              <label class="form-label text-muted small">Fullname</label>
              <div class="fw-semibold" id="vFullname"></div>
            </div>
          </div>
          <div class="row mb-3">
            <div class="col-md-6">
              <label class="form-label text-muted small">Department</label>
              <div class="fw-semibold" id="vDept"></div>
            </div>
            <div class="col-md-6">
              <label class="form-label text-muted small">Position</label>
              <div class="fw-semibold" id="vPos"></div>
            </div>
          </div>
          <div class="row mb-3">
            <div class="col-md-6">
              <label class="form-label text-muted small">Employment Type</label>
              <div class="fw-semibold" id="vType"></div>
            </div>
            <div class="col-md-6">
              <label class="form-label text-muted small">Email</label>
              <div class="fw-semibold" id="vEmail"></div>
            </div>
          </div>
          <div class="row mb-3">
            <div class="col-md-6">
              <label class="form-label text-muted small">Request Type</label>
              <div class="fw-semibold" id="vReqType"></div>
            </div>
            <div class="col-md-6" id="vLeaveTypeRow" style="display:none;">
              <label class="form-label text-muted small">Leave Type</label>
              <div class="fw-semibold" id="vLeaveType"></div>
            </div>
          </div>
          <div class="row mb-3" id="vDateRow" style="display:none;">
            <div class="col-md-4">
              <label class="form-label text-muted small">From Date</label>
              <div class="fw-semibold" id="vFrom"></div>
            </div>
            <div class="col-md-4">
              <label class="form-label text-muted small">To Date</label>
              <div class="fw-semibold" id="vTo"></div>
            </div>
            <div class="col-md-4">
              <label class="form-label text-muted small">Duration</label>
              <div class="fw-semibold" id="vDur"></div>
            </div>
          </div>
          <div class="row mb-3">
            <div class="col-12">
              <label class="form-label text-muted small">Reason</label>
              <div class="border rounded p-3 bg-light" id="vReason"></div>
            </div>
          </div>
          <div class="row">
            <div class="col-md-6">
              <label class="form-label text-muted small">Status</label>
              <div id="vStatus"></div>
            </div>
            <div class="col-md-6">
              <label class="form-label text-muted small">Requested At</label>
              <div class="fw-semibold" id="vRequested"></div>
            </div>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Close</button>
        </div>
      </div>
    </div>
  </div>

  <!-- Reject Modal -->
  <div class="modal fade" id="rejectModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title">Reject Request</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <input type="hidden" id="rejSource">
          <input type="hidden" id="rejEmp">
          <input type="hidden" id="rejTs">
          <input type="hidden" id="rejId">
          <div class="mb-3">
            <label class="form-label fw-semibold">Reason for Rejection</label>
            <textarea id="rejReason" class="form-control" rows="4" placeholder="Please provide a reason for rejecting this request..." required></textarea>
            <div class="form-text">This reason will be sent to the employee via email.</div>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
          <button type="button" id="confirmRejectBtn" class="btn btn-danger">
            <i class="fa-solid fa-ban me-2"></i>Reject Request
          </button>
        </div>
      </div>
    </div>
  </div>

  <!-- Success Modal -->
  <?php if (!empty($_SESSION['success_modal_text'])): ?>
    <div class="modal fade" id="successModal" tabindex="-1" aria-hidden="true">
      <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title">Success</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
          </div>
          <div class="modal-body text-center py-4">
            <i class="fa-solid fa-circle-check fa-3x text-success mb-3"></i>
            <h5 class="mb-3">Request Processed Successfully</h5>
            <p class="text-muted"><?php echo htmlspecialchars($_SESSION['success_modal_text']); ?></p>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-primary" data-bs-dismiss="modal">Continue</button>
          </div>
        </div>
      </div>
    </div>
    <?php unset($_SESSION['success_modal_text']); ?>
  <?php endif; ?>

  <script>
    function filterTable() {
      const input = document.getElementById('searchInput');
      const filter = input.value.toLowerCase();
      const table = document.getElementById('employeeTable');
      const rows = table.getElementsByTagName('tr');

      for (let i = 0; i < rows.length; i++) {
        const cells = rows[i].getElementsByTagName('td');
        let found = false;

        for (let j = 0; j < cells.length - 1; j++) {
          if (cells[j]) {
            const textValue = cells[j].textContent || cells[j].innerText;
            if (textValue.toLowerCase().indexOf(filter) > -1) {
              found = true;
              break;
            }
          }
        }

        rows[i].style.display = found ? '' : 'none';
      }
    }
    
    <?php if ($openPickupModal): ?>
      document.addEventListener('DOMContentLoaded', function() {
        const pickupModal = new bootstrap.Modal(document.getElementById('pickupModal'));
        pickupModal.show();
      });
    <?php endif; ?>
    
    <?php if (!empty($_SESSION['success_modal_text'])): ?>
      document.addEventListener('DOMContentLoaded', function() {
        const successModal = new bootstrap.Modal(document.getElementById('successModal'));
        successModal.show();
      });
    <?php endif; ?>

    // Search on Enter key
    document.getElementById('searchInput').addEventListener('keypress', function(e) {
      if (e.key === 'Enter') {
        filterTable();
      }
    });

    // View details functionality
    document.addEventListener('click', function(e) {
      const viewBtn = e.target.closest('button.view');
      if (viewBtn) {
        e.preventDefault();
        const src = viewBtn.dataset.source;
        const fd = new FormData();
        fd.append('ajax_get_request_detail', '1');
        fd.append('source', src);
        
        if (src === 'leave') {
          fd.append('emp', viewBtn.dataset.emp);
          fd.append('ts', viewBtn.dataset.ts);
        } else {
          fd.append('id', viewBtn.dataset.id);
        }
        
        fetch('Manager_Approvals.php', { method: 'POST', body: fd })
          .then(r => r.json())
          .then(json => {
            if (json && json.ok && json.data) {
              const d = json.data;
              
              // Set values
              document.getElementById('vEmpID').textContent = d.empID || 'N/A';
              document.getElementById('vFullname').textContent = d.fullname || 'N/A';
              document.getElementById('vDept').textContent = d.department || 'N/A';
              document.getElementById('vPos').textContent = d.position || 'N/A';
              document.getElementById('vType').textContent = d.type_name || 'N/A';
              document.getElementById('vEmail').textContent = (d.email_address || d.email || 'N/A');
              document.getElementById('vReqType').textContent = d.request_type_name || 'N/A';
              document.getElementById('vReason').textContent = d.reason || 'N/A';
              
              // Status with badge
              const statusEl = document.getElementById('vStatus');
              let statusClass = '';
              if (d.status === 'Pending') statusClass = 'badge-pending';
              else if (d.status === 'Approved') statusClass = 'badge-approved';
              else if (d.status === 'Rejected') statusClass = 'badge-rejected';
              
              statusEl.innerHTML = `<span class="badge-status ${statusClass}">${d.status || 'N/A'}</span>`;
              
              // Format date
              const requestedDate = d.requested_at ? new Date(d.requested_at) : null;
              document.getElementById('vRequested').textContent = requestedDate ? 
                requestedDate.toLocaleDateString('en-US', { 
                  year: 'numeric', 
                  month: 'long', 
                  day: 'numeric',
                  hour: '2-digit',
                  minute: '2-digit'
                }) : 'N/A';
              
              // Handle leave-specific fields
              const isLeave = !!d.leave_type_name;
              document.getElementById('vLeaveTypeRow').style.display = isLeave ? 'flex' : 'none';
              document.getElementById('vDateRow').style.display = isLeave ? 'flex' : 'none';
              
              if (isLeave) {
                document.getElementById('vLeaveType').textContent = d.leave_type_name || 'N/A';
                document.getElementById('vFrom').textContent = d.from_date ? 
                  new Date(d.from_date).toLocaleDateString('en-US', { year: 'numeric', month: 'long', day: 'numeric' }) : 'N/A';
                document.getElementById('vTo').textContent = d.to_date ? 
                  new Date(d.to_date).toLocaleDateString('en-US', { year: 'numeric', month: 'long', day: 'numeric' }) : 'N/A';
                document.getElementById('vDur').textContent = d.duration ? `${d.duration} days` : 'N/A';
              }
              
              const viewModal = new bootstrap.Modal(document.getElementById('viewModal'));
              viewModal.show();
            }
          });
      }

      // Reject button
      const rejectBtn = e.target.closest('button.reject');
      if (rejectBtn) {
        e.preventDefault();
        document.getElementById('rejSource').value = rejectBtn.dataset.source;
        document.getElementById('rejEmp').value = rejectBtn.dataset.emp || '';
        document.getElementById('rejTs').value = rejectBtn.dataset.ts || '';
        document.getElementById('rejId').value = rejectBtn.dataset.id || '';
        document.getElementById('rejReason').value = '';
        
        const rejectModal = new bootstrap.Modal(document.getElementById('rejectModal'));
        rejectModal.show();
      }
    });

    // Confirm reject
    document.getElementById('confirmRejectBtn').addEventListener('click', function() {
      const src = document.getElementById('rejSource').value;
      const reason = document.getElementById('rejReason').value.trim();
      
      if (!reason) {
        alert('Please provide a reason for rejection.');
        return;
      }
      
      const fd = new FormData();
      fd.append('ajax_reject_request', '1');
      fd.append('source', src);
      fd.append('reject_reason', reason);
      
      if (src === 'leave') {
        fd.append('emp', document.getElementById('rejEmp').value);
        fd.append('ts', document.getElementById('rejTs').value);
      } else {
        fd.append('id', document.getElementById('rejId').value);
      }
      
      // Show loading state
      const rejectBtn = document.getElementById('confirmRejectBtn');
      const originalText = rejectBtn.innerHTML;
      rejectBtn.innerHTML = '<i class="fa-solid fa-spinner fa-spin me-2"></i>Processing...';
      rejectBtn.disabled = true;
      
      fetch('Manager_Approvals.php', { method: 'POST', body: fd })
        .then(r => r.json())
        .then(json => {
          if (json && json.ok) {
            window.location.reload();
          } else {
            alert('Failed to reject request. Please try again.');
            rejectBtn.innerHTML = originalText;
            rejectBtn.disabled = false;
          }
        })
        .catch(() => {
          alert('An error occurred. Please try again.');
          rejectBtn.innerHTML = originalText;
          rejectBtn.disabled = false;
        });
    });

    // Close modals when clicking outside
    document.querySelectorAll('.modal').forEach(modal => {
      modal.addEventListener('click', function(e) {
        if (e.target === this) {
          bootstrap.Modal.getInstance(this).hide();
        }
      });
    });
  </script>
</body>
</html>