<?php
session_start();
require 'admin/db.connect.php';

// Get manager name
$managernameQuery = $conn->query("SELECT fullname, email FROM user WHERE role = 'Employee' AND sub_role ='HR Manager' LIMIT 1");
$manager = ($managernameQuery && $row = $managernameQuery->fetch_assoc()) ? $row : ['fullname' => 'Manager', 'email' => 'manager@example.com'];
$managername = $manager['fullname'];
$manageremail = $manager['email'];
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
  $message = $_POST['message'];
  $manager_email = $manageremail;
  $posted_by = $managername;
  $settingID = $_POST['settingID'] ?? null;

  $stmt2 = $conn->prepare("INSERT INTO manager_announcement (manager_email, title, posted_by, message, settingID) VALUES (?, ?, ?, ?, ?)");
  $stmt2->bind_param("ssssi", $manager_email, $title, $posted_by, $message, $settingID);

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

  <style>
    body {
      background-color: #f7f9fc;
      font-family: "Poppins", "Roboto", sans-serif;
      display: flex;
      margin: 0;
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

    .main-content {
      padding: 40px 30px;
      margin-left: 300px;
      display: flex;
      flex-direction: column;
    }

    .main-content h2 {
      font-size: 26px;
      color: #1E3A8A;
      font-weight: 700;
      margin-bottom: 25px;
      display: flex;
      align-items: center;
      gap: 10px;
    }


    .btn-primary-custom {
      background-color: #1E3A8A;
      color: white;
      border: none;
    }

    .btn-primary-custom:hover {
      background-color: #16326a;
      color: white;
    }

    .btn-danger-custom {
      background-color: red;
      color: white;
      border: none;
    }

    .btn-danger-custom:hover {
      background-color: darkred;
      color: white;
    }

    .form-label i {
      color: #1E3A8A;
      margin-right: 6px;
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

    <!-- Enhanced Leave Form Container -->
    <div class="card shadow-sm p-4 mb-5"
      style="width: 150% ; margin:auto; border-radius:12px; background-color:#ffffff;">
      <div class="card-body">
        <form method="POST">
          <div class="mb-3">
            <label class="form-label fw-semibold"><i class="fa-solid fa-clipboard"></i>Request Type</label>
            <input type="text" class="form-control" value="Leave" readonly>
          </div>

          <div class="row mb-3">
            <div class="col-md-6">
              <label class="form-label fw-semibold"><i class="fa-solid fa-calendar-days"></i>Filing Start Date</label>
              <input type="text" id="start_date" name="start_date" class="form-control" required>
            </div>
            <div class="col-md-6">
              <label class="form-label fw-semibold"><i class="fa-solid fa-calendar-check"></i>Filing End Date</label>
              <input type="text" id="end_date" name="end_date" class="form-control" required>
            </div>
          </div>

          <div class="mb-3">
            <label class="form-label fw-semibold"><i class="fa-solid fa-calendar"></i>Month</label>
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

          <div class="mb-3">
            <label class="form-label fw-semibold"><i class="fa-solid fa-users"></i>Employee Limit</label>
            <input type="number" name="employeeLimit" class="form-control" min="1" required>
          </div>

          

          <div class="mb-4">
            <label class="form-label fw-semibold"><i class="fa-solid fa-user"></i>Created by</label>
            <input type="text" class="form-control" value="<?php echo $managername; ?>" readonly>
          </div>

          <div class="d-flex justify-content-end gap-2">
            <button type="button" class="btn btn-danger-custom"
              onclick="window.location.href='Manager_Dashboard.php'">Cancel</button>
            <button type="submit" name="save_leave" class="btn btn-primary-custom">Post</button>
          </div>
        </form>
      </div>
    </div>

    <?php if (!empty($_SESSION['show_announcement_archived'])): ?>
      <div class="alert alert-success">Announcement archived.</div>
      <?php unset($_SESSION['show_announcement_archived']); endif; ?>
    <?php if (!empty($_SESSION['show_announcement_error'])): ?>
      <div class="alert alert-danger">Failed to delete announcement.</div>
      <?php unset($_SESSION['show_announcement_error']); endif; ?>
    <?php if (!empty($_SESSION['show_announcement_not_allowed'])): ?>
      <div class="alert alert-warning">Deletion not allowed. Only expired announcements can be deleted by the posting manager.</div>
      <?php unset($_SESSION['show_announcement_not_allowed']); endif; ?>

    <div class="card shadow-sm p-4 mb-5" style="width: 150% ; margin:auto; border-radius:12px; background-color:#ffffff;">
      <div class="card-body">
        <h5 class="mb-3" style="color:#1E3A8A;">Announcements</h5>
        <?php if (!empty($announcements)): ?>
          <div class="table-responsive">
            <table class="table table-striped align-middle">
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
                  ?>
                  <tr>
                    <td><?php echo htmlspecialchars($a['title']); ?></td>
                    <td style="max-width:400px; white-space:normal;"><?php echo nl2br(htmlspecialchars($a['message'])); ?></td>
                    <td><?php echo htmlspecialchars($a['posted_by']); ?></td>
                    <td><?php echo htmlspecialchars(date('Y-m-d H:i', strtotime($a['date_posted']))); ?></td>
                    <td><?php echo $expiry ? htmlspecialchars($expiry) : 'N/A'; ?></td>
                    <td>
                      <?php if ((int)($a['is_active'] ?? 1) === 0): ?>
                        <span class="badge bg-secondary">Archived</span>
                      <?php elseif ($expired): ?>
                        <span class="badge bg-danger">Expired</span>
                      <?php else: ?>
                        <span class="badge bg-success">Active</span>
                      <?php endif; ?>
                    </td>
                    <td>
                      <?php if ($expired && $a['manager_email'] === $manageremail && (int)($a['is_active'] ?? 1) === 1): ?>
                        <form method="POST" style="display:inline-block;">
                          <input type="hidden" name="announcement_id" value="<?php echo (int)$a['id']; ?>">
                          <button type="submit" name="archive_announcement" class="btn btn-sm btn-danger">Archive</button>
                        </form>
                      <?php else: ?>
                        <span class="text-muted">—</span>
                      <?php endif; ?>
                    </td>
                  </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        <?php else: ?>
          <p class="text-muted">No announcements yet.</p>
        <?php endif; ?>
      </div>
    </div>
  </div>

  <!-- SUCCESS MODAL -->
  <div class="modal fade" id="successModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
      <div class="modal-content border-0">
        <div class="modal-header bg-primary text-white">
          <h5 class="modal-title">Success!</h5>
          <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body text-center">
          <p class="fs-5">Leave settings inserted successfully!</p>
          <p>Do you want to announce it to employees?</p>
        </div>
        <div class="modal-footer justify-content-center">
          <button type="button" class="btn btn-primary-custom px-4" id="openAnnounceModal">Announce</button>
          <button type="button" class="btn btn-danger-custom px-4" data-bs-dismiss="modal">Cancel</button>
        </div>
      </div>
    </div>
  </div>

  <!-- ANNOUNCEMENT MODAL -->
  <div class="modal fade" id="announceModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
      <div class="modal-content border-0">
        <div class="modal-header bg-primary text-white">
          <h5 class="modal-title">New Announcement</h5>
          <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
        </div>
        <form method="POST">
          <div class="modal-body">
            <input type="hidden" name="settingID" value="<?php echo $_SESSION['settingID'] ?? ''; ?>">
            <label class="form-label fw-semibold mb-2"><i class="fa-solid fa-heading"></i>Title</label>
            <input type="text" name="title" class="form-control mb-3" required>
            <label class="form-label fw-semibold mb-2"><i class="fa-solid fa-message"></i>Message</label>
            <textarea name="message" class="form-control" rows="4" required></textarea>
          </div>
          <div class="modal-footer justify-content-center">
            <button type="submit" name="save_announcement" class="btn btn-primary-custom px-4">Post
              Announcement</button>
            <button type="button" class="btn btn-danger-custom px-4" data-bs-dismiss="modal">Cancel</button>
          </div>
        </form>
      </div>
    </div>
  </div>

  <!-- SUCCESS POPUP -->
  <div class="modal fade success-popup" id="announcementSuccessModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
      <div class="modal-content text-center">
        <div class="modal-header">
          <h5 class="modal-title w-100"><i class="fa-solid fa-circle-check me-2"></i> Announcement Posted</h5>
        </div>
        <div class="modal-body">
          <p>The announcement has been successfully shared with all employees.</p>
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
      $("#start_date, #end_date").datepicker({ dateFormat: "yy-mm-dd", minDate: 0 });
    });

    <?php if (!empty($_SESSION['show_modal'])): ?>
      new bootstrap.Modal(document.getElementById('successModal')).show();
      <?php unset($_SESSION['show_modal']); endif; ?>

    <?php if (!empty($_SESSION['show_announcement_success'])): ?>
      new bootstrap.Modal(document.getElementById('announcementSuccessModal')).show();
      <?php unset($_SESSION['show_announcement_success']); endif; ?>

    document.getElementById('openAnnounceModal').onclick = function () {
      bootstrap.Modal.getInstance(document.getElementById('successModal')).hide();
      new bootstrap.Modal(document.getElementById('announceModal')).show();
    };
  </script>

</body>

</html>
