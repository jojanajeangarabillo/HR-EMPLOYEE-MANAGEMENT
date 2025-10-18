<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Hospital Employee Management</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/7.0.1/css/all.min.css" integrity="sha512-2SwdPD6INVrV/lHTZbO2nodKhrnDdJK9/kg2XD1r9uGqPo1cUbujc+IYdlYdEErWNu69gVcYgdxlmVmzTWnetw==" crossorigin="anonymous" referrerpolicy="no-referrer" />
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

        .admin-sidebar {
            width: 200px;
            background: linear-gradient(180deg, #1e3a8a 0%, #1e40af 100%);
            color: white;
            display: flex;
            flex-direction: column;
        }

        .sidebar-logo {
            padding: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-bottom: 1px solid rgba(255,255,255,0.1);
            font-size: 18px;
            font-weight: bold;
        }

        .sidebar-nav {
            flex: 1;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            padding: 20px 0;
        }

        .primary-top-nav,
        .secondary-buttom-nav {
            list-style: none;
        }

        .nav-item {
            margin-bottom: 5px;
        }

        .nav-link {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 12px 20px;
            cursor: pointer;
            transition: background 0.3s;
            color: white;
            text-decoration: none;
        }

        .nav-link:hover {
            background: rgba(255,255,255,0.1);
        }

        .nav-item:has(.nav-link.active) .nav-link {
            background: rgba(255,255,255,0.15);
            border-left: 3px solid #60a5fa;
        }

        .nav-link i {
            width: 20px;
            text-align: center;
            font-size: 16px;
        }

        .nav-label {
            font-size: 14px;
            font-weight: 500;
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

        .filter-btn, .export-btn {
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

        .filter-btn:hover, .export-btn:hover {
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
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
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

        .table-container {
            background: white;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
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
            gap: 8px;
        }

        .edit-btn, .delete-btn {
            width: 32px;
            height: 32px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: transform 0.2s;
            font-size: 14px;
        }

        .edit-btn {
            background: #fbbf24;
            color: white;
        }

        .delete-btn {
            background: #ef4444;
            color: white;
        }

        .edit-btn:hover, .delete-btn:hover {
            transform: scale(1.1);
        }

        .profile-container {
            display: none;
        }

        .profile-container.active {
            display: block;
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

        .export-as-btn {
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

        .profile-info a {
            color: white;
            text-decoration: underline;
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
        }

        .form-actions {
            display: flex;
            justify-content: flex-end;
            gap: 15px;
            margin-top: 20px;
        }

        .reset-btn, .save-btn {
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
            justify-content: center;
            width: 150px;
        }

        .back-btn:hover {
            background: #1e3a8a;
        }

        .hidden {
            display: none;
        }
    </style>
</head>
<body>
    <aside class="admin-sidebar">
        <div class="sidebar-logo">
            Hospital
        </div>
        <nav class="sidebar-nav">
            <ul class="primary-top-nav">
                <li class="nav-item">
                    <a href="#" class="nav-link">
                        <i class="fa-solid fa-grip"></i>
                        <span class="nav-label">Dashboard</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="#" class="nav-link active">
                        <i class="fa-solid fa-user-group"></i>
                        <span class="nav-label">Employees</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="#" class="nav-link">
                        <i class="fa-solid fa-user-group"></i>
                        <span class="nav-label">Applicants</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="#" class="nav-link">
                        <i class="fa-solid fa-code-pull-request"></i>
                        <span class="nav-label">Requests</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="#" class="nav-link">
                        <i class="fa-solid fa-folder"></i>
                        <span class="nav-label">Job Post</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="#" class="nav-link">
                        <i class="fa-solid fa-chart-simple"></i>
                        <span class="nav-label">Reports</span>
                    </a>
                </li>
            </ul>
            
            <ul class="secondary-buttom-nav">
                <li class="nav-item">
                    <a href="#" class="nav-link">
                        <i class="fa-solid fa-gear"></i>
                        <span class="nav-label">Settings</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="#" class="nav-link">
                        <i class="fa-solid fa-right-from-bracket"></i>
                        <span class="nav-label">Logout</span>
                    </a>
                </li>
            </ul>
        </nav>
    </aside>

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

            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Full Name</th>
                            <th>Department</th>
                            <th>Position</th>
                            <th>Employment Type</th>
                            <th>Email Address</th>
                            <th>Remarks</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody id="employeeTable">
                        <tr data-department="Admin" data-type="Regular" data-remarks="Approved">
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
                        <tr data-department="Admin" data-type="Regular" data-remarks="Pending">
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
                        <tr data-department="Admin" data-type="Regular" data-remarks="Approved">
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
                        <tr data-department="Medical" data-type="Regular" data-remarks="In Review">
                            <td>25-0004</td>
                            <td>Jaroda, Jhanna Rayne</td>
                            <td>Medical</td>
                            <td>Cardiologist Doctor</td>
                            <td>Regular</td>
                            <td>jaroda_jhanna@gmail.com</td>
                            <td>In Review</td>
                            <td>
                                <div class="action-btns">
                                    <button class="edit-btn"><i class="fa-solid fa-pen"></i></button>
                                    <button class="delete-btn"><i class="fa-solid fa-trash"></i></button>
                                </div>
                            </td>
                        </tr>
                        <tr data-department="Nursing" data-type="Probationary" data-remarks="Approved">
                            <td>25-0005</td>
                            <td>Antonio, Rhoanne Nicole</td>
                            <td>Nursing</td>
                            <td>Nurse</td>
                            <td>Probationary</td>
                            <td>antonio_rhoanne@gmail.com</td>
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
    </script>
</body>
</html>