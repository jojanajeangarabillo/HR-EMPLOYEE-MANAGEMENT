<?php
session_start();
require 'admin/db.connect.php';

$employees = 0;
$requests = 0;
$hirings = 0;
$applicants = 0;
$managername = 0;

$managernameQuery = $conn->query("SELECT fullname FROM user WHERE role = 'Employee' AND  sub_role ='HR Manager' LIMIT 1");
if ($managernameQuery && $row = $managernameQuery->fetch_assoc()) {
    $managername = $row['fullname'];
}


$employeeQuery = $conn->query("SELECT COUNT(*) AS count FROM user WHERE role = 'Employee'");
if ($employeeQuery && $row = $employeeQuery->fetch_assoc()) {
    $employees = $row['count'];
}

$applicantQuery = $conn->query("SELECT COUNT(*) AS count FROM user WHERE role = 'Applicant'");
if ($applicantQuery && $row = $applicantQuery->fetch_assoc()) {
    $applicants = $row['count'];
}

?>


<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Admin Dashboard</title>

  <link rel="stylesheet" href="manager-sidebar.css">

  <!-- Font Awesome Icons -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/7.0.1/css/all.min.css"
    crossorigin="anonymous" referrerpolicy="no-referrer" />

  <style>
    body {
      font-family: 'Poppins', 'Roboto', sans-serif;
      margin: 0;
      display: flex;
      background-color: #f1f5fc;
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
      border: 3px solid #ffffff;
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

    /* --- TABLE & FILTER STYLING --- */
    .table-container {
      max-width: 1200px;
      margin: 0 auto;
      margin-left: 200px;
    }

    .controls-bar {
      display: flex;
      justify-content: flex-start;
      align-items: center;
      margin-bottom: 20px;
      gap: 20px;
      flex-wrap: wrap;
    }

    .search-box {
      display: flex;
      align-items: center;
      gap: 10px;
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
      transition: border-color 0.3s;
    }

    .search-box input:focus {
      outline: none;
      border-color: #1E3A8A;
    }

    .search-box button {
      background-color: #1E3A8A;
      color: white;
      border: none;
      border-radius: 6px;
      padding: 10px 15px;
      font-size: 14px;
      cursor: pointer;
      transition: all 0.3s;
    }

    .search-box button:hover {
      background-color: #1e40af;
      transform: translateY(-2px);
      box-shadow: 0 4px 8px rgba(30, 58, 138, 0.3);
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
      padding: 16px 12px;
      text-align: center;
      border: 1px solid #e0e0e0;
    }

    thead {
      background-color: #1E3A8A;
      color: #ffffff;
      font-weight: 600;
    }

    tbody tr:hover {
      background-color: #f8f9fa;
    }

    tbody tr:nth-child(even) {
      background-color: #fafafa;
    }

    .action-icons {
      display: flex;
      justify-content: center;
      gap: 15px;
    }

    .action-icons a {
      color: #333;
      text-decoration: none;
      font-size: 18px;
      transition: color 0.2s ease, transform 0.2s ease;
    }

    .action-icons a:hover {
      transform: scale(1.1);
    }

    .action-icons a.accept:hover {
      color: #10b981;
    }

    .action-icons a.reject:hover {
      color: #dc3545;
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
      <img src="Images/hospitallogo.png" alt="Hospital Logo">
    </div>

    <div class="sidebar-name">
      <p><?php echo "Welcom, $managername" ?></p>
    </div>

    <ul class="nav">
      <li><a href="Manager_Dashboard.php"><i class="fa-solid fa-table-columns"></i>Dashboard</a></li>
      <li><a href="#"><i class="fa-solid fa-user-group"></i>Applicants</a></li>
      <li><a href="#"><i class="fa-solid fa-hourglass-half"></i>Pending Applicants</a></li>
      <li><a href="#"><i class="fa-solid fa-code-pull-request"></i>Requests</a></li>
      <li><a href="Manager-JobPosting.php"><i class="fa-solid fa-briefcase"></i>Job Post</a></li>
      <li><a href="Manager_Calendar.php"><i class="fa-solid fa-calendar"></i>Calendar</a></li>
      <li class="active"><a href="Manager_Approvals.php"><i class="fa-solid fa-circle-check"></i>Approvals</a></li>
      <li><a href="Manager_LeaveSettings.php"><i class="fa-solid fa-gear"></i>Settings</a></li>
      <li><a href="#"><i class="fa-solid fa-right-from-bracket"></i>Logout</a></li>
    </ul>
  </div>

  <!-- MAIN CONTENT -->
  <div class="main-content">
    <div class="main-content-header">
      <h1>Employee Requests</h1>
    </div>

    <div class="table-container">
      <div class="controls-bar">
        <div class="search-box">
          <input type="text" id="searchInput" placeholder="Search requests..." onkeyup="filterTable()">
          <button onclick="filterTable()"><i class="fa-solid fa-filter"></i> Filter</button>
        </div>
      </div>

      <div class="table-responsive">
        <table>
          <thead>
            <tr>
              <th>Employee ID</th>
              <th>Employee Name</th>
              <th>Department</th>
              <th>Request Type</th>
              <th>Reason</th>
              <th>Date</th>
              <th>Action</th>
            </tr>
          </thead>
          <tbody id="employeeTable">
            <tr>
              <td>EMP001</td>
              <td>Juan Dela Cruz</td>
              <td>HR</td>
              <td>Leave</td>
              <td>Vacation</td>
              <td>2025-10-28</td>
              <td class="action-icons">
                <a href="#" class="accept"><i class="fa-solid fa-check"></i></a>
                <a href="#" class="reject"><i class="fa-solid fa-xmark"></i></a>
              </td>
            </tr>
            <tr>
              <td>EMP002</td>
              <td>Maria Santos</td>
              <td>Finance</td>
              <td>Resignation</td>
              <td>Personal Reasons</td>
              <td>2025-10-20</td>
              <td class="action-icons">
                <a href="#" class="accept"><i class="fa-solid fa-check"></i></a>
                <a href="#" class="reject"><i class="fa-solid fa-xmark"></i></a>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>
  </div>

  <script>
    function filterTable() {
      const input = document.getElementById('searchInput');
      const filter = input.value.toLowerCase();
      const table = document.getElementById('employeeTable');
      const rows = table.getElementsByTagName('tr');

      for (let i = 0; i < rows.length; i++) {
        const cells = rows[i].getElementsByTagName('td');
        let found = false;

        for (let j = 0; j < cells.length - 1; j++) {
          if (cells[j]) {
            const textValue = cells[j].textContent || cells[j].innerText;
            if (textValue.toLowerCase().indexOf(filter) > -1) {
              found = true;
              break;
            }
          }
        }

        rows[i].style.display = found ? '' : 'none';
      }
    }
  </script>
</body>

</html>
