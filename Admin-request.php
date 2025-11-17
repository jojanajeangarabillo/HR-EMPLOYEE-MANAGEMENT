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
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">

    <style>
        body {
            font-family: 'Poppins', 'Roboto', sans-serif;
            margin: 0;
            display: flex;
            background-color: #f1f5fc;
            color: #111827;
        }

        .request-container {
            margin-left: 220px;
            /* space for sidebar */
            padding: 40px 30px;
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

        .request-header h1 {
            font-size: 2rem;
            margin: 0;
            color: #1E3A8A;
        }

        .show-filter-btn {
            padding: 6px 12px;
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
            display: none;
            margin-bottom: 20px;
            padding: 15px;
            background-color: #ffffff;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
            display: flex;
            flex-wrap: wrap;
            gap: 15px;
            align-items: flex-start;
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
            width: 100%;
            display: flex;
            gap: 10px;
            margin-top: 10px;
        }

        .filter-buttons button {
            padding: 6px 12px;
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

        /* STATUS BADGES */
        .badge-status-approved {
            background-color: #10b981;
        }

        .badge-status-pending {
            background-color: #f59e0b;
            color: #000;
        }

        .badge-status-rejected {
            background-color: #ef4444;
        }

        /* TABLE CUSTOMIZATION */
        .table-hover tbody tr:hover {
            background-color: #f8fafc;
            transition: background-color 0.2s ease;
        }

        .badge {
            font-size: 0.9rem;
            padding: 0.5em 0.8em;
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
            <p><?php echo "Welcome, $adminname"; ?></p>
        </div>

        <ul class="nav flex-column">
            <li><a href="Admin_Dashboard.php"><i class="fa-solid fa-table-columns"></i>Dashboard</a></li>

            <li><a href="Admin_Vacancies.php"><i class="fa-solid fa-briefcase"></i>Vacancies</a></li>
            <li class="active"><a href="Admin-request.php"><i class="fa-solid fa-code-pull-request"></i>Requests</a>
            </li>
            <li><a href="Admin_Reports.php"><i class="fa-solid fa-chart-simple"></i>Reports</a></li>
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

            <div class="filter-buttons">
                <button class="apply-btn"><i class="fa-solid fa-magnifying-glass"></i> Apply</button>
                <button class="reset-btn"><i class="fas fa-rotate-right"></i> Reset</button>
            </div>
        </div>

        <!-- TABLE -->
        <div class="table-responsive mt-4">
            <table class="table table-hover align-middle shadow-sm">
                <thead class="table-primary text-white" style="background-color: #1E3A8A;">
                    <tr>
                        <th scope="col">Employee ID</th>
                        <th scope="col">Employee Name</th>
                        <th scope="col">Department</th>
                        <th scope="col">Request Type</th>
                        <th scope="col">Reason</th>
                        <th scope="col">Date</th>
                        <th scope="col">Remarks</th>
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
                        <td><span class="badge badge-status-approved">Approved</span></td>
                    </tr>
                    <tr>
                        <td>002</td>
                        <td>Jhanna Jaroda</td>
                        <td>Nursing</td>
                        <td>Leave</td>
                        <td>Sick</td>
                        <td>11/18/2025 - 11/25/2025</td>
                        <td><span class="badge badge-status-pending">Pending</span></td>
                    </tr>
                    <tr>
                        <td>003</td>
                        <td>Jodie Gutierrez</td>
                        <td>HR</td>
                        <td>Leave</td>
                        <td>Vacation</td>
                        <td>10/18/2025 - 10/25/2025</td>
                        <td><span class="badge badge-status-approved">Approved</span></td>
                    </tr>
                </tbody>
            </table>
        </div>
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