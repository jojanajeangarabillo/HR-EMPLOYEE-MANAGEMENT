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

$requests = [];
$stmt = $conn->prepare("SELECT r.request_id, e.empID, e.fullname, e.department, r.request_type_name, r.status, r.reason, r.requested_at 
                        FROM employee_request r
                        JOIN employee e ON r.empID = e.empID
                        LEFT JOIN department d ON e.department = d.deptID
                        ORDER BY r.requested_at DESC");
$stmt->execute();
$res = $stmt->get_result();
while ($row = $res->fetch_assoc()) {
  $requests[] = $row;
}
$stmt->close();


?>


<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Manager Calendar</title>

  <!-- Font Awesome -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/7.0.1/css/all.min.css">

  <!-- External Sidebar CSS -->
  <link rel="stylesheet" href="manager-sidebar.css">

  <style>
    /* MAIN PAGE LAYOUT */
    body {
      background-color: #F4F6F8;
      display: flex;
      font-family: "Poppins", sans-serif;
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

    /* MAIN CONTENT AREA */
    .main-content {
      padding: 40px 30px;
      margin-left: 220px;
      display: flex;
      flex-direction: column
    }

    .main-content h1 {
      color: #1E3A8A;
      font-weight: 700;
      margin-bottom: 25px;
    }

    /* TABLE DESIGN */
    table {
      width: 100%;
      border-collapse: collapse;
      background-color: #ffffff;
      border-radius: 10px;
      overflow: hidden;
      box-shadow: 0 2px 6px rgba(0, 0, 0, 0.1);
    }

    th,
    td {
      text-align: center;
      padding: 12px;
      border: 1px solid #ddd;
    }

    th {
      background-color: #1E3A8A;
      color: white;
      font-weight: 600;
    }

    td {
      color: #333;
    }

    tbody tr:hover {
      background-color: #F2F6FF;
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
        <li><a href="<?php echo $link; ?>"><?php echo $label; ?></a></li>
      <?php endforeach; ?>
    </ul>
  </div>

  <!-- MAIN CONTENT -->
  <div class="main-content">
    <h1>Manager Calendar</h1>

    <table>
      <thead>
        <tr>
          <th>Employee ID</th>
          <th>Employee Name</th>
          <th>Department</th>
          <th>Request Type</th>
          <th>Reason</th>
          <th>Date</th>
          <th>Remarks</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($requests as $req): ?>
          <tr>
            <td><?= htmlspecialchars($req['empID']) ?></td>
            <td><?= htmlspecialchars($req['fullname']) ?></td>
            <td><?= htmlspecialchars($req['department'] ?? 'N/A') ?></td>
            <td><?= htmlspecialchars($req['request_type_name']) ?></td>
            <td><?= htmlspecialchars($req['reason']) ?></td>
            <td><?= date('Y-m-d', strtotime($req['requested_at'])) ?></td>
            <td class="action-icons">
              <?php if ($req['status'] === 'Approved'): ?>
                <span style="color:green;font-weight:bold;">Approved</span>
              <?php elseif ($req['status'] === 'Rejected'): ?>
                <span style="color:red;font-weight:bold;">Rejected</span>
              <?php else: ?>
                <span style="color:orange;font-weight:bold;">Pending</span>
              <?php endif; ?>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</body>

</html>