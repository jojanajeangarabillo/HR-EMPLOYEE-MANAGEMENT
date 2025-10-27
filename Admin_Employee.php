<!DOCTYPE html>
<html lang="en">

<head>
<<<<<<< HEAD
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Admin Employee</title>
=======
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
>>>>>>> 1b8f0d6423020b1aac3befefe757de0a4d16924a

  <!-- Font Awesome -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/7.0.1/css/all.min.css"
    integrity="sha512-2SwdPD6INVrV/lHTZbO2nodKhrnDdJK9/kg2XD1r9uGqPo1cUbujc+IYdlYdEErWNu69gVcYgdxlmVmzTWnetw=="
    crossorigin="anonymous" referrerpolicy="no-referrer" />
<link rel="stylesheet" href="admin-sidebar.css">

<<<<<<< HEAD
 <style>
    body {
      font-family: 'Poppins', 'Roboto', sans-serif;
      margin: 0;
      display: flex;
      background-color: #f1f5fc;
      color: #111827;
    }
=======
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
>>>>>>> 1b8f0d6423020b1aac3befefe757de0a4d16924a

    .sidebar-logo {
      display: flex;
      justify-content: center;
      margin-bottom: 50px;
    }

<<<<<<< HEAD
    .sidebar-logo img {
      height: 120px;
      width: 120px;
    }
=======
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
>>>>>>> 1b8f0d6423020b1aac3befefe757de0a4d16924a

  
        .main-content {
            padding: 40px 30px;
            margin-left: 220px;
            display: flex;
            flex-direction: column
        }

<<<<<<< HEAD
        .main-content-header h1 {
=======
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
>>>>>>> 1b8f0d6423020b1aac3befefe757de0a4d16924a
            margin: 0;
            font-size: 2rem;
            margin-bottom: 40px;
        }

<<<<<<< HEAD
    /* Content section wrapper */
    .content-section {
      max-width: 1400px;
      margin: 0 auto;
    }

    /* Controls bar */
    .controls-bar {
      display: flex;
      justify-content: space-between;
      align-items: center;
      margin-bottom: 20px;
      gap: 20px;
      flex-wrap: wrap;
    }

    .search-box {
      flex: 1;
      min-width: 250px;
      max-width: 400px;
    }

    .search-box input {
      width: 100%;
      padding: 10px 15px;
      border: 1px solid #e0e0e0;
      border-radius: 6px;
      font-size: 14px;
      font-family: 'Poppins', 'Roboto', sans-serif;
      transition: border-color 0.3s;
    }

    .search-box input:focus {
      outline: none;
      border-color: #1E3A8A;
    }

    .button-group {
      display: flex;
      gap: 10px;
    }

    .btn {
      padding: 10px 20px;
      border: none;
      border-radius: 6px;
      font-size: 14px;
      font-family: 'Poppins', 'Roboto', sans-serif;
      cursor: pointer;
      transition: all 0.3s;
      text-decoration: none;
      display: inline-flex;
      align-items: center;
      gap: 8px;
    }

    .btn-primary {
      background-color: #1E3A8A;
      color: white;
    }

    .btn-primary:hover {
      background-color: #1e40af;
      transform: translateY(-2px);
      box-shadow: 0 4px 8px rgba(30, 58, 138, 0.3);
    }

    .btn-success {
      background-color: #10b981;
      color: white;
    }

    .btn-success:hover {
      background-color: #059669;
      transform: translateY(-2px);
      box-shadow: 0 4px 8px rgba(16, 185, 129, 0.3);
    }

.table-container {
  width: 100%;        /* full width of main-content */
  padding: 30 30px;    /* equal left & right spacing */
  margin-top: 20px;   /* keep your top spacing */
  margin-left: 150px;
  box-sizing: border-box;
  width: fit-content;
}

.table-responsive table {
  width: 100%;          /* table fills container */
  table-layout: auto;
}
th, td {
  min-width: 180px; /* adjust as needed for wider cells */
  padding: 16px 25px; /* more horizontal padding also widens cells */
  text-align: center;
}
=======
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
>>>>>>> 1b8f0d6423020b1aac3befefe757de0a4d16924a


    table {
      border-collapse: collapse;
      width: 100%;
      background-color: #ffffff;
      box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
      border-radius: 8px;
      overflow: hidden;
    }

    th,
    td {
      border: 1px solid #e0e0e0;
      padding: 16px 12px;
      text-align: center;
    }

    thead {
      background-color: #1E3A8A;
      font-weight: 600;
      color: #ffffff;
    }

    tbody tr:hover {
      background-color: #f8f9fa;
    }

    tbody tr:nth-child(even) {
      background-color: #fafafa;
    }

    /* Action icons */
    .action-icons {
      display: flex;
      justify-content: center;
      gap: 15px;
    }

    .action-icons a {
      color: #333;
      text-decoration: none;
      transition: color 0.2s ease, transform 0.2s ease;
      font-size: 18px;
    }

    .action-icons a:hover {
      transform: scale(1.1);
    }

    .action-icons a.edit:hover {
      color: #007bff;
    }

