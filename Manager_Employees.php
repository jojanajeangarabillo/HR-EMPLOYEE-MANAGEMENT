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

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

$config = require 'mailer-config.php';


// MENUS
$menus = [
  "HR Director" => [
    "Dashboard" => "Manager_Dashboard.php",
    "Applicants" => "Manager_Applicants.php",
    "Pending Applicants" => "Manager_PendingApplicants.php",
    "Newly Hired" => "Newly-Hired.php",
    "Employees" => "Manager_Employees.php",
    "Requests" => "Manager_Request.php",
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
    "Requests" => "Manager_Request.php",
    "Reports" => "Manager_Reports.php",
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
  "Vacancies" => "fa-briefcase",
  "Job Post" => "fa-bullhorn",
  "Calendar" => "fa-calendar-days",
  "Approvals" => "fa-square-check",
  "Reports" => "fa-chart-column",
  "Settings" => "fa-gear",
  "Logout" => "fa-right-from-bracket"
];

$limit = 10;
$page = isset($_GET['page']) ? max(1, (int) $_GET['page']) : 1;
$start = ($page - 1) * $limit;

$countRes = $conn->query("SELECT COUNT(*) AS count FROM employee");
$countRow = $countRes ? $countRes->fetch_assoc() : ['count' => 0];
$totalEmployees = (int) ($countRow['count'] ?? 0);
$pages = max(1, (int) ceil($totalEmployees / $limit));

$employee = [];
$stmt = $conn->prepare("SELECT e.empID, e.fullname, e.department, e.position, e.type_name, e.email_address, u.status FROM employee e LEFT JOIN user u ON u.applicant_employee_id = e.empID ORDER BY e.fullname ASC LIMIT ?, ?");
$stmt->bind_param("ii", $start, $limit);
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
  $employee[] = $row;
}
$stmt->close();

if (isset($_POST['ajax_set_inactive'])) {
  header('Content-Type: application/json');
  $empID = $_POST['empID'] ?? null;
  if (!$empID) {
    echo json_encode(['status' => 'error', 'message' => 'Missing employee ID']);
    exit;
  }
  $upd = $conn->prepare("UPDATE user SET status = 'Inactive' WHERE applicant_employee_id = ?");
  $upd->bind_param("s", $empID);
  $ok = $upd->execute();
  echo json_encode(['status' => $ok ? 'success' : 'error']);
  exit;
}

