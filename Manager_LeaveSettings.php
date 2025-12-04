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



// --- Handle Leave Setting Submission ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_leave'])) {
  $start_date = $_POST['start_date'] ?? null;
  $end_date = $_POST['end_date'] ?? null;
  $month = isset($_POST['month']) ? intval($_POST['month']) : null;
  $employee_limit = isset($_POST['employeeLimit']) ? intval($_POST['employeeLimit']) : 0;
  $created_by = $managername;

  $typeStmt = $conn->prepare("SELECT id FROM types_of_requests WHERE request_type_name = ? LIMIT 1");
  $typeName = 'Leave';
  $typeStmt->bind_param("s", $typeName);
  $typeStmt->execute();
  $typeRes = $typeStmt->get_result()->fetch_assoc();
  $request_type_id = $typeRes['id'] ?? null;
  $typeStmt->close();

  if (!$request_type_id || !$start_date || !$end_date || !$month || $month < 1 || $month > 12 || $employee_limit <= 0) {
    echo "<script>alert('Please complete all required fields.');</script>";
  } else {
    $stmt = $conn->prepare("INSERT INTO leave_settings (request_type_id, start_date, end_date, month, employee_limit, created_by) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("issiis", $request_type_id, $start_date, $end_date, $month, $employee_limit, $created_by);
    if ($stmt->execute()) {
      $_SESSION['settingID'] = $conn->insert_id;
      $_SESSION['show_modal'] = true;
    } else {
      echo "<script>alert('Error saving leave setting: " . htmlspecialchars($stmt->error) . "');</script>";
    }
    $stmt->close();
  }
}

// --- Handle Announcement Submission ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_announcement'])) {
  $title = $_POST['title'];
  $manageremail = $_SESSION['email'] ?? null;
  $message = $_POST['message'];
  $posted_by = $_SESSION['sub_role'] ?? "Unknown Role";
  $settingID = $_POST['settingID'] ?? null;

 $stmt2 = $conn->prepare(
    "INSERT INTO manager_announcement (title, posted_by, message, settingID, manager_email) 
     VALUES (?, ?, ?, ?, ?)"
);

$stmt2->bind_param("sssis", $title, $posted_by, $message, $settingID, $manageremail);



  if ($stmt2->execute()) {
    $_SESSION['show_announcement_success'] = true;
  } else {
    echo "<script>alert('Error posting announcement: " . $conn->error . "');</script>";
  }
  $stmt2->close();
  unset($_SESSION['settingID']);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['archive_announcement'])) {
  $announcement_id = isset($_POST['announcement_id']) ? intval($_POST['announcement_id']) : 0;
  if ($announcement_id > 0) {
    $chk = $conn->prepare("SELECT a.manager_email, a.is_active, ls.end_date FROM manager_announcement a LEFT JOIN leave_settings ls ON a.settingID = ls.settingID WHERE a.id = ?");
    $chk->bind_param("i", $announcement_id);
    $chk->execute();
    $res = $chk->get_result()->fetch_assoc();
    $chk->close();
    if ($res) {
      $today = date('Y-m-d');
      $end_date = $res['end_date'] ?? null;
      $owns = $res['manager_email'] === $manageremail;
      $expired = ($end_date && $end_date < $today);
      $active = (int)($res['is_active'] ?? 1) === 1;
      if ($owns && $expired && $active) {
        $upd = $conn->prepare("UPDATE manager_announcement SET is_active = 0 WHERE id = ?");
        $upd->bind_param("i", $announcement_id);
        if ($upd->execute()) {
          $_SESSION['show_announcement_archived'] = true;
        } else {
          $_SESSION['show_announcement_error'] = true;
        }
        $upd->close();
      } else {
        $_SESSION['show_announcement_not_allowed'] = true;
      }
    }
  }
}

$announcements = [];
$aquery = $conn->query("SELECT a.id, a.title, a.message, a.posted_by, a.manager_email, a.date_posted, a.is_active, a.settingID, ls.end_date FROM manager_announcement a LEFT JOIN leave_settings ls ON a.settingID = ls.settingID ORDER BY a.date_posted DESC");
if ($aquery) {
  while ($row = $aquery->fetch_assoc()) { $announcements[] = $row; }
}

// Manager name
$managername = $_SESSION['fullname'] ?? "Manager";


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
?>

