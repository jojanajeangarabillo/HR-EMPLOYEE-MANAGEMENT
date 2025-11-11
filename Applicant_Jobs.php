<?php
session_start();
require 'admin/db.connect.php';

// Flash messages
$flash_success = $_SESSION['flash_success'] ?? '';
$flash_error = $_SESSION['flash_error'] ?? '';
unset($_SESSION['flash_success'], $_SESSION['flash_error']);

// Modal triggers
$apply_success_job = $_SESSION['apply_success'] ?? '';
$apply_rejected_job = $_SESSION['apply_rejected'] ?? '';
unset($_SESSION['apply_success'], $_SESSION['apply_rejected']);

// Applicant data
$applicant_id = $_SESSION['applicant_employee_id'] ?? $_SESSION['applicantID'] ?? '';
$applicantname = 'Applicant';

if (!empty($applicant_id)) {
  $stmt = $conn->prepare("SELECT fullName FROM applicant WHERE applicantID = ?");
  $stmt->bind_param("s", $applicant_id);
  $stmt->execute();
  $result = $stmt->get_result();
  if ($row = $result->fetch_assoc()) {
    $applicantname = $row['fullName'];
  }
  $stmt->close();
}

// Search feature
$search = trim($_GET['q'] ?? '');
$job_sql_base = "
  SELECT jp.jobID, jp.job_title, jp.job_description,
         COALESCE(d.deptName,'') AS department_name,
         jp.qualification, jp.educational_level,
         jp.expected_salary, jp.experience_years,
         COALESCE(et.typeName,'') AS employment_type,
        jp.vacancies, jp.date_posted, jp.closing_date
  FROM job_posting jp
  LEFT JOIN department d ON jp.department = d.deptID
  LEFT JOIN employment_type et ON jp.employment_type = et.emtypeID
";

$jobs = [];
if ($search !== '') {
  $job_sql = $job_sql_base . "
    WHERE (jp.job_title LIKE ? OR jp.job_description LIKE ? OR d.deptName LIKE ?)
    ORDER BY jp.date_posted DESC";
  $job_stmt = $conn->prepare($job_sql);
  $like = "%{$search}%";
  $job_stmt->bind_param('sss', $like, $like, $like);
} else {
  $job_sql = $job_sql_base . " ORDER BY jp.date_posted DESC";
  $job_stmt = $conn->prepare($job_sql);
}

if ($job_stmt) {
  $job_stmt->execute();
  $jobs = $job_stmt->get_result()->fetch_all(MYSQLI_ASSOC);
  $job_stmt->close();
}

// Fetch applications with status
$applications = [];
if (!empty($applicant_id)) {
  $app_q = $conn->prepare("SELECT jobID, status FROM applications WHERE applicantID = ?");
  $app_q->bind_param('s', $applicant_id);
  $app_q->execute();
  $ares = $app_q->get_result();
  while ($ar = $ares->fetch_assoc()) {
    $applications[$ar['jobID']] = $ar['status'];
  }
  $app_q->close();
}

