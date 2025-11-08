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

// Flash messages
$flash_success = $_SESSION['flash_success'] ?? '';
$flash_error = $_SESSION['flash_error'] ?? '';
unset($_SESSION['flash_success'], $_SESSION['flash_error']);

// manager name (for signature)
$managername = '';
$managernameQuery = $conn->query("SELECT fullname FROM user WHERE role = 'Employee' AND sub_role = 'HR Manager' LIMIT 1");
if ($managernameQuery && $r = $managernameQuery->fetch_assoc()) $managername = $r['fullname'];

// handle POST: update status and optionally send mail
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  // Common sanitized inputs
  $applicantID = trim($_POST['applicantID'] ?? '');
  $new_status = trim($_POST['new_status'] ?? '');
  $send_email_flag = isset($_POST['send_email']) && (string)$_POST['send_email'] === '1';
  $sched_date = trim($_POST['sched_date'] ?? '');
  $sched_time = trim($_POST['sched_time'] ?? '');
  $meet_person = trim($_POST['meet_person'] ?? '');
  $reminder_info = trim($_POST['reminder_info'] ?? '');
  $extra_notes = trim($_POST['extra_notes'] ?? '');

  // Input validation
  if (empty($applicantID) || empty($new_status)) {
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
    if ($updApp->execute()) {
      // $updApp->affected_rows could be 0 if no row existed — that's fine, still attempt applicant update
    }
    $updApp->close();
  }

  // 2) Update applicant table (primary fallback)
  $upd = $conn->prepare("UPDATE applicant SET status = ? WHERE applicantID = ?");
  if ($upd) {
    $upd->bind_param('ss', $new_status, $applicantID);
    if ($upd->execute()) $updateOk = true;
    $upd->close();
  }

  if (!$updateOk) {
    $_SESSION['flash_error'] = 'Failed to update applicant status.';
    header("Location: Manager_PendingApplicants.php");
    exit;
  }

  // If email is requested (either immediate or from modal), prepare and send
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
    if ($dtDisplay) $detailsBlock .= "<p><strong>{$dtDisplay}</strong></p>";
    if ($meet_person) $detailsBlock .= "<p><strong>Person to meet:</strong> {$meet_person}</p>";
    if ($reminder_info) $detailsBlock .= "<p><strong>Reminder:</strong> {$reminder_info}</p>";
    if ($extra_notes) $detailsBlock .= "<p><strong>Notes:</strong> {$extra_notes}</p>";

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
      $_SESSION['flash_success'] = "Status updated and email sent to {$fullname}.";
    } catch (Exception $e) {
      $_SESSION['flash_error'] = "Status updated but email failed to send: " . $mail->ErrorInfo;
    }
  } else {
    // email not requested (just update)
    $_SESSION['flash_success'] = "Status updated to {$new_status}.";
  }

  // After updating DB (and sending email if requested) reload the page so dropdown reflects current DB values.
  header("Location: Manager_PendingApplicants.php");
  exit;
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
    COALESCE(app.status, a.status) AS application_status,
    app.applied_at,
    a.date_applied