<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Leave Settings</title>
  <link rel="stylesheet" href="manager-sidebar.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/7.0.1/css/all.min.css">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <script src="https://code.jquery.com/jquery-3.6.4.min.js"></script>
  <link rel="stylesheet" href="https://code.jquery.com/ui/1.13.2/themes/base/jquery-ui.css">
  <script src="https://code.jquery.com/ui/1.13.2/jquery-ui.min.js"></script>
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">

  <style>
    body {
      background-color: #f7f9fc;
      font-family: "Poppins", "Roboto", sans-serif;
      display: flex;
      margin: 0;
      min-height: 100vh;
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

    .main-content-header {
      margin-bottom: 30px;
      padding-bottom: 15px;
      border-bottom: 2px solid #eef2ff;
    }

    .main-content h1 {
      font-size: 28px;
      color: #1E3A8A;
      font-weight: 700;
      margin: 0;
      display: flex;
      align-items: center;
      gap: 12px;
    }

    .main-content h1 i {
      color: #1E3A8A;
      font-size: 26px;
    }

    .card-container {
      max-width: 100%;
      margin: 0 auto 30px;
    }

    .card-custom {
      border: none;
      border-radius: 16px;
      background-color: #ffffff;
      box-shadow: 0 8px 24px rgba(30, 58, 138, 0.08);
      overflow: hidden;
      transition: transform 0.3s ease, box-shadow 0.3s ease;
    }

    .card-custom:hover {
      transform: translateY(-4px);
      box-shadow: 0 12px 32px rgba(30, 58, 138, 0.12);
    }

    .card-header-custom {
      background-color: #1E3A8A;
      color: white;
      padding: 20px 25px;
      border-bottom: none;
    }

    .card-header-custom h5 {
      font-weight: 600;
      margin: 0;
      display: flex;
      align-items: center;
      gap: 10px;
      font-size: 20px;
    }

    .card-body {
      padding: 30px;
    }

    .form-label {
      font-weight: 600;
      color: #2d3748;
      margin-bottom: 8px;
      display: flex;
      align-items: center;
      gap: 8px;
    }

    .form-label i {
      color: #1E3A8A;
      font-size: 16px;
    }

    .form-control, .form-select {
      border: 2px solid #e2e8f0;
      border-radius: 10px;
      padding: 12px 16px;
      font-size: 15px;
      transition: all 0.3s ease;
    }

    .form-control:focus, .form-select:focus {
      border-color: #1E3A8A;
      box-shadow: 0 0 0 3px rgba(30, 58, 138, 0.1);
    }

    .form-control[readonly] {
      background-color: #f8fafc;
      border-color: #e2e8f0;
    }

    .btn-primary-custom {
      background: linear-gradient(135deg, #1E3A8A 0%, #2d4db0 100%);
      color: white;
      border: none;
      border-radius: 10px;
      padding: 12px 28px;
      font-weight: 600;
      transition: all 0.3s ease;
      box-shadow: 0 4px 12px rgba(30, 58, 138, 0.2);
    }

    .btn-primary-custom:hover {
      background: linear-gradient(135deg, #16326a 0%, #1E3A8A 100%);
      color: white;
      transform: translateY(-2px);
      box-shadow: 0 6px 16px rgba(30, 58, 138, 0.3);
    }

    .btn-danger-custom {
      background: linear-gradient(135deg, #dc2626 0%, #ef4444 100%);
      color: white;
      border: none;
      border-radius: 10px;
      padding: 12px 28px;
      font-weight: 600;
      transition: all 0.3s ease;
    }

    .btn-danger-custom:hover {
      background: linear-gradient(135deg, #b91c1c 0%, #dc2626 100%);
      color: white;
      transform: translateY(-2px);
    }

    .btn-sm-custom {
      padding: 6px 16px;
      font-size: 14px;
      border-radius: 8px;
    }

    .table-custom {
      margin-bottom: 0;
      border-radius: 12px;
      overflow: hidden;
    }

    .table-custom thead th {
      background-color: #1E3A8A;
      color: white;
      font-weight: 600;
      border: none;
      padding: 16px 20px;
    }

    .table-custom tbody td {
      padding: 16px 20px;
      vertical-align: middle;
      border-color: #f1f5f9;
    }

    .table-custom tbody tr:hover {
      background-color: #f8fafc;
    }

    .badge {
      padding: 6px 12px;
      font-weight: 500;
      border-radius: 8px;
      font-size: 13px;
    }

    .badge.bg-success {
      background-color: #10b981 !important;
    }

    .badge.bg-danger {
      background-color: #ef4444 !important;
    }

    .badge.bg-secondary {
      background-color: #6b7280 !important;
    }

    .alert {
      border-radius: 12px;
      border: none;
      padding: 16px 20px;
      margin-bottom: 20px;
      box-shadow: 0 4px 12px rgba(0,0,0,0.05);
    }

    .alert-success {
      background-color: #d1fae5;
      color: #065f46;
    }

    .alert-danger {
      background-color: #fee2e2;
      color: #991b1b;
    }

    .alert-warning {
      background-color: #fef3c7;
      color: #92400e;
    }

    .status-indicator {
      display: inline-flex;
      align-items: center;
      gap: 6px;
    }

    .status-dot {
      width: 10px;
      height: 10px;
      border-radius: 50%;
    }

    .status-dot.active {
      background-color: #10b981;
    }

    .status-dot.expired {
      background-color: #ef4444;
    }

    .status-dot.archived {
      background-color: #6b7280;
    }

    .modal-content {
      border-radius: 16px;
      border: none;
      box-shadow: 0 20px 40px rgba(0,0,0,0.1);
    }

    .modal-header {
      border-radius: 16px 16px 0 0;
      padding: 25px 30px;
    }

    .modal-body {
      padding: 30px;
    }

    .modal-footer {
      padding: 20px 30px;
      border-top: 1px solid #eef2ff;
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
      <h1><i class="fa-solid fa-gear"></i> Leave Settings</h1>
    </div>

    <!-- Leave Form Card -->
    <div class="card-container">
      <div class="card card-custom">
        <div class="card-header card-header-custom">
          <h5><i class="fa-solid fa-calendar-plus"></i> Configure Leave Settings</h5>
        </div>
        <div class="card-body">
          <form method="POST">
            <div class="row mb-4">
              <div class="col-md-6 mb-3">
                <label class="form-label"><i class="fa-solid fa-clipboard"></i>Request Type</label>
                <input type="text" class="form-control" value="Leave" readonly style="background-color: #f0f4ff;">
              </div>
              <div class="col-md-6 mb-3">
                <label class="form-label"><i class="fa-solid fa-user"></i>Created by</label>
                <input type="text" class="form-control" value="<?php echo $managername; ?>" readonly style="background-color: #f0f4ff;">
              </div>
            </div>

            <div class="row mb-4">
              <div class="col-md-6 mb-3">
                <label class="form-label"><i class="fa-solid fa-calendar-days"></i>Filing Start Date</label>
                <input type="text" id="start_date" name="start_date" class="form-control" required placeholder="Select start date">
              </div>
              <div class="col-md-6 mb-3">
                <label class="form-label"><i class="fa-solid fa-calendar-check"></i>Filing End Date</label>
                <input type="text" id="end_date" name="end_date" class="form-control" required placeholder="Select end date">
              </div>
            </div>

            <div class="row mb-4">
              <div class="col-md-6 mb-3">
                <label class="form-label"><i class="fa-solid fa-calendar"></i>Month</label>
                <select name="month" class="form-select" required>
                  <option value="" disabled selected>Select month</option>
                  <option value="1">January</option>
                  <option value="2">February</option>
                  <option value="3">March</option>
                  <option value="4">April</option>
                  <option value="5">May</option>
                  <option value="6">June</option>
                  <option value="7">July</option>
                  <option value="8">August</option>
                  <option value="9">September</option>
                  <option value="10">October</option>
                  <option value="11">November</option>
                  <option value="12">December</option>
                </select>
              </div>
              <div class="col-md-6 mb-3">
                <label class="form-label"><i class="fa-solid fa-users"></i>Employee Limit</label>
                <input type="number" name="employeeLimit" class="form-control" min="1" required placeholder="Enter employee limit">
                <small class="form-text text-muted mt-1">Maximum number of employees who can apply for leave</small>
              </div>
            </div>

            <div class="d-flex justify-content-end gap-3 pt-3">
              <button type="button" class="btn btn-danger-custom" onclick="window.location.href='Manager_Dashboard.php'">Cancel</button>
              <button type="submit" name="save_leave" class="btn btn-primary-custom">
                <i class="fa-solid fa-paper-plane me-2"></i>Post Settings
              </button>
            </div>
          </form>
        </div>
      </div>
    </div>

    <!-- Alerts -->
    <?php if (!empty($_SESSION['show_announcement_archived'])): ?>
      <div class="alert alert-success">
        <i class="fa-solid fa-check-circle me-2"></i> Announcement has been archived successfully.
      </div>
      <?php unset($_SESSION['show_announcement_archived']); endif; ?>
    <?php if (!empty($_SESSION['show_announcement_error'])): ?>
      <div class="alert alert-danger">
        <i class="fa-solid fa-exclamation-circle me-2"></i> Failed to archive announcement. Please try again.
      </div>
      <?php unset($_SESSION['show_announcement_error']); endif; ?>
    <?php if (!empty($_SESSION['show_announcement_not_allowed'])): ?>
      <div class="alert alert-warning">
        <i class="fa-solid fa-shield-exclamation me-2"></i> Action not allowed. Only expired announcements can be archived by the posting manager.
      </div>
      <?php unset($_SESSION['show_announcement_not_allowed']); endif; ?>

    <!-- Announcements Card -->
    <div class="card-container">
      <div class="card card-custom">
        <div class="card-header card-header-custom">
          <h5><i class="fa-solid fa-bullhorn"></i> Announcements</h5>
        </div>
        <div class="card-body">
          <?php if (!empty($announcements)): ?>
            <div class="table-responsive">
              <table class="table table-custom">
                <thead>
                  <tr>
                    <th>Title</th>
                    <th>Message</th>
                    <th>Posted By</th>
                    <th>Date Posted</th>
                    <th>Expiry Date</th>
                    <th>Status</th>
                    <th>Action</th>
                  </tr>
                </thead>
                <tbody>
                  <?php foreach ($announcements as $a): ?>
                    <?php 
                      $expiry = $a['end_date'] ?? null; 
                      $today = date('Y-m-d'); 
                      $expired = ($expiry && $expiry < $today);
                      $isActive = (int)($a['is_active'] ?? 1) === 1;
                    ?>
                    <tr>
                      <td><strong><?php echo htmlspecialchars($a['title']); ?></strong></td>
                      <td style="max-width: 300px; white-space: normal; word-wrap: break-word;">
                        <?php echo nl2br(htmlspecialchars($a['message'])); ?>
                      </td>
                      <td>
                        <div class="d-flex align-items-center">
                          <i class="fa-solid fa-user-circle me-2" style="color: #1E3A8A;"></i>
                          <?php echo htmlspecialchars($a['posted_by']); ?>
                        </div>
                      </td>
                      <td>
                        <div class="d-flex align-items-center">
                          <i class="fa-solid fa-calendar me-2" style="color: #6b7280;"></i>
                          <?php echo htmlspecialchars(date('M d, Y', strtotime($a['date_posted']))); ?>
                        </div>
                      </td>
                      <td>
                        <?php if ($expiry): ?>
                          <div class="d-flex align-items-center">
                            <i class="fa-solid fa-clock me-2" style="color: <?php echo $expired ? '#ef4444' : '#10b981'; ?>"></i>
                            <?php echo htmlspecialchars(date('M d, Y', strtotime($expiry))); ?>
                          </div>
                        <?php else: ?>
                          <span class="text-muted">N/A</span>
                        <?php endif; ?>
                      </td>
                      <td>
                        <div class="status-indicator">
                          <span class="status-dot 
                            <?php echo !$isActive ? 'archived' : ($expired ? 'expired' : 'active'); ?>">
                          </span>
                          <?php if (!$isActive): ?>
                            <span class="badge bg-secondary">Archived</span>
                          <?php elseif ($expired): ?>
                            <span class="badge bg-danger">Expired</span>
                          <?php else: ?>
                            <span class="badge bg-success">Active</span>
                          <?php endif; ?>
                        </div>
                      </td>
                      <td>
                        <?php if ($expired && $a['manager_email'] === $manageremail && $isActive): ?>
                          <form method="POST" style="display:inline-block;">
                            <input type="hidden" name="announcement_id" value="<?php echo (int)$a['id']; ?>">
                            <button type="submit" name="archive_announcement" class="btn btn-sm btn-danger btn-sm-custom">
                              <i class="fa-solid fa-box-archive me-1"></i>Archive
                            </button>
                          </form>
                        <?php else: ?>
                          <span class="text-muted fst-italic">No action</span>
                        <?php endif; ?>
                      </td>
                    </tr>
                  <?php endforeach; ?>
                </tbody>
              </table>
            </div>
          <?php else: ?>
            <div class="text-center py-5">
              <i class="fa-solid fa-bullhorn" style="font-size: 48px; color: #e2e8f0; margin-bottom: 20px;"></i>
              <h5 class="text-muted mb-3">No Announcements Yet</h5>
              <p class="text-muted">Once you create leave settings, you can post announcements here.</p>
            </div>
          <?php endif; ?>
        </div>
      </div>
    </div>
  </div>

  <!-- SUCCESS MODAL -->
  <div class="modal fade" id="successModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
      <div class="modal-content">
        <div class="modal-header bg-primary text-white">
          <h5 class="modal-title"><i class="fa-solid fa-check-circle me-2"></i>Success!</h5>
          <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body text-center py-4">
          <div class="mb-4">
            <i class="fa-solid fa-circle-check" style="font-size: 64px; color: #10b981;"></i>
          </div>
          <h4 class="mb-3">Leave Settings Saved!</h4>
          <p class="text-muted">Do you want to announce these new leave settings to employees?</p>
        </div>
        <div class="modal-footer justify-content-center">
          <button type="button" class="btn btn-danger-custom px-4" data-bs-dismiss="modal">Cancel</button>
          <button type="button" class="btn btn-primary-custom px-4" id="openAnnounceModal">
            <i class="fa-solid fa-bullhorn me-2"></i>Announce
          </button>
        </div>
      </div>
    </div>
  </div>

  <!-- ANNOUNCEMENT MODAL -->
  <div class="modal fade" id="announceModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
      <div class="modal-content">
        <div class="modal-header bg-primary text-white">
          <h5 class="modal-title"><i class="fa-solid fa-bullhorn me-2"></i>New Announcement</h5>
          <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
        </div>
        <form method="POST">
          <div class="modal-body">
            <input type="hidden" name="settingID" value="<?php echo $_SESSION['settingID'] ?? ''; ?>">
            <div class="mb-4">
              <label class="form-label fw-semibold mb-2"><i class="fa-solid fa-heading"></i>Title</label>
              <input type="text" name="title" class="form-control" placeholder="Enter announcement title" required>
            </div>
            <div class="mb-3">
              <label class="form-label fw-semibold mb-2"><i class="fa-solid fa-message"></i>Message</label>
              <textarea name="message" class="form-control" rows="5" placeholder="Type your announcement message here..." required></textarea>
            </div>
          </div>
          <div class="modal-footer justify-content-center">
            <button type="button" class="btn btn-danger-custom px-4" data-bs-dismiss="modal">Cancel</button>
            <button type="submit" name="save_announcement" class="btn btn-primary-custom px-4">
              <i class="fa-solid fa-paper-plane me-2"></i>Post Announcement
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>

  <!-- SUCCESS POPUP -->
  <div class="modal fade success-popup" id="announcementSuccessModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
      <div class="modal-content text-center">
        <div class="modal-header border-0">
          <h5 class="modal-title w-100"><i class="fa-solid fa-circle-check me-2" style="color: #10b981;"></i> Announcement Posted</h5>
        </div>
        <div class="modal-body py-4">
          <div class="mb-3">
            <i class="fa-solid fa-check-circle" style="font-size: 48px; color: #10b981;"></i>
          </div>
          <h4 class="mb-2">Success!</h4>
          <p class="text-muted">The announcement has been successfully shared with all employees.</p>
        </div>
        <div class="modal-footer justify-content-center border-0">
          <button type="button" class="btn btn-primary-custom px-4" data-bs-dismiss="modal">OK</button>
        </div>
      </div>
    </div>
  </div>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
  <script>
    $(function () {
      $("#start_date, #end_date").datepicker({ 
        dateFormat: "yy-mm-dd", 
        minDate: 0,
        beforeShow: function(input, inst) {
          $(input).addClass('focus-active');
        },
        onClose: function() {
          $(this).removeClass('focus-active');
        }
      });
    });

    <?php if (!empty($_SESSION['show_modal'])): ?>
      new bootstrap.Modal(document.getElementById('successModal')).show();
      <?php unset($_SESSION['show_modal']); endif; ?>

    <?php if (!empty($_SESSION['show_announcement_success'])): ?>
      new bootstrap.Modal(document.getElementById('announcementSuccessModal')).show();
      <?php unset($_SESSION['show_announcement_success']); endif; ?>

    document.getElementById('openAnnounceModal').onclick = function () {
      bootstrap.Modal.getInstance(document.getElementById('successModal')).hide();
      setTimeout(() => {
        new bootstrap.Modal(document.getElementById('announceModal')).show();
      }, 300);
    };

    // Add focus effect to form controls
    document.querySelectorAll('.form-control, .form-select').forEach(element => {
      element.addEventListener('focus', function() {
        this.parentElement.classList.add('focused');
      });
      element.addEventListener('blur', function() {
        this.parentElement.classList.remove('focused');
      });
    });
  </script>

</body>

</html>