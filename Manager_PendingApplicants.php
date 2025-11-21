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

// Helper to send JSON for AJAX requests
function send_json($data)
{
  header('Content-Type: application/json');
  echo json_encode($data);
  exit;
}

// Determine if request is AJAX (fetch will set this header)
$isAjax = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
if (!$isAjax) {
  // Also allow explicit form param 'ajax' for older clients
  if (isset($_REQUEST['ajax']) && $_REQUEST['ajax'] === '1')
    $isAjax = true;
}

// Flash messages for non-AJAX page loads
$flash_success = $_SESSION['flash_success'] ?? '';
$flash_error = $_SESSION['flash_error'] ?? '';
unset($_SESSION['flash_success'], $_SESSION['flash_error']);

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
    "Vacancies" => "Manager_Vacancies.php",
    "Job Post" => "Manager-JobPosting.php",
    "Calendar" => "Manager_Calendar.php",
    "Approvals" => "Manager_Approvals.php",
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
    "Vacancies" => "Manager_Vacancies.php",
    "Job Post" => "Manager-JobPosting.php",
    "Calendar" => "Manager_Calendar.php",
    "Approvals" => "Manager_Approvals.php",
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

  "HR Assistant" => [
    "Dashboard" => "Manager_Dashboard.php",
    "Applicants" => "Manager_Applicants.php",
    "Pending Applicants" => "Manager_PendingApplicants.php",
    "Newly Hired" => "Newly-Hired.php",
    "Employees" => "Manager_Employees.php",
    "Logout" => "Login.php"
  ],

  "Training and Development Coordinator" => [
    "Dashboard" => "Manager_Dashboard.php",
    "Employees" => "Manager_Employees.php",
    "Calendar" => "Manager_Calendar.php",
    "Requests" => "Manager_Request.php",
    "Logout" => "Login.php"
  ]
];

$role = $_SESSION['sub_role'] ?? "HR Manager";