if (isset($_POST['ajax_archive_employee'])) {
  header('Content-Type: application/json');
  $empID = $_POST['empID'] ?? null;
  if (!$empID) {
    echo json_encode(['status' => 'error', 'message' => 'Missing employee ID']);
    exit;
  }
  $usr = $conn->prepare("SELECT user_id, applicant_employee_id, email, password, role, fullname, status, created_at, profile_pic, reset_token, token_expiry, sub_role FROM user WHERE applicant_employee_id = ? LIMIT 1");
  $usr->bind_param("s", $empID);
  $usr->execute();
  $u = $usr->get_result()->fetch_assoc();
  if (!$u) {
    echo json_encode(['status' => 'error', 'message' => 'User not found']);
    exit;
  }
  $ins = $conn->prepare("INSERT INTO user_archive (user_id, applicant_employee_id, email, password, role, fullname, status, created_at, profile_pic, reset_token, token_expiry, sub_role) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
  $ins->bind_param(
    "ssssssssssss",
    $u['user_id'],
    $u['applicant_employee_id'],
    $u['email'],
    $u['password'],
    $u['role'],
    $u['fullname'],
    $u['status'],
    $u['created_at'],
    $u['profile_pic'],
    $u['reset_token'],
    $u['token_expiry'],
    $u['sub_role']
  );
  $okIns = $ins->execute();
  if (!$okIns) {
    echo json_encode(['status' => 'error', 'message' => 'Archive insert failed']);
    exit;
  }
  $delU = $conn->prepare("DELETE FROM user WHERE applicant_employee_id = ?");
  $delU->bind_param("s", $empID);
  $okDelU = $delU->execute();
  $delE = $conn->prepare("DELETE FROM employee WHERE empID = ?");
  $delE->bind_param("s", $empID);
  $okDelE = $delE->execute();
  $ok = $okDelU && $okDelE;
  echo json_encode(['status' => $ok ? 'success' : 'error']);
  exit;
}

// Handle sending message via PHPMailer
if (isset($_POST['send_message'])) {
  require 'PHPMailer-master/src/Exception.php';
  require 'PHPMailer-master/src/PHPMailer.php';
  require 'PHPMailer-master/src/SMTP.php';


  $mail = new PHPMailer(true);

  $email = $_POST['email'];
  $subject = $_POST['subject'];
  $message = $_POST['message'];

  try {
    $mail->isSMTP();
    $mail->Host = $config['host'];
    $mail->SMTPAuth = true;
    $mail->Username = $config['username'];
    $mail->Password = $config['password'];
    $mail->SMTPSecure = $config['encryption'];
    $mail->Port = $config['port'];

    $mail->setFrom($config['from_email'], $config['from_name']);
    $mail->addAddress($email);

    $mail->isHTML(true);
    $mail->Subject = $subject;
    $mail->Body = nl2br($message);

    $mail->send();
    $_SESSION['flash_success'] = "Message sent successfully!";
  } catch (Exception $e) {
    $_SESSION['flash_error'] = "Message could not be sent. Error: " . $mail->ErrorInfo;
  }

  header("Location: Manager_Employees.php");
  exit;
}
?>




<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Manager Employees</title>

  <link rel="stylesheet" href="manager-sidebar.css">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">


  <!-- Font Awesome Icons -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/7.0.1/css/all.min.css"
    integrity="sha512-2SwdPD6INVrV/lHTZbO2nodKhrnDdJK9/kg2XD1r9uGqPo1cUbujc+IYdlYdEErWNu69gVcYgdxlmVmzTWnetw=="
    crossorigin="anonymous" referrerpolicy="no-referrer" />
  <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>


  <style>
    body {
      font-family: 'Poppins', 'Roboto', sans-serif;
      margin: 0;
      display: flex;
      background-color: #f1f5fc;
      color: #111827;
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
      margin-left: 220px;
      display: flex;
      flex-direction: column;
    }

    .main-content-header h1 {
      padding: 25px 30px;
      margin: 0;
      font-size: 2rem;
      margin-bottom: 16px;
      color: #1E3A8A;
      display: flex;
      align-items: center;
      gap: 10px;
    }

    .job-posts h2 {
      padding: 25px 30px;
      margin: 0;
      font-size: 2rem;
      margin-bottom: 40px;
      color: #1E3A8A;
    }

    .stats {
      display: flex;
      gap: 40px;
      flex-wrap: wrap;
      margin-left: 40px;
    }

    .section {
      padding: 25px 30px;
      border-radius: 15px;
      border-top-style: solid;
      border-color: #1E3A8A;
      width: 350px;
      height: 120px;
      background-color: white;
      box-shadow: 0 2px 6px rgba(0, 0, 0, 0.1);
      transition: transform 0.2s ease;
    }

    .section label {
      font-size: 20px;
    }

    .section h3 {
      color: #1E3A8A;
      margin-top: 15px;
      font-size: 25px;
    }

    /* Content section wrapper */
    .content-section {
      max-width: 1400px;
      margin: 0 auto;

    }

    /* Controls bar */
    .controls-bar {
      display: flex;
      justify-content: space-between;
      align-items: center;
      margin-bottom: 20px;
      gap: 20px;
      flex-wrap: wrap;
    }

    .search-box {
      flex: 1;
      min-width: 250px;
      max-width: 400px;
    }

    .search-box input {
      width: 100%;
      padding: 10px 15px;
      border: 1px solid #e0e0e0;
      border-radius: 6px;
      font-size: 14px;
      font-family: 'Poppins', 'Roboto', sans-serif;
      transition: border-color 0.3s;
    }

    .search-box input:focus {
      outline: none;
      border-color: #1E3A8A;
    }

    .button-group {
      display: flex;
      gap: 10px;
    }

    .btn {
      padding: 10px 20px;
      border: none;
      border-radius: 6px;
      font-size: 14px;
      font-family: 'Poppins', 'Roboto', sans-serif;
      cursor: pointer;
      transition: all 0.3s;
      text-decoration: none;
      display: inline-flex;
      align-items: center;
      gap: 8px;
    }

    .btn-primary {
      background-color: #1E3A8A;
      color: white;
    }

    .btn-primary:hover {
      background-color: #1e40af;
      transform: translateY(-2px);
      box-shadow: 0 4px 8px rgba(30, 58, 138, 0.3);
    }

    .btn-success {
      background-color: #10b981;
      color: white;
    }

    .btn-success:hover {
      background-color: #059669;
      transform: translateY(-2px);
      box-shadow: 0 4px 8px rgba(16, 185, 129, 0.3);
    }

    .table-container {
      width: 90%;
      padding: 0 30px;
      margin-top: 12px;
      box-sizing: border-box;
    }


    .table-responsive {
      width: 100%;
      overflow-x: auto;
    }

    .table-responsive table {
      width: 100%;
      border-collapse: collapse;
    }


    table {
      border-collapse: collapse;
      width: 100%;
      background-color: #ffffff;
      box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
      border-radius: 8px;
      overflow: hidden;
    }

    th,
    td {
      min-width: 150px;
      /* adjust as needed for wider cells */
      padding: 16px 20px;
      /* more horizontal padding also widens cells */
      text-align: center;
      border: 1px solid #e0e0e0;
      padding: 16px 12px;
      text-align: center;
    }

    thead {
      background-color: #1E3A8A;
      font-weight: 600;
      color: #ffffff;
    }

    tbody tr:hover {
      background-color: #f8f9fa;
    }

    tbody tr:nth-child(even) {
      background-color: #fafafa;
    }

    /* Action icons */
    .action-icons {
      display: flex;
      justify-content: center;
      gap: 15px;
    }

    .action-icons a {
      color: #333;
      text-decoration: none;
      transition: color 0.2s ease, transform 0.2s ease;
      font-size: 18px;
    }

    .action-icons a:hover {
      transform: scale(1.1);
    }

    .action-icons a.edit:hover {
      color: #007bff;
    }

    .action-icons a.delete:hover {
      color: #dc3545;
    }

    .modal-overlay {
      display: none;
      position: fixed;
      z-index: 9999;
      left: 0;
      top: 0;
      width: 100%;
      height: 100%;
      background: rgba(0, 0, 0, 0.55);
      backdrop-filter: blur(3px);
    }

    /* Modal box */
    .modal-box {
      background: #fff;
      width: 450px;
      max-width: 90%;
      padding: 25px;
      border-radius: 12px;
      box-shadow: 0 10px 30px rgba(0, 0, 0, 0.25);
      position: absolute;
      top: 50%;
      left: 50%;
      transform: translate(-50%, -50%) scale(0.8);
      opacity: 0;
      transition: all 0.25s ease;
    }

    /* Show animation */
    .modal-overlay.active .modal-box {
      transform: translate(-50%, -50%) scale(1);
      opacity: 1;
    }

    /* Close button */
    .modal-close {
      float: right;
      font-size: 20px;
      cursor: pointer;
      color: #1E3A8A;
    }

    .modal-close:hover {
      color: #d00000;
    }

    .modal-title {
      font-size: 22px;
      margin-bottom: 15px;
      font-weight: 600;
      color: #1E3A8A;
    }

    .modal-field label {
      font-weight: bold;
    }

    .modal-field {
      margin-bottom: 10px;
    }

    .filter-box select {
      padding: 10px 15px;
      border: 1px solid #e0e0e0;
      border-radius: 6px;
      font-size: 14px;
      font-family: 'Poppins', 'Roboto', sans-serif;
      cursor: pointer;
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
        <li><a href="<?php echo $link; ?>"><i
              class="fa-solid <?php echo $icons[$label] ?? 'fa-circle'; ?>"></i><?php echo $label; ?></a></li>
      <?php endforeach; ?>
    </ul>
  </div>




  <div class="main-content">
    <div class="main-content-header">
      <h1>Employee List</h1>
    </div>

    <?php if (isset($_SESSION['flash_success'])): ?>
      <div class="alert alert-success alert-dismissible fade show" role="alert">
        <?php
        echo $_SESSION['flash_success'];
        unset($_SESSION['flash_success']);
        ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
      </div>
    <?php endif; ?>

    <?php if (isset($_SESSION['flash_error'])): ?>
      <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <?php
        echo $_SESSION['flash_error'];
        unset($_SESSION['flash_error']);
        ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
      </div>
    <?php endif; ?>

    <div class="table-container">
      <div class="controls-bar">
        <div class="search-box">
          <input type="text" id="searchInput" placeholder="Search employees..." onkeyup="filterTable()">

        </div>
        <div class="filter-box">
          <select id="deptFilter" class="filter-select">
            <option value="">All Departments</option>
          </select>
        </div>
      </div>

      <div class="table-responsive">
        <table>
          <thead>
            <tr>
              <th>Employee ID</th>
              <th>Full Name</th>
              <th>Department</th>
              <th>Position</th>
              <th>Employment Type</th>
              <th>Email Address</th>
              <th>Status</th>
              <th>Action</th>
            </tr>
          </thead>
          <tbody id="employeeTable">
            <?php foreach ($employee as $emp): ?>
              <tr>
                <td><?php echo htmlspecialchars($emp['empID']); ?></td>
                <td><?php echo htmlspecialchars($emp['fullname']); ?></td>
                <td><?php echo htmlspecialchars($emp['department']); ?></td>
                <td><?php echo htmlspecialchars($emp['position']); ?></td>
                <td><?php echo htmlspecialchars($emp['type_name']); ?></td>
                <td><?php echo htmlspecialchars($emp['email_address']); ?></td>
                <td class="status-cell"><?php echo htmlspecialchars($emp['status'] ?? 'Active'); ?></td>
                <td class="action-icons">
                  <a href="#viewModal" class="view" onclick="openViewModal('<?php echo $emp['empID']; ?>',
                             '<?php echo $emp['fullname']; ?>',
                             '<?php echo $emp['department']; ?>',
                             '<?php echo $emp['position']; ?>',
                             '<?php echo $emp['type_name']; ?>',
                             '<?php echo $emp['email_address']; ?>')">
                    <i class="fa-solid fa-eye"></i>
                  </a>

                  <a href="#messageModal" class="message"
                    onclick="openMessageModal('<?php echo $emp['email_address']; ?>')">
                    <i class="fa-solid fa-envelope"></i>
                  </a>
                  <?php $isInactive = strtolower($emp['status'] ?? 'Active') === 'inactive'; ?>
                  <?php if (!$isInactive): ?>
                    <a href="#inactiveModal" class="status-toggle" data-empid="<?php echo $emp['empID']; ?>">
                      <i class="fa-solid fa-circle" style="color:#10b981;"></i>
                    </a>
                  <?php else: ?>
                    <a href="#archiveModal" class="archive-btn" data-empid="<?php echo $emp['empID']; ?>">
                      <i class="fa-solid fa-trash"></i>
                    </a>
                  <?php endif; ?>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>
    <nav aria-label="Employee pagination" class="mt-3">
      <ul class="pagination justify-content-center">
        <li class="page-item <?php echo $page <= 1 ? 'disabled' : ''; ?>">
          <a class="page-link" href="?page=<?php echo max(1, $page - 1); ?>" tabindex="-1">Previous</a>
        </li>
        <?php for ($p = 1; $p <= $pages; $p++): ?>
          <li class="page-item <?php echo $p === $page ? 'active' : ''; ?>">
            <a class="page-link" href="?page=<?php echo $p; ?>"><?php echo $p; ?></a>
          </li>
        <?php endfor; ?>
        <li class="page-item <?php echo $page >= $pages ? 'disabled' : ''; ?>">
          <a class="page-link" href="?page=<?php echo min($pages, $page + 1); ?>">Next</a>
        </li>
      </ul>
    </nav>
  </div>

  <!-- View Modal -->
  <div id="viewModal" class="modal-overlay">
    <div class="modal-box">
      <span class="modal-close" onclick="closeViewModal()">&times;</span>
      <h2 class="modal-title">Employee Details</h2>

      <div class="modal-field"><label>ID:</label> <span id="v_id"></span></div>
      <div class="modal-field"><label>Name:</label> <span id="v_name"></span></div>
      <div class="modal-field"><label>Department:</label> <span id="v_dept"></span></div>
      <div class="modal-field"><label>Position:</label> <span id="v_pos"></span></div>
      <div class="modal-field"><label>Employment Type:</label> <span id="v_type"></span></div>
      <div class="modal-field"><label>Email:</label> <span id="v_email"></span></div>
    </div>
  </div>

  <!-- Message Modal -->
  <div id="messageModal" class="modal-overlay">
    <div class="modal-box">
      <span class="modal-close" onclick="closeMessageModal()">&times;</span>
      <h2 class="modal-title">Send Message</h2>

      <form action="Manager_Employees.php" method="POST">
        <input type="hidden" id="m_email" name="email">
        <input type="hidden" name="send_message" value="1">

        <!-- Subject -->
        <label>Subject:</label>
        <input type="text" name="subject" style="
        width: 100%; padding: 10px; border-radius: 6px;
        border: 1px solid #ccc; font-size: 14px; margin-bottom: 10px;
      " required>

        <!-- Message -->
        <label>Message:</label>
        <textarea name="message" rows="5" style="
        width: 100%; padding: 10px; border-radius: 6px;
        border: 1px solid #ccc; font-size: 14px;
      " required></textarea>

        <button type="submit" style="
        margin-top: 15px; width: 100%; padding: 12px;
        background: #1E3A8A; color: white; border: none;
        border-radius: 6px; cursor: pointer; font-size: 15px;
      ">Send Email</button>
      </form>
    </div>
  </div>

  <!-- Inactive Confirm Modal -->
  <div class="modal fade" id="inactiveModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title">Confirm Inactive</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          Are you sure you want to make this employee Inactive?
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
          <button type="button" class="btn btn-primary" id="confirmInactiveBtn">Yes</button>
        </div>
      </div>
    </div>
  </div>

  <!-- Archive Confirm Modal -->
  <div class="modal fade" id="archiveModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title">Confirm Archive</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          Are you sure you want to archive this employee?
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
          <button type="button" class="btn btn-danger" id="confirmArchiveBtn">Yes</button>
        </div>
      </div>
    </div>
  </div>



  <form method="POST" id="inactiveForm" style="display:none;">
    <input type="hidden" name="empID" id="inactiveEmpID">
    <input type="hidden" name="ajax_set_inactive" value="1">
  </form>
  <form method="POST" id="archiveForm" style="display:none;">
    <input type="hidden" name="empID" id="archiveEmpID">
    <input type="hidden" name="ajax_archive_employee" value="1">
  </form>

  <script>
    $(document).ready(function () {

      // Build unique department list from table
      let departments = new Set();

      $("#employeeTable tr").each(function () {
        let dept = $(this).find("td:nth-child(3)").text().trim();
        if (dept !== "") {
          departments.add(dept);
        }
      });

      // Append departments to dropdown
      departments.forEach(function (d) {
        $("#deptFilter").append(`<option value="${d}">${d}</option>`);
      });

      // Filter when department changes
      $("#deptFilter").on("change", function () {
        filterTable();
      });
    });

    function filterTable() {
      const search = $("#searchInput").val().toLowerCase();
      const deptFilter = $("#deptFilter").val().toLowerCase();

      $("#employeeTable tr").each(function () {
        let row = $(this);
        let textMatch = false;

        // Check search input across all columns except Action
        row.find("td").each(function (index) {
          if (index < row.find("td").length - 1) {
            let cellText = $(this).text().toLowerCase();
            if (cellText.includes(search)) {
              textMatch = true;
            }
          }
        });

        // Check department filter
        let rowDept = row.find("td:nth-child(3)").text().toLowerCase();
        let deptMatch = deptFilter === "" || rowDept === deptFilter;

        // Show only if both match
        row.toggle(textMatch && deptMatch);
      });
    }

    function openViewModal(id, name, dept, pos, type, email) {
      $("#v_id").text(id);
      $("#v_name").text(name);
      $("#v_dept").text(dept);
      $("#v_pos").text(pos);
      $("#v_type").text(type);
      $("#v_email").text(email);

      $("#viewModal").addClass("active").fadeIn(150);
    }

    function closeViewModal() {
      $("#viewModal").removeClass("active").fadeOut(150);
    }

    function openMessageModal(email) {
      $("#m_email").val(email);
      $("#messageModal").addClass("active").fadeIn(150);
    }

    function closeMessageModal() {
      $("#messageModal").removeClass("active").fadeOut(150);
    }

    let currentEmpId = null;
    $(document).on('click', '.status-toggle', function (e) {
      e.preventDefault();
      currentEmpId = $(this).data('empid');
      const modal = new bootstrap.Modal(document.getElementById('inactiveModal'));
      modal.show();
    });

    $('#confirmInactiveBtn').on('click', function () {
      const form = $('#inactiveForm');
      $('#inactiveEmpID').val(currentEmpId);
      $.post('Manager_Employees.php', form.serialize(), function (resp) {
        if (resp && resp.status === 'success') {
          const row = $(`a.status-toggle[data-empid="${currentEmpId}"]`).closest('tr');
          row.find('.status-cell').text('Inactive');
          const actionCell = row.find('.action-icons');
          actionCell.find('a.status-toggle').replaceWith(`<a href="#archiveModal" class="archive-btn" data-empid="${currentEmpId}"><i class="fa-solid fa-trash"></i></a>`);
          bootstrap.Modal.getInstance(document.getElementById('inactiveModal')).hide();
        }
      }, 'json');
    });

    $(document).on('click', '.archive-btn', function (e) {
      e.preventDefault();
      currentEmpId = $(this).data('empid');
      const modal = new bootstrap.Modal(document.getElementById('archiveModal'));
      modal.show();
    });

    $('#confirmArchiveBtn').on('click', function () {
      const form = $('#archiveForm');
      $('#archiveEmpID').val(currentEmpId);
      $.post('Manager_Employees.php', form.serialize(), function (resp) {
        if (resp && resp.status === 'success') {
          const row = $(`a.archive-btn[data-empid="${currentEmpId}"]`).closest('tr');
          row.remove();
          bootstrap.Modal.getInstance(document.getElementById('archiveModal')).hide();
        }
      }, 'json');
    });
  </script>
  <!-- Bootstrap JS -->
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

</body>


</html>