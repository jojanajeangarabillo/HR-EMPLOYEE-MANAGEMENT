<?php
session_start();
require 'admin/db.connect.php';

$adminanmeQuery = $conn->query("SELECT fullname FROM user WHERE role = 'Admin'");
if ($adminanmeQuery && $row = $adminanmeQuery->fetch_assoc()) {
    $adminname = $row['fullname'];
}
?>

!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Applicants</title>
    <link rel="stylesheet" href="admin-sidebar.css">
    <!--For icons-->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/7.0.1/css/all.min.css"
        integrity="sha512-2SwdPD6INVrV/lHTZbO2nodKhrnDdJK9/kg2XD1r9uGqPo1cUbujc+IYdlYdEErWNu69gVcYgdxlmVmzTWnetw=="
        crossorigin="anonymous" referrerpolicy="no-referrer" />
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">


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
            margin-left: 250px;
            display: flex;
            flex-direction: column;
            color: #1e3a8a;
            margin-bottom: 350px;
        }

        .main-content-header h1 {
            margin: 0;
            font-size: 2rem;
            margin-bottom: 40px;
        }

        .controls {
            display: flex;
            gap: 15px;
            margin-bottom: 20px;
            align-items: center;
        }

        .search-box {
            flex: 1;
            position: relative;
            max-width: 350px;
        }

        .search-box input {
            width: 100%;
            padding: 10px 40px 10px 40px;
            border: 1px solid #d1d5db;
            border-radius: 25px;
            font-size: 14px;
            outline: none;
        }

        .search-box input:focus {
            border-color: #1e40af;
        }

        .search-icon {
            position: absolute;
            left: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: #6b7280;
        }

        .filter-btn,
        .export-btn {
            padding: 10px 20px;
            border: 1px solid #d1d5db;
            background: white;
            border-radius: 6px;
            cursor: pointer;
            font-size: 14px;
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 8px;
            transition: all 0.3s;
            position: relative;
        }

        .filter-btn:hover,
        .export-btn:hover {
            border-color: #1e40af;
            color: #1e40af;
        }

        .export-btn i {
            font-size: 16px;
        }

        .filter-panel {
            display: none;
            background: #e5e7eb;
            border-radius: 8px;
            padding: 25px;
            margin-bottom: 20px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }

        .filter-panel.active {
            display: block;
        }

        .filter-header {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 20px;
        }

        .filter-header i {
            color: #1e40af;
            font-size: 20px;
        }

        .filter-header span {
            font-weight: 600;
            color: #1f2937;
            font-size: 14px;
        }

        .filter-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 20px;
            margin-bottom: 20px;
        }

        .filter-group {
            display: flex;
            flex-direction: column;
            gap: 8px;
        }

        .filter-label {
            font-weight: 600;
            color: #1f2937;
            font-size: 13px;
        }

        .filter-select {
            padding: 10px 12px;
            border: 1px solid #d1d5db;
            border-radius: 6px;
            background: white;
            font-size: 13px;
            color: #1f2937;
            cursor: pointer;
            outline: none;
        }

        .filter-select:focus {
            border-color: #1e40af;
        }

        .filter-actions {
            display: flex;
            gap: 10px;
            justify-content: flex-end;
        }

        .apply-btn {
            padding: 10px 30px;
            background: #1e40af;
            color: white;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-size: 13px;
            font-weight: 600;
            transition: all 0.3s;
        }

        .apply-btn:hover {
            background: #1e3a8a;
        }

        .reset-btn {
            width: 40px;
            height: 40px;
            background: white;
            border: 1px solid #d1d5db;
            border-radius: 6px;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.3s;
        }

        .reset-btn i {
            color: #ef4444;
            font-size: 16px;
        }

        .reset-btn:hover {
            border-color: #ef4444;
            background: #fef2f2;
        }

        .table-container {
            background: white;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
            margin-bottom: 100px;
            overflow-x: auto;
        }

        .table {
            min-width: 1400px;
        }

        /* Bootstrap Table Customization */
        .table {
            margin-bottom: 0;
        }

        .table thead th {
            background: #1e40af;
            color: white;
            font-weight: 600;
            font-size: 13px;
            border: none;
            padding: 15px;
            vertical-align: middle;
        }

        .table tbody tr {
            border-bottom: 1px solid #e5e7eb;
        }

        .table tbody td {
            padding: 15px;
            font-size: 13px;
            vertical-align: middle;
        }

        .table-hover tbody tr:hover {
            background-color: #f9fafb;
        }

        .action-column {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .status-cell {
            position: relative;
        }

        .status-dropdown-container {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 6px 12px;
            border-radius: 6px;
            cursor: pointer;
            transition: all 0.3s;
            min-width: 160px;
            justify-content: space-between;
        }

        .status-dropdown-container:hover {
            opacity: 0.9;
            transform: translateY(-1px);
        }

        .view-btn {
            padding: 8px 20px;
            background: #fbbf24;
            color: white;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-size: 12px;
            font-weight: 600;
            transition: all 0.3s;
        }

        .view-btn:hover {
            background: #f59e0b;
        }

        .status-badge {
            font-size: 12px;
            font-weight: 600;
        }

        .status-submitted {
            background: #9ca3af;
            color: white;
        }

        .status-under-review {
            background: #3b82f6;
            color: white;
        }

        .status-shortlisted {
            background: #8b5cf6;
            color: white;
        }

        .status-assessment {
            background: #06b6d4;
            color: white;
        }

        .status-interview-scheduled {
            background: #10b981;
            color: white;
        }

        .status-interview {
            background: #16a34a;
            color: white;
        }

        .status-interview-completed {
            background: #059669;
            color: white;
        }

        .status-final-interview {
            background: #047857;
            color: white;
        }

        .status-offer-made {
            background: #84cc16;
            color: white;
        }

        .status-hired {
            background: #22c55e;
            color: white;
        }

        .status-rejected {
            background: #dc2626;
            color: white;
        }

        .status-on-hold {
            background: #f59e0b;
            color: white;
        }

        .status-withdrawn {
            background: #6b7280;
            color: white;
        }

        .dropdown-icon {
            font-size: 14px;
            cursor: pointer;
            transition: transform 0.3s;
        }

        .dropdown-icon.open {
            transform: rotate(180deg);
        }

        .status-dropdown-menu {
            position: absolute;
            top: 100%;
            left: 0;
            background: white;
            border: 1px solid #d1d5db;
            border-radius: 8px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            z-index: 1000;
            min-width: 160px;
            margin-top: 5px;
            display: none;
        }

        .status-dropdown-menu.active {
            display: block;
        }

        .status-option {
            padding: 10px 15px;
            cursor: pointer;
            font-size: 13px;
            color: #1f2937;
            transition: background 0.2s;
            border-bottom: 1px solid #f3f4f6;
        }

        .status-option:last-child {
            border-bottom: none;
        }

        .status-option:hover {
            background: #f3f4f6;
        }

        /* Profile Page Styles */
        .profile-container {
            display: none;
        }

        .profile-container.active {
            display: block;
            max-height: calc(100vh - 60px);
            /* Adjust based on header height */
            overflow-y: auto;
            padding-right: 10px;
            /* optional, to avoid scrollbar overlapping content */
        }

        .profile-card {
            max-width: 800px;
            margin: 0 auto;
        }

        .profile-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
        }

        .export-buttons {
            display: flex;
            gap: 10px;
        }

        .export-pdf-btn,
        .export-excel-btn {
            padding: 10px 20px;
            border: 1px solid #d1d5db;
            background: white;
            border-radius: 6px;
            cursor: pointer;
            font-size: 14px;
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 8px;
            transition: all 0.3s;
        }

        .export-pdf-btn:hover,
        .export-excel-btn:hover {
            border-color: #1e40af;
        }

        .profile-card {
            background: white;
            border-radius: 12px;
            padding: 40px;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
            max-width: 800px;
            margin: 0 auto;
        }

        .profile-top {
            display: flex;
            gap: 30px;
            margin-bottom: 40px;
            padding-bottom: 30px;
            border-bottom: 2px solid #e5e7eb;
        }

        .profile-avatar {
            width: 120px;
            height: 120px;
            border-radius: 50%;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }

        .profile-avatar i {
            font-size: 60px;
            color: white;
        }

        .profile-info {
            flex: 1;
        }

        .profile-name {
            font-size: 32px;
            font-weight: 700;
            color: #1e40af;
            margin-bottom: 15px;
        }

        .profile-contact {
            display: flex;
            flex-direction: column;
            gap: 8px;
        }

        .contact-item {
            display: flex;
            align-items: center;
            gap: 10px;
            color: #4b5563;
            font-size: 14px;
        }

        .contact-item i {
            width: 20px;
            color: #1e40af;
        }

        .back-btn {
            padding: 12px 30px;
            background: #1e40af;
            color: white;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-size: 14px;
            font-weight: 600;
            transition: all 0.3s;
            margin-top: 30px;
            display: block;
            margin-left: auto;
            margin-right: auto;
        }

        .back-btn:hover {
            background: #1e3a8a;
        }

        .list-view {
            display: block;
        }

        .list-view.hidden {
            display: none;
        }
    </style>