// Apply logic
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['apply_job'])) {
  if (empty($applicant_id)) {
    header('Location: Login.php');
    exit();
  }

  $job_id = (int) $_POST['job_id'];
  if ($job_id <= 0) {
    $_SESSION['flash_error'] = 'Invalid job selected.';
    header('Location: Applicant_Jobs.php');
    exit();
  }

  // Prevent duplicate
  $check = $conn->prepare("SELECT id FROM applications WHERE applicantID = ? AND jobID = ? LIMIT 1");
  $check->bind_param('si', $applicant_id, $job_id);
  $check->execute();
  if ($check->get_result()->num_rows > 0) {
    $_SESSION['flash_error'] = 'You have already applied for this job.';
    $check->close();
    header('Location: Applicant_Jobs.php');
    exit();
  }
  $check->close();

  // Applicant experience
  $app_data = $conn->prepare("SELECT years_experience FROM applicant WHERE applicantID = ? LIMIT 1");
  $app_data->bind_param('s', $applicant_id);
  $app_data->execute();
  $applicant_exp = (int) ($app_data->get_result()->fetch_assoc()['years_experience'] ?? 0);
  $app_data->close();

  // Job data
  $job_data = $conn->prepare("SELECT job_title, experience_years FROM job_posting WHERE jobID = ? LIMIT 1");
  $job_data->bind_param('i', $job_id);
  $job_data->execute();
  $job_info = $job_data->get_result()->fetch_assoc();
  $job_data->close();

  $required_exp = (int) ($job_info['experience_years'] ?? 0);
  $job_title = $job_info['job_title'] ?? 'this job';

  $matches = $applicant_exp >= $required_exp;
  $app_status = $matches ? 'Pending' : 'Rejected';

  // Insert application
  $insert = $conn->prepare("INSERT INTO applications (applicantID, jobID, status) VALUES (?, ?, ?)");
  $insert->bind_param('sis', $applicant_id, $job_id, $app_status);
  if ($insert->execute()) {
    if ($app_status === 'Pending') {
      $_SESSION['apply_success'] = $job_title;
    } else {
      $_SESSION['apply_rejected'] = $job_title;
    }
  } else {
    $_SESSION['flash_error'] = 'Failed to submit application.';
  }
  $insert->close();

  header('Location: Applicant_Jobs.php');
  exit();
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Job Listing</title>
  <!-- Bootstrap CSS -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">

  <!-- Font Awesome -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

  <!-- Custom CSS -->
  <link rel="stylesheet" href="applicant.css">
  <style>
    body {
      font-family: 'Poppins', sans-serif;
      background-color: #f9fafc;
      display: flex;
    }

    .main-content {
      margin-left: 150px;
      padding: 24px 32px;
      width: calc(100% - 240px);
    }

    h1 {
      font-size: 40px;
      font-weight: 600;
      color: #1E3A8A;
      margin-bottom: 10px;

    }

    .job-table {
      margin-top: 3%;

    }

    .search-bar {
      position: absolute;
      top: 25px;
      right: 40px;
      background: #f3f0fa;
      border-radius: 20px;
      padding: 8px 15px;
      display: flex;
      align-items: center;
    }

    .search-bar input {
      border: none;
      background: transparent;
      outline: none;
    }

    .flash-success,
    .flash-error {
      padding: 12px;
      border-radius: 8px;
      margin-bottom: 12px;
      margin-left: 200px;
      max-width: 1200px;
      box-shadow: 0 2px 6px rgba(16, 24, 40, 0.06);
    }

    .flash-success {
      background: #d1fae5;
      color: #065f46;
    }

    .flash-error {
      background: #fee2e2;
      color: #991b1b;
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
  </style>
</head>

<body>
  <!-- Sidebar -->
  <div class="sidebar">
    <a href="Applicant_Profile.php" class="profile"><i class="fa-solid fa-user"></i></a>
    <div class="sidebar-name">
      <p><?php echo "Welcome, $applicantname"; ?></p>
    </div>
    <ul class="nav">
      <li><a href="Applicant_Dashboard.php"><i class="fa-solid fa-table-columns"></i>Dashboard</a></li>
      <li><a href="Applicant_Application.php"><i class="fa-solid fa-file-lines"></i>Applications</a></li>
      <li class="active"><a href="Applicant_Jobs.php"><i class="fa-solid fa-briefcase"></i>Jobs</a></li>
      <li><a href="Applicant_Login.php"><i class="fa-solid fa-right-from-bracket"></i>Log Out</a></li>
    </ul>
  </div>

  <!-- Main Content -->
  <div class="main-content">
    <h1>Job Listing</h1>
    <hr>

    <?php if (!empty($flash_success)): ?>
      <div class="flash-success"><?php echo htmlspecialchars($flash_success); ?></div>
    <?php endif; ?>
    <?php if (!empty($flash_error)): ?>
      <div class="flash-error"><?php echo htmlspecialchars($flash_error); ?></div>
    <?php endif; ?>

    <!-- Search 
    <form class="search-bar" method="get">
      <input type="text" name="q" placeholder="Search Jobs" value="<?= htmlspecialchars($search) ?>">
      <button type="submit" class="btn btn-link p-0 text-dark"><i class="fa-solid fa-magnifying-glass"></i></button>
    </form> -->

    <!-- Job Table -->
    <div class="job-table">
      <div class="table-responsive mt-5">
        <table class="table table-bordered table-hover align-middle">
          <thead class="table-primary text-center">
            <tr>
              <th>Job Title</th>
              <th>Department</th>
              <th>Type</th>
              <th>Experience</th>
              <th>Description</th>
              <th>Action / Status</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($jobs as $job):
              $jid = (int) $job['jobID'];
              $status = $applications[$jid] ?? null;
              ?>
              <tr>
                <td><?= htmlspecialchars($job['job_title']) ?></td>
                <td><?= htmlspecialchars($job['department_name']) ?></td>
                <td><?= htmlspecialchars($job['employment_type']) ?></td>
                <td><?= (int) $job['experience_years'] ?> years</td>
                <td><?= htmlspecialchars(substr($job['job_description'], 0, 80)) ?>...</td>
                <td class="text-center">
                  <?php if ($status): ?>
                    <span
                      class="badge <?= $status === 'Rejected' ? 'bg-danger' : ($status === 'Pending' ? 'bg-warning text-dark' : 'bg-success') ?>">
                      <?= htmlspecialchars($status) ?>
                    </span>
                  <?php else: ?>
                    <form method="POST">
                      <input type="hidden" name="job_id" value="<?= $jid ?>">
                      <button type="submit" name="apply_job" class="btn btn-success btn-sm">Apply</button>
                    </form>
                  <?php endif; ?>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>

  </div>

  <!-- Success Modal -->
  <div class="modal fade" id="applySuccessModal" tabindex="-1" data-bs-backdrop="true" data-bs-keyboard="true">
    <div class="modal-dialog">
      <div class="modal-content">
        <div class="modal-header bg-success text-white">
          <h5 class="modal-title">Application Submitted!</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <p>You successfully applied for <strong><?= htmlspecialchars($apply_success_job) ?></strong>.</p>
          <p>We will review your application soon.</p>
        </div>
      </div>
    </div>
  </div>

  <!-- Rejection Modal -->
  <div class="modal fade" id="applyRejectedModal" tabindex="-1" data-bs-backdrop="true" data-bs-keyboard="true">
    <div class="modal-dialog">
      <div class="modal-content">
        <div class="modal-header bg-danger text-white">
          <h5 class="modal-title">Application Rejected</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <p>Unfortunately, you didn’t meet the required experience for
            <strong><?= htmlspecialchars($apply_rejected_job) ?></strong>.
          </p>
          <p>Consider improving your qualifications and try again later.</p>
        </div>
      </div>
    </div>
  </div>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"></script>

  <?php if ($apply_success_job): ?>
    <script>new bootstrap.Modal(document.getElementById('applySuccessModal')).show();</script>
  <?php elseif ($apply_rejected_job): ?>
    <script>new bootstrap.Modal(document.getElementById('applyRejectedModal')).show();</script>
  <?php endif; ?>
</body>

</html>