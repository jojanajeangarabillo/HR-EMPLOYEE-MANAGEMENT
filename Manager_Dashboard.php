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

$pending_applicantQuery = $conn->query("SELECT COUNT(*) AS count FROM applications WHERE id = '0'");
if ($pending_applicantQuery && $row = $pending_applicantQuery->fetch_assoc()) {
    $pending_applicantQuery = $row['count'];
}

// Fetch total number of positions for Hirings (status = 'On-Going' or 'To Post')
$hiringsQuery = $conn->query("
    SELECT SUM(vacancy_count) AS count 
    FROM vacancies 
    WHERE status IN ('On-Going', 'To Post')
");
if ($hiringsQuery && $row = $hiringsQuery->fetch_assoc()) {
    $hirings = $row['count'] ?? 0; 
}

// Fetch recent vacancies for listing (only On-Going)
$recentVacanciesQuery = $conn->query("
    SELECT v.id, v.vacancy_count, v.status, d.deptName, p.position_title, e.typeName AS employment_type
    FROM vacancies v
    JOIN department d ON v.department_id = d.deptID
    JOIN position p ON v.position_id = p.positionID
    JOIN employment_type e ON v.employment_type_id = e.emtypeID
    WHERE v.status = 'On-Going'
    ORDER BY v.id DESC
    LIMIT 5
");



?>




<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manager Dashboard</title>

    <link rel="stylesheet" href="manager-sidebar.css">

    <!-- Font Awesome Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/7.0.1/css/all.min.css"
        integrity="sha512-2SwdPD6INVrV/lHTZbO2nodKhrnDdJK9/kg2XD1r9uGqPo1cUbujc+IYdlYdEErWNu69gVcYgdxlmVmzTWnetw=="
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
            <li class="active"><a href="Manager_Dashboard.php"><i class="fa-solid fa-table-columns"></i>Dashboard</a></li>
            <li><a href="Manager_Applicants.php"><i class="fa-solid fa-user-group"></i>Applicants</a></li>
            <li><a href="Manager_PendingApplicants.php"><i class="fa-solid fa-hourglass-half"></i>Pending Applicants</a></li>
            <li ><a href="Manager_Employees.php"><i class="fa-solid fa-user-group me-2"></i>Employees</a></li>
            <li><a href="Manager_Request.php"><i class="fa-solid fa-code-pull-request"></i>Requests</a></li>
            <li><a href="Manager-JobPosting.php"><i class="fa-solid fa-briefcase"></i>Job Post</a></li>
            <li><a href="Manager_Calendar.php"><i class="fa-solid fa-calendar"></i>Calendar</a></li>
            <li><a href="Manager_Approvals.php"><i class="fa-solid fa-circle-check"></i>Approvals</a></li>
            <li><a href="Manager_LeaveSettings.php"><i class="fa-solid fa-gear"></i>Settings</a></li>
            <li><a href="#"><i class="fa-solid fa-right-from-bracket"></i>Logout</a></li>
        </ul>
    </div>

    <!-- MAIN CONTENT -->
    <main class="main-content">
        <div class="main-content-header">
            <h1>Dashboard Overview</h1>
        </div>

        <div class="stats">
    <div class="section">
        <label>Employees</label>
        <h3><?php echo $employees; ?></h3>
    </div>

    <div class="section">
        <label>Applicants</label>
        <h3><?php echo $applicants; ?></h3>
    </div>

    <div class="section">
        <label>Requests</label>
        <h3><?php echo $requests; ?></h3>
    </div>

    <div class="section">
        <label>Hirings</label>
        <h3><?php echo $hirings; ?></h3>
    </div>

    <div class="section">
        <label>Pending Applicants</label>
        <h3><?php echo $pending_applicantQuery;  ?></h3>
    </div>
</div>


        <div class="job-posts">
            <h2>Recent Job Posts</h2>
        </div>
    </main>

</body>

</html>
