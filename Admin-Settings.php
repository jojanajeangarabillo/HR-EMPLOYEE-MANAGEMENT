<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Employee Requests</title>

    <!-- Global stylesheet (for sidebar & header) -->
    <link rel="stylesheet" href="admin-sidebar.css">

    <!-- Page-specific stylesheet -->
    <link rel="stylesheet" href="employee-request.css">

    <!-- Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/7.0.1/css/all.min.css">

    <!-- jQuery for interactivity -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

    <style>
        .sidebar-logo {
            display: flex;
            justify-content: center;
            margin-bottom: 50px;
        }

        .sidebar-logo img {
            height: 120px;
            width: 120px;
        }
    </style>
</head>

<body class="admin-dashboard">

    <!-- SIDEBAR -->
    <div class="sidebar">

        <div class="sidebar-logo">
            <img src="Images/hospitallogo.png" alt="">
        </div>

        <ul class="nav">
            <li><a href="Admin_Dashboard.php"><i class="fa-solid fa-table-columns"></i>Dashboard</a>
            </li>
            <li><a href="Admin_Employee.php"><i class="fa-solid fa-user-group"></i>Employees</a></li>
            <li><a href="Admin-JobPosting.php"><i class="fa-solid fa-user-group"></i>Applicants</a></li>
            <li><a href="Admin-Pending-Applicants.php"><i class="fa-solid fa-user-group"></i>Pending Applicants</a></li>
            <li><a href="#"><i class="fa-solid fa-briefcase"></i>Vacancies</a></li>
            <li><a href="Admin-request"><i class="fa-solid fa-code-pull-request"></i>Requests</a></li>
            <li class="active"><a href="Admin-Settings"><i class="fa-solid fa-gear"></i>Settings</a></li>
            <li><a href="Login.php"><i class="fa-solid fa-right-from-bracket"></i>Logout</a></li>
        </ul>
    </div>