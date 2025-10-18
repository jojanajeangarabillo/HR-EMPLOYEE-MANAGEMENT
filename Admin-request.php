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

            <button class="apply-btn"><i class="fa-solid fa-magnifying-glass"></i> Apply</button>
              <button class="reset-btn">
    <i class="fas fa-rotate-right"></i> Reset
  </button>

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
        $(document).ready(function(){
            $(".show-filter-btn").click(function(){
                $(".filter-box").slideToggle();
            });
        });
    </script>

</body>
</html>
