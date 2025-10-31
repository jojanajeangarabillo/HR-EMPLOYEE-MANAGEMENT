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
  <link rel="stylesheet"
    href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/7.0.1/css/all.min.css"
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

    .main-content {
      padding: 40px 30px;
      margin-left: 220px;
      display: flex;
      flex-direction: column;
    }

    .main-content-header h1 {
      margin: 0 0 25px 0;
      font-size: 2rem;
      color: #1E3A8A;
    }

    /* Header for Search + Filter */
    .header {
      display: flex;
      justify-content: space-between;
      align-items: center;
      margin-bottom: 20px;
    }

    .search-filter {
      display: flex;
      align-items: center;
      gap: 15px;
    }

    .search-box {
      position: relative;
      flex: 1;
      max-width: 350px;
    }

    .search-box input {
      width: 100%;
      padding: 10px 40px;
      border: 1px solid #d1d5db;
      border-radius: 25px;
      font-size: 14px;
      background-color: white;
      outline: none;
      transition: all 0.3s;
    }

    .search-box input:focus {
      border-color: #1e3a8a;
      box-shadow: 0 0 0 3px rgba(30, 58, 138, 0.2);
    }

    .search-box i {
      position: absolute;
      left: 15px;
      top: 50%;
      transform: translateY(-50%);
      color: #6b7280;
      font-size: 14px;
    }

    select {
      border-radius: 25px;
      padding: 10px 18px;
      border: 1px solid #d1d5db;
      background-color: #fff;
      font-size: 14px;
      color: #333;
      outline: none;
      cursor: pointer;
      transition: all 0.3s;
    }

    select:hover {
      border-color: #1E3A8A;
    }

    /* Table Styling */
    table {
      width: 90%;
      border-collapse: collapse;
      background-color: #ffffff;
      border-radius: 10px;
      overflow: hidden;
      box-shadow: 0 3px 6px rgba(0, 0, 0, 0.1);
      margin-left: 200px;
    }

    th,
    td {
      padding: 18px 30px;
      text-align: center;
      border-bottom: 1px solid #e0e0e0;
    }

    th {
      font-size: 15px;
      letter-spacing: 0.3px;
    }

    td {
      font-size: 14px;
    }

    thead {
      background-color: #1E3A8A;
      color: white;
    }

    tbody tr:hover {
      background-color: #f8f9fa;
    }

    /* ✅ Wider Column Proportions */
    th:nth-child(1),
    td:nth-child(1) {
      width: 25%;
    }

    th:nth-child(2),
    td:nth-child(2) {
      width: 30%;
    }

    th:nth-child(3),
    td:nth-child(3) {
      width: 20%;
    }

    th:nth-child(4),
    td:nth-child(4) {
      width: 25%;
    }

    /* View Button */
    #view-btn {
      background-color: #1E3A8A;
      color: white;
      border: none;
      border-radius: 25px;
      padding: 8px 16px;
      font-size: 13px;
      cursor: pointer;
      transition: all 0.3s;
      display: inline-flex;
      align-items: center;
      gap: 6px;
    }

    #view-btn:hover {
      background-color: #1e40af;
      transform: translateY(-2px);
      box-shadow: 0 4px 8px rgba(30, 58, 138, 0.3);
    }

    #view-btn i {
      font-size: 13px;
    }

    /* Status Color Coding */
    .status {
      font-weight: 600;
      border-radius: 30px;
      padding: 6px 14px;
      display: inline-block;
    }

    .status.pending {
      background-color: #fef3c7;
      color: #a16207;
    }

    .status.interviewed {
      background-color: #dcfce7;
      color: #166534;
    }

    .status.rejected {
      background-color: #fee2e2;
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
  <!-- SIDEBAR -->
  <div class="sidebar">
    <div class="sidebar-logo">
      <img src="Images/hospitallogo.png" alt="Hospital Logo">
    </div>

    <div class="sidebar-name">
            <p><?php echo "Welcome, $managername"; ?></p>
        </div>

    <ul class="nav">
            <li><a href="Manager_Dashboard.php"><i class="fa-solid fa-table-columns"></i>Dashboard</a></li>
            <li><a href="Manager_Applicants.php"><i class="fa-solid fa-user-group"></i>Applicants</a></li>
            <li class="active"><a href="Manager_PendingApplicants.php"><i class="fa-solid fa-hourglass-half"></i>Pending Applicants</a></li>
            <li><a href="Manager_Request.php"><i class="fa-solid fa-code-pull-request"></i>Requests</a></li>
            <li><a href="Manager-JobPosting.php"><i class="fa-solid fa-briefcase"></i>Job Post</a></li>
            <li><a href="Manager_Calendar.php"><i class="fa-solid fa-calendar"></i>Calendar</a></li>
            <li><a href="Manager_Approvals.php"><i class="fa-solid fa-circle-check"></i>Approvals</a></li>
            <li><a href="Manager_LeaveSettings.php"><i class="fa-solid fa-gear"></i>Settings</a></li>
            <li><a href="#"><i class="fa-solid fa-right-from-bracket"></i>Logout</a></li>
        </ul>
  </div>

  <!-- MAIN CONTENT -->
  <div class="main-content">
    <div class="main-content-header">
      <h1>Pending Applicants</h1>
    </div>

    <div class="header">
      <div class="search-filter">
        <div class="search-box">
          <i class="fa-solid fa-magnifying-glass"></i>
          <input type="text" id="searchInput" placeholder="Search applicants...">
        </div>

        <select id="statusFilter">
          <option value="all">All Status</option>
          <option value="Pending">Pending</option>
          <option value="Interviewed">Interviewed</option>
          <option value="Rejected">Rejected</option>
        </select>
      </div>
    </div>

    <table id="applicantTable">
      <thead>
        <tr>
          <th>Applicant ID</th>
          <th>Full Name</th>
          <th>Action</th>
          <th>Status</th>
        </tr>
      </thead>
      <tbody>
        <tr>
          <td>25-0001</td>
          <td>John Smith</td>
          <td><button id="view-btn"><i class="fa-solid fa-eye"></i> View</button></td>
          <td><span class="status pending">Pending</span></td>
        </tr>
        <tr>
          <td>25-0002</td>
          <td>Garabillo, Jojana Jean</td>
          <td><button id="view-btn"><i class="fa-solid fa-eye"></i> View</button></td>
          <td><span class="status interviewed">Interviewed</span></td>
        </tr>
        <tr>
          <td>25-0003</td>
          <td>Maria Santos</td>
          <td><button id="view-btn"><i class="fa-solid fa-eye"></i> View</button></td>
          <td><span class="status rejected">Rejected</span></td>
        </tr>
      </tbody>
    </table>
  </div>

  <script>
    // Search & Filter Functionality
    const searchInput = document.getElementById('searchInput');
    const statusFilter = document.getElementById('statusFilter');
    const tableRows = document.querySelectorAll('#applicantTable tbody tr');

    function filterTable() {
      const searchTerm = searchInput.value.toLowerCase();
      const filterValue = statusFilter.value;

      tableRows.forEach(row => {
        const name = row.cells[1].textContent.toLowerCase();
        const status = row.cells[3].textContent.trim();

        const matchesSearch = name.includes(searchTerm);
        const matchesFilter = filterValue === 'all' || status === filterValue;

        row.style.display = matchesSearch && matchesFilter ? '' : 'none';
      });
    }

    searchInput.addEventListener('keyup', filterTable);
    statusFilter.addEventListener('change', filterTable);
  </script>
</body>

</html>
