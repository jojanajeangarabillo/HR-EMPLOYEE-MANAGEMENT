<?php
session_start();
require 'admin/db.connect.php';

$employees = $requests = $hirings = $applicants = 0;
$managername = "";

// Fetch HR Manager name
$managernameQuery = $conn->query("SELECT fullname FROM user WHERE role = 'Employee' AND sub_role = 'HR Manager' LIMIT 1");
if ($managernameQuery && $row = $managernameQuery->fetch_assoc()) {
  $managername = $row['fullname'];
}

// Employee & Applicant Counts
$employeeQuery = $conn->query("SELECT COUNT(*) AS count FROM user WHERE role = 'Employee'");
if ($employeeQuery && $row = $employeeQuery->fetch_assoc())
  $employees = $row['count'];

$applicantQuery = $conn->query("SELECT COUNT(*) AS count FROM user WHERE role = 'Applicant'");
if ($applicantQuery && $row = $applicantQuery->fetch_assoc())
  $applicants = $row['count'];

// Handle Vacancy Status Update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['vacancy_id'], $_POST['status']) && !isset($_POST['new_post'])) {
  $id = intval($_POST['vacancy_id']);
  $status = $_POST['status'];
  $stmt = $conn->prepare("UPDATE vacancies SET status = ? WHERE id = ?");
  $stmt->bind_param("si", $status, $id);
  $stmt->execute();
  header("Location: " . $_SERVER['PHP_SELF']);
  exit;
}

