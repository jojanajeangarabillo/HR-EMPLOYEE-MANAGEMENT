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

    // (2) Prepare email message
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

    // (3) Update database
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

    // (4) SEND EMAIL
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

      // ✔ Set flash message
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

?>


<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Manager Approvals</title>

  <link rel="stylesheet" href="manager-sidebar.css">

  <!-- Font Awesome Icons -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/7.0.1/css/all.min.css"
    crossorigin="anonymous" referrerpolicy="no-referrer" />
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>


  <style>
    body {
      font-family: 'Poppins', 'Roboto', sans-serif;
      margin: 0;
      display: flex;
      background-color: #f1f5fc;
      color: #111827;
    }

    .main-content {
      padding: 40px 30px;
      margin-left: 220px;
      display: flex;
      flex-direction: column;
    }

    .main-content-header {
      display: flex;
      align-items: center;
      justify-content: space-between;
      margin-bottom: 16px;
      padding-bottom: 12px;
      border-bottom: 1px solid #e5e7eb;
    }

    .main-content-header h1 {
      margin: 0;
      font-size: 2rem;
      color: #1E3A8A;
      display: flex;
      align-items: center;
      gap: 10px;
    }

    /* --- TABLE & FILTER STYLING --- */
    .table-container {
      max-width: 1220px;
      margin: 0 auto;
    }

    .controls-bar {
      display: flex;
      justify-content: flex-start;
      align-items: center;
      margin-bottom: 20px;
      gap: 20px;
      flex-wrap: wrap;
    }

    .search-box {
      display: flex;
      align-items: center;
      gap: 10px;
      flex: 1;
      min-width: 250px;
      max-width: 400px;
    }

    .search-box input {
      width: 100%;
      padding: 10px 15px;
      border: 1px solid #e0e0e0;
      border-radius: 6px;
      font-size: 14px;
      transition: border-color 0.3s;
    }

    .search-box input:focus {
      outline: none;
      border-color: #1E3A8A;
    }

    .search-box button {
      background-color: #1E3A8A;
      color: white;
      border: none;
      border-radius: 6px;
      padding: 10px 15px;
      font-size: 14px;
      cursor: pointer;
      transition: all 0.3s;
    }

    .search-box button:hover {
      background-color: #1e40af;
      transform: translateY(-2px);
      box-shadow: 0 4px 8px rgba(30, 58, 138, 0.3);
    }

    .table {
      border-collapse: collapse;
      width: 90%;
      background-color: #ffffff;
      box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
      border-radius: 8px;
      overflow: hidden;

    }



    th,
    td {
      min-width: 150px;
      padding: 16px 12px;
      text-align: center;
      border: 1px solid #e0e0e0;
    }

    thead {
      background-color: #1E3A8A;
      color: #ffffff;
      font-weight: 600;
    }

    tbody tr:hover {
      background-color: #f8f9fa;
    }

    tbody tr:nth-child(even) {
      background-color: #fafafa;
    }

    .action-icons {
      display: flex;
      justify-content: center;
      gap: 15px;
    }

    .action-icons a {
      color: #333;
      text-decoration: none;
      font-size: 18px;
      transition: color 0.2s ease, transform 0.2s ease;
    }

    .action-icons a:hover {
      transform: scale(1.1);
    }

    .action-icons a.accept:hover {
      color: #10b981;
    }

    .action-icons a.reject:hover {
      color: #dc3545;
    }


    .sidebar-name {
      display: flex;
      justify-content: center;
      align-items: center;
      text-align: center;
      color: white;
      padding: 10px;
      margin-bottom: 30px;
      font-size: 18px;
      flex-direction: column;
    }

    .sidebar-profile-img {
      width: 130px;
      height: 130px;
      border-radius: 50%;
      object-fit: cover;
      margin-bottom: 20px;
      transition: transform 0.3s ease;
    }

    .sidebar-profile-img:hover {
      transform: scale(1.05);
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
        <li><a href="<?php echo $link; ?>"><i
              class="fa-solid <?php echo $icons[$label] ?? 'fa-circle'; ?>"></i><?php echo $label; ?></a></li>
      <?php endforeach; ?>
    </ul>
  </div>

  <!-- MAIN CONTENT -->
  <div class="main-content">
    <div class="main-content-header">
      <h1><i class="fa-solid fa-square-check"></i> Employee Requests</h1>
    </div>


    <?php if (isset($_SESSION['flash_success'])): ?>
      <div class="alert alert-success alert-dismissible fade show" role="alert"
        style="max-width: 700px; margin: 0 auto 20px auto;">
        <?= $_SESSION['flash_success']; ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
      </div>
      <?php unset($_SESSION['flash_success']); ?>
    <?php endif; ?>

    <?php if (isset($_SESSION['flash_error'])): ?>
      <div class="alert alert-danger alert-dismissible fade show" role="alert"
        style="max-width: 700px; margin: 0 auto 20px auto;">
        <?= $_SESSION['flash_error']; ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
      </div>
      <?php unset($_SESSION['flash_error']); ?>
    <?php endif; ?>

    <div class="table-container" style="margin-top:10px;">
      <div class="controls-bar">
        <div class="search-box">
          <input type="text" id="searchInput" placeholder="Search requests..." onkeyup="filterTable()">
          <button onclick="filterTable()"><i class="fa-solid fa-filter"></i> Filter</button>
        </div>
      </div>

      <div class="table-responsive">
        <table>
          <thead>
            <tr>
              <th>Employee ID</th>
              <th>Employee Name</th>
              <th>Department</th>
              <th>Request Type</th>
              <th>Reason</th>
              <th>Date</th>
              <th>Action</th>
            </tr>
          </thead>
          <tbody id="employeeTable">
            <?php if (!empty($requests)): ?>
              <?php foreach ($requests as $req): ?>
                <tr>
                  <td><?= htmlspecialchars($req['empID']) ?></td>
                  <td><?= htmlspecialchars($req['fullname']) ?></td>
                  <td><?= htmlspecialchars($req['department'] ?? 'N/A') ?></td>
                  <td>
                    <?= htmlspecialchars($req['request_type_name']) ?>
                    <?php if ($req['source'] === 'leave' && $limitLookupStmt && !empty($req['from_date'])): ?>
                      <?php
                      $monthNum = intval(date('n', strtotime($req['from_date'])));
                      $limitLookupStmt->bind_param('ii', $leave_type_id_for_lookup, $monthNum);
                      $limitLookupStmt->execute();
                      $lr = $limitLookupStmt->get_result()->fetch_assoc();
                      $slotsLeft = $lr['employee_limit'] ?? null;
                      ?>
                      <?php if ($slotsLeft !== null): ?>
                        <span class="badge bg-info text-dark ms-2">Slots left: <?= (int) $slotsLeft ?></span>
                      <?php endif; ?>
                    <?php endif; ?>
                  </td>
                  <td><?= htmlspecialchars($req['reason']) ?></td>
                  <td><?= date('Y-m-d h:i A', strtotime($req['requested_at'])) ?></td>
                  <td class="action-icons">
                    <?php if ($req['status'] === 'Approved'): ?>
                      <span style="color:green;font-weight:bold;">Approved</span>
                    <?php elseif ($req['status'] === 'Rejected'): ?>
                      <span style="color:red;font-weight:bold;">Rejected</span>
                    <?php else: ?>
                      <?php if ($req['source'] === 'leave'): ?>
                        <a href="#" class="view" data-source="leave" data-emp="<?= htmlspecialchars($req['empID']) ?>"
                          data-ts="<?= htmlspecialchars($req['requested_at']) ?>"><i class="fa-solid fa-eye"></i></a>
                        <a href="?type=leave&action=accept&emp=<?= urlencode($req['empID']) ?>&ts=<?= urlencode($req['requested_at']) ?>"
                          class="accept"><i class="fa-solid fa-check"></i></a>
                        <a href="#" class="reject" data-source="leave" data-emp="<?= htmlspecialchars($req['empID']) ?>"
                          data-ts="<?= htmlspecialchars($req['requested_at']) ?>"><i class="fa-solid fa-xmark"></i></a>
                      <?php else: ?>
                        <a href="#" class="view" data-source="general" data-id="<?= (int) $req['rid'] ?>"><i
                            class="fa-solid fa-eye"></i></a>
                        <a href="?id=<?= (int) $req['rid'] ?>&type=general&action=accept" class="accept"><i
                            class="fa-solid fa-check"></i></a>
                        <a href="#" class="reject" data-source="general" data-id="<?= (int) $req['rid'] ?>"><i
                            class="fa-solid fa-xmark"></i></a>
                      <?php endif; ?>
                    <?php endif; ?>
                  </td>
                </tr>
              <?php endforeach; ?>
            <?php else: ?>
              <tr>
                <td colspan="7">No requests found.</td>
              </tr>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>

  <div class="modal fade" id="pickupModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
      <div class="modal-content">
        <div class="modal-header bg-primary text-white">
          <h5 class="modal-title">Schedule Pickup</h5>
          <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
        </div>
        <form method="POST" action="Manager_Approvals.php">
          <div class="modal-body">
            <?php if ($pickupInfo): ?>
              <div class="mb-2"><strong>Fullname:</strong> <?= htmlspecialchars($pickupInfo['fullname']) ?></div>
              <div class="mb-2"><strong>Department:</strong> <?= htmlspecialchars($pickupInfo['department']) ?></div>
              <div class="mb-2"><strong>Position:</strong> <?= htmlspecialchars($pickupInfo['position']) ?></div>
              <div class="mb-2"><strong>Type of Employment:</strong>
                <?= htmlspecialchars($pickupInfo['type_name'] ?? 'N/A') ?></div>
              <div class="mb-2"><strong>Email:</strong> <?= htmlspecialchars($pickupInfo['email']) ?></div>
              <div class="mb-3"><strong>Request Type:</strong> <?= htmlspecialchars($pickupInfo['request_type_name']) ?>
              </div>
            <?php endif; ?>
            <input type="hidden" name="general_id" value="<?= $pickupInfo['request_id'] ?? '' ?>">
            <label class="form-label">Pickup Date</label>
            <input type="date" name="pickup_date" class="form-control" required>
          </div>
          <div class="modal-footer">
            <button type="submit" name="schedule_pickup" class="btn btn-primary">Send Approval</button>
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
          </div>
        </form>
      </div>
    </div>
  </div>

  <div class="modal fade" id="viewModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
      <div class="modal-content">
        <div class="modal-header bg-primary text-white">
          <h5 class="modal-title">Request Details</h5>
          <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <div class="mb-2"><strong>Employee ID:</strong> <span id="vEmpID"></span></div>
          <div class="mb-2"><strong>Fullname:</strong> <span id="vFullname"></span></div>
          <div class="mb-2"><strong>Department:</strong> <span id="vDept"></span></div>
          <div class="mb-2"><strong>Position:</strong> <span id="vPos"></span></div>
          <div class="mb-2"><strong>Type of Employment:</strong> <span id="vType"></span></div>
          <div class="mb-2"><strong>Email:</strong> <span id="vEmail"></span></div>
          <div class="mb-2"><strong>Request Type:</strong> <span id="vReqType"></span></div>
          <div class="mb-2" id="vLeaveTypeRow" style="display:none;"><strong>Leave Type:</strong> <span
              id="vLeaveType"></span></div>
          <div class="mb-2" id="vFromRow" style="display:none;"><strong>From:</strong> <span id="vFrom"></span></div>
          <div class="mb-2" id="vToRow" style="display:none;"><strong>To:</strong> <span id="vTo"></span></div>
          <div class="mb-2" id="vDurRow" style="display:none;"><strong>Duration:</strong> <span id="vDur"></span></div>
          <div class="mb-2"><strong>Reason:</strong> <span id="vReason"></span></div>
          <div class="mb-2"><strong>Status:</strong> <span id="vStatus"></span></div>
          <div class="mb-2"><strong>Requested At:</strong> <span id="vRequested"></span></div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
        </div>
      </div>
    </div>
  </div>

  <div class="modal fade" id="rejectModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
      <div class="modal-content">
        <div class="modal-header bg-danger text-white">
          <h5 class="modal-title">Reject Request</h5>
          <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <input type="hidden" id="rejSource">
          <input type="hidden" id="rejEmp">
          <input type="hidden" id="rejTs">
          <input type="hidden" id="rejId">
          <label class="form-label">Reason</label>
          <textarea id="rejReason" class="form-control" rows="4" placeholder="Type reason" required></textarea>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
          <button type="button" id="confirmRejectBtn" class="btn btn-danger">Reject</button>
        </div>
      </div>
    </div>
  </div>

  <?php if (!empty($_SESSION['success_modal_text'])): ?>
    <div class="modal fade" id="successModal" tabindex="-1" aria-hidden="true">
      <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-success">
          <div class="modal-header bg-success text-white">
            <h5 class="modal-title">Success</h5>
            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
          </div>
          <div class="modal-body text-center">
            <i class="fa-solid fa-check-circle fa-2x text-success mb-3"></i>
            <p><?php echo htmlspecialchars($_SESSION['success_modal_text']); ?></p>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-outline-success" data-bs-dismiss="modal">Close</button>
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
      const m = new bootstrap.Modal(document.getElementById('pickupModal'));
      m.show();
    <?php endif; ?>
    <?php if (!empty($_SESSION['success_modal_text'])): ?>
      document.addEventListener('DOMContentLoaded', function () {
        var s = document.getElementById('successModal');
        if (s) new bootstrap.Modal(s).show();
      });
    <?php endif; ?>

    document.addEventListener('click', function (e) {
      const v = e.target.closest('a.view');
      if (v) {
        e.preventDefault();
        const src = v.dataset.source;
        const fd = new FormData();
        fd.append('ajax_get_request_detail', '1');
        fd.append('source', src);
        if (src === 'leave') {
          fd.append('emp', v.dataset.emp);
          fd.append('ts', v.dataset.ts);
        } else {
          fd.append('id', v.dataset.id);
        }
        fetch('Manager_Approvals.php', { method: 'POST', body: fd })
          .then(r => r.json())
          .then(json => {
            if (json && json.ok && json.data) {
              const d = json.data;
              document.getElementById('vEmpID').textContent = d.empID || '';
              document.getElementById('vFullname').textContent = d.fullname || '';
              document.getElementById('vDept').textContent = d.department || '';
              document.getElementById('vPos').textContent = d.position || '';
              document.getElementById('vType').textContent = d.type_name || '';
              document.getElementById('vEmail').textContent = (d.email_address || d.email || '');
              document.getElementById('vReqType').textContent = d.request_type_name || '';
              document.getElementById('vReason').textContent = d.reason || '';
              document.getElementById('vStatus').textContent = d.status || '';
              document.getElementById('vRequested').textContent = d.requested_at || '';
              const isLeave = !!d.leave_type_name;
              document.getElementById('vLeaveTypeRow').style.display = isLeave ? '' : 'none';
              document.getElementById('vFromRow').style.display = isLeave ? '' : 'none';
              document.getElementById('vToRow').style.display = isLeave ? '' : 'none';
              document.getElementById('vDurRow').style.display = isLeave ? '' : 'none';
              document.getElementById('vLeaveType').textContent = d.leave_type_name || '';
              document.getElementById('vFrom').textContent = d.from_date || '';
              document.getElementById('vTo').textContent = d.to_date || '';
              document.getElementById('vDur').textContent = d.duration || '';
              new bootstrap.Modal(document.getElementById('viewModal')).show();
            }
          });
      }

      const rj = e.target.closest('a.reject');
      if (rj) {
        e.preventDefault();
        document.getElementById('rejSource').value = rj.dataset.source;
        document.getElementById('rejEmp').value = rj.dataset.emp || '';
        document.getElementById('rejTs').value = rj.dataset.ts || '';
        document.getElementById('rejId').value = rj.dataset.id || '';
        new bootstrap.Modal(document.getElementById('rejectModal')).show();
      }
    });

    document.getElementById('confirmRejectBtn').addEventListener('click', function () {
      const src = document.getElementById('rejSource').value;
      const fd = new FormData();
      fd.append('ajax_reject_request', '1');
      fd.append('source', src);
      fd.append('reject_reason', document.getElementById('rejReason').value);
      if (src === 'leave') {
        fd.append('emp', document.getElementById('rejEmp').value);
        fd.append('ts', document.getElementById('rejTs').value);
      } else {
        fd.append('id', document.getElementById('rejId').value);
      }
      fetch('Manager_Approvals.php', { method: 'POST', body: fd })
        .then(r => r.json())
        .then(json => {
          if (json && json.ok) {
            window.location.reload();
          }
        });
    });
  </script>
</body>

</html>