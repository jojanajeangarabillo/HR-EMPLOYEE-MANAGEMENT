<?php
session_start();
require 'admin/db.connect.php';

$employees = 0;
$requests = 0;
$hirings = 0;
$applicants = 0;

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
    <title>Document</title>
    <link rel="stylesheet" href="stylesheet.css">
    <!--For icons-->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/7.0.1/css/all.min.css"
        integrity="sha512-2SwdPD6INVrV/lHTZbO2nodKhrnDdJK9/kg2XD1r9uGqPo1cUbujc+IYdlYdEErWNu69gVcYgdxlmVmzTWnetw=="
        crossorigin="anonymous" referrerpolicy="no-referrer" />
    <style>
        .admin-main {
            padding: 25px;
            grid-area: main;
            display: grid;
            grid-template-columns: 1fr 1fr;
            grid-template-rows: auto;
            gap: 35px;
            margin: 15px;
        }

        .banner-card {
            grid-column: 1/3;
            grid-row: 1/2;
            background-color: #6E7BED;
            border-radius: 20px;
            padding: 40px;

        }

        .employee-card {
            grid-column: 1/2;
            grid-row: 2/3;
            background-color: #dfe0ed;
            border-radius: 20px;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 20px;
            width: 480px;
            height: 139px;
            margin-left: 120px;
        }

        .admin-main .fa-solid {
            height: 20px;
            width: 20px;
            color: #1E3A8A;
        }

        .admin-main h1,
        .admin-main h4 {
            font-family: "Poppins", sans-serif;
            font-weight: 700;
            font-style: normal;
            color: white;
        }

        .admin-main label {
            font-family: "Poppins", sans-serif;
            font-weight: 700;
            font-style: normal;
            font-size: 36px;
            margin: 20px;
        }

        .admin-main h3 {
            font-family: "Poppins", sans-serif;
            font-weight: 700;
            font-style: normal;
            font-size: 46px;
            margin: 20px;
        }

        .applicant-card {
            grid-column: 2/3;
            grid-row: 2/3;
            background-color: #dfe0ed;
            border-radius: 20px;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 20px;
            width: 480px;
            height: 139px;
            margin-left: 120px;
        }

        .request-card {
            grid-column: 1/2;
            grid-row: 3/4;
            background-color: #dfe0ed;
            border-radius: 20px;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 20px;
            width: 480px;
            height: 139px;
            margin-left: 120px;
        }

        .hiring-card {
            grid-column: 2/3;
            grid-row: 3/4;
            background-color: #dfe0ed;
            border-radius: 20px;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 20px;
            width: 480px;
            height: 139px;
            margin-left: 120px;
        }

        .recent-job-post-header {
            background-color: white;
            grid-column: 1/3;
            grid-row: 4/5;
        }

        .recent-job-post-card {
            grid-column: 1/3;
            grid-row: 5/6;
            background-color: #6E7BED;
            border-radius: 20px;
        }

        .newly-hired-header {
            grid-column: 1/3;
            grid-row: 6/7;
            background-color: white;
        }

        .newly-hired-card {
            grid-column: 1/3;
            grid-row: 7/8;
            background-color: #6E7BED;
            display: grid;
            grid-template-columns: 1fr 1fr;
            grid-template-rows: auto;
            grid-template-areas:
                "newly-hired-1 newly-hired-2";
        }

        .sub-newly-hired-card {
            background-color: #D9D9D9;
            grid-area: newly-hired-1;
        }

        .sidebar-nav .nav-link {
            color: black;
            text-decoration: none;
        }
    </style>
</head>
</head>

<body class="admin-dashboard">
    <header class="admin-header">
        <h1 class="admin-header-text">Human Resource</h1>
    </header>

    <aside class="admin-sidebar">
        <div class="sidebar-logo">
            <img src="Images/hospitallogo.png" alt="happy">
        </div>
        <nav class="sidebar-nav">
            <!--Primary top nav-->
            <ul class="primary-top-nav">
                <li class="nav-item">
                    <a href="#" class="nav-link">
                        <i class="fa-solid fa-grip"></i>
                        <span class="nav-label">Dashboard</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="#" class="nav-link">
                        <i class="fa-solid fa-user-group"></i>
                        <span class="nav-label">Employees</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="#" class="nav-link">
                        <i class="fa-solid fa-user-group"></i>
                        <span class="nav-label">Applicants</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="#" class="nav-link">
                        <i class="fa-solid fa-code-pull-request"></i>
                        <span class="nav-label">Requests</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="#" class="nav-link">
                        <i class="fa-solid fa-folder"></i>
                        <span class="nav-label">Job Post</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="#" class="nav-link">
                        <i class="fa-solid fa-chart-simple"></i>
                        <span class="nav-label">Reports</span>
                    </a>
                </li>
            </ul>
            <!--Secondary bottom nav-->
            <ul class="secondary-buttom-nav">
                <li class="nav-item">
                    <a href="#" class="nav-link">
                        <i class="fa-solid fa-gear"></i>
                        <span class="nav-label">Settings</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="#" class="nav-link">
                        <i class="fa-solid fa-right-from-bracket"></i>
                        <span class="nav-label">Logout</span>
                    </a>
                </li>
            </ul>
        </nav>
    </aside>
    <main class="admin-main">
        <div class="banner-card">
            <h1>Welcome to Admin Dashboard</h1>
            <h4>Overview of statistics</h4>
        </div>
        <div class="employee-card">
            <i class="fa-solid fa-user-group"></i>
            <label for="employees" class="employee-label">Employees</label>
            <h3 class="employee-count" name="employees">
                <?php echo $employees; ?>
            </h3>
        </div>
        <div class="applicant-card">
            <i class="fa-solid fa-user-group"></i>
            <label for="applicants" class="applicant-label">Applicants</label>
            <h3 class="applicant-count" name="applicants">
                <?php echo $applicants; ?>
            </h3>
        </div>
        <div class="request-card">
            <i class="fa-solid fa-code-pull-request"></i>
            <label for="requests" class="request-label">Requests</label>
            <h3 class="request-count" name="requests">
                <?php echo $requests ?>
            </h3>
        </div>
        <div class="hiring-card">
            <i class="fa-solid fa-folder"></i>
            <label for="hirings" class="hiring-label">Hiring</label>
            <h3 class="hiring-count" name="hirings">
                <?php echo $hirings ?>
            </h3>
        </div>
        <div class="recent-job-post-header">
            <i class="fa-solid fa-folder"></i>
            <label for="recent-job-post" class="recent-job-post-label">Recent Job Posts</label>
        </div>
        <div class="recent-job-post-card">
            <h3></h3>
        </div>
        <div class="newly-hired-header">
            <i class="fa-solid fa-user-group"></i>
            <label for="newly-hired" class="newly-hired-label">Newly Hired</label>
        </div>
        <div class="newly-hired-card">
            <div class="sub-newly-hired-card"></div>
            <div class="sub-newly-hired-card"></div>
        </div>
    </main>

</body>

</html>