// ---------- Handle POST: update status and optionally send mail ----------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  // Common sanitized inputs
  $applicantID = trim($_POST['applicantID'] ?? '');
  $new_status = trim($_POST['new_status'] ?? '');
  $send_email_flag = isset($_POST['send_email']) && (string) $_POST['send_email'] === '1';
  $sched_date = trim($_POST['sched_date'] ?? '');
  $sched_time = trim($_POST['sched_time'] ?? '');
  $meet_person = trim($_POST['meet_person'] ?? '');
  $reminder_info = trim($_POST['reminder_info'] ?? '');
  $extra_notes = trim($_POST['extra_notes'] ?? '');

  // Input validation
  if (empty($applicantID) || empty($new_status)) {
    if ($isAjax)
      send_json(['success' => false, 'message' => 'Missing required fields.']);
    $_SESSION['flash_error'] = 'Missing required fields.';
    header("Location: Manager_PendingApplicants.php");
    exit;
  }

  // We'll update both tables (applications and applicant) so UI and DB stay consistent.
  $updateOk = false;

  // 1) Update applications table if row exists
  $updApp = $conn->prepare("UPDATE applications SET status = ? WHERE applicantID = ?");
  if ($updApp) {
    $updApp->bind_param('ss', $new_status, $applicantID);
    $updApp->execute(); // ignore affected_rows here
    $updApp->close();
  }

  // 2) Update applicant table (primary fallback)
  $upd = $conn->prepare("UPDATE applicant SET status = ? WHERE applicantID = ?");
  if ($upd) {
    $upd->bind_param('ss', $new_status, $applicantID);
    if ($upd->execute())
      $updateOk = true;
    $upd->close();
  }

  if (!$updateOk) {
    if ($isAjax)
      send_json(['success' => false, 'message' => 'Failed to update applicant status.']);
    $_SESSION['flash_error'] = 'Failed to update applicant status.';
    header("Location: Manager_PendingApplicants.php");
    exit;
  }

  // If manager selects HIRED, copy job info into applicant table
  if ($new_status === 'Hired') {

    // Fetch job_title, department, and type_name from applications table
    $stmtJob = $conn->prepare("
        SELECT job_title, department_name, type_name
        FROM applications 
        WHERE applicantID = ? 
        LIMIT 1
    ");
    $stmtJob->bind_param("s", $applicantID);
    $stmtJob->execute();
    $jobRes = $stmtJob->get_result();

    if ($jobRes && $jobRow = $jobRes->fetch_assoc()) {
      $jobTitle = $jobRow['job_title'] ?? '';
      $deptName = $jobRow['department_name'] ?? '';
      $typeName = $jobRow['type_name'] ?? '';

      // Update applicant table
      $stmtUpdateApplicant = $conn->prepare("
            UPDATE applicant 
            SET position_applied = ?, department = ?, type_name = ?, hired_at = NOW()
            WHERE applicantID = ?
        ");


      $stmtUpdateApplicant->bind_param("ssss", $jobTitle, $deptName, $typeName, $applicantID);
      $stmtUpdateApplicant->execute();
      $stmtUpdateApplicant->close();
    }

    $stmtJob->close();

  }


  // If email is requested, prepare and send
  if ($send_email_flag) {
    // fetch applicant email & name
    $q = $conn->prepare("SELECT fullName, email_address FROM applicant WHERE applicantID = ? LIMIT 1");
    $fullname = '';
    $email_address = '';
    if ($q) {
      $q->bind_param('s', $applicantID);
      $q->execute();
      $res = $q->get_result();
      if ($res && $row = $res->fetch_assoc()) {
        $fullname = $row['fullName'];
        $email_address = $row['email_address'];
      }
      $q->close();
    }

    if (empty($email_address)) {
      if ($isAjax)
        send_json(['success' => true, 'message' => 'Applicant email not found; status updated but email not sent.']);
      $_SESSION['flash_error'] = 'Applicant email not found; status updated but email not sent.';
      header("Location: Manager_PendingApplicants.php");
      exit;
    }

    // Build subject & body based on status
    $subject = "Application Update";
    $bodyHtml = "";
    $bodyPlain = "";

    // Helper to produce date/time display
    $dtDisplay = '';
    if (!empty($sched_date) || !empty($sched_time)) {
      $dtDisplay = trim(($sched_date ? "Date: {$sched_date}" : '') . ' ' . ($sched_time ? "Time: {$sched_time}" : ''));
    }
    $detailsBlock = '';
    if ($dtDisplay)
      $detailsBlock .= "<p><strong>{$dtDisplay}</strong></p>";
    if ($meet_person)
      $detailsBlock .= "<p><strong>Person to meet:</strong> {$meet_person}</p>";
    if ($reminder_info)
      $detailsBlock .= "<p><strong>Reminder:</strong> {$reminder_info}</p>";
    if ($extra_notes)
      $detailsBlock .= "<p><strong>Notes:</strong> {$extra_notes}</p>";


    if (isset($new_status) && $new_status === 'Rejected') {
      $reason = trim($_POST['reason'] ?? '');

      // 1) Get jobID from applications table (if exists)
      $jobID = null;
      $q = $conn->prepare("SELECT jobID FROM applications WHERE applicantID = ? LIMIT 1");
      if ($q) {
        $q->bind_param('s', $applicantID);
        $q->execute();
        $res = $q->get_result();
        if ($res && $row = $res->fetch_assoc()) {
          $jobID = $row['jobID'];
        }
        $q->close();
      }

      // 2) Insert into rejected_applications
      if ($jobID) {
        $ins = $conn->prepare("INSERT INTO rejected_applications (applicantID, jobID, reason) VALUES (?, ?, ?)");
        if ($ins) {
          $ins->bind_param('sis', $applicantID, $jobID, $reason);
          $ins->execute();
          $ins->close();
        }
      }

      // 3) Delete from applications table
      $del = $conn->prepare("DELETE FROM applications WHERE applicantID = ?");
      if ($del) {
        $del->bind_param('s', $applicantID);
        $del->execute();
        $del->close();
      }

      // 4) Update applicant status to NULL
      $upd = $conn->prepare("UPDATE applicant SET status = 'Pending' WHERE applicantID = ?");
      if ($upd) {
        $upd->bind_param('s', $applicantID);
        $upd->execute();
        $upd->close();
      }
    }


    switch ($new_status) {
      case 'Initial Interview':
        $subject = "HOSPITAL HUMAN RESOURCE Initial Interview Invitation";
        $bodyHtml = "<p>Dear <strong>{$fullname}</strong>,</p>
                     <p>We would like to invite you for an <strong>Initial Interview</strong>.</p>
                     {$detailsBlock}
                     <p>Please reply if you cannot make it or need to reschedule.</p>
                     <p>Regards,<br><strong>{$managername}</strong><br>HR Department</p>";
        break;

      case 'Assessment':
        $subject = "HOSPITAL HUMAN RESOURCE Assessment Invitation (Hands-on Testing)";
        $bodyHtml = "<p>Dear <strong>{$fullname}</strong>,</p>
                     <p>You have been scheduled for an <strong>Assessment (hands-on testing)</strong>.</p>
                     {$detailsBlock}
                     <p>Please bring relevant materials and arrive 10 minutes early.</p>
                     <p>Regards,<br><strong>{$managername}</strong><br>HR Department</p>";
        break;

      case 'Final Interview':
        $subject = "HOSPITAL HUMAN RESOURCE  Final Interview Invitation";
        $bodyHtml = "<p>Dear <strong>{$fullname}</strong>,</p>
                     <p>Congratulations — you have progressed to the <strong>Final Interview</strong> stage.</p>
                     {$detailsBlock}
                     <p>We look forward to meeting you.</p>
                     <p>Regards,<br><strong>{$managername}</strong><br>HR Department</p>";
        break;

      case 'Requirements':
        $subject = "HOSPITAL HUMAN RESOURCE Requirements Submission Request";
        $bodyHtml = "<p>Dear <strong>{$fullname}</strong>,</p>
                     <p>Please submit the required employment documents (e.g., medicals, IDs, clearances) as soon as possible.</p>
                     {$detailsBlock}
                     <p>If you have questions, reply to this email.</p>
                     <p>Regards,<br><strong>{$managername}</strong><br>HR Department</p>";
        break;

      case 'Hired':
        $subject = "HOSPITAL HUMAN RESOURCE  Employment Confirmation";
        $bodyHtml = "<p>Dear <strong>{$fullname}</strong>,</p>
                     <p>Congratulations! We are pleased to inform you that you have been <strong>Hired</strong>. Welcome aboard!</p>
                     <p>Further onboarding details will be sent to you shortly.</p>
                     <p>Regards,<br><strong>{$managername}</strong><br>HR Department</p>";
        break;


      case 'Rejected':
        $subject = "HOSPITAL HUMAN RESOURCE  Application Update";
        $bodyHtml = "<p>Dear <strong>{$fullname}</strong>,</p>
                     <p>Thank you for applying. After careful consideration, we regret to inform you that you were not selected for this position.</p>
                     <p>We appreciate your interest and wish you success in your future endeavors.</p>
                     <p>Regards,<br><strong>{$managername}</strong><br>HR Department</p>";
        break;

      default:
        $subject = "HOSPITAL HUMAN RESOURCE  Application Status: {$new_status}";
        $bodyHtml = "<p>Dear <strong>{$fullname}</strong>,</p>
                     <p>Your application status has been updated to <strong>{$new_status}</strong>.</p>
                     {$detailsBlock}
                     <p>Regards,<br><strong>{$managername}</strong><br>HR Department</p>";
    }

    // plain text fallback
    $bodyPlain = strip_tags(str_replace(['<br>', '<br/>', '<p>', '</p>'], "\n", $bodyHtml));

    // send mail via PHPMailer
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
      $mail->addAddress($email_address, $fullname);
      $mail->isHTML(true);
      $mail->Subject = $subject;
      $mail->Body = $bodyHtml;
      $mail->AltBody = $bodyPlain;

      $mail->send();
      if ($isAjax)
        send_json(['success' => true, 'message' => "Status updated and email sent to {$fullname}."]);
      $_SESSION['flash_success'] = "Status updated and email sent to {$fullname}.";
    } catch (Exception $e) {
      $err = $mail->ErrorInfo ?? $e->getMessage();
      if ($isAjax)
        send_json(['success' => false, 'message' => "Status updated but email failed to send: " . $err]);
      $_SESSION['flash_error'] = "Status updated but email failed to send: " . $err;
    }
  } else {
    // email not requested (just update)
    if ($isAjax)
      send_json(['success' => true, 'message' => "Status updated to {$new_status}."]);
    $_SESSION['flash_success'] = "Status updated to {$new_status}.";
  }

  // After updating DB (and sending email if requested) non-AJAX will redirect
  if (!$isAjax) {
    header("Location: Manager_PendingApplicants.php");
    exit;
  }
}

