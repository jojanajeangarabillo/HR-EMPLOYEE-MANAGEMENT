<?php
session_start();
require 'admin/db.connect.php';

$employees = $requests = $hirings = $applicants = 0;
$managername = "";

// Fetch HR Manager name
$managernameQuery = $conn->query("SELECT fullname FROM user WHERE role='Employee' AND sub_role='HR Manager' LIMIT 1");
if ($managernameQuery && $row = $managernameQuery->fetch_assoc()) {
    $managername = $row['fullname'];
}

// Employee & Applicant Counts
$employeeQuery = $conn->query("SELECT COUNT(*) AS count FROM user WHERE role='Employee'");
if ($employeeQuery && $row = $employeeQuery->fetch_assoc())
    $employees = $row['count'];

$applicantQuery = $conn->query("SELECT COUNT(*) AS count FROM user WHERE role='Applicant'");
if ($applicantQuery && $row = $applicantQuery->fetch_assoc())
    $applicants = $row['count'];

// Handle New Job Post Creation
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['new_post'])) {
    $job_title = trim($_POST['job_title'] ?? '');
    $educational_level = trim($_POST['educational_level'] ?? '');
    $expected_salary = trim($_POST['expected_salary'] ?? '');
    $experience_years = intval($_POST['experience_years'] ?? 0);
    $job_description = trim($_POST['job_description'] ?? '');
    $vacancies = intval($_POST['vacancies'] ?? 0);
    $closing_date = $_POST['closing_date'] ?? null;
    $skills = trim($_POST['skills'] ?? '');
    $vacancy_id = intval($_POST['vacancy_id'] ?? 0);
    $date_posted = date('Y-m-d');

    // Lookup department_id and employment_type_id
    $department_id = $employment_type_id = null;
    $vacancyStmt = $conn->prepare("SELECT department_id, employment_type_id FROM vacancies WHERE id=? LIMIT 1");
    $vacancyStmt->bind_param("i", $vacancy_id);
    $vacancyStmt->execute();
    $vacRes = $vacancyStmt->get_result();
    if ($vacRes && $vacRes->num_rows > 0) {
        $vacRow = $vacRes->fetch_assoc();
        $department_id = intval($vacRow['department_id']);
        $employment_type_id = intval($vacRow['employment_type_id']);
    } else {
        echo "<script>alert('Invalid vacancy selected.'); window.location.href='Manager-JobPosting.php';</script>";
        exit;
    }

    // Normalize skills
    $skills_normalized = '';
    if ($skills !== '') {
        $parts = array_filter(array_map('trim', explode(',', $skills)), fn($v) => $v !== '');
        $parts = array_map('strtolower', $parts);
        $parts = array_unique($parts);
        $skills_normalized = implode(', ', array_map('ucwords', $parts));
    }

    $stmt = $conn->prepare("INSERT INTO job_posting (job_title, department, educational_level, expected_salary, experience_years, job_description, employment_type, vacancies, date_posted, closing_date, skills)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $types = "sissisiisss";
    $stmt->bind_param($types, $job_title, $department_id, $educational_level, $expected_salary, $experience_years, $job_description, $employment_type_id, $vacancies, $date_posted, $closing_date, $skills_normalized);
    if ($stmt->execute()) {
        $update = $conn->prepare("UPDATE vacancies SET status='On-Going' WHERE id=?");
        $update->bind_param("i", $vacancy_id);
        $update->execute();

        echo "<script>alert('Job post created successfully!'); window.location.href='Manager-JobPosting.php';</script>";
        exit;
    } else {
        echo "Error: " . htmlspecialchars($stmt->error);
    }
}

// Fetch Vacancies Available to Post
$vacancies = [];
$vacQuery = "
SELECT v.id, d.deptName, p.position_title, et.typeName AS employment_type, v.vacancy_count, v.status
FROM vacancies v
JOIN department d ON v.department_id=d.deptID
JOIN position p ON v.position_id=p.positionID
JOIN employment_type et ON v.employment_type_id=et.emtypeID
WHERE v.status='To Post'
ORDER BY v.created_at DESC
";
$result = $conn->query($vacQuery);
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) $vacancies[] = $row;
}

// Fetch Recently Posted Jobs with department & employment type names
$recentJobs = [];
$recentQuery = "
SELECT j.*, d.deptName, et.typeName AS employment_type
FROM job_posting j
JOIN department d ON j.department=d.deptID
JOIN employment_type et ON j.employment_type=et.emtypeID
ORDER BY j.date_posted DESC
";
$res = $conn->query($recentQuery);
if ($res && $res->num_rows > 0) {
    while ($row = $res->fetch_assoc()) $recentJobs[] = $row;
}

