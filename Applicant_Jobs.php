<?php
session_start();
require 'admin/db.connect.php';

// -------------------------------
// 1. Get Applicant ID from Session
// -------------------------------
$applicantID = $_SESSION['applicant_employee_id'] ?? null;

if (!$applicantID) {
    die("Applicant ID not found in session.");
}

// -------------------------------
// 2. Fetch Applicant Basic Info (Full Name + Picture)
// -------------------------------
$stmt = $conn->prepare("SELECT fullName, profile_pic FROM applicant WHERE applicantID = ?");
$stmt->bind_param("s", $applicantID);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $row = $result->fetch_assoc();
    $applicantname = $row['fullName'];
    $profile_picture = !empty($row['profile_pic'])
        ? "uploads/applicants/" . $row['profile_pic']
        : "uploads/employees/default.png";
} else {
    $applicantname = "Applicant";
    $profile_picture = "uploads/employees/default.png";
}

// Handle AJAX apply request
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ajax_apply_job'])) {
  header('Content-Type: application/json');

  if (empty($applicantID)) {
    echo json_encode(['status' => 'error', 'message' => 'Please login first.']);
    exit();
  }

  $job_id = (int) ($_POST['job_id'] ?? 0);
  if ($job_id <= 0) {
    echo json_encode(['status' => 'error', 'message' => 'Invalid job selected.']);
    exit();
  }

  // Check profile completion
  $stmt = $conn->prepare("SELECT course FROM applicant WHERE applicantID=? LIMIT 1");
  $stmt->bind_param('s', $applicantID);
  $stmt->execute();
  $res = $stmt->get_result()->fetch_assoc();
  $stmt->close();

  if (!$res || empty($res['course'])) {
    echo json_encode(['status' => 'error', 'message' => 'Complete your profile first.']);
    exit();
  }

  $applicant_course = $res['course'];

  // Check active applications
  $active_statuses = ['Pending', 'Initial Interview', 'Assessment', 'Final Interview', 'Requirements', 'Hired'];
  $placeholders = implode(',', array_fill(0, count($active_statuses), '?'));
  $types = str_repeat('s', count($active_statuses) + 1);
  $sql_active = "SELECT id FROM applications WHERE applicantID = ? AND status IN ($placeholders)";
  $stmt_active = $conn->prepare($sql_active);
  $params = array_merge([$applicantID], $active_statuses);
  $refs = [];
  foreach ($params as $key => $value)
    $refs[$key] = &$params[$key];
  call_user_func_array([$stmt_active, 'bind_param'], array_merge([$types], $refs));
  $stmt_active->execute();
  $res_active = $stmt_active->get_result();
  if ($res_active->num_rows > 0) {
    echo json_encode(['status' => 'pending_modal']);
    $stmt_active->close();
    exit();
  }
  $stmt_active->close();

  // Get job info
  $stmt = $conn->prepare("SELECT job_title, educational_level, department  FROM job_posting WHERE jobID=? LIMIT 1");
  $stmt->bind_param('i', $job_id);
  $stmt->execute();
  $job_info = $stmt->get_result()->fetch_assoc();
  $stmt->close();

  $job_title = $job_info['job_title'] ?? 'this job';
  $required_level = $job_info['educational_level'] ?? '';
  $dept_id = $job_info['department'] ?? null;


  // Fetch department name
$department_name = '';
if ($dept_id) {
    $stmt = $conn->prepare("SELECT deptName FROM department WHERE deptID=? LIMIT 1");
    $stmt->bind_param('i', $dept_id);
    $stmt->execute();
    $department_name = $stmt->get_result()->fetch_assoc()['deptName'] ?? '';
    $stmt->close();
}

// Fetch employment type name from job_posting + employment_type
$stmt = $conn->prepare("
    SELECT et.typeName 
    FROM job_posting jp
    LEFT JOIN employment_type et ON jp.employment_type = et.emtypeID
    WHERE jp.jobID = ? 
    LIMIT 1
");
$stmt->bind_param('i', $job_id);
$stmt->execute();
$applicant_type = $stmt->get_result()->fetch_assoc()['typeName'] ?? '';
$stmt->close();




  // ✅ Immediate course match check and insert
  if (strcasecmp(trim($applicant_course), trim($required_level)) === 0) {
     $app_status = 'Pending';
    

    try {
      $conn->begin_transaction();

      $stmt = $conn->prepare("INSERT INTO applications(applicantID, jobID, job_title, department_name, type_name, status) VALUES(?,?,?,?,?,?)");
      $stmt->bind_param('sissss', $applicantID, $job_id, $job_title, $department_name, $applicant_type, $app_status);
      $stmt->execute();
      $stmt->close();

      $stmt = $conn->prepare("UPDATE applicant SET status=? WHERE applicantID=?");
      $stmt->bind_param('ss', $app_status, $applicantID);
      $stmt->execute();
      $stmt->close();

      $conn->commit();

      // ✅ Respond immediately for button update
      echo json_encode(['status' => 'success', 'job' => $job_title]);
    } catch (Exception $e) {
      $conn->rollback();
      echo json_encode(['status' => 'error', 'message' => 'Failed to apply. Please try again.']);
    }
  } else {
    // Course mismatch
    $reason = 'Course mismatch';
    $stmt = $conn->prepare("INSERT INTO rejected_applications(applicantID, jobID, reason, rejected_at) VALUES(?,?,?,NOW())");
    $stmt->bind_param('sis', $applicantID, $job_id, $reason);
    $stmt->execute();
    $stmt->close();

    echo json_encode(['status' => 'rejected', 'job' => $job_title]);
  }
  exit();
}

