
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
    <title>Employee Requests</title>

    <!-- Global stylesheet (for sidebar & header) -->
    <link rel="stylesheet" href="admin-sidebar.css">

    <!-- Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/7.0.1/css/all.min.css">

    <!-- jQuery for interactivity -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

    <style>
        body {
            font-family: 'Poppins', 'Roboto', sans-serif;
            margin: 0;
            display: flex;
            background-color: #f1f5fc;
            color: #111827;
        }
        .request-container {
            margin-left: 220px; /* space for sidebar */
            padding: 40px 30px; /* top/bottom and left/right spacing */
            background-color: #f1f5fc;
            min-height: 100vh;
            box-sizing: border-box;
        }

        /* HEADER */
        .request-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
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

        .request-header h1 {
            font-size: 2rem;
            margin: 0;
            color: #1E3A8A;
        }

        .show-filter-btn {
            padding: 6px 12px; /* normal size */
            font-size: 14px;
            background-color: #1E3A8A;
            color: white;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .show-filter-btn:hover {
            background-color: #1e40af;
        }

        /* FILTER BOX */
        .filter-box {
            display: none; /* initially hidden */
            margin-bottom: 20px;
            padding: 15px;
            background-color: #ffffff;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
            display: flex;
            flex-wrap: wrap;
            gap: 15px;
            align-items: flex-start; /* ensures buttons start on new row */
        }

        .filter-box label {
            font-weight: 500;
        }

        .filter-box select {
            padding: 6px 10px;
            border-radius: 6px;
            border: 1px solid #e0e0e0;
            font-size: 14px;
        }

        .filter-buttons {
            width: 100%;           /* full width so buttons appear below selects */
            display: flex;
            gap: 10px;
            margin-top: 10px;
        }

        .filter-buttons button {
            padding: 6px 12px; /* normal size */
            font-size: 14px;
            border-radius: 6px;
            border: none;
            cursor: pointer;
        }

        .apply-btn {
            background-color: #1E3A8A;
            color: white;
        }

        .apply-btn:hover {
            background-color: #1e40af;
        }

        .reset-btn {
            background-color: #e5e7eb;
            color: #111827;
        }

        .reset-btn:hover {
            background-color: #d1d5db;
        }

        /* TABLE */
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
            background-color: #fff;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
        }

        th,
        td {
            padding: 20px 24px; /* wider cells */
            text-align: center;
            border: 1px solid #e0e0e0;
            min-width: 150px;
        }

        thead {
            background-color: #1E3A8A;
            color: white;
            font-weight: 600;
        }

        tbody tr:nth-child(even) {
            background-color: #fafafa;
        }

        tbody tr:hover {
            background-color: #f8f9fa;
        }

        /* STATUS COLORS */
        .status-approved {
            color: #10b981;
            font-weight: 600;
        }

        .status-pending {
            color: #f59e0b;
            font-weight: 600;
        }

        .status-rejected {
            color: #ef4444;
            font-weight: 600;
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

     <div class="sidebar">

        <div class="sidebar-logo">
            <img src="Images/hospitallogo.png" alt="">
        </div>

        <div class="sidebar-name">
            <p><?php echo "Welcome, $adminname"; ?></p>
        </div>


        <ul class="nav">
            <li><a href="Admin_Dashboard.php"><i class="fa-solid fa-table-columns"></i>Dashboard</a></li>
            <li><a href="Admin_Employee.php"><i class="fa-solid fa-user-group"></i>Employees</a></li>
            <li><a href="Admin-Applicants.php"><i class="fa-solid fa-user-group"></i>Applicants</a></li>
            <li><a href="Admin-Pending-Applicants.php"><i class="fa-solid fa-user-group"></i>Pending Applicants</a></li>
            <li><a href="Admin_Vacancies.php"><i class="fa-solid fa-briefcase"></i>Vacancies</a></li>
            <li class="active"><a href="Admin-request.php"><i class="fa-solid fa-code-pull-request"></i>Requests</a></li>
            <li><a href="#"><i class="fa-solid fa-chart-simple"></i>Reports</a></li>
            <li><a href="Admin-Settings.php"><i class="fa-solid fa-gear"></i>Settings</a></li>
            <li><a href="Login.php"><i class="fa-solid fa-right-from-bracket"></i>Logout</a></li>
        </ul>
    </div>

    <!-- MAIN CONTENT -->
    <main class="request-container">
        <div class="request-header">
            <h1><i class="fa-solid fa-person-circle-check"></i> Employee Request</h1>
            <button class="show-filter-btn"><i class="fa-solid fa-filter"></i> Show/Hide Filter</button>
        </div>

        <!-- FILTER BOX -->
        <div class="filter-box">
            <label>Department:</label>
            <select id="department">
                <option value="">Select</option>
                <option>Anesthetics</option>
                <option>Breast Screening</option>
                <option>Cardiology</option>
                <option>Ear, Nose & Throat</option>
                <option>Elderly Services</option>
                <option>Gastroenterology</option>
                <option>General Surgery</option>
                <option>Gynecology</option>
                <option>Hematology</option>
            </select>

            <label>Type:</label>
            <select id="type">
                <option value="">Select</option>
                <option>Leave</option>
                <option>Resignation</option>
                <option>Certificate</option>
            </select>

            <label>Remarks:</label>
            <select id="remarks">
                <option value="">Select</option>
                <option>Approved</option>
                <option>Pending</option>
                <option>Rejected</option>
            </select>

            <!-- Buttons now inside the filter box -->
            <div class="filter-buttons">
                <button class="apply-btn"><i class="fa-solid fa-magnifying-glass"></i> Apply</button>
                <button class="reset-btn"><i class="fas fa-rotate-right"></i> Reset</button>
            </div>
        </div>

        <!-- TABLE -->
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
                    <td>10/18/2025 - 10/25/2025</td>
                    <td class="status-approved">Approved</td>
                </tr>
                <tr>
                    <td>002</td>
                    <td>Jhanna Jaroda</td>
                    <td>Nursing</td>
                    <td>Leave</td>
                    <td>Sick</td>
                    <td>11/18/2025 - 11/25/2025</td>
                    <td class="status-pending">Pending</td>
                </tr>
                <tr>
                    <td>003</td>
                    <td>Jodie Gutierrez</td>
                    <td>HR</td>
                    <td>Leave</td>
                    <td>Vacation</td>
                    <td>10/18/2025 - 10/25/2025</td>
                    <td class="status-approved">Approved</td>
                </tr>
            </tbody>
        </table>
    </main>

    <!-- FILTER TOGGLE SCRIPT -->
    <script>
        $(document).ready(function () {
            $(".show-filter-btn").click(function () {
                $(".filter-box").slideToggle();
            });
        });
    </script>

</body>

</html>
