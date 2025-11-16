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
  <title>Manager Calendar</title>

  <!-- Font Awesome -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/7.0.1/css/all.min.css">

  <!-- External Sidebar CSS -->
  <link rel="stylesheet" href="manager-sidebar.css">

  <style>


/* SIDEBAR LOGO */
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


    /* MAIN PAGE LAYOUT */
    body {
      background-color: #F4F6F8;
      display: flex;
      font-family: "Poppins", sans-serif;
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
      box-shadow: 0 2px 6px rgba(0,0,0,0.1);
    }

    th, td {
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
      <img src="Images/hospitallogo.png" alt="Hospital Logo">
    </div>

    <div class="sidebar-name">
      <p><?php echo "Welcome, $managername"?></p>
    </div>

    <ul class="nav">
      <li><a href="Manager_Dashboard.php"><i class="fa-solid fa-table-columns"></i>Dashboard</a></li>
      <li><a href="Manager_Applicants.php"><i class="fa-solid fa-user-group"></i>Applicants</a></li>
      <li><a href="Manager_PendingApplicants.php"><i class="fa-solid fa-hourglass-half"></i>Pending Applicants</a></li>
      <li ><a href="Manager_Employees.php"><i class="fa-solid fa-user-group me-2"></i>Employees</a></li>
      <li><a href="Manager_Request.php"><i class="fa-solid fa-code-pull-request"></i>Requests</a></li>
      <li><a href="Manager-JobPosting.php"><i class="fa-solid fa-briefcase"></i>Job Post</a></li>
      <li class="active"><a href="Manager_Calendar.php"><i class="fa-solid fa-calendar"></i>Calendar</a></li>
      <li><a href="Manager_Approvals.php"><i class="fa-solid fa-circle-check"></i>Approvals</a></li>
      <li><a href="Manager_LeaveSettings.php"><i class="fa-solid fa-gear"></i>Settings</a></li>
      <li><a href="#"><i class="fa-solid fa-right-from-bracket"></i>Logout</a></li>
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
        <tr>
          <td>001</td>
          <td>Jojana Garabillo</td>
          <td>Gynecology</td>
          <td>Leave</td>
          <td>Vacation</td>
          <td>10/18/2025–10/25/2025</td>
          <td>Approved</td>
        </tr>
        <tr>
          <td>002</td>
          <td>Jhanna Jaroda</td>
          <td>Nursing</td>
          <td>Leave</td>
          <td>Sick</td>
          <td>11/18/2025–11/25/2025</td>
          <td>Approved</td>
        </tr>
        <tr>
          <td>003</td>
          <td>Angela Sison</td>
          <td>IT</td>
          <td>Leave</td>
          <td>Vacation</td>
          <td>09/02/2025–09/04/2025</td>
          <td>Rejected</td>
        </tr>
      </tbody>
    </table>
  </div>
</body>
</html>