</head>

<body>
      <!-- SIDEBAR -->
  <div class="sidebar d-flex flex-column align-items-center position-fixed top-0 start-0 h-100 p-3">
  <div class="text-center mb-4">
    <img src="Images/hospitallogo.png" alt="Hospital Logo" class="img-fluid rounded-circle mb-3" style="width:75px; height:75px;">
    <p class="text-white fw-semibold mb-0">
      <?php echo "Welcome, $adminname"; ?>
    </p>
  </div>

  <nav class="nav flex-column w-100">
    <a href="Admin_Dashboard.php" class="nav-link  d-flex align-items-center text-white py-2 px-3">
      <i class="fa-solid fa-table-columns me-2"></i>Dashboard
    </a>
    <a href="Admin_Employee.php" class="nav-link d-flex align-items-center text-white py-2 px-3">
      <i class="fa-solid fa-user-group me-2"></i>Employees
    </a>
    <a href="Admin-Applicants.php" class="nav-link d-flex align-items-center text-white py-2 px-3">
      <i class="fa-solid fa-user-group me-2"></i>Applicants
    </a>
    <a href="Admin-Pending-Applicants.php" class="nav-link active d-flex align-items-center text-white py-2 px-3">
      <i class="fa-solid fa-user-clock me-2"></i>Pending Applicants
    </a>
    <a href="Admin_Vacancies.php" class="nav-link d-flex align-items-center text-white py-2 px-3">
      <i class="fa-solid fa-briefcase me-2"></i>Vacancies
    </a>
    <a href="Admin-request.php" class="nav-link d-flex align-items-center text-white py-2 px-3">
      <i class="fa-solid fa-code-pull-request me-2"></i>Requests
    </a>
    <a href="#" class="nav-link d-flex align-items-center text-white py-2 px-3">
      <i class="fa-solid fa-chart-simple me-2"></i>Reports
    </a>
    <a href="Admin-Settings.php" class="nav-link d-flex align-items-center text-white py-2 px-3">
      <i class="fa-solid fa-gear me-2"></i>Settings
    </a>
    <a href="Login.php" class="nav-link d-flex align-items-center text-white py-2 px-3">
      <i class="fa-solid fa-right-from-bracket me-2"></i>Logout
    </a>
  </nav>