// Handle New Job Post Creation
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['new_post'])) {
  // Sanitize and collect posted values
  $job_title = isset($_POST['job_title']) ? trim($_POST['job_title']) : '';
  $educational_level = isset($_POST['educational_level']) ? trim($_POST['educational_level']) : '';
  $expected_salary = isset($_POST['expected_salary']) ? trim($_POST['expected_salary']) : '';
  $experience_years = isset($_POST['experience_years']) ? intval($_POST['experience_years']) : 0;
  $job_description = isset($_POST['job_description']) ? trim($_POST['job_description']) : '';
  $vacancies = isset($_POST['vacancies']) ? intval($_POST['vacancies']) : 0;
  $closing_date = isset($_POST['closing_date']) ? $_POST['closing_date'] : null;
  $skills = isset($_POST['skills']) ? trim($_POST['skills']) : '';
  $vacancy_id = isset($_POST['vacancy_id']) ? intval($_POST['vacancy_id']) : 0;
  $date_posted = date('Y-m-d');

  // Look up department_id and employment_type_id from the selected vacancy (they are integers in the DB)
  $department_id = null;
  $employment_type_id = null;
  $vacancyStmt = $conn->prepare("SELECT department_id, employment_type_id FROM vacancies WHERE id = ? LIMIT 1");
  if ($vacancyStmt) {
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
  } else {
    echo "<script>alert('Server error (vacancy lookup).'); window.location.href='Manager-JobPosting.php';</script>";
    exit;
  }

  // Normalize skills: split by comma, trim, lowercase, dedupe, join as Title Case
  $skills_normalized = '';
  if ($skills !== '') {
    $parts = array_filter(array_map('trim', explode(',', $skills)), function ($v) {
      return $v !== '';
    });
    $parts = array_map('strtolower', $parts);
    $parts = array_unique($parts);
    $parts = array_values($parts);
    $skills_normalized = implode(', ', array_map('ucwords', $parts));
  }

  $stmt = $conn->prepare("INSERT INTO job_posting (job_title, department, educational_level, expected_salary, experience_years, job_description, employment_type, vacancies, date_posted, closing_date, skills)
                          VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
  if (!$stmt) {
    echo "Error preparing statement: " . htmlspecialchars($conn->error);
    exit;
  }

  $types = "sissisiisss";
  $stmt->bind_param(
    $types,
    $job_title,
    $department_id,
    $educational_level,
    $expected_salary,
    $experience_years,
    $job_description,
    $employment_type_id,
    $vacancies,
    $date_posted,
    $closing_date,
    $skills_normalized
  );

  if ($stmt->execute()) {
    // Update Vacancy Status
    $update = $conn->prepare("UPDATE vacancies SET status = 'On-Going' WHERE id = ?");
    if ($update) {
      $update->bind_param("i", $vacancy_id);
      $update->execute();
    }

    echo "<script>alert('Job post created successfully!'); window.location.href='Manager-JobPosting.php';</script>";
    exit;
  } else {
    // Log error and show friendly message
    error_log('Job insert failed: ' . $stmt->error);
    echo "Error: " . htmlspecialchars($stmt->error);
  }
}

// Fetch Vacancies Available to Post
$vacancies = [];
$query = "
SELECT v.id, d.deptName, p.position_title, v.vacancy_count, v.status
FROM vacancies v
JOIN department d ON v.department_id = d.deptID
JOIN position p ON v.position_id = p.positionID
WHERE v.status = 'To Post'
ORDER BY v.created_at DESC
";
$result = $conn->query($query);
if ($result && $result->num_rows > 0) {
  while ($row = $result->fetch_assoc()) {
    $vacancies[] = $row;
  }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Manager - Job Posting</title>

  <link rel="stylesheet" href="manager-sidebar.css">

  <!-- Icons -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/7.0.1/css/all.min.css">
  <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

  <style>
    body {
      margin: 0;
      padding: 0;
      font-family: 'Poppins', 'Roboto', sans-serif;
      background: #f1f5fc;
      display: flex;
      color: #111827;
    }

    .sidebar-logo {
      display: flex;
      justify-content: center;
      margin-bottom: 25px;
    }

    .sidebar-logo img {
      height: 110px;
      width: 110px;
      border-radius: 50%;
      object-fit: cover;
      border: 3px solid #fff;
    }

    .job-postings-container {
      flex-grow: 1;
      margin-left: 220px;
      padding: 40px;
    }

    .job-header {
      display: flex;
      justify-content: space-between;
      align-items: center;
      margin-bottom: 25px;
    }

    .job-header h2 {
      font-size: 26px;
      font-weight: 700;
      color: #1f3c88;
      display: flex;
      align-items: center;
      gap: 10px;
    }

    .add-job-btn {
      background: #1f3c88;
      color: #fff;
      padding: 10px 18px;
      border: none;
      border-radius: 5px;
      cursor: pointer;
    }

    .job-table-header,
    .job-row {
      display: grid;
      grid-template-columns: 1.8fr 1.2fr 1.2fr 0.7fr 0.8fr 1fr;
      padding: 12px 15px;
      align-items: center;
    }

    .job-table-header {
      background: #1f3c88;
      color: #fff;
      border-radius: 6px;
      margin-bottom: 10px;
      font-weight: 700;
    }

    .job-row {
      background: #2f5fca;
      color: #fff;
      margin-bottom: 8px;
      border-radius: 6px;
    }

    .job-icon {
      font-size: 28px;
      color: #1a3f9b;
    }

    .sidebar-name {
      text-align: center;
      color: #fff;
      margin-bottom: 30px;
      font-size: 18px;
    }

    .status-dropdown {
      width: 100%;
      padding: 6px 10px;
      border-radius: 6px;
      border: 1px solid #ccc;
      background: #f1f5fc;
      color: #1f3c88;
      font-weight: 500;
    }

    .modal-overlay {
      position: fixed;
      top: 0;
      left: 0;
      width: 100%;
      height: 100%;
      background: rgba(0, 0, 0, 0.4);
      display: flex;
      justify-content: center;
      align-items: center;
      visibility: hidden;
      opacity: 0;
      transition: opacity .3s;
    }

    .modal-overlay.show {
      visibility: visible;
      opacity: 1;
    }

    .modal-content {
      background: #1f57ff;
      padding: 30px;
      width: 70%;
      border-radius: 20px;
      color: #fff;
    }

    .form-group {
      margin-bottom: 15px;
    }

    .form-group label {
      display: block;
      margin-bottom: 5px;
    }

    .form-group input,
    .form-group textarea,
    .form-group select {
      width: 100%;
      padding: 8px;
      border: none;
      border-radius: 5px;
    }

    .button-group {
      display: flex;
      justify-content: flex-end;
      gap: 15px;
      margin-top: 20px;
    }

    .cancel-btn {
      background: #b30000;
      color: #fff;
      padding: 8px 20px;
      border: none;
      border-radius: 5px;
      cursor: pointer;
    }

    .post-btn {
      background: #0b8f2e;
      color: #fff;
      padding: 8px 20px;
      border: none;
      border-radius: 5px;
      cursor: pointer;
    }
  </style>
</head>

<body>
  <div class="sidebar">
    <div class="sidebar-logo"><img src="Images/hospitallogo.png" alt="Hospital Logo"></div>
    <div class="sidebar-name">
      <p><?php echo "Welcome, " . htmlspecialchars($managername); ?></p>
    </div>

    <ul class="nav">
      <li><a href="Manager_Dashboard.php"><i class="fa-solid fa-table-columns"></i>Dashboard</a></li>
      <li><a href="Manager_Applicants.php"><i class="fa-solid fa-user-group"></i>Applicants</a></li>
      <li><a href="Manager_PendingApplicants.php"><i class="fa-solid fa-hourglass-half"></i>Pending Applicants</a></li>
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
      <h2><i class="fa-solid fa-briefcase job-icon"></i> Job Posting</h2>
      <button class="add-job-btn">+ Add New Job</button>
    </div>

    <div class="available-jobs">
      <h3 style="color:#1f3c88;">Available Jobs to Upload</h3>

      <?php if (!empty($vacancies)): ?>
        <div
          style="display:grid;grid-template-columns:1.5fr 1.2fr 0.8fr 0.8fr;padding:10px;background:#e2e8f0;border-radius:8px;">
          <div><strong>Job Title</strong></div>
          <div><strong>Department</strong></div>
          <div><strong>Vacancies</strong></div>
          <div><strong>Status</strong></div>
        </div>

        <?php foreach ($vacancies as $job): ?>
          <div
            style="display:grid;grid-template-columns:1.5fr 1.2fr 0.8fr 0.8fr;padding:10px;background:#fff;border-radius:6px;margin-top:5px;">
            <div><?= htmlspecialchars($job['position_title']); ?></div>
            <div><?= htmlspecialchars($job['deptName']); ?></div>
            <div><?= htmlspecialchars($job['vacancy_count']); ?></div>
            <div>
              <form method="POST" style="margin:0;">
                <input type="hidden" name="vacancy_id" value="<?= (int) $job['id']; ?>">
                <select name="status" class="status-dropdown" onchange="this.form.submit()">
                  <option value="To Post" <?= ($job['status'] == 'To Post') ? 'selected' : ''; ?>>To Post</option>
                  <option value="On-Going" <?= ($job['status'] == 'On-Going') ? 'selected' : ''; ?>>On-Going</option>
                </select>
              </form>
            </div>
          </div>
        <?php endforeach; ?>
      <?php else: ?>
        <p style="color:#555;">No available jobs found.</p>
      <?php endif; ?>
    </div>

    <div class="job-table">
      <h3 style="color:#1f3c88;">Recently Posted</h3>
      <?php
      $recentJobs = $conn->query("SELECT * FROM job_posting ORDER BY date_posted DESC");
      if ($recentJobs && $recentJobs->num_rows > 0): ?>
        <div class="job-table-header">
          <div>Job Title</div>
          <div>Department</div>
          <div>Vacancies</div>
          <div>Date Posted</div>
          <div>Closing Date</div>

        </div>
        <?php while ($job = $recentJobs->fetch_assoc()): ?>
          <div class="job-row">
            <div><?= htmlspecialchars($job['job_title']); ?></div>
            <div><?= htmlspecialchars($job['department']); ?></div>
            <div><?= htmlspecialchars($job['vacancies']); ?></div>
            <div><?= htmlspecialchars($job['date_posted']); ?></div>
            <div><?= htmlspecialchars($job['closing_date']); ?></div>
          </div>
        <?php endwhile; ?>
      <?php else: ?>
        <p style="color:#555;">No job posts yet.</p>
      <?php endif; ?>
    </div>
  </main>

  <!-- ADD JOB MODAL -->
  <div id="jobModal" class="modal-overlay">
    <div class="modal-content">
      <form method="POST">
        <input type="hidden" name="new_post" value="1">
        <input type="hidden" name="vacancy_id" id="vacancy_id">

        <div class="form-group">
          <label>Job Title</label>
          <select name="job_title" id="job_title" required>
            <option value="">-- Select Job Title --</option>
            <?php foreach ($vacancies as $vac): ?>
              <option value="<?= htmlspecialchars($vac['position_title']); ?>"
                data-department="<?= htmlspecialchars($vac['deptName']); ?>"
                data-vacancies="<?= htmlspecialchars($vac['vacancy_count']); ?>" data-id="<?= (int) $vac['id']; ?>">
                <?= htmlspecialchars($vac['position_title']); ?>
              </option>
            <?php endforeach; ?>
          </select>
        </div>

        <div class="form-group">
          <label>Department</label>
          <input type="text" name="department" id="department" readonly>
        </div>

        <div class="form-group">
          <label>Employment Type</label>
          <input type="text" name="employment_type" id="employment_type" readonly>
        </div>

        <div class="form-group">
          <label>Vacancies</label>
          <input type="number" name="vacancies" id="vacancies" readonly>
        </div>

        <div class="form-group">
          <label>Educational Level</label>
          <input type="text" name="educational_level" required>
        </div>

        <div class="form-group">
          <label>Skills (comma separated)</label>
          <input type="text" name="skills" placeholder="e.g. Nursing, BLS, Phlebotomy">
        </div>

        <div class="form-group">
          <label>Expected Salary</label>
          <input type="text" name="expected_salary" required>
        </div>

        <div class="form-group">
          <label>Experience in Years</label>
          <input type="text" name="experience_years" required>
        </div>

        <div class="form-group">
          <label for="job_description">Job Description:</label>
          <textarea name="job_description" id="job_description" rows="4" required></textarea>
        </div>

        <div class="form-group">
          <label>Closing Date</label>
          <input type="date" name="closing_date" required>
        </div>

        <div class="button-group">
          <button type="button" class="cancel-btn">Cancel</button>
          <button type="submit" class="post-btn">Post Job</button>
        </div>
      </form>
    </div>
  </div>

  <script>
    $(document).ready(function () {
      $(".add-job-btn").click(() => $("#jobModal").addClass("show"));
      $(".cancel-btn").click(() => $("#jobModal").removeClass("show"));

      // Auto-fill fields when job selected; employment_type may be absent, so default to empty string
      $('#job_title').change(function () {
        var selected = $(this).find(':selected');
        $('#department').val(selected.data('department') || '');
        // jobtype might not exist in your DB; keep safe
        $('#employment_type').val(selected.data('jobtype') || '');
        $('#vacancies').val(selected.data('vacancies') || '');
        $('#vacancy_id').val(selected.data('id') || '');
      });
    });
  </script>
</body>

</html>