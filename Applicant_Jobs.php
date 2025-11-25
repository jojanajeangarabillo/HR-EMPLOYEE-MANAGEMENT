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
    $reason = 'Qualification mismatch';
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
  <title>Job Opportunities</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <link rel="stylesheet" href="admin-sidebar.css">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/7.0.1/css/all.min.css">

  <style>
    :root {
      --primary: #1E3A8A;
      --primary-light: #3B82F6;
      --primary-dark: #1E40AF;
      --secondary: #2563EB;
      --accent: #10B981;
      --warning: #F59E0B;
      --danger: #EF4444;
      --success: #10B981;
      --light: #F8FAFC;
      --dark: #111827;
      --gray: #6B7280;
      --gray-light: #E5E7EB;
      --border-radius: 16px;
      --border-radius-sm: 8px;
      --box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
      --box-shadow-lg: 0 12px 40px rgba(0, 0, 0, 0.15);
      --transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    }

    * {
      margin: 0;
      padding: 0;
      box-sizing: border-box;
    }

    body {
      font-family: 'Poppins', sans-serif;
      display: flex;
      background: linear-gradient(135deg, #f8fafc 0%, #e2e8f0 100%);
      color: var(--dark);
      min-height: 100vh;
      line-height: 1.6;
    }

    .main-content {
      flex: 1;
      padding: 20px 40px;
      display: flex;
      flex-direction: column;
      gap: 24px;
      margin-left: 260px;
      transition: var(--transition);
      width: calc(100% - 260px);
    }

    /* Header Section */
    .header {
      display: flex;
      justify-content: space-between;
      align-items: center;
      margin-bottom: 8px;
     
    }

    .header-content h1 {
      font-weight: 700;
      font-size: 32px;
      background: linear-gradient(135deg, #1E3A8A 0%, #3B82F6 100%);
      -webkit-background-clip: text;
      -webkit-text-fill-color: transparent;
      background-clip: text;
      margin-bottom: 4px;
    }

    .header-content p {
      color: var(--gray);
      font-size: 14px;
      font-weight: 400;
    }

    .header-actions {
      display: flex;
      gap: 16px;
      align-items: center;
    }

    .search-box {
      display: flex;
      align-items: center;
      background: rgba(255, 255, 255, 0.9);
      backdrop-filter: blur(10px);
      border-radius: var(--border-radius);
      padding: 12px 20px;
      width: 320px;
      box-shadow: var(--box-shadow);
      border: 1px solid rgba(255, 255, 255, 0.8);
      transition: var(--transition);
    }

    .search-box:focus-within {
      box-shadow: var(--box-shadow-lg);
      transform: translateY(-2px);
      border-color: var(--primary-light);
    }

    .search-box input {
      border: none;
      outline: none;
      background: none;
      width: 100%;
      padding-left: 12px;
      font-size: 14px;
      color: var(--dark);
      font-weight: 500;
    }

    .search-box input::placeholder {
      color: var(--gray);
      font-weight: 400;
    }

    .search-box i {
      color: var(--primary);
      font-size: 16px;
    }

    /* Job Cards Grid */
    .jobs-grid {
      display: grid;
      grid-template-columns: repeat(auto-fill, minmax(380px, 1fr));
      gap: 24px;
      
    }

    .job-card {
      background: rgba(255, 255, 255, 0.9);
      backdrop-filter: blur(10px);
      border-radius: var(--border-radius);
      padding: 28px;
      box-shadow: var(--box-shadow);
      transition: var(--transition);
      border: 1px solid rgba(255, 255, 255, 0.8);
      position: relative;
      overflow: hidden;
      display: flex;
      flex-direction: column;
      height: 100%;
    }

    .job-card::before {
      content: '';
      position: absolute;
      top: 0;
      left: 0;
      right: 0;
      height: 4px;
      background: linear-gradient(135deg, var(--primary) 0%, var(--primary-light) 100%);
    }

    .job-card:hover {
      transform: translateY(-5px);
      box-shadow: var(--box-shadow-lg);
    }

    .job-header {
      display: flex;
      justify-content: between;
      align-items: flex-start;
      margin-bottom: 16px;
    }

    .job-title {
      font-size: 20px;
      font-weight: 600;
      color: var(--dark);
      margin-bottom: 8px;
      line-height: 1.3;
      flex: 1;
    }

    .job-badge {
      padding: 6px 12px;
      border-radius: 20px;
      font-size: 12px;
      font-weight: 600;
      text-transform: uppercase;
      letter-spacing: 0.5px;
      box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
      white-space: nowrap;
    }

    .badge-pending { background: linear-gradient(135deg, #F59E0B 0%, #FBBF24 100%); color: white; }
    .badge-rejected { background: linear-gradient(135deg, #EF4444 0%, #F87171 100%); color: white; }
    .badge-success { background: linear-gradient(135deg, #10B981 0%, #34D399 100%); color: white; }
    .badge-info { background: linear-gradient(135deg, #3B82F6 0%, #60A5FA 100%); color: white; }

    .job-meta {
      display: flex;
      flex-direction: column;
      gap: 12px;
      margin-bottom: 20px;
    }

    .meta-item {
      display: flex;
      align-items: center;
      gap: 10px;
      font-size: 14px;
      color: var(--gray);
    }

    .meta-item i {
      color: var(--primary);
      font-size: 16px;
      width: 16px;
      text-align: center;
    }

    .job-description {
      flex: 1;
      margin-bottom: 20px;
    }

    .job-description p {
      color: var(--dark);
      font-size: 14px;
      line-height: 1.5;
      display: -webkit-box;
      -webkit-line-clamp: 3;
      -webkit-box-orient: vertical;
      overflow: hidden;
    }

    .job-footer {
      display: flex;
      justify-content: space-between;
      align-items: center;
      margin-top: auto;
      padding-top: 20px;
      border-top: 1px solid rgba(0, 0, 0, 0.06);
    }

    .job-actions {
      display: flex;
      gap: 10px;
      align-items: center;
    }

    .closing-date {
      font-size: 13px;
      color: var(--gray);
      display: flex;
      align-items: center;
      gap: 6px;
    }

    .closing-date i {
      color: var(--primary);
    }

    .action-btn {
      padding: 10px 20px;
      border-radius: var(--border-radius-sm);
      font-size: 13px;
      font-weight: 600;
      cursor: pointer;
      transition: var(--transition);
      border: none;
      display: flex;
      align-items: center;
      gap: 6px;
      text-transform: uppercase;
      letter-spacing: 0.5px;
      box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
    }

    .btn-view {
      background: rgba(59, 130, 246, 0.1);
      color: var(--primary);
      border: 1px solid rgba(59, 130, 246, 0.2);
    }

    .btn-view:hover {
      background: var(--primary);
      color: white;
      transform: translateY(-2px);
      box-shadow: 0 8px 20px rgba(59, 130, 246, 0.4);
    }

    .btn-apply {
      background: linear-gradient(135deg, var(--primary) 0%, var(--primary-light) 100%);
      color: white;
    }

    .btn-apply:hover {
      transform: translateY(-2px);
      box-shadow: 0 8px 20px rgba(59, 130, 246, 0.4);
    }

    .btn-disabled {
      background: var(--gray-light);
      color: var(--gray);
      cursor: not-allowed;
      opacity: 0.7;
    }

    /* Empty State */
    .empty-state {
      text-align: center;
      padding: 80px 40px;
      color: var(--gray);
      background: rgba(255, 255, 255, 0.9);
      backdrop-filter: blur(10px);
      border-radius: var(--border-radius);
      box-shadow: var(--box-shadow);
      border: 1px solid rgba(255, 255, 255, 0.8);
      
      grid-column: 1 / -1;
    }

    .empty-icon {
      font-size: 80px;
      margin-bottom: 24px;
      color: var(--gray-light);
      opacity: 0.7;
    }

    .empty-state h3 {
      font-size: 24px;
      margin-bottom: 12px;
      color: var(--dark);
      font-weight: 600;
    }

    .empty-state p {
      margin-bottom: 32px;
      font-size: 16px;
      max-width: 400px;
      margin-left: auto;
      margin-right: auto;
      line-height: 1.6;
    }

    /* Job Details Modal */
    .job-details-modal .modal-content {
      border-radius: var(--border-radius);
      border: none;
      box-shadow: var(--box-shadow-lg);
    }

    .job-details-modal .modal-header {
      background: linear-gradient(135deg, var(--primary) 0%, var(--primary-light) 100%);
      color: white;
      border-radius: var(--border-radius) var(--border-radius) 0 0;
      border: none;
      padding: 24px;
    }

    .job-details-modal .modal-body {
      padding: 32px;
      background: var(--light);
    }

    .job-details-section {
      background: white;
      border-radius: var(--border-radius-sm);
      padding: 24px;
      margin-bottom: 20px;
      box-shadow: var(--box-shadow);
    }

    .job-details-section h5 {
      color: var(--primary);
      font-weight: 600;
      margin-bottom: 16px;
      display: flex;
      align-items: center;
      gap: 8px;
    }

    .job-details-section h5 i {
      font-size: 18px;
    }

    .details-grid {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
      gap: 16px;
    }

    .detail-item {
      display: flex;
      flex-direction: column;
      gap: 4px;
    }

    .detail-label {
      font-size: 12px;
      color: var(--gray);
      font-weight: 500;
      text-transform: uppercase;
      letter-spacing: 0.5px;
    }

    .detail-value {
      font-size: 14px;
      color: var(--dark);
      font-weight: 500;
    }

    .job-description-full {
      line-height: 1.7;
      color: var(--dark);
    }

    .requirements-list {
      list-style: none;
      padding: 0;
    }

    .requirements-list li {
      padding: 8px 0;
      border-bottom: 1px solid var(--gray-light);
      display: flex;
      align-items: center;
      gap: 10px;
    }

    .requirements-list li:last-child {
      border-bottom: none;
    }

    .requirements-list i {
      color: var(--success);
      font-size: 14px;
    }

    /* Animations */
    @keyframes fadeInDown {
      from {
        opacity: 0;
        transform: translateY(-20px);
      }
      to {
        opacity: 1;
        transform: translateY(0);
      }
    }

    @keyframes fadeInUp {
      from {
        opacity: 0;
        transform: translateY(20px);
      }
      to {
        opacity: 1;
        transform: translateY(0);
      }
    }

    @keyframes fadeIn {
      from {
        opacity: 0;
      }
      to {
        opacity: 1;
      }
    }

    /* Responsive Design */
    @media (max-width: 1200px) {
      .main-content {
        margin-left: 0;
        padding: 20px;
        width: 100%;
      }
    }

    @media (max-width: 768px) {
      .header {
        flex-direction: column;
        gap: 16px;
        align-items: flex-start;
      }

      .header-actions {
        width: 100%;
        justify-content: space-between;
      }

      .search-box {
        width: 100%;
      }

      .jobs-grid {
        grid-template-columns: 1fr;
        gap: 16px;
      }

      .job-actions {
        flex-direction: column;
        width: 100%;
      }

      .job-actions .action-btn {
        width: 100%;
        justify-content: center;
      }
    }

    @media (max-width: 576px) {
      .main-content {
        padding: 16px;
      }
      
      .header-content h1 {
        font-size: 28px;
      }

      .job-card {
        padding: 20px;
      }

      .job-details-modal .modal-body {
        padding: 20px;
      }

      .details-grid {
        grid-template-columns: 1fr;
      }
    }

    /* Loading Animation */
    .loading {
      opacity: 0.7;
      pointer-events: none;
    }

    .pulse {
      animation: pulse 1.5s ease-in-out infinite;
    }

    @keyframes pulse {
      0% { opacity: 1; }
      50% { opacity: 0.5; }
      100% { opacity: 1; }
    }

    /* Sidebar Styles */
    .sidebar-profile-img {
      width: 130px;
      height: 130px;
      border-radius: 50%;
      object-fit: cover;
      border: 4px solid rgba(255, 255, 255, 0.2);
      margin-bottom: 20px;
      transition: transform 0.3s ease;
    }

    .sidebar-profile-img:hover {
      transform: scale(1.05);
      border-color: rgba(255, 255, 255, 0.4);
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
  <?php $current = basename($_SERVER['PHP_SELF']); ?>
  <div class="sidebar">
    <a href="Applicant_Profile.php" class="profile">
      <img
        src="<?php echo !empty($profile_picture) ? htmlspecialchars($profile_picture) : 'uploads/employees/default.png'; ?>"
        alt="Profile" class="sidebar-profile-img">
    </a>
    <div class="sidebar-name">
      <p><?= "Welcome, $applicantname" ?></p>
    </div>
    <ul class="nav">
      <li<?php echo $current==='Applicant_Dashboard.php' ? ' class="active"' : ''; ?>><a href="Applicant_Dashboard.php"><i class="fa-solid fa-table-columns"></i>Dashboard</a></li>
      <li<?php echo $current==='Applicant_Application.php' ? ' class="active"' : ''; ?>><a href="Applicant_Application.php"><i class="fa-solid fa-file-lines"></i>Applications</a></li>
      <li<?php echo $current==='Applicant_Jobs.php' ? ' class="active"' : ''; ?>><a href="Applicant_Jobs.php"><i class="fa-solid fa-briefcase"></i>Jobs</a></li>
      <li><a href="Login.php"><i class="fa-solid fa-right-from-bracket"></i>Log Out</a></li>
    </ul>
  </div>

  <div class="main-content">
    <!-- Header -->
    <div class="header">
      <div class="header-content">
        <h1>Job Opportunities</h1>
        <p>Discover your next career move</p>
      </div>
      <div class="header-actions">
        <div class="search-box">
          <i class="fa-solid fa-magnifying-glass"></i>
          <input type="text" placeholder="Search jobs" 
                 value="<?= htmlspecialchars($search) ?>"
                 onkeypress="if(event.keyCode==13) searchJobs()">
        </div>
        <button class="action-btn btn-apply" onclick="searchJobs()">
          <i class="fa-solid fa-search"></i> Search
        </button>
      </div>
    </div>

    <!-- Jobs Grid -->
    <div class="jobs-grid">
      <?php if (empty($jobs)): ?>
        <div class="empty-state">
          <div class="empty-icon">
            <i class="fa-solid fa-briefcase"></i>
          </div>
          <h3>No Jobs Available</h3>
          <p>There are currently no job openings matching your search criteria. Please check back later or try different search terms.</p>
        </div>
      <?php else: ?>
        <?php foreach ($jobs as $job):
          $jid = (int) $job['jobID'];
          $status = $all_statuses[$jid] ?? null; ?>
          <div class="job-card">
            <div class="job-header">
              <h3 class="job-title"><?= htmlspecialchars($job['job_title']) ?></h3>
              <?php if ($status): ?>
                <?php if ($status === 'Rejected'): ?>
                  <span class="job-badge badge-rejected">
                    <i class="fa-solid fa-xmark"></i> Rejected
                  </span>
                <?php elseif ($status === 'Pending'): ?>
                  <span class="job-badge badge-pending">
                    <i class="fa-solid fa-clock"></i> Pending
                  </span>
                <?php else: ?>
                  <span class="job-badge badge-info">
                    <i class="fa-solid fa-spinner"></i> <?= htmlspecialchars($status) ?>
                  </span>
                <?php endif; ?>
              <?php endif; ?>
            </div>

            <div class="job-meta">
              <div class="meta-item">
                <i class="fa-solid fa-building"></i>
                <span><?= htmlspecialchars($job['department_name']) ?></span>
              </div>
              <div class="meta-item">
                <i class="fa-solid fa-briefcase"></i>
                <span><?= htmlspecialchars($job['employment_type']) ?></span>
              </div>
              <div class="meta-item">
                <i class="fa-solid fa-chart-line"></i>
                <span><?= (int) $job['experience_years'] ?> years experience</span>
              </div>
              <div class="meta-item">
                <i class="fa-solid fa-graduation-cap"></i>
                <span><?= htmlspecialchars($job['educational_level']) ?></span>
              </div>
            </div>

            <div class="job-description">
              <p><?= htmlspecialchars($job['job_description']) ?></p>
            </div>

            <div class="job-footer">
              <div class="closing-date">
                <i class="fa-solid fa-calendar"></i>
                <?php if (!empty($job['closing_date'])): ?>
                  Closes <?= date('M d, Y', strtotime($job['closing_date'])) ?>
                <?php else: ?>
                  No closing date
                <?php endif; ?>
              </div>
              
              <div class="job-actions">
                <button class="action-btn btn-view view-details-btn" data-jobid="<?= $jid ?>">
                  <i class="fa-solid fa-eye"></i> View Details
                </button>
                
                <?php if ($status): ?>
                  <button class="action-btn btn-disabled" disabled>
                    <?php if ($status === 'Rejected'): ?>
                      <i class="fa-solid fa-xmark"></i> Not Eligible
                    <?php else: ?>
                      <i class="fa-solid fa-check"></i> Applied
                    <?php endif; ?>
                  </button>
                <?php else: ?>
                  <button class="action-btn btn-apply apply-btn" data-jobid="<?= $jid ?>">
                    <i class="fa-solid fa-paper-plane"></i> Apply Now
                  </button>
                <?php endif; ?>
              </div>
            </div>
          </div>
        <?php endforeach; ?>
      <?php endif; ?>
    </div>
  </div>

  <!-- Job Details Modal -->
  <div class="modal fade job-details-modal" id="jobDetailsModal" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-centered">
      <div class="modal-content">
        <div class="modal-header">
          <h4 class="modal-title" id="jobDetailsTitle"></h4>
          <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <div class="job-details-section">
            <h5><i class="fa-solid fa-info-circle"></i> Job Overview</h5>
            <div class="details-grid">
              <div class="detail-item">
                <span class="detail-label">Department</span>
                <span class="detail-value" id="detailDepartment"></span>
              </div>
              <div class="detail-item">
                <span class="detail-label">Employment Type</span>
                <span class="detail-value" id="detailEmploymentType"></span>
              </div>
              <div class="detail-item">
                <span class="detail-label">Experience Required</span>
                <span class="detail-value" id="detailExperience"></span>
              </div>
              <div class="detail-item">
                <span class="detail-label">Education Level</span>
                <span class="detail-value" id="detailEducation"></span>
              </div>
            </div>
          </div>

          <div class="job-details-section">
            <h5><i class="fa-solid fa-file-lines"></i> Job Description</h5>
            <div class="job-description-full" id="detailDescription"></div>
          </div>

          <div class="job-details-section">
            <h5><i class="fa-solid fa-calendar"></i> Application Details</h5>
            <div class="details-grid">
              <div class="detail-item">
                <span class="detail-label">Closing Date</span>
                <span class="detail-value" id="detailClosingDate"></span>
              </div>
              <div class="detail-item">
                <span class="detail-label">Status</span>
                <span class="detail-value" id="detailStatus"></span>
              </div>
            </div>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
          <button type="button" class="btn btn-primary" id="modalApplyBtn" style="display: none;">
            <i class="fa-solid fa-paper-plane"></i> Apply Now
          </button>
        </div>
      </div>
    </div>
  </div>

  <!-- Other Modals (existing ones) -->
  <!-- 🟡 Pending Modal -->
  <div class="modal fade" id="pendingModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
      <div class="modal-content border-warning">
        <div class="modal-header bg-warning text-dark">
          <h5 class="modal-title"><i class="fa-solid fa-triangle-exclamation"></i> Pending Application</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body text-center">
          <i class="fa-solid fa-clock fa-3x text-warning mb-3"></i>
          <p class="mb-3">You still have a pending application. Applicants can only have one active application at a time.</p>
          <p class="text-muted small">Please wait for your current application to be processed before applying for new positions.</p>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-warning" data-bs-dismiss="modal">Understand</button>
        </div>
      </div>
    </div>
  </div>

  <!-- 🟢 Success Modal -->
  <div class="modal fade" id="successModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
      <div class="modal-content border-success">
        <div class="modal-header bg-success text-white">
          <h5 class="modal-title"><i class="fa-solid fa-check-circle"></i> Application Submitted</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body text-center">
          <i class="fa-solid fa-check-circle fa-3x text-success mb-3"></i>
          <p id="successMessage" class="mb-3"></p>
          <p class="text-muted small">We'll review your application and contact you soon.</p>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-success" data-bs-dismiss="modal">Continue Browsing</button>
        </div>
      </div>
    </div>
  </div>

  <!-- 🔴 Rejected Modal -->
  <div class="modal fade" id="rejectedModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
      <div class="modal-content border-danger">
        <div class="modal-header bg-danger text-white">
          <h5 class="modal-title"><i class="fa-solid fa-xmark-circle"></i> Application Rejected</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body text-center">
          <i class="fa-solid fa-xmark-circle fa-3x text-danger mb-3"></i>
          <p id="rejectedMessage" class="mb-3"></p>
          <p class="text-muted small">Don't worry! There are plenty of other opportunities that match your qualifications.</p>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-danger" data-bs-dismiss="modal">Find Other Jobs</button>
        </div>
      </div>
    </div>
  </div>

  <!-- ⚠️ Error Modal -->
  <div class="modal fade" id="errorModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
      <div class="modal-content border-secondary">
        <div class="modal-header bg-secondary text-white">
          <h5 class="modal-title"><i class="fa-solid fa-exclamation-triangle"></i> Notice</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body text-center">
          <i class="fa-solid fa-exclamation-triangle fa-3x text-warning mb-3"></i>
          <p id="errorMessage" class="mb-3"></p>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
        </div>
      </div>
    </div>
  </div>

  <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
  <script>
    // Job data storage
    const jobData = <?= json_encode($jobs) ?>;

    function searchJobs() {
      const searchInput = document.querySelector('.search-box input');
      const searchTerm = searchInput.value.trim();
      const url = new URL(window.location.href);
      
      if (searchTerm) {
        url.searchParams.set('q', searchTerm);
      } else {
        url.searchParams.delete('q');
      }
      
      window.location.href = url.toString();
    }

    function showJobDetails(jobId) {
      const job = jobData.find(j => j.jobID == jobId);
      if (!job) return;

      // Populate modal with job data
      document.getElementById('jobDetailsTitle').textContent = job.job_title;
      document.getElementById('detailDepartment').textContent = job.department_name || 'Not specified';
      document.getElementById('detailEmploymentType').textContent = job.employment_type || 'Not specified';
      document.getElementById('detailExperience').textContent = job.experience_years + ' years';
      document.getElementById('detailEducation').textContent = job.educational_level || 'Not specified';
      document.getElementById('detailDescription').textContent = job.job_description || 'No description available';
      document.getElementById('detailClosingDate').textContent = job.closing_date ? 
        new Date(job.closing_date).toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' }) : 'No closing date';

      // Check if already applied
      const hasApplied = <?= json_encode($all_statuses) ?>[jobId];
      document.getElementById('detailStatus').textContent = hasApplied ? 'Already Applied' : 'Open for Applications';
      
      // Show/hide apply button in modal
      const modalApplyBtn = document.getElementById('modalApplyBtn');
      if (hasApplied) {
        modalApplyBtn.style.display = 'none';
      } else {
        modalApplyBtn.style.display = 'block';
        modalApplyBtn.onclick = function() {
          $('#jobDetailsModal').modal('hide');
          // Trigger apply action
          $(`.apply-btn[data-jobid="${jobId}"]`).click();
        };
      }

      // Show modal
      new bootstrap.Modal(document.getElementById('jobDetailsModal')).show();
    }

    $(function () {
      // View details button handler
      $('.view-details-btn').click(function () {
        const jobId = $(this).data('jobid');
        showJobDetails(jobId);
      });

      // Apply button handler
      $('.apply-btn').click(function () {
        var btn = $(this);
        var jobID = btn.data('jobid');
        var card = btn.closest('.job-card');
        
        // Add loading state
        btn.addClass('loading pulse');
        card.addClass('loading');
        
        $.post('', { ajax_apply_job: 1, job_id: jobID }, function (res) {
          // Remove loading state
          btn.removeClass('loading pulse');
          card.removeClass('loading');
          
          if (res.status === 'success') {
            $('#successMessage').text("You have successfully applied for " + res.job + ".");
            new bootstrap.Modal($('#successModal')).show();
            
            // Update UI
            btn.removeClass('btn-apply').addClass('btn-disabled').prop('disabled', true);
            btn.html('<i class="fa-solid fa-check"></i> Applied');
            card.find('.job-header').append('<span class="job-badge badge-pending"><i class="fa-solid fa-clock"></i> Pending</span>');
          }
          else if (res.status === 'rejected') {
            $('#rejectedMessage').text("Your application for " + res.job + " was rejected due to qualification mismatch.");
            new bootstrap.Modal($('#rejectedModal')).show();
            
            // Update UI
            btn.removeClass('btn-apply').addClass('btn-disabled').prop('disabled', true);
            btn.html('<i class="fa-solid fa-xmark"></i> Not Eligible');
            card.find('.job-header').append('<span class="job-badge badge-rejected"><i class="fa-solid fa-xmark"></i> Rejected</span>');
          }
          else if (res.status === 'pending_modal') {
            new bootstrap.Modal($('#pendingModal')).show();
          }
          else {
            $('#errorMessage').text(res.message);
            new bootstrap.Modal($('#errorModal')).show();
          }
        }, 'json').fail(function() {
          // Remove loading state on error
          btn.removeClass('loading pulse');
          card.removeClass('loading');
          
          $('#errorMessage').text('Network error. Please try again.');
          new bootstrap.Modal($('#errorModal')).show();
        });
      });

      // Add hover effects
      $('.job-card').hover(
        function() {
          $(this).css('transform', 'translateY(-5px)');
        },
        function() {
          $(this).css('transform', 'translateY(0)');
        }
      );
    });
  </script>
</body>
</html>