// ---------- fetch applicants for display ----------
$search = trim($_GET['q'] ?? '');
$statusFilter = $_GET['status'] ?? 'Pending';

$pendingApplicants = [];
// Use LEFT JOIN so applicants without an 'applications' row still appear.
$sql = "
SELECT 
    a.applicantID,
    a.fullName,
    a.email_address,
    app.status AS application_status,
    app.applied_at,
    a.date_applied
FROM applicant a
INNER JOIN applications app 
    ON a.applicantID = app.applicantID
";

$clauses = [];
$params = [];
$types = '';

if ($statusFilter && strtolower($statusFilter) !== 'all') {
  $clauses[] = "COALESCE(app.status, a.status) = ?";
  $types .= 's';
  $params[] = $statusFilter;
}
if ($search !== '') {
  $clauses[] = "(a.applicantID LIKE ? OR a.fullName LIKE ? OR a.email_address LIKE ?)";
  $like = "%{$search}%";
  $types .= 'sss';
  $params[] = $like;
  $params[] = $like;
  $params[] = $like;
}
if (!empty($clauses))
  $sql .= ' WHERE ' . implode(' AND ', $clauses);

// Order by the most relevant date (applications.applied_at if present, otherwise applicant.date_applied)
$sql .= ' ORDER BY COALESCE(app.applied_at, a.date_applied) DESC';

