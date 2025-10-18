<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Employee Requests</title>

    <!-- Global stylesheet (for sidebar & header) -->
    <link rel="stylesheet" href="stylesheet.css">

    <!-- Page-specific stylesheet -->
    <link rel="stylesheet" href="employee-request.css">

    <!-- Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/7.0.1/css/all.min.css">
    
    <!-- jQuery for interactivity -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
</head>

<body class="admin-dashboard">

    <!-- HEADER -->
    <header class="admin-header">
        <h1 class="admin-header-text">Human Resource</h1>
    </header>

    <!-- SIDEBAR -->
    <aside class="admin-sidebar">
        <div class="sidebar-logo">
            <img src="Images/hospitallogo.png" alt="Company Logo">
        </div>

        <nav class="sidebar-nav">
            <ul class="primary-top-nav">
                <li class="nav-item"><a href="#"><i class="fa-solid fa-grip"></i><span class="nav-label">Dashboard</span></a></li>
                <li class="nav-item"><a href="#"><i class="fa-solid fa-user-group"></i><span class="nav-label">Employees</span></a></li>
                <li class="nav-item"><a href="#"><i class="fa-solid fa-user-group"></i><span class="nav-label">Applicants</span></a></li>
                <li class="nav-item active"><a href="#"><i class="fa-solid fa-code-pull-request"></i><span class="nav-label">Requests</span></a></li>
                <li class="nav-item"><a href="#"><i class="fa-solid fa-folder"></i><span class="nav-label">Job Post</span></a></li>
                <li class="nav-item"><a href="#"><i class="fa-solid fa-chart-simple"></i><span class="nav-label">Reports</span></a></li>
            </ul>

            <ul class="secondary-buttom-nav">
                <li class="nav-item"><a href="#"><i class="fa-solid fa-gear"></i><span class="nav-label">Settings</span></a></li>
                <li class="nav-item"><a href="#"><i class="fa-solid fa-right-from-bracket"></i><span class="nav-label">Logout</span></a></li>
            </ul>
        </nav>
    </aside>
