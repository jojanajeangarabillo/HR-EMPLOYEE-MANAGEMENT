<?php
session_start();
require 'admin/db.connect.php';

// Manager name
$managername = $_SESSION['fullname'] ?? "Manager";

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

$employee = [];
$stmt = $conn->prepare("SELECT empID, fullname, department, position, type_name, email_address FROM employee");
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
  $employee[] = $row;
}
$stmt->close();

// Handle delete employee
if (isset($_POST['delete_emp'])) {
  $empID = $_POST['empID'] ?? null;

  if (!$empID || !is_numeric($empID)) {
    $_SESSION['flash_error'] = "Invalid employee ID.";
    header("Location: Manager_Employees.php");
    exit;
  }

  $empID = intval($empID);

  // Fetch employee info from user table
  $stmtEmp = $conn->prepare("
        SELECT user_id, applicant_employee_id, email, password, role, fullname, status, created_at, profile_pic, sub_role
        FROM user
        WHERE applicant_employee_id = ?
    ");
  $stmtEmp->bind_param("i", $empID);
  $stmtEmp->execute();
  $empData = $stmtEmp->get_result()->fetch_assoc();
  $stmtEmp->close();

  if (!$empData) {
    $_SESSION['flash_error'] = "Employee not found.";
    header("Location: Manager_Employees.php");
    exit;
  }

  // Begin transaction
  $conn->begin_transaction();

  try {
    // Insert into user_archive
    $archiveStmt = $conn->prepare("
            INSERT INTO user_archive
            (user_id, applicant_employee_id, email, password, role, fullname, status, created_at, profile_pic, sub_role)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");

    $archiveStmt->bind_param(
      "iissssssss",
      $empData['user_id'],
      $empData['applicant_employee_id'],
      $empData['email'],
      $empData['password'],
      $empData['role'],
      $empData['fullname'],
      $empData['status'],
      $empData['created_at'],
      $empData['profile_pic'],
      $empData['sub_role']
    );

    $archiveStmt->execute();
    $archiveStmt->close();

    // Delete from user table
    $delUser = $conn->prepare("DELETE FROM user WHERE applicant_employee_id = ?");
    $delUser->bind_param("i", $empID);
    $delUser->execute();
    $delUser->close();


    // Delete from employee table
    $delEmp = $conn->prepare("DELETE FROM employee WHERE empID = ?");
    $delEmp->bind_param("i", $empID);
    $delEmp->execute();
    $delEmp->close();

    // Commit transaction
    $conn->commit();

    $_SESSION['flash_success'] = "Employee archived and deleted successfully!";
  } catch (Exception $e) {
    // Rollback transaction if anything fails
    $conn->rollback();
    $_SESSION['flash_error'] = "Failed to delete employee: " . $e->getMessage();
  }

  header("Location: Manager_Employees.php");
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

  <!-- Font Awesome Icons -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/7.0.1/css/all.min.css"
    integrity="sha512-2SwdPD6INVrV/lHTZbO2nodKhrnDdJK9/kg2XD1r9uGqPo1cUbujc+IYdlYdEErWNu69gVcYgdxlmVmzTWnetw=="
    crossorigin="anonymous" referrerpolicy="no-referrer" />
  <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
  ">

  <style>
    body {
      font-family: 'Poppins', 'Roboto', sans-serif;
      margin: 0;
      display: flex;
      background-color: #f1f5fc;
      color: #111827;
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
      margin-bottom: 40px;
      color: #1E3A8A;
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
      width: 100%;
      padding: 0 30px;
      margin-top: 20px;
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
      <img src="Images/hospitallogo.png" alt="Hospital Logo">
    </div>

    <div class="sidebar-name">
      <p><?php echo "Welcome, $managername"; ?></p>
    </div>

    <ul class="nav">
      <?php foreach ($menus[$role] as $label => $link): ?>
        <li><a href="<?php echo $link; ?>"><?php echo $label; ?></a></li>
      <?php endforeach; ?>
    </ul>
  </div>

  <div class="main-content">
    <div class="main-content-header">
      <h1>Employee List</h1>
    </div>

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

                  <a href="#deleteModal" class="delete" onclick="confirmDelete('<?php echo $emp['empID']; ?>')">
                    <i class="fa-solid fa-trash"></i>
                  </a>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>
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



  <form method="POST" id="deleteForm" style="display:none;">
    <input type="hidden" name="empID" id="deleteID">
    <input type="hidden" name="delete_emp" value="1">
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
    } function filterTable() {
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

    function confirmDelete(empID) {
      if (confirm("Are you sure you want to delete this employee?")) {
        $("#deleteID").val(empID);
        $("#deleteForm").submit();
      }
    }
  </script>
</body>


</html>