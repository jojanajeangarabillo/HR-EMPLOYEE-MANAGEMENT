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
    "Requests" => "Manager_Request.php",
    "Reports" => "Manager_Reports.php",
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
$icons = [
  "Dashboard" => "fa-table-columns",
  "Applicants" => "fa-user",
  "Pending Applicants" => "fa-clock",
  "Newly Hired" => "fa-user-check",
  "Employees" => "fa-users",
  "Requests" => "fa-file-lines",
  "Vacancies" => "fa-briefcase",
  "Job Post" => "fa-bullhorn",
  "Calendar" => "fa-calendar-days",
  "Approvals" => "fa-square-check",
  "Reports" => "fa-chart-column",
  "Settings" => "fa-gear",
  "Logout" => "fa-right-from-bracket"
];

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

  <!-- Bootstrap CSS -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" crossorigin="anonymous">

  <!-- Font Awesome -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/7.0.1/css/all.min.css" crossorigin="anonymous" referrerpolicy="no-referrer" />

  <!-- Google Fonts -->
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">

  <!-- Custom Styles -->
  <link rel="stylesheet" href="manager-sidebar.css">
  
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
      padding-bottom: 15px;
      border-bottom: 1px solid var(--gray-light);
    }

    .main-content-header h1 {
      color: var(--primary);
      font-weight: 700;
      margin: 0;
      font-size: 28px;
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

    /* Stats Summary */
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

    .stat-card.pending { border-left-color: var(--warning); }
    .stat-card.interview { border-left-color: var(--primary-light); }
    .stat-card.assessment { border-left-color: var(--accent); }
    .stat-card.requirements { border-left-color: var(--secondary); }

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

    .stat-card.pending .stat-card-icon { background: var(--warning); }
    .stat-card.interview .stat-card-icon { background: var(--primary-light); }
    .stat-card.assessment .stat-card-icon { background: var(--accent); }
    .stat-card.requirements .stat-card-icon { background: var(--secondary); }

    .stat-card-value {
      font-size: 28px;
      font-weight: 700;
      color: var(--dark);
      margin: 0;
    }

    /* Search and Filter */
    .search-filter-container {
      background: white;
      border-radius: 15px;
      padding: 25px;
      box-shadow: var(--card-shadow);
      margin-bottom: 30px;
    }

    .search-box {
      position: relative;
      flex: 1;
    }

    .search-box input {
      width: 100%;
      padding: 12px 45px;
      border: 1px solid var(--gray-light);
      border-radius: 10px;
      font-size: 14px;
      background: white;
      transition: all 0.3s;
    }

    .search-box input:focus {
      outline: none;
      border-color: var(--primary);
      box-shadow: 0 0 0 3px rgba(30, 58, 138, 0.12);
    }

    .search-box i {
      position: absolute;
      left: 15px;
      top: 50%;
      transform: translateY(-50%);
      color: var(--gray);
      font-size: 16px;
    }

    .filter-box select {
      border-radius: 10px;
      padding: 12px 20px;
      border: 1px solid var(--gray-light);
      background: white;
      font-size: 14px;
      color: var(--dark);
      min-width: 180px;
      transition: all 0.3s;
    }

    .filter-box select:focus {
      outline: none;
      border-color: var(--primary);
      box-shadow: 0 0 0 3px rgba(30, 58, 138, 0.12);
    }

    .search-btn {
      background: var(--primary);
      color: white;
      border: none;
      border-radius: 10px;
      padding: 12px 24px;
      font-weight: 500;
      transition: all 0.3s;
      display: flex;
      align-items: center;
      gap: 8px;
    }

    .search-btn:hover {
      background: var(--primary-dark);
      transform: translateY(-2px);
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

    /* Buttons */
    .view-btn {
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

    .view-btn:hover {
      background: var(--primary-dark);
      transform: translateY(-2px);
      box-shadow: 0 4px 12px rgba(30, 64, 175, 0.3);
    }

    .status-select {
      border: 1px solid var(--gray-light);
      border-radius: 8px;
      padding: 8px 12px;
      font-size: 13px;
      background: white;
      color: var(--dark);
      transition: all 0.3s;
      min-width: 160px;
    }

    .status-select:focus {
      outline: none;
      border-color: var(--primary);
      box-shadow: 0 0 0 3px rgba(30, 58, 138, 0.12);
    }

    /* Enhanced Modal Styles */
    .modal-custom {
      display: none;
      position: fixed;
      z-index: 1200;
      left: 0;
      top: 0;
      width: 100%;
      height: 100%;
      background: rgba(0, 0, 0, 0.5);
      justify-content: center;
      align-items: center;
      backdrop-filter: blur(4px);
    }

    .modal-custom.active {
      display: flex;
      animation: fadeIn 0.3s ease;
    }

    @keyframes fadeIn {
      from { opacity: 0; }
      to { opacity: 1; }
    }

    .modal-content-custom {
      background: white;
      border-radius: 15px;
      width: 500px;
      max-width: 90%;
      max-height: 90vh;
      overflow-y: auto;
      box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
      animation: slideUp 0.3s ease;
    }

    @keyframes slideUp {
      from {
        transform: translateY(40px);
        opacity: 0;
      }
      to {
        transform: translateY(0);
        opacity: 1;
      }
    }

    .modal-header-custom {
      padding: 20px 25px;
      border-bottom: 1px solid var(--gray-light);
      display: flex;
      justify-content: space-between;
      align-items: center;
    }

    .modal-title-custom {
      font-size: 20px;
      font-weight: 600;
      color: var(--primary);
      margin: 0;
      display: flex;
      align-items: center;
      gap: 10px;
    }

    .close-btn-custom {
      background: transparent;
      border: none;
      color: var(--gray);
      font-size: 20px;
      cursor: pointer;
      transition: all 0.3s;
      width: 32px;
      height: 32px;
      border-radius: 50%;
      display: flex;
      align-items: center;
      justify-content: center;
    }

    .close-btn-custom:hover {
      background: var(--gray-light);
      color: var(--dark);
    }

    .modal-body-custom {
      padding: 25px;
    }

    .modal-row {
      display: flex;
      gap: 15px;
      margin-bottom: 15px;
    }

    .modal-row .col {
      flex: 1;
    }

    .form-group {
      margin-bottom: 15px;
    }

    .form-group label {
      display: block;
      font-size: 14px;
      font-weight: 500;
      margin-bottom: 6px;
      color: var(--dark);
    }

    .form-control-custom {
      width: 100%;
      padding: 10px 12px;
      border: 1px solid var(--gray-light);
      border-radius: 8px;
      font-size: 14px;
      transition: all 0.3s;
    }

    .form-control-custom:focus {
      outline: none;
      border-color: var(--primary);
      box-shadow: 0 0 0 3px rgba(30, 58, 138, 0.12);
    }

    .modal-actions {
      display: flex;
      justify-content: flex-end;
      gap: 10px;
      margin-top: 20px;
      padding-top: 20px;
      border-top: 1px solid var(--gray-light);
    }

    .cancel-btn {
      background: var(--gray-light);
      color: var(--dark);
      border: none;
      border-radius: 8px;
      padding: 10px 20px;
      font-weight: 500;
      cursor: pointer;
      transition: all 0.3s;
    }

    .cancel-btn:hover {
      background: #d1d5db;
    }

    .send-btn {
      background: var(--primary);
      color: white;
      border: none;
      border-radius: 8px;
      padding: 10px 20px;
      font-weight: 500;
      cursor: pointer;
      transition: all 0.3s;
      display: flex;
      align-items: center;
      gap: 6px;
    }

    .send-btn:hover {
      background: var(--primary-dark);
      transform: translateY(-2px);
    }

    /* Success Modal */
    .success-modal .modal-content {
      text-align: center;
      padding: 30px;
    }

    .success-icon {
      width: 80px;
      height: 80px;
      background: var(--secondary);
      border-radius: 50%;
      display: flex;
      align-items: center;
      justify-content: center;
      margin: 0 auto 20px;
      color: white;
      font-size: 36px;
    }

    .success-title {
      font-size: 24px;
      font-weight: 600;
      color: var(--secondary);
      margin-bottom: 10px;
    }

    .success-message {
      color: var(--gray);
      margin-bottom: 25px;
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
      
      .search-filter-container {
        padding: 20px;
      }
      
      .table-header {
        flex-direction: column;
        gap: 15px;
        align-items: flex-start;
      }
      
      .modal-row {
        flex-direction: column;
        gap: 0;
      }
    }

    @media (max-width: 576px) {
      .main-content {
        padding: 20px 15px;
      }
      
      .modal-content-custom {
        width: 95%;
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
        <li><a href="<?php echo $link; ?>"><i
              class="fa-solid <?php echo $icons[$label] ?? 'fa-circle'; ?>"></i><?php echo $label; ?></a></li>
      <?php endforeach; ?>
    </ul>
  </div>

  <!-- MAIN CONTENT -->
  <div class="main-content">
    <div class="main-content-header">
      <h1>Pending Applicants</h1>
      <div class="header-actions">
        <span class="date-display">
          <i class="far fa-calendar-alt me-2"></i>
          <?php echo date("F j, Y"); ?>
        </span>
      </div>
    </div>

    <!-- Flash Messages -->
    <?php if (!empty($flash_success)): ?>
      <div class="alert alert-success alert-dismissible fade show" role="alert" style="max-width: 100%;">
        <i class="fas fa-check-circle me-2"></i>
        <?php echo htmlspecialchars($flash_success); ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
      </div>
    <?php endif; ?>

    <?php if (!empty($flash_error)): ?>
      <div class="alert alert-danger alert-dismissible fade show" role="alert" style="max-width: 100%;">
        <i class="fas fa-exclamation-circle me-2"></i>
        <?php echo htmlspecialchars($flash_error); ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
      </div>
    <?php endif; ?>

    <!-- Stats Summary -->
    <?php
   
    // Calculate status counts
    $pendingCount = 0;
    $initialInterviewCount = 0;
    $assessmentCount = 0;
    $finalInterviewCount = 0;
    $requirementsCount = 0;
    $rejectedCount = 0;
    $hiredCount = 0;
    
    foreach ($pendingApplicants as $applicant) {
      $status = $applicant['application_status'] ?? '';
      if ($status === 'Pending') $pendingCount++;
      elseif ($status === 'Initial Interview') $initialInterviewCount++;
      elseif ($status === 'Assessment') $assessmentCount++;
      elseif ($status === 'Final Interview') $finalInterviewCount++;
      elseif ($status === 'Requirements') $requirementsCount++;
      elseif ($status === 'Rejected') $rejectedCount++;
      elseif ($status === 'Hired') $hiredCount++;
    }
    
    $totalCount = count($pendingApplicants);
    ?>

    
    
    <div class="stats-summary">
         <div class="stat-card" style="border-left-color: #1E3A8A;">
        <div class="stat-card-header">
          <h3 class="stat-card-title">Total Applicants</h3>
          <div class="stat-card-icon" style="background: #1E3A8A;">
            <i class="fas fa-users"></i>
          </div>
        </div>
        <p class="stat-card-value"><?php echo $totalCount; ?></p>
      </div>

      <div class="stat-card pending">
        <div class="stat-card-header">
          <h3 class="stat-card-title">Pending Review</h3>
          <div class="stat-card-icon">
            <i class="fas fa-clock"></i>
          </div>
        </div>
        <p class="stat-card-value"><?php echo $pendingCount; ?></p>
      </div>

      <div class="stat-card interview">
        <div class="stat-card-header">
          <h3 class="stat-card-title">Initial Interviews</h3>
          <div class="stat-card-icon">
            <i class="fas fa-handshake"></i>
          </div>
        </div>
        <p class="stat-card-value"><?php echo $initialInterviewCount; ?></p>
      </div>

      <div class="stat-card assessment">
        <div class="stat-card-header">
          <h3 class="stat-card-title">Assessments</h3>
          <div class="stat-card-icon">
            <i class="fas fa-tasks"></i>
          </div>
        </div>
        <p class="stat-card-value"><?php echo $assessmentCount; ?></p>
      </div>

      <div class="stat-card requirements">
        <div class="stat-card-header">
          <h3 class="stat-card-title">Requirements</h3>
          <div class="stat-card-icon">
            <i class="fas fa-file-alt"></i>
          </div>
        </div>
        <p class="stat-card-value"><?php echo $requirementsCount; ?></p>
      </div>

       <!-- New Stat Cards -->
      <div class="stat-card" style="border-left-color: #8B5CF6;">
        <div class="stat-card-header">
          <h3 class="stat-card-title">Final Interviews</h3>
          <div class="stat-card-icon" style="background: #8B5CF6;">
            <i class="fas fa-user-tie"></i>
          </div>
        </div>
        <p class="stat-card-value"><?php echo $finalInterviewCount; ?></p>
      </div>

      <div class="stat-card" style="border-left-color: #EF4444;">
        <div class="stat-card-header">
          <h3 class="stat-card-title">Rejected</h3>
          <div class="stat-card-icon" style="background: #EF4444;">
            <i class="fas fa-times-circle"></i>
          </div>
        </div>
        <p class="stat-card-value"><?php echo $rejectedCount; ?></p>
      </div>

      <div class="stat-card" style="border-left-color: #10B981;">
        <div class="stat-card-header">
          <h3 class="stat-card-title">Hired</h3>
          <div class="stat-card-icon" style="background: #10B981;">
            <i class="fas fa-user-check"></i>
          </div>
        </div>
        <p class="stat-card-value"><?php echo $hiredCount; ?></p>
      </div>

   
    </div>

    

    <!-- Search and Filter -->
    <div class="search-filter-container">
      <form method="get" id="filterForm">
        <div class="row g-3 align-items-center">
          <div class="col-md-6">
            <div class="search-box">
              <i class="fa-solid fa-magnifying-glass"></i>
              <input autocomplete="off" type="text" id="searchInput" name="q" placeholder="Search by name, ID, or email..."
                value="<?php echo htmlspecialchars($search ?? ''); ?>">
            </div>
          </div>
          <div class="col-md-4">
            <div class="filter-box">
              <select id="statusFilter" name="status" class="form-select">
                <option value="all" <?php echo (isset($statusFilter) && strtolower($statusFilter) === 'all') ? 'selected' : ''; ?>>All Status</option>
                <option value="Pending" <?php echo (isset($statusFilter) && $statusFilter === 'Pending') ? 'selected' : ''; ?>>Pending</option>
                <option value="Initial Interview" <?php echo (isset($statusFilter) && $statusFilter === 'Initial Interview') ? 'selected' : ''; ?>>Initial Interview</option>
                <option value="Assessment" <?php echo (isset($statusFilter) && $statusFilter === 'Assessment') ? 'selected' : ''; ?>>Assessment</option>
                <option value="Final Interview" <?php echo (isset($statusFilter) && $statusFilter === 'Final Interview') ? 'selected' : ''; ?>>Final Interview</option>
                <option value="Requirements" <?php echo (isset($statusFilter) && $statusFilter === 'Requirements') ? 'selected' : ''; ?>>Requirements</option>
              </select>
            </div>
          </div>
          <div class="col-md-2">
            <button type="submit" class="search-btn w-100">
              <i class="fas fa-search"></i> Search
            </button>
          </div>
        </div>
      </form>
    </div>

    <!-- Applicants Table -->
    <div class="table-container">
      <div class="table-header">
        <h3 class="table-title">Applicant Management</h3>
        <div class="table-actions">
          <button class="refresh-btn" onclick="refreshTable()">
            <i class="fas fa-sync-alt"></i> Refresh
          </button>
        </div>
      </div>
      
      <div class="table-responsive">
        <table class="table-custom" id="applicantTable">
          <thead>
            <tr>
              <th>Applicant ID</th>
              <th>Full Name</th>
              <th>Email</th>
              <th>Status</th>
              <th>Actions</th>
            </tr>
          </thead>
          <tbody>
            <?php if (empty($pendingApplicants)): ?>
              <tr>
                <td colspan="5">
                  <div class="empty-state">
                    <i class="fas fa-users"></i>
                    <h4>No applicants found</h4>
                    <p>Try adjusting your search criteria or check back later.</p>
                  </div>
                </td>
              </tr>
            <?php else:
              foreach ($pendingApplicants as $p): ?>
                <tr id="row-<?php echo htmlspecialchars($p['applicantID']); ?>">
                  <td>
                    <span class="fw-bold text-primary"><?php echo htmlspecialchars($p['applicantID']); ?></span>
                  </td>
                  <td>
                    <strong><?php echo htmlspecialchars($p['fullName']); ?></strong>
                  </td>
                  <td>
                    <?php echo htmlspecialchars($p['email_address']); ?>
                  </td>
                  <td>
                    <?php
                    $current = $p['application_status'] ?? '';
                    if (empty($current))
                      $current = 'Pending';
                    $opts = ['Pending', 'Initial Interview', 'Assessment', 'Final Interview', 'Requirements', 'Hired', 'Rejected'];
                    ?>

                    <select class="status-select"
                      data-appid="<?php echo htmlspecialchars($p['applicantID']); ?>"
                      data-email="<?php echo htmlspecialchars($p['email_address']); ?>"
                      data-fullname="<?php echo htmlspecialchars($p['fullName']); ?>">
                      <?php foreach ($opts as $opt): ?>
                        <option value="<?php echo htmlspecialchars($opt); ?>" <?php echo ($current === $opt) ? 'selected' : ''; ?>>
                          <?php echo htmlspecialchars($opt); ?>
                        </option>
                      <?php endforeach; ?>
                    </select>
                  </td>
                  <td>
                    <button class="view-btn" data-appid="<?php echo htmlspecialchars($p['applicantID']); ?>">
                      <i class="fa-solid fa-eye"></i> View Details
                    </button>
                  </td>
                </tr>
              <?php endforeach; endif; ?>
          </tbody>
        </table>
      </div>
    </div>
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

  <!-- Schedule Modal -->
  <div id="scheduleModal" class="modal-custom" role="dialog" aria-hidden="true">
    <div class="modal-content-custom" role="document">
      <div class="modal-header-custom">
        <h3 class="modal-title-custom"><i class="fas fa-calendar-alt"></i> Schedule Details</h3>
        <button class="close-btn-custom" id="modalCancelBtn"><i class="fa-solid fa-xmark"></i></button>
      </div>

      <div class="modal-body-custom">
        <p id="modalSub" class="text-muted mb-4">Fill in the details below and click <strong>Send Email</strong> to notify the applicant.</p>

        <form id="modalForm" method="post">
          <input type="hidden" name="applicantID" id="m_applicantID" value="">
          <input type="hidden" name="new_status" id="m_new_status" value="">
          <input type="hidden" name="send_email" value="1">
          
          <div class="modal-row">
            <div class="col">
              <div class="form-group">
                <label for="sched_date">Date</label>
                <input type="date" id="sched_date" name="sched_date" class="form-control-custom" required>
              </div>
            </div>
            <div class="col">
              <div class="form-group">
                <label for="sched_time">Time</label>
                <input type="time" id="sched_time" name="sched_time" class="form-control-custom" required>
              </div>
            </div>
          </div>

          <div class="form-group">
            <label for="meet_person">Person to meet</label>
            <input type="text" id="meet_person" name="meet_person" class="form-control-custom" placeholder="Interviewer name" required>
          </div>

          <div class="form-group">
            <label for="reminder_info">Reminder (optional)</label>
            <input type="text" id="reminder_info" name="reminder_info" class="form-control-custom" placeholder="E.g., 1 day before 9:00 AM">
          </div>

          <div class="form-group">
            <label for="extra_notes">Additional notes (optional)</label>
            <textarea id="extra_notes" name="extra_notes" rows="3" class="form-control-custom" placeholder="Notes for the applicant"></textarea>
          </div>

          <div class="modal-actions">
            <button type="button" class="cancel-btn" id="modalCancelBtn2">Cancel</button>
            <button type="submit" class="send-btn">
              <i class="fas fa-paper-plane"></i> Send Email & Update
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>

  <!-- Success Modal -->
  <div class="modal fade" id="updateResultModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
      <div class="modal-content border-0 shadow">
        <div class="modal-header bg-success text-white">
          <h5 class="modal-title"><i class="fas fa-check-circle me-2"></i> Success!</h5>
          <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body text-center py-4">
          <div class="success-icon">
            <i class="fas fa-check"></i>
          </div>
          <h4 class="success-title">Success!</h4>
          <p class="success-message" id="successMessage">Operation completed successfully.</p>
        </div>
        <div class="modal-footer justify-content-center">
          <button type="button" class="btn btn-success" data-bs-dismiss="modal">
            <i class="fas fa-check me-2"></i> Continue
          </button>
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

  <!-- Bootstrap JS -->
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" crossorigin="anonymous"></script>

  <script>
    // Your existing JavaScript code remains the same...
    // (Keep all the JavaScript functions from your previous version)
    
    // Manager-wide alert / confirm / prompt modals
    (function setupManagerModals() {
      const html = `
      <div class="modal fade" id="mgrAlertModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
          <div class="modal-content">
            <div class="modal-header bg-primary text-white">
              <h5 class="modal-title">Notice</h5>
              <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body"><div id="mgrAlertMessage"></div></div>
            <div class="modal-footer">
              <button type="button" class="btn btn-primary" data-bs-dismiss="modal">OK</button>
            </div>
          </div>
        </div>
      </div>
      <div class="modal fade" id="mgrConfirmModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
          <div class="modal-content">
            <div class="modal-header bg-warning">
              <h5 class="modal-title">Confirm</h5>
              <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body"><div id="mgrConfirmMessage"></div></div>
            <div class="modal-footer">
              <button type="button" class="btn btn-secondary" data-bs-dismiss="modal" id="mgrConfirmCancel">Cancel</button>
              <button type="button" class="btn btn-warning" id="mgrConfirmOk">OK</button>
            </div>
          </div>
        </div>
      </div>
      <div class="modal fade" id="mgrPromptModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
          <div class="modal-content">
            <div class="modal-header bg-info text-white">
              <h5 class="modal-title">Enter Details</h5>
              <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
              <div id="mgrPromptMessage" class="mb-2"></div>
              <input type="text" class="form-control" id="mgrPromptInput" placeholder="Type here...">
            </div>
            <div class="modal-footer">
              <button type="button" class="btn btn-secondary" data-bs-dismiss="modal" id="mgrPromptCancel">Cancel</button>
              <button type="button" class="btn btn-info" id="mgrPromptOk">OK</button>
            </div>
          </div>
        </div>
      </div>`;
      document.body.insertAdjacentHTML('beforeend', html);
      const alertModal = new bootstrap.Modal(document.getElementById('mgrAlertModal'));
      const confirmModal = new bootstrap.Modal(document.getElementById('mgrConfirmModal'));
      const promptModal = new bootstrap.Modal(document.getElementById('mgrPromptModal'));
      
      window.showAlertModal = function (message) {
        document.getElementById('mgrAlertMessage').textContent = message;
        alertModal.show();
      };
      
      window.showConfirmModal = function (message) {
        return new Promise(resolve => {
          document.getElementById('mgrConfirmMessage').textContent = message;
          const ok = document.getElementById('mgrConfirmOk');
          const cancel = document.getElementById('mgrConfirmCancel');
          const cleanup = () => { 
            ok.replaceWith(ok.cloneNode(true)); 
            cancel.replaceWith(cancel.cloneNode(true)); 
          };
          confirmModal.show();
          document.getElementById('mgrConfirmOk').addEventListener('click', () => { cleanup(); confirmModal.hide(); resolve(true); });
          document.getElementById('mgrConfirmCancel').addEventListener('click', () => { cleanup(); confirmModal.hide(); resolve(false); });
        });
      };
      
      window.showPromptModal = function (message, defaultText = '') {
        return new Promise(resolve => {
          document.getElementById('mgrPromptMessage').textContent = message;
          const input = document.getElementById('mgrPromptInput');
          const ok = document.getElementById('mgrPromptOk');
          const cancel = document.getElementById('mgrPromptCancel');
          input.value = defaultText;
          const cleanup = () => { ok.replaceWith(ok.cloneNode(true)); cancel.replaceWith(cancel.cloneNode(true)); };
          promptModal.show();
          document.getElementById('mgrPromptOk').addEventListener('click', () => { const val = input.value; cleanup(); promptModal.hide(); resolve(val); });
          document.getElementById('mgrPromptCancel').addEventListener('click', () => { cleanup(); promptModal.hide(); resolve(null); });
        });
      };
    })();

    // Function to show success modal
    function showSuccessModal(message) {
      const updateResultModal = new bootstrap.Modal(document.getElementById('updateResultModal'));
      document.getElementById('successMessage').textContent = message;
      updateResultModal.show();
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
            const ok = await showConfirmModal(`Are you sure you want to hire ${fullname}?`);
            if (!ok) {
              this.value = 'Pending';
              return;
            }
            await updateStatus(appid, newStatus, true, sel);
          } else if (newStatus === 'Rejected') {
            const ok = await showConfirmModal(`Are you sure you want to reject ${fullname}?`);
            if (!ok) {
              this.value = 'Pending';
              return;
            }
            const reason = await showPromptModal("Enter reason for rejection (optional):", "");
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
            // Show success modal for ALL successful updates (with or without email)
            showSuccessModal(data.message);
            
            if (status === 'Hired' || status === 'Rejected') {
              selectEl.disabled = true;
              selectEl.value = status;
            }
          } else {
            showAlertModal(data.message);
          }
        } catch (err) {
          showAlertModal('Network error. Try again.');
        }
      }

      // Modal cancel buttons
      document.getElementById('modalCancelBtn').addEventListener('click', closeModal);
      document.getElementById('modalCancelBtn2').addEventListener('click', closeModal);

      // Modal form submission: AJAX post
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
            // Show success modal for email sends
            showSuccessModal(data.message || 'Email sent and status updated');
            
            // Update the select DOM to the new value for that applicant
            const appid = document.getElementById('m_applicantID').value;
            const newStatus = document.getElementById('m_new_status').value;
            const sel = document.querySelector('.status-select[data-appid="' + appid + '"]');
            if (sel) sel.value = newStatus;
            closeModal();
          } else {
            showAlertModal(data.message || 'Failed to send email / update');
          }
        } catch (err) {
          showAlertModal('Network error. Try again.');
        }
      });

      // Auto-submit filter form when status changes
      const statusFilter = document.getElementById('statusFilter');
      const filterForm = document.getElementById('filterForm');
      if (statusFilter && filterForm) {
        statusFilter.addEventListener('change', () => filterForm.submit());
      }

      // Attach click handler for all view buttons
      document.querySelectorAll('.view-btn').forEach(btn => {
        btn.addEventListener('click', () => showApplicantDetails(btn.dataset.appid));
      });
    });

    function openModalFor(appid, status, fullname, email) {
      document.getElementById('m_applicantID').value = appid;
      document.getElementById('m_new_status').value = status;
      const title = `Schedule ${status} for ${fullname}`;
      document.querySelector('#scheduleModal .modal-title-custom').textContent = title;
      document.getElementById('modalSub').textContent = 'Kindly set the date, time, person to meet, and reminders.';
      
      // Set default values
      const today = new Date().toISOString().split('T')[0];
      document.getElementById('sched_date').value = today;
      document.getElementById('sched_time').value = '09:00';
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
        if (!data.success) { 
          showAlertModal(data.message || 'Applicant not found'); 
          return; 
        }

        const d = data.data;
        document.getElementById('applicantModalTitle').textContent = d.fullName;
        document.getElementById('applicantPic').src = d.profile_pic || 'Images/default-profile.png';
        document.getElementById('applicantName').textContent = d.fullName;
        document.getElementById('applicantEmail').textContent = d.email_address;
        document.getElementById('applicantContact').textContent = d.contact_number || '-';
        document.getElementById('applicantPosition').textContent = d.applied_job_title || '-';
        document.getElementById('applicantDepartment').textContent = d.department_name || '-';
        document.getElementById('applicantDateApplied').textContent = d.date_applied || '-';
        document.getElementById('applicantEducation').textContent = `${d.university || '-'} (${d.course || '-'}, Graduated: ${d.year_graduated || '-'})`;
        document.getElementById('applicantExperience').textContent = `${d.years_experience || 0} years`;
        document.getElementById('applicantSkills').textContent = d.skills || '-';
        document.getElementById('applicantSummary').textContent = d.summary || '-';

        const modal = new bootstrap.Modal(document.getElementById('applicantModal'));
        modal.show();
      } catch (err) {
        showAlertModal('Network error. Try again.');
      }
    }

    function refreshTable() {
      location.reload();
    }

    // Close modals when clicking outside
    document.addEventListener('click', function(e) {
      if (e.target.classList.contains('modal-custom')) {
        e.target.classList.remove('active');
      }
    });

    // Close modals with Escape key
    document.addEventListener('keydown', function(e) {
      if (e.key === 'Escape') {
        document.querySelectorAll('.modal-custom.active').forEach(modal => {
          modal.classList.remove('active');
        });
      }
    });
  </script>
</body>
</html>