<<<<<<< HEAD
    .action-icons a.delete:hover {
      color: #dc3545;
    }
  </style>
</head>

<body>
=======
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
>>>>>>> 1b8f0d6423020b1aac3befefe757de0a4d16924a

  <div class="sidebar">
    <div class="sidebar-logo">
      <img src="Images/hospitallogo.png" alt="Hospital Logo">
    </div>

<<<<<<< HEAD
    <ul class="nav">
      <li><a href="Admin_Dashboard.php"><i class="fa-solid fa-table-columns"></i>Dashboard</a></li>
      <li class="active"><a href="Admin_Employee.php"><i class="fa-solid fa-user-group"></i>Employees</a></li>
      <li><a href="Admin-JobPosting.php"><i class="fa-solid fa-user-group"></i>Applicants</a></li>
      <li><a href="#"><i class="fa-solid fa-user-group"></i>Pending Applicants</a></li>
      <li><a href="#"><i class="fa-solid fa-briefcase"></i>Vacancies</a></li>
      <li><a href="Admin-request"><i class="fa-solid fa-code-pull-request"></i>Requests</a></li>
      <li><a href="Admin-Settings"><i class="fa-solid fa-gear"></i>Settings</a></li>
      <li><a href="Login.php"><i class="fa-solid fa-right-from-bracket"></i>Logout</a></li>
    </ul>
  </div>
=======
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
>>>>>>> 1b8f0d6423020b1aac3befefe757de0a4d16924a

 <div class="main-content">
  <div class="main-content-header">
    <h1>Employee List</h1>
  </div>

  <div class="table-container">
    <div class="controls-bar">
      <div class="search-box">
        <input type="text" id="searchInput" placeholder="Search employees..." onkeyup="filterTable()">
      </div>
      <div class="button-group">
        <button class="btn btn-primary"><i class="fa-solid fa-plus"></i> Add Employee</button>
        <button class="btn btn-success"><i class="fa-solid fa-file-export"></i> Export</button>
      </div>
    </div>

<<<<<<< HEAD
    <div class="table-responsive">
      <table>
        <thead>
          <tr>
            <th>Employee ID</th>
            <th>Full Name</th>
            <th>Department</th>
            <th>Position</th>
            <th>Employment Type</th>
            <th>Email Address</th>
            <th>Action</th>
          </tr>
        </thead>
        <tbody id="employeeTable">
          <tr>
            <td>EMP001</td>
            <td>Juan Dela Cruz</td>
            <td>Human Resources</td>
            <td>HR Manager</td>
            <td>Full-Time</td>
            <td>juan.delacruz@example.com</td>
            <td class="action-icons">
              <a href="#" class="edit"><i class="fa-solid fa-pen"></i></a>
              <a href="#" class="delete"><i class="fa-solid fa-trash"></i></a>
            </td>
          </tr>
        </tbody>
      </table>
    </div>
  </div>
</div>
=======
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
>>>>>>> 1b8f0d6423020b1aac3befefe757de0a4d16924a

  <script>
    function filterTable() {
      const input = document.getElementById('searchInput');
      const filter = input.value.toLowerCase();
      const table = document.getElementById('employeeTable');
      const rows = table.getElementsByTagName('tr');

      for (let i = 0; i < rows.length; i++) {
        const cells = rows[i].getElementsByTagName('td');
        let found = false;

<<<<<<< HEAD
        for (let j = 0; j < cells.length - 1; j++) {
          if (cells[j]) {
            const textValue = cells[j].textContent || cells[j].innerText;
            if (textValue.toLowerCase().indexOf(filter) > -1) {
              found = true;
              break;
=======
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
>>>>>>> 1b8f0d6423020b1aac3befefe757de0a4d16924a
            }
          }
        }

        rows[i].style.display = found ? '' : 'none';
      }
    }

<<<<<<< HEAD
    function addEmployee() {
      alert('Add Employee functionality - Connect to your form/modal');
    }

    function exportData() {
      alert('Export functionality - Connect to your export logic (CSV/Excel)');
    }
  </script>
</body>



=======
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

>>>>>>> 1b8f0d6423020b1aac3befefe757de0a4d16924a
</html>