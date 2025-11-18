<?php
session_start();
require 'admin/db.connect.php';

// Fetch counts
$employees = $conn->query("SELECT COUNT(*) AS count FROM user WHERE role='Employee'")->fetch_assoc()['count'] ?? 0;
$applicants = $conn->query("SELECT COUNT(*) AS count FROM user WHERE role='Applicant'")->fetch_assoc()['count'] ?? 0;

// Pending applicants
$pendingApplicants = 0;
$q = $conn->query("SELECT COUNT(*) AS count FROM applications WHERE id = 0");
if ($q && $row = $q->fetch_assoc()) {
    $pendingApplicants = $row['count'];
}

// Hirings
$hirings = 0;
$q2 = $conn->query("SELECT SUM(vacancy_count) AS count FROM vacancies WHERE status IN ('On-Going', 'To Post')");
if ($q2 && $row = $q2->fetch_assoc()) {
    $hirings = $row['count'] ?? 0;
}

// Manager name
$managername = "Manager";
$q3 = $conn->query("SELECT fullname FROM user WHERE role='Employee' AND sub_role='HR Manager' LIMIT 1");
if ($q3 && $row = $q3->fetch_assoc()) {
    $managername = $row['fullname'];
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
        "Vacancies" => "Admin_Vacancies.php",
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
        "Vacancies" => "Admin_Vacancies.php",
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
        "Vacancies" => "Admin_Vacancies.php",
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

    "HR Assistant" => [
        "Dashboard" => "Manager_Dashboard.php",
        "Applicants" => "Manager_Applicants.php",
        "Pending Applicants" => "Manager_PendingApplicants.php",
        "Newly Hired" => "Newly-Hired.php",
        "Employees" => "Manager_Employees.php",
        "Logout" => "Login.php"
    ],

    "Training & Development Manager" => [
        "Dashboard" => "Manager_Dashboard.php",
        "Employees" => "Manager_Employees.php",
        "Calendar" => "Manager_Calendar.php",
        "Requests" => "Manager_Request.php",
        "Logout" => "Login.php"
    ]
];

$role = $_SESSION['sub_role'] ?? "HR Manager";
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
        <?php foreach ($menus[$role] as $label => $link): ?>
                <li><a href="<?php echo $link; ?>"><i class="fa-solid fa-circle"></i><?php echo $label; ?></a></li>
        <?php endforeach; ?>
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
                <h3><?php echo $pending_applicantQuery; ?></h3>
            </div>
        </div>


        <div class="job-posts">
            <h2>Recent Job Posts</h2>
        </div>
    </main>

</body>

</html>