// Fetch jobs
$search = trim($_GET['q'] ?? '');
$job_sql_base = "
  SELECT jp.jobID, jp.job_title, jp.job_description,
         COALESCE(d.deptName,'') AS department_name,
         jp.educational_level, jp.experience_years,
         COALESCE(et.typeName,'') AS employment_type
  FROM job_posting jp
  LEFT JOIN department d ON jp.department = d.deptID
  LEFT JOIN employment_type et ON jp.employment_type = et.emtypeID
";
$jobs = [];
if ($search !== '') {
  $job_sql = $job_sql_base . " WHERE (jp.job_title LIKE ? OR jp.job_description LIKE ? OR d.deptName LIKE ?) ORDER BY jp.date_posted DESC";
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

// Fetch applications + rejected
$applications = [];
$rejected_jobs = [];
if (!empty($applicantID)) {
  $app_q = $conn->prepare("SELECT jobID, status FROM applications WHERE applicantID = ?");
  $app_q->bind_param('s', $applicantID);
  $app_q->execute();
  $ares = $app_q->get_result();
  while ($ar = $ares->fetch_assoc()) {
    $applications[$ar['jobID']] = $ar['status'];
  }
  $app_q->close();
}

// Apply logic
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['apply_job'])) {
  if (empty($applicantID)) {
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
  $check->bind_param('si', $applicantID, $job_id);
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
  $app_data->bind_param('s', $applicantID);
  $app_data->execute();
  $applicant_exp = (int) ($app_data->get_result()->fetch_assoc()['years_experience'] ?? 0);
  $app_data->close();

 // Fetch job info including title
$job_data = $conn->prepare("SELECT job_title, experience_years FROM job_posting WHERE jobID = ? LIMIT 1");
$job_data->bind_param('i', $job_id);
$job_data->execute();
$job_info = $job_data->get_result()->fetch_assoc();
$job_data->close();

$job_title = $job_info['job_title'] ?? 'this job';
$required_exp = (int) ($job_info['experience_years'] ?? 0);

$app_status = ($applicant_exp >= $required_exp) ? 'Pending' : 'Rejected';

// Fetch job info
$job_data = $conn->prepare("SELECT job_title, department FROM job_posting WHERE jobID = ? LIMIT 1");
$job_data->bind_param('i', $job_id);
$job_data->execute();
$job_info = $job_data->get_result()->fetch_assoc();
$job_data->close();

$job_title = $job_info['job_title'] ?? 'this job';
$dept_id = $job_info['department'] ?? null;
$department_name = '';

if ($dept_id) {
    $stmt = $conn->prepare("SELECT deptName FROM department WHERE deptID = ? LIMIT 1");
    $stmt->bind_param('i', $dept_id);
    $stmt->execute();
    $department_name = $stmt->get_result()->fetch_assoc()['deptName'] ?? '';
    $stmt->close();
}

// Insert into applications
$insert = $conn->prepare("
    INSERT INTO applications (applicantID, jobID, job_title, department_name, type_name, status) 
    VALUES (?, ?, ?, ?, ?,?)
");
$insert->bind_param('sissss', $applicantID, $job_id, $job_title, $department_name, $applicant_type, $app_status);
$success = $insert->execute();
$insert->close();

if (!$success) {
    $_SESSION['flash_error'] = 'Failed to submit application.';
}
}

$all_statuses = $applications + $rejected_jobs;

$stmt = $conn->prepare("SELECT job_title, department FROM job_posting WHERE jobID = ? LIMIT 1");
$stmt->bind_param('i', $job_id);
$stmt->execute();
$job_info = $stmt->get_result()->fetch_assoc();
$stmt->close();

$job_title = $job_info['job_title'] ?? 'this job';

// Fetch department name
$dept_id = $job_info['department'] ?? null;
$department_name = '';
if ($dept_id) {
    $stmt = $conn->prepare("SELECT deptName FROM department WHERE deptID = ? LIMIT 1");
    $stmt->bind_param('i', $dept_id);
    $stmt->execute();
    $department_name = $stmt->get_result()->fetch_assoc()['deptName'] ?? '';
    $stmt->close();
}


// After AJAX or apply insert
$applications = [];
if (!empty($applicantID)) {
  $app_q = $conn->prepare("SELECT jobID, status FROM applications WHERE applicantID = ?");
  $app_q->bind_param('s', $applicantID);
  $app_q->execute();
  $ares = $app_q->get_result();
  while ($ar = $ares->fetch_assoc()) {
    $applications[$ar['jobID']] = $ar['status'];
  }
  $app_q->close();
}

// Fetch rejected jobs
$rejected_jobs = [];
$rej_q = $conn->prepare("SELECT jobID FROM rejected_applications WHERE applicantID = ?");
$rej_q->bind_param('s', $applicantID);
$rej_q->execute();
$rres = $rej_q->get_result();
while ($r = $rres->fetch_assoc()) {
  $rejected_jobs[$r['jobID']] = 'Rejected';
}
$rej_q->close();

// Merge
$all_statuses = $applications + $rejected_jobs;



$search = trim($_GET['q'] ?? '');
$job_sql_base = "
  SELECT jp.jobID, jp.job_title, jp.job_description,
         COALESCE(d.deptName,'') AS department_name,
         jp.educational_level, jp.experience_years,
         COALESCE(et.typeName,'') AS employment_type,
         jp.closing_date
  FROM job_posting jp
  LEFT JOIN department d ON jp.department = d.deptID
  LEFT JOIN employment_type et ON jp.employment_type = et.emtypeID
";

if ($search !== '') {
    $job_sql = $job_sql_base . " WHERE (jp.job_title LIKE ? OR jp.job_description LIKE ? OR d.deptName LIKE ?) ORDER BY jp.date_posted DESC";
    $job_stmt = $conn->prepare($job_sql);
    $like = "%{$search}%";
    $job_stmt->bind_param('sss', $like, $like, $like);
} else {
    $job_sql = $job_sql_base . " ORDER BY jp.date_posted DESC";
    $job_stmt = $conn->prepare($job_sql);
}

$jobs = [];
if ($job_stmt) {
    $job_stmt->execute();
    $jobs = $job_stmt->get_result()->fetch_all(MYSQLI_ASSOC); // ✅ Now closing_date is included
    $job_stmt->close();
}

$today = date('Y-m-d'); // current date

if ($search !== '') {
    $job_sql = $job_sql_base . " 
        WHERE (jp.job_title LIKE ? OR jp.job_description LIKE ? OR d.deptName LIKE ?)
        AND (jp.closing_date IS NULL OR jp.closing_date >= ?)
        ORDER BY jp.date_posted DESC";
    $job_stmt = $conn->prepare($job_sql);
    $like = "%{$search}%";
    $job_stmt->bind_param('ssss', $like, $like, $like, $today);
} else {
    $job_sql = $job_sql_base . " 
        WHERE jp.closing_date IS NULL OR jp.closing_date >= ?
        ORDER BY jp.date_posted DESC";
    $job_stmt = $conn->prepare($job_sql);
    $job_stmt->bind_param('s', $today);
}



?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <title>Job Listing</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <link rel="stylesheet" href="applicant.css">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

  <style>
    body {
      font-family: 'Poppins', 'Roboto', sans-serif;
      margin: 0;
      display: flex;
      background-color: #f1f5fc;
      color: #111827;
    }

    .main-content {
      flex: 1;
      padding: 30px 80px;
      display: flex;
      flex-direction: column;
      gap: 40px;
      margin-left: 230px;
    }



    .main-content h1 {
      color: #1E3A8A;
      font-weight: 700;
      font-size: 2.2rem;
      margin-bottom: 20px;
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
  <div class="sidebar">
    <a href="Applicant_Profile.php" class="profile">
    <img src="<?php echo !empty($profile_picture) ? htmlspecialchars($profile_picture) : 'uploads/employees/default.png'; ?>" 
     alt="Profile" class="sidebar-profile-img">
    </a>
    <div class="sidebar-name">
      <p><?= "Welcome, $applicantname" ?></p>
    </div>
    <ul class="nav">
      <li><a href="Applicant_Dashboard.php"><i class="fa-solid fa-table-columns"></i>Dashboard</a></li>
      <li><a href="Applicant_Application.php"><i class="fa-solid fa-file-lines"></i>Applications</a></li>
      <li class="active"><a href="Applicant_Jobs.php"><i class="fa-solid fa-briefcase"></i>Jobs</a></li>
      <li><a href="Login.php"><i class="fa-solid fa-right-from-bracket"></i>Log Out</a></li>
    </ul>
  </div>

  <div class="main-content">
    <h1>Job Listing</h1>
    <hr>
    <div class="table-responsive mt-5">
      <table class="table table-bordered table-hover align-middle text-center">
        <thead class="table-primary">
          <tr>
            <th>Job Title</th>
            <th>Department</th>
            <th>Type</th>
            <th>Experience</th>
            <th>Description</th>
            <th>Closing Date</th>
            <th>Action / Status</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($jobs as $job):
            $jid = (int) $job['jobID'];
            $status = $all_statuses[$jid] ?? null; ?>
            <tr>
              <td><?= htmlspecialchars($job['job_title']) ?></td>
              <td><?= htmlspecialchars($job['department_name']) ?></td>
              <td><?= htmlspecialchars($job['employment_type']) ?></td>
              <td><?= (int) $job['experience_years'] ?> years</td>
              <td><?= htmlspecialchars(substr($job['job_description'], 0, 80)) ?>...</td>
              <td><?= !empty($job['closing_date']) ? date('M d, Y', strtotime($job['closing_date'])) : 'N/A' ?></td>
              <td>
                <?php if ($status): ?>
                  <?php if ($status === 'Rejected'): ?>
                    <button class="btn btn-danger btn-sm" disabled>Rejected</button>
                  <?php elseif ($status === 'Pending'): ?>
                    <button class="btn btn-warning btn-sm" disabled>Pending</button>
                  <?php else: ?>
                    <button class="btn btn-info btn-sm" disabled><?= htmlspecialchars($status) ?></button>
                  <?php endif; ?>
                <?php else: ?>
                  <button class="btn btn-success btn-sm apply-btn" data-jobid="<?= $jid ?>">Apply</button>
                <?php endif; ?>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>

  <!-- 🟡 Pending Modal -->
  <div class="modal fade" id="pendingModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
      <div class="modal-content border-warning">
        <div class="modal-header bg-warning text-dark">
          <h5 class="modal-title">Pending Application</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body text-center">
          <i class="fa-solid fa-triangle-exclamation fa-2x text-warning mb-3"></i>
          <p>You still have a pending application. Applicants can only have one application at a time.</p>
        </div>
      </div>
    </div>
  </div>

  <!-- 🟢 Success Modal -->
  <div class="modal fade" id="successModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
      <div class="modal-content border-success">
        <div class="modal-header bg-success text-white">
          <h5 class="modal-title">Application Submitted</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body text-center">
          <i class="fa-solid fa-check-circle fa-2x text-success mb-3"></i>
          <p id="successMessage"></p>
        </div>
      </div>
    </div>
  </div>

  <!-- 🔴 Rejected Modal -->
  <div class="modal fade" id="rejectedModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
      <div class="modal-content border-danger">
        <div class="modal-header bg-danger text-white">
          <h5 class="modal-title">Application Rejected</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body text-center">
          <i class="fa-solid fa-xmark fa-2x text-danger mb-3"></i>
          <p id="rejectedMessage"></p>
        </div>
      </div>
    </div>
  </div>

  <!-- ⚠️ Error Modal -->
  <div class="modal fade" id="errorModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
      <div class="modal-content border-secondary">
        <div class="modal-header bg-secondary text-white">
          <h5 class="modal-title">Notice</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body text-center">
          <p id="errorMessage"></p>
        </div>
      </div>
    </div>
  </div>

  <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"></script>
  <script>
    $(function () {
      $('.apply-btn').click(function () {
        var btn = $(this);
        var jobID = btn.data('jobid');
        $.post('', { ajax_apply_job: 1, job_id: jobID }, function (res) {
          if (res.status === 'success') {
            $('#successMessage').text("You have successfully applied for " + res.job + ".");
            new bootstrap.Modal($('#successModal')).show();
            btn.removeClass('btn-success').addClass('btn-warning').text('Pending').prop('disabled', true);
          }
          else if (res.status === 'rejected') {
            $('#rejectedMessage').text("Your application for " + res.job + " was rejected due to a course mismatch.");
            new bootstrap.Modal($('#rejectedModal')).show();
            btn.removeClass('btn-success').addClass('btn-danger').text('Rejected').prop('disabled', true);
          }
          else if (res.status === 'pending_modal') {
            new bootstrap.Modal($('#pendingModal')).show();
          }
          else {
            $('#errorMessage').text(res.message);
            new bootstrap.Modal($('#errorModal')).show();
          }
        }, 'json');
      });
    });
  </script>
</body>

</html>