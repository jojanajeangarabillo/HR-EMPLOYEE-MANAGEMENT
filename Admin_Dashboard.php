<?php
session_start();
require 'admin/db.connect.php';

$employees = 0;
$requests = 0;
$hirings = 0;
$applicants = 0;

$adminanmeQuery = $conn->query("SELECT fullname FROM user WHERE role = 'Admin'");
if ($adminanmeQuery && $row = $adminanmeQuery->fetch_assoc()) {
    $adminname = $row['fullname'];
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
    <title>Admin Dashboard </title>
    <link rel="stylesheet" href="admin-sidebar.css">
    <!--For icons-->
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
            margin-bottom: 50px;
        }

        .sidebar-logo img {
            height: 120px;
            width: 120px;
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
            flex-direction: column
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

    <div class="sidebar">

        <div class="sidebar-logo">
            <img src="Images/hospitallogo.png" alt="">
        </div>

        <div class="sidebar-name">
            <p><?php echo "Welcome, $adminname"; ?></p>
        </div>

        <ul class="nav">
            <li class="active"><a href="Admin_Dashboard.php"><i class="fa-solid fa-table-columns"></i>Dashboard</a>
            </li>
            <li><a href="Admin_Employee.php"><i class="fa-solid fa-user-group"></i>Employees</a></li>
            <li><a href="Admin-Applicants.php"><i class="fa-solid fa-user-group"></i>Applicants</a></li>
            <li><a href="Admin-Pending-Applicants.php"><i class="fa-solid fa-user-group"></i>Pending Applicants</a></li>
            <li><a href="Admin_Vacancies.php"><i class="fa-solid fa-briefcase"></i>Vacancies</a></li>
            <li><a href="Admin-request.php"><i class="fa-solid fa-code-pull-request"></i>Requests</a></li>
            <li><a href="#"><i class="fa-solid fa-chart-simple"></i>Reports</a></li>
            <li><a href="Admin-Settings.php"><i class="fa-solid fa-gear"></i>Settings</a></li>
            <li><a href="Login.php"><i class="fa-solid fa-right-from-bracket"></i>Logout</a></li>
        </ul>
    </div>

    <main class="main-content">
        <div class="main-content-header">
            <h1>Dashboard Overview</h1>
        </div>
        <div class="stats">
            <div class="section">
                <label for="employees" class="employee-label">Employees</label>
                <h3 class="employee-count" name="employees">
                    <?php echo $employees; ?>
                </h3>
            </div>
            <div class="section">
                <label for="applicants" class="applicant-label">Applicants</label>
                <h3 class="applicant-count" name="applicants">
                    <?php echo $applicants; ?>
                </h3>
            </div>
            <div class="section">
                <label for="requests" class="requests-label">Requests</label>
                <h3 class="request-count" name="requests">
                    0
                </h3>
            </div>
            <div class="section">
                <label for="hirings" class="hirings-label">Hirings</label>
                <h3 class="hiring-count" name="hirings">
                    0
                </h3>
            </div>

            <div class="section">
                <label for="pending-applicants" class="pending">Pending Applicants</label>
                <h3 class="pending-count" name="pendings">
                    0
                </h3>
            </div>
        </div>
        <div class="job-posts">
            <h2>Recent Job Posts</h2>
        </div>
    </main>

</body>

</html>