FROM applicant a
LEFT JOIN applications app 
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
  $params[] = $like; $params[] = $like; $params[] = $like;
}
if (!empty($clauses)) $sql .= ' WHERE ' . implode(' AND ', $clauses);

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
  while ($r = $res->fetch_assoc()) $pendingApplicants[] = $r;
  $stmt->close();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Manager - Pending Applicants</title>

  <link rel="stylesheet" href="manager-sidebar.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/7.0.1/css/all.min.css"
    crossorigin="anonymous" referrerpolicy="no-referrer" />

  <style>
    /* keep your styles and small modal additions */
    body { font-family: 'Poppins','Roboto',sans-serif; margin:0; display:flex; background:#f1f5fc; color:#111827; }
    .sidebar-logo{ display:flex; justify-content:center; margin-bottom:25px; }
    .sidebar-logo img{ height:110px; width:110px; border-radius:50%; object-fit:cover; border:3px solid #fff; }
    .main-content{ padding:40px 30px; margin-left:220px; display:flex; flex-direction:column; }
    .main-content-header h1{ margin:0 0 25px 0; font-size:2rem; color:#1E3A8A;}
    .header{ display:flex; justify-content:space-between; align-items:center; margin-bottom:20px; }
    .search-filter{ display:flex; align-items:center; gap:15px; }
    .search-box{ position:relative; flex:1; max-width:350px; }
    .search-box input{ width:100%; padding:10px 40px; border:1px solid #d1d5db; border-radius:25px; font-size:14px; background:white; outline:none; transition:all .3s; }
    .search-box input:focus { border-color:#1e3a8a; box-shadow:0 0 0 3px rgba(30,58,138,0.15); }
    .search-box i{ position:absolute; left:15px; top:50%; transform:translateY(-50%); color:#6b7280; font-size:14px; }
    select{ border-radius:25px; padding:10px 18px; border:1px solid #d1d5db; background:#fff; font-size:14px; color:#333; outline:none; cursor:pointer; transition:all .3s;}
    table{ width:90%; border-collapse:collapse; background:#fff; border-radius:10px; overflow:hidden; box-shadow:0 3px 6px rgba(0,0,0,0.08); margin-left:200px; }
    th,td{ padding:18px 30px; text-align:center; border-bottom:1px solid #e0e0e0; font-size:14px; }
    thead{ background:#1E3A8A; color:#fff; }
    tbody tr:hover{ background:#f8f9fa; }
    th:nth-child(1), td:nth-child(1){ width:25%; }
    th:nth-child(2), td:nth-child(2){ width:30%; }
    th:nth-child(3), td:nth-child(3){ width:20%; }
    th:nth-child(4), td:nth-child(4){ width:25%; }
    .view-btn{ background:#1E3A8A;color:#fff;border:none;border-radius:25px;padding:8px 16px;font-size:13px;cursor:pointer;display:inline-flex;align-items:center;gap:6px;}
    .view-btn:hover{ background:#1e40af; transform:translateY(-2px); }
    .flash-success{ background:#d1fae5; color:#065f46; padding:12px; border-radius:8px; margin-bottom:12px; margin-left:200px; max-width:1200px; box-shadow:0 2px 6px rgba(16,24,40,0.06); }
    .flash-error{ background:#fee2e2; color:#991b1b; padding:12px; border-radius:8px; margin-bottom:12px; margin-left:200px; max-width:1200px; box-shadow:0 2px 6px rgba(16,24,40,0.06); }

    /* Modal */
    .modal { display:none; position:fixed; z-index:1200; left:0; top:0; width:100%; height:100%; background: rgba(0,0,0,0.45); justify-content:center; align-items:center; }
    .modal.active { display:flex; }
    .modal-content { background:#fff; padding:18px 20px; border-radius:10px; width:480px; max-width:94%; box-shadow:0 8px 30px rgba(2,6,23,0.16); }
    .modal-content h3{ margin:0 0 8px 0; font-size:18px; color:#0f172a; }
    .modal-row{ display:flex; gap:10px; margin-bottom:10px; }
    .modal-row .col{ flex:1; }
    .modal-content label{ display:block; font-size:13px; margin-bottom:6px; color:#374151; }
    .modal-content input[type="date"], .modal-content input[type="time"], .modal-content input[type="text"], .modal-content textarea { width:100%; padding:8px; border:1px solid #d1d5db; border-radius:6px; box-sizing:border-box; font-size:14px; }
    .modal-actions{ display:flex; justify-content:flex-end; gap:8px; margin-top:12px; }
    .cancel-btn{ background:#efefef; color:#111827; padding:8px 14px; border-radius:6px; border:none; cursor:pointer; }
    .send-btn{ background:#1E3A8A; color:#fff; border:none; padding:8px 14px; border-radius:6px; cursor:pointer; }
  </style>
</head>
<body>
  <!-- Sidebar -->
  <div class="sidebar">
    <div class="sidebar-logo">
      <img src="Images/hospitallogo.png" alt="Logo">
    </div>
    <div class="sidebar-name"><p><?php echo "Welcome, " . htmlspecialchars($managername); ?></p></div>
    <ul class="nav">
      <li><a href="Manager_Dashboard.php"><i class="fa-solid fa-table-columns"></i>Dashboard</a></li>
      <li><a href="Manager_Applicants.php"><i class="fa-solid fa-user-group"></i>Applicants</a></li>
      <li class="active"><a href="Manager_PendingApplicants.php"><i class="fa-solid fa-hourglass-half"></i>Pending Applicants</a></li>
      <li><a href="Manager_Request.php"><i class="fa-solid fa-code-pull-request"></i>Requests</a></li>
      <li><a href="Manager-JobPosting.php"><i class="fa-solid fa-briefcase"></i>Job Post</a></li>
      <li><a href="Manager_Calendar.php"><i class="fa-solid fa-calendar"></i>Calendar</a></li>
      <li><a href="Manager_Approvals.php"><i class="fa-solid fa-circle-check"></i>Approvals</a></li>
      <li><a href="Manager_LeaveSettings.php"><i class="fa-solid fa-gear"></i>Settings</a></li>
      <li><a href="#"><i class="fa-solid fa-right-from-bracket"></i>Logout</a></li>
    </ul>
  </div>

  <div class="main-content">
    <div class="main-content-header"><h1>Pending Applicants</h1></div>

    <?php if (!empty($flash_success)): ?>
      <div class="flash-success"><?php echo htmlspecialchars($flash_success); ?></div>
    <?php endif; ?>
    <?php if (!empty($flash_error)): ?>
      <div class="flash-error"><?php echo htmlspecialchars($flash_error); ?></div>
    <?php endif; ?>

    <div class="header">
      <div class="search-filter">
        <form method="get" id="filterForm" style="display:flex;align-items:center;gap:15px;">
          <div class="search-box">
            <i class="fa-solid fa-magnifying-glass"></i>
            <input type="text" id="searchInput" name="q" placeholder="Search applicants..." value="<?php echo htmlspecialchars($search ?? ''); ?>">
          </div>

          <select id="statusFilter" name="status">
            <option value="all" <?php echo (isset($statusFilter) && strtolower($statusFilter) === 'all') ? 'selected':''; ?>>All Status</option>
            <option value="Pending" <?php echo (isset($statusFilter) && $statusFilter === 'Pending') ? 'selected':''; ?>>Pending</option>
            <option value="Initial Interview" <?php echo (isset($statusFilter) && $statusFilter === 'Initial Interview') ? 'selected':''; ?>>Initial Interview</option>
            <option value="Assessment" <?php echo (isset($statusFilter) && $statusFilter === 'Assessment') ? 'selected':''; ?>>Assessment</option>
            <option value="Final Interview" <?php echo (isset($statusFilter) && $statusFilter === 'Final Interview') ? 'selected':''; ?>>Final Interview</option>
            <option value="Requirements" <?php echo (isset($statusFilter) && $statusFilter === 'Requirements') ? 'selected':''; ?>>Requirements</option>
            <option value="Hired" <?php echo (isset($statusFilter) && $statusFilter === 'Hired') ? 'selected':''; ?>>Hired</option>
            <option value="Rejected" <?php echo (isset($statusFilter) && $statusFilter === 'Rejected') ? 'selected':''; ?>>Rejected</option>
          </select>

          <button type="submit" style="background:#1E3A8A;color:#fff;border:none;padding:8px 14px;border-radius:8px;">Search</button>
        </form>
      </div>
    </div>

    <table id="applicantTable">
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
          <tr><td colspan="4">No applicants found.</td></tr>
        <?php else: foreach ($pendingApplicants as $p): ?>
          <tr>
            <td><?php echo htmlspecialchars($p['applicantID']); ?></td>
            <td><?php echo htmlspecialchars($p['fullName']); ?></td>
            <td>
              <button class="view-btn" data-appid="<?php echo htmlspecialchars($p['applicantID']); ?>"> <i class="fa-solid fa-eye"></i> View</button>
            </td>
            <td>
             <?php
              $current = $p['application_status'] ?? '';
              if (empty($current)) $current = 'Pending';
              $opts = ['Pending','Initial Interview','Assessment','Final Interview','Requirements','Hired','Rejected'];
             ?>

              <select class="status-select"
                      data-appid="<?php echo htmlspecialchars($p['applicantID']); ?>"
                      data-email="<?php echo htmlspecialchars($p['email_address']); ?>"
                      data-fullname="<?php echo htmlspecialchars($p['fullName']); ?>">
                <?php foreach ($opts as $opt): ?>
                  <option value="<?php echo htmlspecialchars($opt); ?>" <?php echo ($current === $opt) ? 'selected':''; ?>><?php echo htmlspecialchars($opt); ?></option>
                <?php endforeach; ?>
              </select>
            </td>
          </tr>
        <?php endforeach; endif; ?>
      </tbody>
    </table>
  </div>

  <!-- Hidden form for immediate submits (Hired/Rejected/Pending without modal) -->
  <form id="quickForm" method="post" style="display:none;">
    <input type="hidden" name="applicantID" id="q_applicantID" value="">
    <input type="hidden" name="new_status" id="q_new_status" value="">
    <input type="hidden" name="send_email" id="q_send_email" value="0">
  </form>

  <!-- Modal (reused for Interview, Assessment, Final Interview, Requirements) -->
  <div id="scheduleModal" class="modal" role="dialog" aria-hidden="true">
    <div class="modal-content" role="document">
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
          <button type="button" class="cancel-btn" onclick="closeModal()">Cancel</button>
          <button type="submit" class="send-btn">Send Email & Update</button>
        </div>
      </form>
    </div>
  </div>

  <script>
    // All client-side behavior in vanilla JS
    document.addEventListener('DOMContentLoaded', function () {
      const selects = document.querySelectorAll('.status-select');
      selects.forEach(sel => {
        sel.addEventListener('change', function (e) {
          const newStatus = this.value;
          const appid = this.dataset.appid;
          const email = this.dataset.email;
          const fullname = this.dataset.fullname;

          // statuses that need modal details
          const needsModal = ['Initial Interview','Assessment','Final Interview','Requirements'];

          if (needsModal.includes(newStatus)) {
            openModalFor(appid, newStatus, fullname, email);
          } else {
            // Hired / Rejected / Pending -> immediate action
            // For Hired/Rejected we want to send email automatically
            const sendEmail = (newStatus === 'Hired' || newStatus === 'Rejected') ? '1' : '0';
            document.getElementById('q_applicantID').value = appid;
            document.getElementById('q_new_status').value = newStatus;
            document.getElementById('q_send_email').value = sendEmail;

            // Submit the quick form (non-AJAX) - page will reload and reflect database status
            document.getElementById('quickForm').submit();
          }
        });
      });

      // modal form submission: normal POST back to this file (so DB updates and page reload will reflect new status)
      const modalForm = document.getElementById('modalForm');
      modalForm.addEventListener('submit', function (evt) {
        // allow normal submission
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
  </script>
</body>
</html>
