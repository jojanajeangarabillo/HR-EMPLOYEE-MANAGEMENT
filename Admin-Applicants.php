<!DOCTYPE html>
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


        .main-content {
            padding: 40px 30px;
            margin-left: 220px;
            display: flex;
            flex-direction: column
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
            min-height: 600px;
            padding-bottom: 50px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        thead {
            background: #1e40af;
            color: white;
        }

        th {
            padding: 15px;
            text-align: left;
            font-weight: 600;
            font-size: 13px;
        }

        tbody tr {
            background: #3b82f6;
            color: white;
            border-bottom: 8px solid #f3f4f6;
            transition: background 0.3s;
        }

        tbody tr:hover {
            background: #2563eb;
        }

        td {
            padding: 15px;
            font-size: 13px;
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

        .profile-section {
            margin-bottom: 35px;
        }

        .section-title {
            font-size: 20px;
            font-weight: 700;
            color: #1e40af;
            margin-bottom: 15px;
            padding-bottom: 10px;
            border-bottom: 2px solid #e5e7eb;
        }

        .job-entry {
            margin-bottom: 25px;
        }

        .job-title {
            font-size: 16px;
            font-weight: 600;
            color: #1f2937;
            margin-bottom: 5px;
        }

        .job-company {
            font-size: 14px;
            color: #6b7280;
            margin-bottom: 10px;
        }

        .job-details {
            list-style: none;
            padding-left: 0;
        }

        .job-details li {
            padding-left: 20px;
            position: relative;
            color: #4b5563;
            font-size: 14px;
            line-height: 1.8;
        }

        .job-details li:before {
            content: "•";
            position: absolute;
            left: 5px;
            color: #1e40af;
            font-weight: bold;
        }

        .education-entry {
            margin-bottom: 10px;
        }

        .degree {
            font-size: 16px;
            font-weight: 600;
            color: #1f2937;
        }

        .university {
            font-size: 14px;
            color: #6b7280;
            margin-top: 3px;
        }

        .graduation {
            font-size: 13px;
            color: #9ca3af;
            margin-top: 3px;
        }

        .skills-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 15px;
        }

        .skill-item {
            padding-left: 20px;
            position: relative;
            color: #4b5563;
            font-size: 14px;
        }

        .skill-item:before {
            content: "•";
            position: absolute;
            left: 5px;
            color: #1e40af;
            font-weight: bold;
        }

        .summary-text {
            color: #4b5563;
            font-size: 14px;
            line-height: 1.8;
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
    <div class="sidebar">

        <div class="sidebar-logo">
            <img src="Images/hospitallogo.png" alt="">
        </div>

        <ul class="nav">
            <li><a href="Admin_Dashboard.php"><i class="fa-solid fa-table-columns"></i>Dashboard</a>
            </li>
            <li><a href="Admin_Employee.php"><i class="fa-solid fa-user-group"></i>Employees</a></li>
            <li class="active"><a href="Admin-Applicants.php"><i class="fa-solid fa-user-group"></i>Applicants</a></li>
            <li><a href="#"><i class="fa-solid fa-user-group"></i>Pending Applicants</a></li>
            <li><a href="#"><i class="fa-solid fa-briefcase"></i>Vacancies</a></li>
            <li><a href="Admin-request.php"><i class="fa-solid fa-code-pull-request"></i>Requests</a></li>
            <li><a href="Admin-Settings.php"><i class="fa-solid fa-gear"></i>Settings</a></li>
            <li><a href="Login.php"><i class="fa-solid fa-right-from-bracket"></i>Logout</a></li>
        </ul>
    </div>

    <main class="main-content">
        <div class="main-content-header">
            <h1>Applicant List</h1>
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
                <table>
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


</body>

</html>