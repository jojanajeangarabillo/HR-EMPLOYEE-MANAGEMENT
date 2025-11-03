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
        // ✅ Save last inserted settingID
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

    // ✅ Retrieve settingID from POST if exists
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
/* ✅ Your CSS untouched */
body { background-color:#f7f9fc; font-family:"Poppins","Roboto",sans-serif; display:flex; margin:0; }
.main-content { padding:40px 30px; margin-left:300px; display:flex; flex-direction:column; }
.main-content h2 { font-size:24px; color:#1E3A8A; font-weight:700; margin-bottom:25px; display:flex; align-items:center; gap:10px; }
.leave-form-container { background-color:#E5E7EB; padding:40px; border-radius:10px; width:500px; max-width:90%; box-shadow:0 4px 10px rgba(0,0,0,0.1); }
.leave-form-container form { display:flex; flex-direction:column; gap:15px; }
.leave-form-container label { font-weight:600; color:#333; }
.leave-form-container input, .leave-form-container select { padding:10px; border-radius:5px; border:1px solid #ccc; outline:none; transition:0.2s; }
.leave-form-container input:focus, .leave-form-container select:focus { border-color:#1E3A8A; }
.form-buttons { display:flex; justify-content:flex-end; gap:10px; margin-top:10px; }
.cancel-btn { background-color:#b91c1c; color:white; border:none; padding:8px 20px; border-radius:5px; cursor:pointer; transition:0.3s; }
.cancel-btn:hover { background-color:#991b1b; }
.post-btn { background-color:#15803d; color:white; border:none; padding:8px 20px; border-radius:5px; cursor:pointer; transition:0.3s; }
.post-btn:hover { background-color:#166534; }
.sidebar-name { display:flex; justify-content:center; align-items:center; text-align:center; color:white; padding:10px; margin-bottom:30px; font-size:18px; flex-direction:column; }
.sidebar-logo img { height:110px; width:110px; border-radius:50%; object-fit:cover; border:3px solid #fff; }
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

<div class="leave-form-container">
<form method="POST">
<label>Purpose:</label>
<input type="text" name="purpose" value="Leave" readonly required>

<label>Filing Start Date:</label>
<input type="text" id="start_date" name="start_date" required>

<label>Filing End Date:</label>
<input type="text" id="end_date" name="end_date" required>

<label>Employee Limit:</label>
<input type="number" name="employeeLimit" min="1" required>

<label>Leave Duration:</label>
<select name="leaveDuration" required>
<option value="" disabled selected>Select duration</option>
<option value="1 day">1 day</option>
<option value="2 days">2 days</option>
<option value="3 days">3 days</option>
<option value="5 days">5 days</option>
<option value="1 week">1 week</option>
<option value="2 weeks">2 weeks</option>
</select>

<label>Created by:</label>
<input type="text" name="createdBy" value="<?php echo $managername; ?>" readonly>

<div class="form-buttons">
<button type="button" class="cancel-btn" onclick="window.location.href='Manager_Dashboard.php'">Cancel</button>
<button type="submit" name="save_leave" class="post-btn">Post</button>
</div>
</form>
</div>
</div>

<!-- SUCCESS MODAL -->
<div class="modal fade" id="successModal" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content border-0">
      <div class="modal-header bg-success text-white">
        <h5 class="modal-title">Success!</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body text-center">
        <p class="fs-5">Leave settings inserted successfully!</p>
        <p>Do you want to announce it to employees?</p>
      </div>
      <div class="modal-footer justify-content-center">
        <button type="button" class="btn btn-primary px-4" id="openAnnounceModal">Announce</button>
        <button type="button" class="btn btn-secondary px-4" data-bs-dismiss="modal">Cancel</button>
      </div>
    </div>
  </div>
</div>

<!-- ✅ ANNOUNCEMENT MODAL WITH SETTING ID -->
<div class="modal fade" id="announceModal" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content border-0">
      <div class="modal-header bg-primary text-white">
        <h5 class="modal-title">New Announcement</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>

      <form method="POST">
        <div class="modal-body">

          <!-- ✅ Hidden settingID -->
          <input type="hidden" name="settingID" value="<?php echo $_SESSION['settingID'] ?? ''; ?>">

          <label class="fw-semibold mb-2">Title</label>
          <input type="text" name="title" class="form-control mb-3" required>

          <label class="fw-semibold mb-2">Message</label>
          <textarea name="message" class="form-control" rows="4" required></textarea>
        </div>

        <div class="modal-footer justify-content-center">
          <button type="submit" name="save_announcement" class="btn btn-success px-4">Post Announcement</button>
          <button type="button" class="btn btn-secondary px-4" data-bs-dismiss="modal">Cancel</button>
        </div>
      </form>

    </div>
  </div>
</div>

<!-- SUCCESS POPUP -->
<div class="modal fade success-popup" id="announcementSuccessModal" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content text-center">
      <div class="modal-header"><h5 class="modal-title w-100"><i class="fa-solid fa-circle-check me-2"></i> Announcement Posted</h5></div>
      <div class="modal-body"><p>The announcement has been successfully shared with all employees.</p></div>
      <div class="modal-footer justify-content-center border-0"><button type="button" class="btn btn-success px-4" data-bs-dismiss="modal">OK</button></div>
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

<script>
$(function() {
  $("#start_date, #end_date").datepicker({ dateFormat: "yy-mm-dd", minDate: 0 });
});

// Show modals
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
