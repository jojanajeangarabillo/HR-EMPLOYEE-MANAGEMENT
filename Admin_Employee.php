<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Hospital Employee Management</title>
    <link rel="stylesheet" href="admin-sidebar.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/7.0.1/css/all.min.css"
        integrity="sha512-2SwdPD6INVrV/lHTZbO2nodKhrnDdJK9/kg2XD1r9uGqPo1cUbujc+IYdlYdEErWNu69gVcYgdxlmVmzTWnetw=="
        crossorigin="anonymous" referrerpolicy="no-referrer" />
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            display: flex;
            height: 100vh;
            overflow: hidden;
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

        /* Table header */
        .custom-table thead th {
            font-size: 12px;
            /* smaller text */
            padding: 8px 10px;
            /* smaller padding */
            background-color: #1e40af !important;
            color: white !important;
            font-weight: 600;
            text-align: center;
            /* center-align header text */
        }

        /* Table body rows */
        .custom-table tbody tr {
            background-color: #3b82f6;
            color: white;
            border-bottom: 12px solid #f3f4f6;
        }

        .custom-table tbody tr:hover {
            background-color: #2563eb;
        }

        /* Reduce padding for thinner rows */
        .custom-table th,
        .custom-table td {
            padding: 1rem 1.4rem;
        }

        /* Table width */
        .table-container {
            width: 100%;
            max-width: 100%;
            margin: 0;
            padding: 0;
            background: white;
            border-radius: 0;
            overflow: hidden;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
        }

        .custom-table {
            width: 100%;
            border-collapse: collapse;
        }

        .main-content {
            flex: 1;
            background: #f3f4f6;
            overflow-y: auto;
            padding: 30px;
        }

        .header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 30px;
        }

        .header-title {
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .header-title h1 {
            font-size: 28px;
            color: #1e40af;
            font-weight: 700;
        }

        .add-btn {
            background: #1e40af;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 6px;
            cursor: pointer;
            font-size: 14px;
            font-weight: 600;
            transition: background 0.3s;
        }

        .add-btn:hover {
            background: #1e3a8a;
        }

        .controls {
            display: flex;
            gap: 15px;
            margin-bottom: 20px;
            align-items: center;
            flex-wrap: wrap;
        }

        .search-box {
            flex: 1;
            position: relative;
            min-width: 300px;
            max-width: 400px;
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

        .reset-filter-btn {
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

        .reset-filter-btn i {
            color: #ef4444;
            font-size: 16px;
        }

        .reset-filter-btn:hover {
            border-color: #ef4444;
            background: #fef2f2;
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
            cursor: pointer;
            transition: background 0.3s;
        }

        tbody tr:hover {
            background: #2563eb;
        }

        td {
            padding: 15px;
            font-size: 13px;
        }

        .action-btns {
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 8px;
        }

        .edit-btn,
        .delete-btn {
            width: 32px;
            height: 32px;
            border: none;
            border-radius: 4px;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            padding: 0;
            margin: 0;
            font-size: 0;
            transition: transform 0.2s;
        }

        .edit-btn i,
        .delete-btn i {
            font-size: 14px;
            display: block;
            line-height: 1;
        }

        .edit-btn {
            background: #fbbf24;
            color: white;
        }

        .delete-btn {
            background: #ef4444;
            color: white;
        }

        .edit-btn:hover,
        .delete-btn:hover {
            transform: scale(1.1);
        }

        .profile-form {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 20px;
        }


        .profile-container {
            display: none !important;
        }

        .profile-container.active {
            display: block !important;
        }

        .profile-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
        }

        .profile-title {
            font-size: 28px;
            color: #1e40af;
            font-weight: 700;
        }

        .profile-card {
            background: #3b82f6;
            border-radius: 12px;
            padding: 30px;
            color: white;
            margin-bottom: 30px;
        }

        .profile-top {
            display: flex;
            gap: 30px;
            margin-bottom: 30px;
            align-items: flex-start;
        }

        .profile-avatar {
            width: 100px;
            height: 100px;
            background: white;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }

        .profile-avatar i {
            font-size: 50px;
            color: #3b82f6;
        }

        .profile-info h2 {
            font-size: 24px;
            margin-bottom: 10px;
            font-weight: 700;
            text-transform: uppercase;
        }

        .profile-info p {
            margin-bottom: 5px;
            font-size: 14px;
        }

        .profile-form {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 20px;
        }


        .form-group {
            display: flex;
            flex-direction: column;
            gap: 8px;
        }

        .form-group label {
            font-size: 13px;
            font-weight: 500;
        }

        .form-group input,
        .form-group select {
            padding: 10px 15px;
            border: none;
            border-radius: 6px;
            font-size: 14px;
            outline: none;
            color: #1f2937;
        }

        .form-actions {
            display: flex;
            justify-content: flex-end;
            gap: 15px;
            margin-top: 20px;
        }

        .reset-btn,
        .save-btn {
            padding: 12px 30px;
            border: none;
            border-radius: 6px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
        }

        .reset-btn {
            background: #dc2626;
            color: white;
        }

        .reset-btn:hover {
            background: #b91c1c;
        }

        .save-btn {
            background: #16a34a;
            color: white;
        }

        .save-btn:hover {
            background: #15803d;
        }

        .back-btn {
            padding: 12px 30px;
            background: #1e40af;
            color: white;
            border: none;
            border-radius: 6px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            transition: background 0.3s;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .back-btn:hover {
            background: #1e3a8a;
        }

        .hidden {
            display: none !important;
        }
    </style>
</head>

<body>
    <div class="sidebar">

        <div class="sidebar-logo">
            <img src="Images/hospitallogo.png" alt="">
        </div>

        <ul class="nav">
            <li class="active"><a href="Admin_Dashboard.php"><i class="fa-solid fa-table-columns"></i>Dashboard</a>
            </li>
            <li><a href="Admin_Employee.php"><i class="fa-solid fa-user-group"></i>Employees</a></li>
            <li><a href="Admin-Applicants.php"><i class="fa-solid fa-user-group"></i>Applicants</a></li>
            <li><a href="Admin-Pending-Applicants"><i class="fa-solid fa-user-group"></i>Pending Applicants</a></li>
            <li><a href="#"><i class="fa-solid fa-briefcase"></i>Vacancies</a></li>
            <li><a href="Admin-request.php"><i class="fa-solid fa-code-pull-request"></i>Requests</a></li>
             <li><a href="#"><i class="fa-solid fa-clipboard-list"></i>Reports</a></li>
            <li><a href="Admin-Settings.php"><i class="fa-solid fa-gear"></i>Settings</a></li>
            <li><a href="Login.php"><i class="fa-solid fa-right-from-bracket"></i>Logout</a></li>
        </ul>
    </div>

    <div class="main-content">
        <div id="employeeListView">
            <div class="header">
                <div class="header-title">
                    <h1>Employee List</h1>
                </div>
                <button class="add-btn">+ Add Employee</button>
            </div>

            <div class="controls">
                <div class="search-box">
                    <i class="fa-solid fa-magnifying-glass search-icon"></i>
                    <input type="text" placeholder="Search Employee" id="searchInput">
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
                            <option value="">All Departments</option>
                            <option value="Admin">Admin</option>
                            <option value="Medical">Medical</option>
                            <option value="Nursing">Nursing</option>
                            <option value="HR">HR</option>
                            <option value="IT">Information Technology</option>
                        </select>
                    </div>
                    <div class="filter-group">
                        <label class="filter-label">Type:</label>
                        <select class="filter-select" id="typeFilter">
                            <option value="">All Types</option>
                            <option value="Regular">Regular</option>
                            <option value="Probationary">Probationary</option>
                            <option value="Contract">Contract</option>
                            <option value="Leave">Leave</option>
                        </select>
                    </div>
                    <div class="filter-group">
                        <label class="filter-label">Remarks:</label>
                        <select class="filter-select" id="remarksFilter">
                            <option value="">All Remarks</option>
                            <option value="Approved">Approved</option>
                            <option value="Pending">Pending</option>
                            <option value="Rejected">Rejected</option>
                            <option value="In Review">In Review</option>
                        </select>
                    </div>
                </div>
                <div class="filter-actions">
                    <button class="apply-btn" onclick="applyFilters()">Apply</button>
                    <button class="reset-filter-btn" onclick="resetFilters()" title="Reset Filters">
                        <i class="fa-solid fa-rotate-right"></i>
                    </button>
                </div>
            </div>

            <div class="table-container py-4">
                <div class="table-responsive">
                    <table class="table align-middle table-borderless text-center custom-table">
                        <thead>
                            <tr>
                                <th style="background-color: #1e40af; color: white;">ID</th>
                                <th style="background-color: #1e40af; color: white;">Full Name</th>
                                <th style="background-color: #1e40af; color: white;">Department</th>
                                <th style="background-color: #1e40af; color: white;">Position</th>
                                <th style="background-color: #1e40af; color: white;">Employment Type</th>
                                <th style="background-color: #1e40af; color: white;">Email Address</th>
                                <th style="background-color: #1e40af; color: white;">Remarks</th>
                                <th style="background-color: #1e40af; color: white;">Action</th>
                            </tr>
                        </thead>
                        <tbody id="employeeTable">
                            <tr>
                                <td>25-0001</td>
                                <td>Garabillo, Jojana Jean</td>
                                <td>Admin</td>
                                <td>Admin Moderator</td>
                                <td>Regular</td>
                                <td>garabillo_jojana@gmail.com</td>
                                <td>Approved</td>
                                <td>
                                    <div class="action-btns">
                                        <button class="edit-btn"><i class="fa-solid fa-pen"></i></button>
                                        <button class="delete-btn"><i class="fa-solid fa-trash"></i></button>
                                    </div>
                                </td>
                            </tr>
                            <tr>
                                <td>25-0002</td>
                                <td>Cacho, Shane Ella Mae</td>
                                <td>Admin</td>
                                <td>Admin Moderator</td>
                                <td>Regular</td>
                                <td>cacho_shane@gmail.com</td>
                                <td>Pending</td>
                                <td>
                                    <div class="action-btns">
                                        <button class="edit-btn"><i class="fa-solid fa-pen"></i></button>
                                        <button class="delete-btn"><i class="fa-solid fa-trash"></i></button>
                                    </div>
                                </td>
                            </tr>
                            <tr>
                                <td>25-0003</td>
                                <td>Gutierrez, Jodie Lyn</td>
                                <td>Admin</td>
                                <td>Admin Moderator</td>
                                <td>Regular</td>
                                <td>gutierrez_jodie@gmail.com</td>
                                <td>Approved</td>
                                <td>
                                    <div class="action-btns">
                                        <button class="edit-btn"><i class="fa-solid fa-pen"></i></button>
                                        <button class="delete-btn"><i class="fa-solid fa-trash"></i></button>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div id="employeeProfileView" class="profile-container">
            <div class="profile-header">
                <h1 class="profile-title">Employee Profile</h1>
                <button class="back-btn" onclick="showListView()">
                    <i class="fa-solid fa-arrow-left"></i> Back
                </button>
            </div>

            <div class="profile-card">
                <div class="profile-top">
                    <div class="profile-avatar">
                        <i class="fa-solid fa-user"></i>
                    </div>
                    <div class="profile-info">
                        <h2 id="profileName">Name Here</h2>
                        <p><strong>ID:</strong> <span id="profileId">...</span></p>
                        <p><strong>Department:</strong> <span id="profileDepartment">...</span></p>
                        <p><strong>Position:</strong> <span id="profilePosition">...</span></p>
                        <p><strong>Type:</strong> <span id="profileType">...</span></p>
                        <p><strong>Email:</strong> <span id="profileEmail">...</span></p>
                        <p><strong>Remarks:</strong> <span id="profileRemarks">...</span></p>
                    </div>
                </div>

                <hr style="border-color: rgba(255,255,255,0.3); margin: 20px 0;">

                <div class="profile-form">
                    <div class="form-group">
                        <label>Full Name</label>
                        <input type="text" id="fullNameInput" class="fixed-input">
                    </div>
                    <div class="form-group">
                        <label>Employee ID</label>
                        <input type="text" id="employeeIdInput" class="fixed-input">
                    </div>
                    <div class="form-group">
                        <label>Position</label>
                        <input type="text" id="positionInput" class="fixed-input">
                    </div>
                    <div class="form-group">
                        <label>Department</label>
                        <input type="text" id="departmentInput" class="fixed-input">
                    </div>
                    <div class="form-group">
                        <label>Employment Status</label>
                        <select id="employmentStatusInput" class="fixed-input">
                            <option>Regular</option>
                            <option>Probationary</option>
                            <option>Contract</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Email Address</label>
                        <input type="email" id="emailInput" class="fixed-input"
                            style="height: 45px; padding: 10px 15px; font-size: 14px;">

                    </div>
                    <div class="form-group">
                        <label>Date of Birth</label>
                        <input type="date" id="dobInput" class="fixed-input">
                    </div>
                    <div class="form-group">
                        <label>Gender</label>
                        <select id="genderInput" class="fixed-input">
                            <option>Male</option>
                            <option>Female</option>
                            <option>Other</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Home Address</label>
                        <input type="text" id="homeAddressInput" class="fixed-input">
                    </div>
                    <div class="form-group">
                        <label>Contact Number</label>
                        <input type="tel" id="contactNumberInput" class="fixed-input">
                    </div>
                    <div class="form-group">
                        <label>Emergency Contact Number</label>
                        <input type="tel" id="emergencyContactInput" class="fixed-input">
                    </div>
                    <div class="form-group">
                        <label>TIN Number</label>
                        <input type="text" id="tinInput" class="fixed-input">
                    </div>
                    <div class="form-group">
                        <label>PhilHealth Number</label>
                        <input type="text" id="philhealthInput" class="fixed-input">
                    </div>
                    <div class="form-group">
                        <label>SSS Number</label>
                        <input type="text" id="sssInput" class="fixed-input">
                    </div>
                    <div class="form-group">
                        <label>Pag-ibig Number</label>
                        <input type="text" id="pagIbigInput" class="fixed-input">
                    </div>
                </div>

                <div class="form-actions">
                    <button class="reset-btn" onclick="resetForm()">
                        <i class="fa-solid fa-rotate-right"></i> Reset
                    </button>
                    <button class="save-btn" onclick="saveProfile()">
                        <i class="fa-solid fa-floppy-disk"></i> Save Changes
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script>
        function toggleFilter() {
            const filterPanel = document.getElementById('filterPanel');
            filterPanel.classList.toggle('active');
        }

        function applyFilters() {
            const department = document.getElementById('departmentFilter').value;
            const type = document.getElementById('typeFilter').value;
            const remarks = document.getElementById('remarksFilter').value;
            const rows = document.querySelectorAll('#employeeTable tr');

            rows.forEach(row => {
                const rowDept = row.getAttribute('data-department');
                const rowType = row.getAttribute('data-type');
                const rowRemarks = row.getAttribute('data-remarks');

                const deptMatch = !department || rowDept === department;
                const typeMatch = !type || rowType === type;
                const remarksMatch = !remarks || rowRemarks === remarks;

                row.style.display = (deptMatch && typeMatch && remarksMatch) ? '' : 'none';
            });
        }

        function resetFilters() {
            document.getElementById('departmentFilter').value = '';
            document.getElementById('typeFilter').value = '';
            document.getElementById('remarksFilter').value = '';

            const rows = document.querySelectorAll('#employeeTable tr');
            rows.forEach(row => {
                row.style.display = '';
            });
        }

        function showProfile(employeeData) {
            const listView = document.getElementById('employeeListView');
            const profileView = document.getElementById('employeeProfileView');

            listView.classList.add('hidden');
            profileView.classList.add('active');

            // Fill profile display fields
            document.getElementById('profileId').textContent = employeeData.id;
            document.getElementById('profileName').textContent = employeeData.name;
            document.getElementById('profileDepartment').textContent = employeeData.department;
            document.getElementById('profilePosition').textContent = employeeData.position;
            document.getElementById('profileType').textContent = employeeData.type;
            document.getElementById('profileEmail').textContent = employeeData.email;
            document.getElementById('profileRemarks').textContent = employeeData.remarks;

            // Fill form fields
            const nameParts = employeeData.name.split(', ');
            document.getElementById('lastName').value = nameParts[0] || '';
            document.getElementById('firstName').value = nameParts[1] || '';
            document.getElementById('emailInput').value = employeeData.email;
            document.getElementById('departmentInput').value = employeeData.department;
            document.getElementById('positionInput').value = employeeData.position;
            document.getElementById('employmentTypeInput').value = employeeData.type;
            document.getElementById('statusInput').value = employeeData.remarks;
        }

        function showListView() {
            const listView = document.getElementById('employeeListView');
            const profileView = document.getElementById('employeeProfileView');

            listView.classList.remove('hidden');
            profileView.classList.remove('active');
        }

        function resetForm() {
            if (confirm('Are you sure you want to reset all changes?')) {
                document.querySelectorAll('#employeeProfileView input, #employeeProfileView select').forEach(field => {
                    field.value = '';
                });
            }
        }

        function saveProfile() {
            alert('Profile saved successfully!');
            showListView();
        }

        // Initialize after DOM loads
        setTimeout(function () {
            document.querySelectorAll('#employeeTable tr').forEach(function (row) {
                const cells = row.querySelectorAll('td');

                if (cells.length < 7) return;

                const employeeData = {
                    id: cells[0].textContent.trim(),
                    name: cells[1].textContent.trim(),
                    department: cells[2].textContent.trim(),
                    position: cells[3].textContent.trim(),
                    type: cells[4].textContent.trim(),
                    email: cells[5].textContent.trim(),
                    remarks: cells[6].textContent.trim()
                };

                // Click on row
                row.onclick = function (e) {
                    if (!e.target.closest('.delete-btn') && !e.target.closest('.edit-btn')) {
                        showProfile(employeeData);
                    }
                };

                // Click on edit button
                const editBtn = row.querySelector('.edit-btn');
                if (editBtn) {
                    editBtn.onclick = function (e) {
                        e.stopPropagation();
                        showProfile(employeeData);
                    };
                }
            });
        }, 100);
    </script>
</body>

</html>