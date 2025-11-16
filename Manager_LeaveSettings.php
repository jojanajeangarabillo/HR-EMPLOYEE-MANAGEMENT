<?php
session_start();
require 'admin/db.connect.php';

// Get manager name
$managernameQuery = $conn->query("SELECT fullname, email FROM user WHERE role = 'Employee' AND sub_role ='HR Manager' LIMIT 1");
$manager = ($managernameQuery && $row = $managernameQuery->fetch_assoc()) ? $row : ['fullname' => 'Manager', 'email' => 'manager@example.com'];
$managername = $manager['fullname'];
$manageremail = $manager['email'];

// --- Handle Leave Setting Submission ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_leave'])) {
    $leave_type = $_POST['purpose'];
    $start_date = $_POST['start_date'];
    $end_date = $_POST['end_date'];
    $employee_limit = $_POST['employeeLimit'];
    $time_limit = $_POST['leaveDuration'];
    $created_by = $_POST['createdBy'];
    $duration = "$start_date to $end_date";

    $stmt = $conn->prepare("INSERT INTO leave_settings (leave_type, duration, employee_limit, time_limit, created_by) VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param("ssiss", $leave_type, $duration, $employee_limit, $time_limit, $created_by);

    if ($stmt->execute()) {
        $_SESSION['settingID'] = $conn->insert_id;
        $_SESSION['show_modal'] = true;
    } else {
        echo "<script>alert('Error saving leave setting: " . $conn->error . "');</script>";
    }
    $stmt->close();
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
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Leave Settings</title>
<link rel="stylesheet" href="manager-sidebar.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<script src="https://code.jquery.com/jquery-3.6.4.min.js"></script>
<link rel="stylesheet" href="https://code.jquery.com/ui/1.13.2/themes/base/jquery-ui.css">
<script src="https://code.jquery.com/ui/1.13.2/jquery-ui.min.js"></script>

<style>
body { background-color:#f7f9fc; font-family:"Poppins","Roboto",sans-serif; display:flex; margin:0; }
.main-content { padding:40px 30px; margin-left:300px; display:flex; flex-direction:column; }
.main-content h2 { font-size:26px; color:#1E3A8A; font-weight:700; margin-bottom:25px; display:flex; align-items:center; gap:10px; }
.sidebar-name { display:flex; justify-content:center; align-items:center; text-align:center; color:white; padding:10px; margin-bottom:30px; font-size:18px; flex-direction:column; }
.sidebar-logo img { height:110px; width:110px; border-radius:50%; object-fit:cover; border:3px solid #fff; }


.btn-primary-custom { background-color: #1E3A8A; color: white; border: none; }
.btn-primary-custom:hover { background-color: #16326a; color: white; }

.btn-danger-custom { background-color: red; color: white; border: none; }
.btn-danger-custom:hover { background-color: darkred; color: white; }

.form-label i { color: #1E3A8A; margin-right: 6px; }

</style>
</head>
<body>

<!-- SIDEBAR -->
<div class="sidebar">
  <div class="sidebar-logo"><img src="Images/hospitallogo.png" alt="Hospital Logo"></div>
  <div class="sidebar-name"><p><?php echo "Welcome, $managername"; ?></p></div>
  <ul class="nav">
    <li><a href="Manager_Dashboard.php"><i class="fa-solid fa-table-columns"></i>Dashboard</a></li>
    <li><a href="Manager_Applicants.php"><i class="fa-solid fa-user-group"></i>Applicants</a></li>
    <li><a href="Manager_PendingApplicants.php"><i class="fa-solid fa-hourglass-half"></i>Pending Applicants</a></li>
       <li><a href="Newly-Hired.php"><i class="fa-solid fa-user-plus"></i>Newly Hired</a></li>
    <li ><a href="Manager_Employees.php"><i class="fa-solid fa-user-group me-2"></i>Employees</a></li>
    <li><a href="Manager_Request.php"><i class="fa-solid fa-code-pull-request"></i>Requests</a></li>
    <li><a href="Manager-JobPosting.php"><i class="fa-solid fa-briefcase"></i>Job Post</a></li>
    <li><a href="Manager_Calendar.php"><i class="fa-solid fa-calendar"></i>Calendar</a></li>
    <li><a href="Manager_Approvals.php"><i class="fa-solid fa-circle-check"></i>Approvals</a></li>
    <li class="active"><a href="Manager_LeaveSettings.php"><i class="fa-solid fa-gear"></i>Settings</a></li>
    <li><a href="#"><i class="fa-solid fa-right-from-bracket"></i>Logout</a></li>
  </ul>
</div>

<!-- MAIN CONTENT -->
<div class="main-content">
  <h2><i class="fa-solid fa-gear"></i> Leave Settings</h2>

  <!-- Enhanced Leave Form Container -->
  <div class="card shadow-sm p-4 mb-5" style="width: 150% ; margin:auto; border-radius:12px; background-color:#ffffff;">
    <div class="card-body">
      <form method="POST">
        <div class="mb-3">
          <label class="form-label fw-semibold"><i class="fa-solid fa-clipboard"></i>Purpose</label>
          <input type="text" name="purpose" class="form-control" value="Leave" readonly required>
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
          <label class="form-label fw-semibold"><i class="fa-solid fa-users"></i>Employee Limit</label>
          <input type="number" name="employeeLimit" class="form-control" min="1" required>
        </div>

        <div class="mb-3">
          <label class="form-label fw-semibold"><i class="fa-solid fa-clock"></i>Leave Duration</label>
          <select name="leaveDuration" class="form-select" required>
            <option value="" disabled selected>Select duration</option>
            <option value="1 day">1 day</option>
            <option value="2 days">2 days</option>
            <option value="3 days">3 days</option>
            <option value="5 days">5 days</option>
            <option value="1 week">1 week</option>
            <option value="2 weeks">2 weeks</option>
          </select>
        </div>

        <div class="mb-4">
          <label class="form-label fw-semibold"><i class="fa-solid fa-user"></i>Created by</label>
          <input type="text" name="createdBy" class="form-control" value="<?php echo $managername; ?>" readonly>
        </div>

        <div class="d-flex justify-content-end gap-2">
          <button type="button" class="btn btn-danger-custom" onclick="window.location.href='Manager_Dashboard.php'">Cancel</button>
          <button type="submit" name="save_leave" class="btn btn-primary-custom">Post</button>
        </div>
      </form>
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
          <button type="submit" name="save_announcement" class="btn btn-primary-custom px-4">Post Announcement</button>
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

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
$(function() {
  $("#start_date, #end_date").datepicker({ dateFormat: "yy-mm-dd", minDate: 0 });
});

<?php if (!empty($_SESSION['show_modal'])): ?> 
new bootstrap.Modal(document.getElementById('successModal')).show();
<?php unset($_SESSION['show_modal']); endif; ?>

<?php if (!empty($_SESSION['show_announcement_success'])): ?> 
new bootstrap.Modal(document.getElementById('announcementSuccessModal')).show();
<?php unset($_SESSION['show_announcement_success']); endif; ?>

document.getElementById('openAnnounceModal').onclick = function() {
  bootstrap.Modal.getInstance(document.getElementById('successModal')).hide();
  new bootstrap.Modal(document.getElementById('announceModal')).show();
};
</script>

</body>
</html>
