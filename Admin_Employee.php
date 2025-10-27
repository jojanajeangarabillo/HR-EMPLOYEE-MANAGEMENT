<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Admin Employee</title>

  <!-- Font Awesome -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/7.0.1/css/all.min.css"
    integrity="sha512-2SwdPD6INVrV/lHTZbO2nodKhrnDdJK9/kg2XD1r9uGqPo1cUbujc+IYdlYdEErWNu69gVcYgdxlmVmzTWnetw=="
    crossorigin="anonymous" referrerpolicy="no-referrer" />
<link rel="stylesheet" href="admin-sidebar.css">

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
            margin-left: 250px;
            display: flex;
            flex-direction: column
        }

        .main-content-header h1 {
            margin: 0;
            font-size: 2rem;
            margin-bottom: 40px;
        }

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
   width: 100%;
  table-layout: auto;
}


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
        min-width: 150px; /* adjust as needed for wider cells */
        padding: 16px 20px; /* more horizontal padding also widens cells */
        text-align: center;
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

    .action-icons a.delete:hover {
      color: #dc3545;
    }
  </style>
</head>

<body>

  <div class="sidebar">
    <div class="sidebar-logo">
      <img src="Images/hospitallogo.png" alt="Hospital Logo">
    </div>

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

  <script>
    function filterTable() {
      const input = document.getElementById('searchInput');
      const filter = input.value.toLowerCase();
      const table = document.getElementById('employeeTable');
      const rows = table.getElementsByTagName('tr');

      for (let i = 0; i < rows.length; i++) {
        const cells = rows[i].getElementsByTagName('td');
        let found = false;

        for (let j = 0; j < cells.length - 1; j++) {
          if (cells[j]) {
            const textValue = cells[j].textContent || cells[j].innerText;
            if (textValue.toLowerCase().indexOf(filter) > -1) {
              found = true;
              break;
            }
          }
        }

        rows[i].style.display = found ? '' : 'none';
      }
    }

    function addEmployee() {
      alert('Add Employee functionality - Connect to your form/modal');
    }

    function exportData() {
      alert('Export functionality - Connect to your export logic (CSV/Excel)');
    }
  </script>
</body>



</html>