</div>

    <main class="main-content">
        <div class="main-content-header">
            <h1>Pending Applicants</h1>
        </div>
        <div id="listView" class="list-view">
            <div class="controls">
                <div class="search-box">
                    <i class="fa-solid fa-magnifying-glass search-icon"></i>
                    <input type="text" placeholder="Search Applicant" id="searchInput">
                </div>
                <button class="filter-btn" onclick="toggleFilter()">
                    Filter <i class="fa-solid fa-filter"></i>
                </button>
                <button class="export-btn">
                    Export As
                    <i class="fa-solid fa-file-pdf" style="color: #ef4444;"></i>
                    <i class="fa-solid fa-file-excel" style="color: #10b981;"></i>
                </button>
            </div>

            <div class="filter-panel" id="filterPanel">
                <div class="filter-header">
                    <i class="fa-solid fa-filter"></i>
                    <span>Show/Hide Filter</span>
                </div>
                <div class="filter-grid">
                    <div class="filter-group">
                        <label class="filter-label">Department:</label>
                        <select class="filter-select" id="departmentFilter">
                            <option value="">Nursing</option>
                            <option value="it">Information Technology</option>
                            <option value="admin">Admin</option>
                            <option value="hr">Human Resources</option>
                            <option value="finance">Finance</option>
                        </select>
                    </div>
                    <div class="filter-group">
                        <label class="filter-label">Type:</label>
                        <select class="filter-select" id="typeFilter">
                            <option value="">Leave</option>
                            <option value="full-time">Full Time</option>
                            <option value="part-time">Part Time</option>
                            <option value="contract">Contract</option>
                        </select>
                    </div>
                    <div class="filter-group">
                        <label class="filter-label">Remarks:</label>
                        <select class="filter-select" id="remarksFilter">
                            <option value="">Approved</option>
                            <option value="pending">Pending</option>
                            <option value="rejected">Rejected</option>
                        </select>
                    </div>
                </div>
                <div class="filter-actions">
                    <button class="apply-btn">Apply</button>
                    <button class="reset-btn" title="Reset Filters">
                        <i class="fa-solid fa-rotate-right"></i>
                    </button>
                </div>
            </div>

            <div class="table-container">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>Applicant ID</th>
                            <th>Full Name</th>
                            <th>Position Applied</th>
                            <th>Department</th>
                            <th>Date Applied</th>
                            <th>Action</th>
                            <th>Application Status</th>
                        </tr>
                    </thead>
                    <tbody id="applicantTable">
                        <tr>
                            <td>25-0001</td>
                            <td>John Smith</td>
                            <td>IT Support Specialist</td>
                            <td>Information Technology</td>
                            <td>October 15, 2025</td>
                            <td>
                                <div class="action-column">
                                    <button class="view-btn" onclick="viewApplicant('25-0001', 'John Smith')">View
                                        Applicant</button>
                                </div>
                            </td>
                            <td class="status-cell">
                                <div class="status-dropdown-container status-interview"
                                    onclick="toggleStatusDropdown(this, event)">
                                    <span class="status-badge">Interview</span>
                                    <i class="fa-solid fa-chevron-down dropdown-icon"></i>
                                </div>
                                <div class="status-dropdown-menu">
                                    <div class="status-option"
                                        onclick="changeStatus(this, 'Interview', 'status-interview')">Interview</div>
                                    <div class="status-option"
                                        onclick="changeStatus(this, 'Rejected', 'status-rejected')">Rejected</div>
                                </div>
                            </td>
                        </tr>
                        <tr>
                            <td>25-0002</td>
                            <td>Garabillo, Jojana Jean</td>
                            <td>Admin Moderator</td>
                            <td>Admin</td>
                            <td>October 15, 2025</td>
                            <td>
                                <div class="action-column">
                                    <button class="view-btn"
                                        onclick="viewApplicant('25-0002', 'Garabillo, Jojana Jean')">View
                                        Applicant</button>
                                </div>
                            </td>
                            <td class="status-cell">
                                <div class="status-dropdown-container status-rejected"
                                    onclick="toggleStatusDropdown(this, event)">
                                    <span class="status-badge">Rejected</span>
                                    <i class="fa-solid fa-chevron-down dropdown-icon"></i>
                                </div>
                                <div class="status-dropdown-menu">
                                    <div class="status-option"
                                        onclick="changeStatus(this, 'Interview', 'status-interview')">Interview</div>
                                    <div class="status-option"
                                        onclick="changeStatus(this, 'Rejected', 'status-rejected')">Rejected</div>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

    </main>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Toggle filter panel
        function toggleFilter() {
            const filterPanel = document.getElementById('filterPanel');
            filterPanel.classList.toggle('active');
        }

        // Search functionality
        const searchInput = document.getElementById('searchInput');
        const table = document.getElementById('applicantTable');
        const rows = table.getElementsByTagName('tr');

        searchInput.addEventListener('keyup', function () {
            const filter = searchInput.value.toLowerCase();

            for (let i = 0; i < rows.length; i++) {
                const cells = rows[i].getElementsByTagName('td');
                let found = false;

                for (let j = 0; j < cells.length; j++) {
                    if (cells[j].textContent.toLowerCase().indexOf(filter) > -1) {
                        found = true;
                        break;
                    }
                }

                rows[i].style.display = found ? '' : 'none';
            }
        });

        // View Applicant Function
        function viewApplicant(id, name) {
            document.getElementById('listView').classList.add('hidden');
            document.getElementById('profileView').classList.add('active');
            document.getElementById('profileName').textContent = name;
            window.scrollTo(0, 0);
        }

        // Back to List Function
        function backToList() {
            document.getElementById('profileView').classList.remove('active');
            document.getElementById('listView').classList.remove('hidden');
            window.scrollTo(0, 0);
        }

        // Dropdown functionality
        document.querySelectorAll('.dropdown-icon').forEach(icon => {
            icon.addEventListener('click', function (e) {
                e.stopPropagation();
                alert('Dropdown menu would appear here with options');
            });
        });

        // Toggle status dropdown
        function toggleStatusDropdown(container, event) {
            event.stopPropagation();

            // Close all other dropdowns
            document.querySelectorAll('.status-dropdown-menu').forEach(menu => {
                if (menu !== container.nextElementSibling) {
                    menu.classList.remove('active');
                }
            });

            document.querySelectorAll('.dropdown-icon').forEach(icon => {
                if (icon !== container.querySelector('.dropdown-icon')) {
                    icon.classList.remove('open');
                }
            });

            // Toggle current dropdown
            const menu = container.nextElementSibling;
            const icon = container.querySelector('.dropdown-icon');
            menu.classList.toggle('active');
            icon.classList.toggle('open');
        }

        // Change status
        function changeStatus(option, statusText, statusClass) {
            event.stopPropagation();

            const menu = option.parentElement;
            const container = menu.previousElementSibling;
            const icon = container.querySelector('.dropdown-icon');

            // Remove all status color classes from container
            container.className = 'status-dropdown-container';

            // Add new status class to container
            container.classList.add(statusClass);

            // Update badge text
            container.querySelector('.status-badge').textContent = statusText;

            // Close dropdown
            menu.classList.remove('active');
            icon.classList.remove('open');
        }

        // Close dropdowns when clicking outside
        document.addEventListener('click', function () {
            document.querySelectorAll('.status-dropdown-menu').forEach(menu => {
                menu.classList.remove('active');
            });
            document.querySelectorAll('.dropdown-icon').forEach(icon => {
                icon.classList.remove('open');
            });
        });
    </script>

</body>

</html>