if ($stmt = $conn->prepare($sql)) {
  if (!empty($params)) {
    // Properly bind params (bind_param requires references)
    $refs = [];
    $refs[] = &$types;
    for ($i = 0; $i < count($params); $i++) {
      $refs[] = &$params[$i];
    }
    call_user_func_array([$stmt, 'bind_param'], $refs);
  }
  $stmt->execute();
  $res = $stmt->get_result();
  while ($r = $res->fetch_assoc())
    $pendingApplicants[] = $r;
  $stmt->close();
}
// AJAX endpoint to fetch full applicant + application info
if ($isAjax && isset($_GET['action']) && $_GET['action'] === 'getApplicantDetails') {
  $appid = trim($_GET['applicantID'] ?? '');
  if (empty($appid))
    send_json(['success' => false, 'message' => 'Missing applicant ID']);

  // Fetch all applicant fields + job info from applications
  $stmt = $conn->prepare("
        SELECT 
            a.*, 
            app.jobID, 
            app.job_title AS applied_job_title, 
            app.department_name
        FROM applicant a
        LEFT JOIN applications app ON a.applicantID = app.applicantID
        WHERE a.applicantID = ? 
        LIMIT 1
    ");
  $stmt->bind_param('s', $appid);
  $stmt->execute();
  $res = $stmt->get_result();

  if ($row = $res->fetch_assoc()) {
    // Convert local path to web URL for profile picture
    $row['profile_pic'] = !empty($row['profile_pic'])
      ? 'uploads/applicants/' . $row['profile_pic']
      : ''; // fallback to empty or default

    send_json(['success' => true, 'data' => $row]);
  } else {
    send_json(['success' => false, 'message' => 'Applicant not found']);
  }
}


?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Manager - Pending Applicants</title>

  <!-- Bootstrap CSS (keep your theme colors intact) -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity=""
    crossorigin="anonymous">

  <link rel="stylesheet" href="manager-sidebar.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/7.0.1/css/all.min.css"
    crossorigin="anonymous" referrerpolicy="no-referrer" />

  <style>
    /* keep your styles and small modal additions */
    body {
      font-family: 'Poppins', 'Roboto', sans-serif;
      margin: 0;
      display: flex;
      background: #f1f5fc;
      color: #111827;
    }

    .main-content {
      padding: 40px 30px;
      margin-left: 220px;
      display: flex;
      flex-direction: column;
    }

    .main-content-header h1 {
      margin: 0;
      font-size: 26px;
      font-weight: 700;
      margin-bottom: 40px;
      color: #1E3A8A;
      margin-left: 40px;
    }

    .header {
      display: flex;
      justify-content: space-between;
      align-items: center;
      margin-bottom: 20px;
    }

    .search-filter {
      display: flex;
      align-items: center;
      gap: 15px;
      margin-left: 40px;
      width: 100%;
    }

    .search-box {
      position: relative;
      flex: 1;
      max-width: 350px;
    }

    .search-box input {
      width: 100%;
      padding: 10px 40px;
      border: 1px solid #d1d5db;
      border-radius: 25px;
      font-size: 14px;
      background: white;
      outline: none;
      transition: all .3s;
    }

    .search-box input:focus {
      border-color: #1e3a8a;
      box-shadow: 0 0 0 3px rgba(30, 58, 138, 0.15);
    }

    .search-box i {
      position: absolute;
      left: 15px;
      top: 50%;
      transform: translateY(-50%);
      color: #6b7280;
      font-size: 14px;
    }

    select {
      border-radius: 25px;
      padding: 10px 18px;
      border: 1px solid #d1d5db;
      background: #fff;
      font-size: 14px;
      color: #333;
      outline: none;
      cursor: pointer;
      transition: all .3s;
    }

    .table-custom {
      width: 100%;
      border-collapse: collapse;
      background: #fff;
      border-radius: 10px;
      overflow: hidden;
      box-shadow: 0 3px 6px rgba(0, 0, 0, 0.08);
      margin-left: 100px;
      margin-left: 40px;
    }

    .table-custom th,
    .table-custom td {
      text-align: center;
      vertical-align: middle;
      padding: 18px 30px;
      border-bottom: 1px solid #e0e0e0;
      font-size: 14px;
    }

    .table-custom thead {
      background: #1E3A8A;
      color: #fff;
    }

    tbody tr:hover {
      background: #f8f9fa;
    }

    .table-custom {
      width: 150%;
      border-collapse: collapse;
      background: #fff;
      border-radius: 10px;
      overflow: hidden;
      box-shadow: 0 3px 6px rgba(0, 0, 0, 0.08);
      table-layout: auto;
    }

    .table-custom th,
    .table-custom td {
      text-align: center;
      vertical-align: middle;
      padding: 18px 30px;
      border-bottom: 1px solid #e0e0e0;
      font-size: 14px;
      word-break: break-word;
      /* wrap long text */
      overflow-wrap: break-word;
    }

    .view-btn {
      background: #1E3A8A;
      color: #fff;
      border: none;
      border-radius: 25px;
      padding: 8px 16px;
      font-size: 13px;
      cursor: pointer;
      display: inline-flex;
      align-items: center;
      gap: 6px;
    }

    .view-btn:hover {
      background: #1e40af;
      transform: translateY(-2px);
    }

    .flash-wrap {
      margin-left: 200px;
      max-width: 1200px;
      width: 100%;
    }

    /* Modal */
    .modal-custom {
      display: none;
      position: fixed;
      z-index: 1200;
      left: 0;
      top: 0;
      width: 100%;
      height: 100%;
      background: rgba(0, 0, 0, 0.45);
      justify-content: center;
      align-items: center;
    }

    .modal-custom.active {
      display: flex;
    }

    .modal-content-custom {
      background: #fff;
      padding: 18px 20px;
      border-radius: 10px;
      width: 480px;
      max-width: 94%;
      box-shadow: 0 8px 30px rgba(2, 6, 23, 0.16);
    }

    .modal-content-custom h3 {
      margin: 0 0 8px 0;
      font-size: 18px;
      color: #1E3A8A;
    }

    .modal-row {
      display: flex;
      gap: 10px;
      margin-bottom: 10px;
    }

    .modal-row .col {
      flex: 1;
    }

    .modal-content-custom label {
      display: block;
      font-size: 13px;
      margin-bottom: 6px;
      color: #374151;
    }

    .modal-content-custom input[type="date"],
    .modal-content-custom input[type="time"],
    .modal-content-custom input[type="text"],
    .modal-content-custom textarea {
      width: 100%;
      padding: 8px;
      border: 1px solid #d1d5db;
      border-radius: 6px;
      box-sizing: border-box;
      font-size: 14px;
    }

    .modal-actions {
      display: flex;
      justify-content: flex-end;
      gap: 8px;
      margin-top: 12px;
    }

    .cancel-btn {
      background: #efefef;
      color: #111827;
      padding: 8px 14px;
      border-radius: 6px;
      border: none;
      cursor: pointer;
    }

    .send-btn {
      background: #1E3A8A;
      color: #fff;
      border: none;
      padding: 8px 14px;
      border-radius: 6px;
      cursor: pointer;
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
        <li><a href="<?php echo $link; ?>"><?php echo $label; ?></a></li>
      <?php endforeach; ?>
    </ul>
  </div>


  <div class="main-content">
    <div class="main-content-header">
      <h1>Pending Applicants</h1>
    </div>

    <div class="flash-wrap" id="flashWrap">
      <?php if (!empty($flash_success)): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
          <?php echo htmlspecialchars($flash_success); ?>
          <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
      <?php endif; ?>
      <?php if (!empty($flash_error)): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
          <?php echo htmlspecialchars($flash_error); ?>
          <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
      <?php endif; ?>
    </div>

    <div class="header">
      <div class="search-filter">
        <form method="get" id="filterForm" style="display:flex;align-items:center;gap:15px;">
          <div class="search-box">
            <i class="fa-solid fa-magnifying-glass"></i>
            <input autocomplete="off" type="text" id="searchInput" name="q" placeholder="Search applicants..."
              value="<?php echo htmlspecialchars($search ?? ''); ?>">
          </div>

          <select id="statusFilter" name="status">
            <option value="all" <?php echo (isset($statusFilter) && strtolower($statusFilter) === 'all') ? 'selected' : ''; ?>>All Status</option>
            <option value="Pending" <?php echo (isset($statusFilter) && $statusFilter === 'Pending') ? 'selected' : ''; ?>>
              Pending</option>
            <option value="Initial Interview" <?php echo (isset($statusFilter) && $statusFilter === 'Initial Interview') ? 'selected' : ''; ?>>Initial Interview</option>
            <option value="Assessment" <?php echo (isset($statusFilter) && $statusFilter === 'Assessment') ? 'selected' : ''; ?>>Assessment</option>
            <option value="Final Interview" <?php echo (isset($statusFilter) && $statusFilter === 'Final Interview') ? 'selected' : ''; ?>>Final Interview</option>
            <option value="Requirements" <?php echo (isset($statusFilter) && $statusFilter === 'Requirements') ? 'selected' : ''; ?>>Requirements</option>

          </select>

          <button type="submit" class="btn"
            style="background:#1E3A8A;color:#fff;border:none;padding:8px 14px;border-radius:8px;">Search</button>
        </form>
      </div>
    </div>

    <table class="table table-custom" id="applicantTable">
      <thead>
        <tr>
          <th>Applicant ID</th>
          <th>Full Name</th>
          <th>Action</th>
          <th>Status</th>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($pendingApplicants)): ?>
          <tr>
            <td colspan="4">No applicants found.</td>
          </tr>
        <?php else:
          foreach ($pendingApplicants as $p): ?>
            <tr id="row-<?php echo htmlspecialchars($p['applicantID']); ?>">
              <td><?php echo htmlspecialchars($p['applicantID']); ?></td>
              <td><?php echo htmlspecialchars($p['fullName']); ?></td>
              <td>
                <button class="view-btn" data-appid="<?php echo htmlspecialchars($p['applicantID']); ?>"> <i
                    class="fa-solid fa-eye"></i> View</button>
              </td>
              <td>
                <?php
                $current = $p['application_status'] ?? '';
                if (empty($current))
                  $current = 'Pending';
                $opts = ['Pending', 'Initial Interview', 'Assessment', 'Final Interview', 'Requirements', 'Hired', 'Rejected'];
                ?>

                <select class="status-select form-select form-select-sm"
                  data-appid="<?php echo htmlspecialchars($p['applicantID']); ?>"
                  data-email="<?php echo htmlspecialchars($p['email_address']); ?>"
                  data-fullname="<?php echo htmlspecialchars($p['fullName']); ?>"
                  style="max-width:220px; display:inline-block;">
                  <?php foreach ($opts as $opt): ?>
                    <option value="<?php echo htmlspecialchars($opt); ?>" <?php echo ($current === $opt) ? 'selected' : ''; ?>>
                      <?php echo htmlspecialchars($opt); ?>
                    </option>
                  <?php endforeach; ?>
                </select>
              </td>
            </tr>
          <?php endforeach; endif; ?>
      </tbody>
    </table>
  </div>

  <!-- Applicant Details Modal -->
  <div id="applicantModal" class="modal fade" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
      <div class="modal-content border-0 shadow-lg">
        <div class="modal-header bg-primary text-white">
          <h5 class="modal-title" id="applicantModalTitle">Applicant Details</h5>
          <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <div class="row mb-3">
            <div class="col-md-4 text-center">
              <img id="applicantPic" src="Images/default-profile.png" alt="Profile Picture" class="img-fluid border"
                style="max-height:200px; width:auto; object-fit:cover;">
            </div>
            <div class="col-md-8">
              <p><strong>Name:</strong> <span id="applicantName"></span></p>
              <p><strong>Email:</strong> <span id="applicantEmail"></span></p>
              <p><strong>Contact:</strong> <span id="applicantContact"></span></p>
              <p><strong>Position Applied:</strong> <span id="applicantPosition"></span></p>
              <p><strong>Department:</strong> <span id="applicantDepartment"></span></p>
              <p><strong>Date Applied:</strong> <span id="applicantDateApplied"></span></p>
            </div>
          </div>

          <hr>

          <div class="row mb-3">
            <div class="col-md-6">
              <p><strong>Education:</strong> <span id="applicantEducation"></span></p>
              <p><strong>Experience:</strong> <span id="applicantExperience"></span></p>
            </div>
            <div class="col-md-6">
              <p><strong>Skills:</strong> <span id="applicantSkills"></span></p>
              <p><strong>Summary:</strong> <span id="applicantSummary"></span></p>
            </div>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
        </div>
      </div>
    </div>
  </div>



  <!-- Hidden quick form is no longer submitted directly; kept for fallback -->
  <form id="quickForm" method="post" style="display:none;">
    <input type="hidden" name="applicantID" id="q_applicantID" value="">
    <input type="hidden" name="new_status" id="q_new_status" value="">
    <input type="hidden" name="send_email" id="q_send_email" value="0">
    <input type="hidden" name="ajax" value="1">
  </form>

  <!-- Modal (custom but used with AJAX) -->
  <div id="scheduleModal" class="modal-custom" role="dialog" aria-hidden="true">
    <div class="modal-content-custom" role="document">
      <h3 id="modalTitle">Schedule / Details</h3>
      <p id="modalSub">Fill in the details below and click <strong>Send Email</strong>.</p>

      <form id="modalForm" method="post">
        <input type="hidden" name="applicantID" id="m_applicantID" value="">
        <input type="hidden" name="new_status" id="m_new_status" value="">
        <input type="hidden" name="send_email" value="1">
        <div class="modal-row">
          <div class="col">
            <label for="sched_date">Date</label>
            <input type="date" id="sched_date" name="sched_date" required>
          </div>
          <div class="col">
            <label for="sched_time">Time</label>
            <input type="time" id="sched_time" name="sched_time" required>
          </div>
        </div>

        <div style="margin-bottom:10px;">
          <label for="meet_person">Person to meet</label>
          <input type="text" id="meet_person" name="meet_person" placeholder="Person / Interviewer name" required>
        </div>

        <div style="margin-bottom:10px;">
          <label for="reminder_info">Reminder (optional)</label>
          <input type="text" id="reminder_info" name="reminder_info" placeholder="E.g., 1 day before 9:00 AM">
        </div>

        <div style="margin-bottom:6px;">
          <label for="extra_notes">Additional notes (optional)</label>
          <textarea id="extra_notes" name="extra_notes" rows="3" placeholder="Notes for the applicant"></textarea>
        </div>

        <div class="modal-actions">
          <button type="button" class="cancel-btn" id="modalCancelBtn">Cancel</button>
          <button type="submit" class="send-btn">Send Email & Update</button>
        </div>
      </form>
    </div>
  </div>

  <!-- Bootstrap JS (bundle incl. Popper) -->
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity=""
    crossorigin="anonymous"></script>

  <script>
    // Utility: show bootstrap alert dynamically in flashWrap
    function showAlert(message, type = 'success', autoClose = true) {
      const wrap = document.getElementById('flashWrap');
      if (!wrap) return;
      const alertId = 'alert-' + Date.now();
      const div = document.createElement('div');
      div.className = `alert alert-${type} alert-dismissible fade show`;
      div.role = 'alert';
      div.id = alertId;
      div.innerHTML = `${message}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>`;
      // Insert at top
      wrap.prepend(div);
      if (autoClose) {
        setTimeout(() => {
          const bsAlert = bootstrap.Alert.getOrCreateInstance(div);
          bsAlert.close();
        }, 6000);
      }
    }

    // All client-side behavior - use fetch for AJAX
    document.addEventListener('DOMContentLoaded', function () {
      const selects = document.querySelectorAll('.status-select');

      selects.forEach(sel => {
        sel.addEventListener('change', async function (e) {
          const newStatus = this.value;
          const appid = this.dataset.appid;
          const fullname = this.dataset.fullname;

          if (newStatus === 'Hired') {
            if (!confirm(`Are you sure you want to hire ${fullname}?`)) {
              this.value = 'Pending';
              return;
            }


            await updateStatus(appid, newStatus, true, sel);
          } else if (newStatus === 'Rejected') {
            if (!confirm(`Are you sure you want to reject ${fullname}?`)) {
              this.value = 'Pending';
              return;
            }
            const reason = prompt("Enter reason for rejection (optional):", "");
            await updateStatus(appid, newStatus, true, sel, reason);
          } else {
            const needsModal = ['Initial Interview', 'Assessment', 'Final Interview', 'Requirements'];
            if (needsModal.includes(newStatus)) {
              openModalFor(appid, newStatus, fullname);
            } else {
              await updateStatus(appid, newStatus, false, sel);
            }
          }
        });
      });

      // <-- Add the helper function below -->
      async function updateStatus(appid, status, sendEmail, selectEl, reason = '') {
        const payload = new URLSearchParams();
        payload.append('applicantID', appid);
        payload.append('new_status', status);
        payload.append('send_email', sendEmail ? '1' : '0');
        payload.append('ajax', '1');
        if (reason) payload.append('reason', reason);

        try {
          const resp = await fetch(window.location.href, {
            method: 'POST',
            headers: { 'X-Requested-With': 'XMLHttpRequest', 'Content-Type': 'application/x-www-form-urlencoded' },
            body: payload.toString()
          });
          const data = await resp.json();
          if (data.success) {
            showAlert(data.message, 'success');
            if (status === 'Hired' || status === 'Rejected') {
              selectEl.disabled = true;
              selectEl.value = status;
            }
          } else {
            showAlert(data.message, 'danger');
          }
        } catch (err) {
          showAlert('Network error. Try again.', 'danger');
        }
      }
      // modal cancel
      document.getElementById('modalCancelBtn').addEventListener('click', closeModal);

      // modal form submission: AJAX post
      const modalForm = document.getElementById('modalForm');
      modalForm.addEventListener('submit', async function (evt) {
        evt.preventDefault();
        const formData = new FormData(modalForm);
        formData.append('ajax', '1');

        // Convert FormData to x-www-form-urlencoded
        const params = new URLSearchParams();
        for (const pair of formData.entries()) params.append(pair[0], pair[1]);

        try {
          const resp = await fetch(window.location.href, {
            method: 'POST',
            headers: {
              'X-Requested-With': 'XMLHttpRequest',
              'Content-Type': 'application/x-www-form-urlencoded'
            },
            body: params.toString()
          });
          const data = await resp.json();
          if (data.success) {
            showAlert(data.message || 'Email sent and status updated', 'success');
            // Update the select DOM to the new value for that applicant
            const appid = document.getElementById('m_applicantID').value;
            const newStatus = document.getElementById('m_new_status').value;
            const sel = document.querySelector('.status-select[data-appid="' + appid + '"]');
            if (sel) sel.value = newStatus;
            closeModal();
          } else {
            showAlert(data.message || 'Failed to send email / update', 'danger');
          }
        } catch (err) {
          showAlert('Network error. Try again.', 'danger');
        }
      });

      // keep search filter auto-submit
      const statusFilter = document.getElementById('statusFilter');
      const filterForm = document.getElementById('filterForm');
      if (statusFilter && filterForm) {
        statusFilter.addEventListener('change', () => filterForm.submit());
      }
    });

    function openModalFor(appid, status, fullname, email) {
      document.getElementById('m_applicantID').value = appid;
      document.getElementById('m_new_status').value = status;
      const title = `You will invite ${fullname} for ${status}`;
      document.getElementById('modalTitle').textContent = title;
      document.getElementById('modalSub').textContent = 'Kindly set the date, time, person to meet, and reminders.';
      document.getElementById('sched_date').value = '';
      document.getElementById('sched_time').value = '';
      document.getElementById('meet_person').value = '';
      document.getElementById('reminder_info').value = '';
      document.getElementById('extra_notes').value = '';
      const modal = document.getElementById('scheduleModal');
      modal.classList.add('active');
      modal.setAttribute('aria-hidden', 'false');
    }

    function closeModal() {
      const modal = document.getElementById('scheduleModal');
      modal.classList.remove('active');
      modal.setAttribute('aria-hidden', 'true');
    }

    // Show applicant modal with Bootstrap
    async function showApplicantDetails(applicantID) {
      try {
        const resp = await fetch(`?action=getApplicantDetails&applicantID=${encodeURIComponent(applicantID)}`, {
          headers: { 'X-Requested-With': 'XMLHttpRequest' }
        });
        const data = await resp.json();
        if (!data.success) return alert(data.message || 'Applicant not found');

        const d = data.data;
        document.getElementById('applicantModalTitle').textContent = d.fullName;
        document.getElementById('applicantPic').src = d.profile_pic || 'Images/default-profile.png';
        document.getElementById('applicantName').textContent = d.fullName;
        document.getElementById('applicantEmail').textContent = d.email_address;
        document.getElementById('applicantContact').textContent = d.contact_number || '-';
        document.getElementById('applicantPosition').textContent =  d.applied_job_title || '-';
        document.getElementById('applicantDepartment').textContent = d.department_name || '-';
        document.getElementById('applicantDateApplied').textContent = d.date_applied || '-';
        document.getElementById('applicantEducation').textContent = `${d.university || '-'} (${d.course || '-'}, Graduated: ${d.year_graduated || '-'})`;
        document.getElementById('applicantExperience').textContent = `${d.years_experience || 0} years`;
        document.getElementById('applicantSkills').textContent = d.skills || '-';
        document.getElementById('applicantSummary').textContent = d.summary || '-';

        const modal = new bootstrap.Modal(document.getElementById('applicantModal'));
        modal.show();
      } catch (err) {
        alert('Network error. Try again.');
      }
    }

    // Attach click handler for all view buttons
    document.querySelectorAll('.view-btn').forEach(btn => {
      btn.addEventListener('click', () => showApplicantDetails(btn.dataset.appid));
    });

    function closeApplicantModal() {
      const modal = document.getElementById('applicantModal');
      modal.classList.remove('active');
      modal.setAttribute('aria-hidden', 'true');
    }

  </script>
</body>

</html>