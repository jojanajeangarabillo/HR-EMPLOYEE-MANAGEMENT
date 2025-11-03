<?php
session_start();
require 'admin/db.connect.php';

$adminanmeQuery = $conn->query("SELECT fullname FROM user WHERE role = 'Admin'");
if ($adminanmeQuery && $row = $adminanmeQuery->fetch_assoc()) {
    $adminname = $row['fullname'];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>System Settings</title>

    <link rel="stylesheet" href="admin-sidebar.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/7.0.1/css/all.min.css">

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
            margin-top: -250px;
            margin-left: 250px;
            display: flex;
            flex-direction: column;
            background-color: #f1f5fc;
            min-height: 100vh;
        }

        .main-content h2 {
            color: #1e3a8a;
            margin-bottom: 25px;
            font-size: 26px;
            display: flex;
            align-items: center;
            gap: 10px;
            font-weight: 700;
        }

        .settings-container {
            background-color: #e5e7eb;
            padding: 60px; 
            border-radius: 10px;
            width: 200%; 
            margin-left: 30px; 
            box-shadow: 0 4px 10px rgba(0,0,0,0.1);
        }

        form {
            display: flex;
            flex-direction: column;
            gap: 15px;
        }

        .form-row {
            display: flex;
            gap: 20px;
        }

        .form-group {
            flex: 1;
            display: flex;
            flex-direction: column;
        }

        .form-group label {
            font-weight: 600;
            margin-bottom: 5px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .form-group input[type="text"],
        .form-group input[type="email"],
        .form-group input[type="file"],
        .form-group textarea {
            width: 100%;
            padding: 10px;
            border: none;
            border-radius: 5px;
            background-color: #fff;
            font-family: 'Poppins', sans-serif;
            font-size: 14px;
        }

        .form-group textarea {
            resize: none;
        }

        .form-actions {
            display: flex;
            justify-content: flex-end;
            align-items: center;
            margin-top: 10px;
        }

        .form-actions button {
            background-color: #1e3a8a;
            color: white;
            border: none;
            border-radius: 5px;
            padding: 8px 25px;
            font-size: 14px;
            cursor: pointer;
        }

        .form-actions button:hover {
            background-color: #1d4ed8;
        }

        /* Icon color */
        .form-group label i {
            color: #1e3a8a;
        }
    </style>
</head>

<body class="admin-dashboard">

    <!-- SIDEBAR -->
    <div class="sidebar">
        <div class="sidebar-logo">
            <img src="Images/hospitallogo.png" alt="Hospital Logo">
        </div>
        <div class="sidebar-name">
            <p><?php echo "Welcome, $adminname"; ?></p>
        </div>

        <ul class="nav">
            <li><a href="Admin_Dashboard.php"><i class="fa-solid fa-table-columns"></i>Dashboard</a></li>
            <li><a href="Admin_Employee.php"><i class="fa-solid fa-user-group"></i>Employees</a></li>
            <li><a href="Admin-JobPosting.php"><i class="fa-solid fa-user-group"></i>Applicants</a></li>
            <li><a href="Admin-Pending-Applicants.php"><i class="fa-solid fa-user-group"></i>Pending Applicants</a></li>
            <li><a href="Admin_Vacancies.php"><i class="fa-solid fa-briefcase"></i>Vacancies</a></li>
            <li><a href="Admin-request"><i class="fa-solid fa-code-pull-request"></i>Requests</a></li>
            <li><a href="#"><i class="fa-solid fa-chart-simple"></i>Reports</a></li>
            <li class="active"><a href="Admin-Settings"><i class="fa-solid fa-gear"></i>Settings</a></li>
            <li><a href="Login.php"><i class="fa-solid fa-right-from-bracket"></i>Logout</a></li>
        </ul>
    </div>

    <!-- MAIN CONTENT -->
    <div class="main-content">
        <h2><i class="fa-solid fa-gear"></i> System Settings</h2>

        <div class="settings-container">
            <form action="#" method="post" enctype="multipart/form-data">

                <div class="form-row">
                    <div class="form-group">
                        <label for="system-name"><i class="fa-solid fa-computer"></i> System Name:</label>
                        <input type="text" id="system-name" name="system-name">
                    </div>

                    <div class="form-group">
                        <label for="email"><i class="fa-solid fa-envelope"></i> Email:</label>
                        <input type="email" id="email" name="email">
                    </div>
                </div>

                <div class="form-group">
                    <label for="contact"><i class="fa-solid fa-phone"></i> Contact:</label>
                    <input type="text" id="contact" name="contact">
                </div>

                <div class="form-group">
                    <label for="about"><i class="fa-solid fa-circle-info"></i> About:</label>
                    <textarea id="about" name="about" rows="5"></textarea>
                </div>

                <div class="form-group">
                    <label for="cover-image"><i class="fa-solid fa-image"></i> Cover Image:</label>
                    <input type="file" id="cover-image" name="cover-image">
                </div>

                <div class="form-actions">
                    <button type="submit"><i class="fa-solid fa-rotate-right"></i> Apply</button>
                </div>

            </form>
        </div>
    </div>
</body>
</html>