$today = date('Y-m-d'); // current date

$recentQuery = "
SELECT j.*, d.deptName, et.typeName AS employment_type
FROM job_posting j
JOIN department d ON j.department=d.deptID
JOIN employment_type et ON j.employment_type=et.emtypeID
WHERE j.closing_date IS NULL OR j.closing_date >= ?
ORDER BY j.date_posted DESC
";

$stmt = $conn->prepare($recentQuery);
$stmt->bind_param('s', $today);
$stmt->execute();
$res = $stmt->get_result();
$recentJobs = [];
while ($row = $res->fetch_assoc()) $recentJobs[] = $row;
$stmt->close();

?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8" />
<meta name="viewport" content="width=device-width, initial-scale=1.0" />
<title>Manager - Job Posting</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
<link rel="stylesheet" href="manager-sidebar.css">
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<style>
body { margin:0; padding:0; font-family:'Poppins','Roboto',sans-serif; background:#f1f5fc; display:flex; color:#111827; }
.job-postings-container { flex-grow:1; margin-left:220px; padding:40px; }
.job-header { display:flex; justify-content:space-between; align-items:center; margin-bottom:25px; }
.job-header h2 { font-size:26px; font-weight:700; color:#1f3c88; display:flex; align-items:center; gap:10px; }
</style>
</head>
<body>

<div class="sidebar">
    <div class="sidebar-logo"><img src="Images/hospitallogo.png" alt="Hospital Logo"></div>
    <div class="sidebar-name"><p><?php echo "Welcome, ".htmlspecialchars($managername); ?></p></div>
    <ul class="nav">
      <li><a href="Manager_Dashboard.php"><i class="fa-solid fa-table-columns"></i>Dashboard</a></li>
      <li><a href="Manager_Applicants.php"><i class="fa-solid fa-user-group"></i>Applicants</a></li>
      <li><a href="Manager_PendingApplicants.php"><i class="fa-solid fa-hourglass-half"></i>Pending Applicants</a></li>
        <li><a href="Newly-Hired.php"><i class="fa-solid fa-user-plus"></i>Newly Hired</a></li>
      <li ><a href="Manager_Employees.php"><i class="fa-solid fa-user-group me-2"></i>Employees</a></li>
      <li><a href="Manager_Request.php"><i class="fa-solid fa-code-pull-request"></i>Requests</a></li>
      <li class="active"><a href="Manager-JobPosting.php"><i class="fa-solid fa-briefcase"></i>Job Post</a></li>
      <li><a href="Manager_Calendar.php"><i class="fa-solid fa-calendar"></i>Calendar</a></li>
      <li><a href="Manager_Approvals.php"><i class="fa-solid fa-circle-check"></i>Approvals</a></li>
      <li><a href="Manager_LeaveSettings.php"><i class="fa-solid fa-gear"></i>Settings</a></li>
      <li><a href="#"><i class="fa-solid fa-right-from-bracket"></i>Logout</a></li>
    </ul>
</div>

<main class="job-postings-container">
    <div class="job-header">
        <h2><i class="fa-solid fa-briefcase"></i> Job Posting</h2>
        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#jobModal">+ Add New Job</button>
    </div>

    <!-- Available Jobs -->
    <div class="available-jobs mb-4">
        <h3 style="color:#1f3c88;">Available Jobs to Upload</h3>
        <?php if(!empty($vacancies)): ?>
        <div class="row fw-bold bg-light p-2 rounded mb-2">
            <div class="col-4">Job Title</div>
            <div class="col-3">Department</div>
            <div class="col-2">Vacancies</div>
            <div class="col-3">Status</div>
        </div>
        <?php foreach($vacancies as $job): ?>
        <div class="row align-items-center bg-white p-2 rounded mb-1">
            <div class="col-4"><?= htmlspecialchars($job['position_title']); ?></div>
            <div class="col-3"><?= htmlspecialchars($job['deptName']); ?></div>
            <div class="col-2"><?= htmlspecialchars($job['vacancy_count']); ?></div>
            <div class="col-3">
                <select class="form-select status-dropdown" data-id="<?= (int)$job['id']; ?>">
                    <option value="To Post" <?= ($job['status']=='To Post')?'selected':''; ?>>To Post</option>
                    <option value="On-Going" <?= ($job['status']=='On-Going')?'selected':''; ?>>On-Going</option>
                </select>
            </div>
        </div>
        <?php endforeach; else: ?>
        <p>No available jobs found.</p>
        <?php endif; ?>
    </div>

   <!-- Recently Posted -->
<div class="job-table">
    <h3 style="color:#1f3c88;">Recently Posted</h3>
    <?php if(!empty($recentJobs)): ?>
    <!-- Scrollable container -->
    <div style="max-height: 400px; overflow-y: auto;">
        <div class="row fw-bold bg-primary text-white p-2 rounded mb-2">
            <div class="col-3">Job Title</div>
            <div class="col-2">Department</div>
            <div class="col-2">Vacancies</div>
            <div class="col-2">Date Posted</div>
            <div class="col-3">Closing Date</div>
        </div>
        <?php foreach($recentJobs as $job): ?>
        <div class="row bg-white p-2 rounded mb-1">
            <div class="col-3"><?= htmlspecialchars($job['job_title']); ?></div>
            <div class="col-2"><?= htmlspecialchars($job['deptName']); ?></div>
            <div class="col-2"><?= htmlspecialchars($job['vacancies']); ?></div>
            <div class="col-2"><?= htmlspecialchars($job['date_posted']); ?></div>
            <div class="col-3"><?= htmlspecialchars($job['closing_date']); ?></div>
            
        </div>
        <?php endforeach; ?>
    </div>
    <?php else: ?>
    <p>No job posts yet.</p>
    <?php endif; ?>
</div>

</main>

<!-- Add Job Modal -->
<div class="modal fade" id="jobModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered" style="max-width: 700px;" style="max-height: 100px;" style="font-size:15px;">
    <div class="modal-content">
      <form id="addJobForm" method="POST">
        <div class="modal-header bg-primary text-white">
          <h5 class="modal-title">Add New Job</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <input type="hidden" name="new_post" value="1">
          <input type="hidden" name="vacancy_id" id="vacancy_id">
          <div class="mb-3">
            <label>Job Title</label>
            <select name="job_title" id="job_title" class="form-select" required>
              <option value="">-- Select Job Title --</option>
              <?php foreach($vacancies as $vac): ?>
              <option value="<?= htmlspecialchars($vac['position_title']); ?>"
                      data-department="<?= htmlspecialchars($vac['deptName']); ?>"
                      data-employment="<?= htmlspecialchars($vac['employment_type']); ?>"
                      data-vacancies="<?= htmlspecialchars($vac['vacancy_count']); ?>"
                      data-id="<?= (int)$vac['id']; ?>">
                <?= htmlspecialchars($vac['position_title']); ?>
              </option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="mb-3"><label>Department</label><input type="text" id="department" class="form-control" readonly></div>
          <div class="mb-3"><label>Employment Type</label><input type="text" id="employment_type" class="form-control" readonly></div>
          <div class="mb-3"><label>Vacancies</label><input type="number" id="vacancies" class="form-control" readonly></div>
          <div class="mb-3"><label>Educational Level</label><input type="text" name="educational_level" class="form-control" required></div>
          <div class="mb-3"><label>Skills</label><input type="text" name="skills" class="form-control"></div>
          <div class="mb-3"><label>Expected Salary</label><input type="text" name="expected_salary" class="form-control" required></div>
          <div class="mb-3"><label>Experience in Years</label><input type="text" name="experience_years" class="form-control" required></div>
          <div class="mb-3"><label>Job Description</label><textarea name="job_description" class="form-control" rows="4" required></textarea></div>
          <div class="mb-3"><label>Closing Date</label><input type="date" name="closing_date" class="form-control" required></div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-danger" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-success">Post Job</button>
        </div>
      </form>
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
$(document).ready(function() {
    // Auto-fill Add Job fields
    $('#job_title').change(function(){
        var selected = $(this).find(':selected');
        $('#department').val(selected.data('department') || '');
        $('#employment_type').val(selected.data('employment') || '');
        $('#vacancies').val(selected.data('vacancies') || '');
        $('#vacancy_id').val(selected.data('id') || '');
    });

    // AJAX status update
    $('.status-dropdown').change(function(){
        var status = $(this).val();
        var id = $(this).data('id');
        $.ajax({
            url: 'update_vacancy_status.php',
            type: 'POST',
            data: {vacancy_id:id, status:status},
            success:function(res){ console.log(res); },
            error:function(){ alert('Error updating status.'); }
        });
    });
});
</script>
</body>
</html>
