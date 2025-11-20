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
?>

<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Admin Reports</title>

  <!-- Bootstrap & FontAwesome -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/7.0.1/css/all.min.css"
    integrity="sha512-2SwdPD6INVrV/lHTZbO2nodKhrnDdJK9/kg2XD1r9uGqPo1cUbujc+IYdlYdEErWNu69gVcYgdxlmVmzTWnetw=="
    crossorigin="anonymous" referrerpolicy="no-referrer" />
  <link rel="stylesheet" href="admin-sidebar.css">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <script src="https://kit.fontawesome.com/a2e0e9d66b.js" crossorigin="anonymous"></script>
  <style>
    @import url('https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap');

    body {
      font-family: 'Poppins', sans-serif;
      margin: 0;
      display: flex;
      background-color: #F5F8FF;
      color: #1E3A8A;
    }

    /* HEADER */
    .admin-header {
      position: fixed;
      left: 220px;
      /* width of the sidebar */
      width: calc(100% - 220px);
      height: 60px;
      /* adjust as needed */
      padding: 15px 25px;
      border: none;
      z-index: 10;
      display: flex;
      align-items: center;
    }


    .admin-header h1 {
      font-size: 1.6rem;
      font-weight: 700;
      color: #1E3A8A;
      margin: 0;
      display: flex;
      align-items: center;
      gap: 10px;
    }

    /* MAIN CONTENT */
    .admin-main {
      margin-left: 220px;
      /* aligns with sidebar */
      padding: 80px 25px 40px 25px;
      /* top padding = header height + some space */
      width: calc(100% - 220px);
      background-color: #F5F8FF;
      box-sizing: border-box;
    }


    /* REPORT CONFIGURATION */
    .report-config-container {
      background-color: #fff;
      border-radius: 10px;
      padding: 20px 25px;
      box-shadow: 0 2px 6px rgba(0, 0, 0, 0.08);
      margin-bottom: 20px;
      margin-top: 20px;
    }

    .report-config-container h2 {
      font-weight: 700;
      color: #1E3A8A;
      font-size: 1.2rem;
      margin-bottom: 20px;
    }

    .form-label {
      font-weight: 600;
      color: #1E3A8A;
      margin-bottom: 8px;
    }

    .form-select {
      border-radius: 8px;
      padding: 10px;
      font-size: 15px;
    }

    /* Export Buttons */
    .export-buttons {
      display: flex;
      justify-content: flex-end;
      gap: 8px;
      margin-bottom: 15px;
    }

    .export-buttons .btn {
      border: none;
      border-radius: 8px;
      padding: 8px 14px;
      font-weight: 600;
      display: flex;
      align-items: center;
      gap: 8px;
      transition: 0.3s ease;
    }

    .export-excel {
      background-color: #198754;
      color: white;
    }

    .export-pdf {
      background-color: #DC3545;
      color: white;
    }

    .export-excel:hover {
      background-color: #157347;
    }

    .export-pdf:hover {
      background-color: #bb2d3b;
    }

    .btn-primary {
      background-color: #1E3A8A;
      border: none;
      border-radius: 8px;
      font-weight: 600;
    }

    .btn-primary:hover {
      opacity: 0.9;
      transform: translateY(-1px);
    }

    /* REPORT SECTIONS */
    .report-section {
      background-color: #fff;
      border-radius: 10px;
      padding: 25px;
      box-shadow: 0 2px 6px rgba(0, 0, 0, 0.07);
      display: none;
    }

    .report-section.active {
      display: block;
    }

    .report-section h2 {
      font-weight: 700;
      color: #1E3A8A;
      margin-bottom: 20px;
      font-size: 1.2rem;
    }

    /* TABLE DESIGN */
    table {
      border-collapse: separate !important;
      border-spacing: 0;
    }

    thead th {
      background-color: #1E3A8A !important;
      color: white !important;
      font-weight: 600;
      vertical-align: middle;
    }

    tbody td {
      background-color: #FAFBFF !important;
      color: #1E3A8A;
      font-size: 15px;
    }

    tbody tr:nth-child(even) td {
      background-color: #EEF3FF !important;
    }

    table th,
    table td {
      padding: 12px;
      text-align: center;
      border: 1px solid #E2E8F0;
    }

    .total-employees {
      font-weight: 600;
      text-align: right;
      color: #1E3A8A;
      font-size: 16px;
      margin-top: 10px;
    }
  </style>
</head>

<body>

  <div class="sidebar">
    <div class="sidebar-logo">
      <img src="Images/hospitallogo.png" alt="Hospital Logo">
    </div>

    <div class="sidebar-name">
      <p><?php echo "Welcome Admin, $adminname"; ?></p>
    </div>

    <ul class="nav flex-column">
        <li><a href="Admin_Dashboard.php"><i class="fa-solid fa-table-columns"></i> Dashboard</a></li>
            <li><a href="Admin_UserManagement.php"><i class="fa-solid fa-users"></i> User Management</a></li>
             <li><a href="Admin_Departments.php"><i class="fa-solid fa-building-columns"></i> Departments</a></li>
            <li><a href="Admin_Vacancies.php"><i class="fa-solid fa-briefcase"></i> Vacancies</a></li>
            <li><a href="Admin-Applicants.php"><i class="fa-solid fa-user-check"></i> Applicants</a></li>
            <li  class="active"><a href="Admin_Reports.php"><i class="fa-solid fa-chart-simple"></i> Reports</a></li>
            <li><a href="Admin-Settings.php"><i class="fa-solid fa-gear"></i> Settings</a></li>
            <li><a href="Login.php"><i class="fa-solid fa-right-from-bracket"></i> Logout</a></li>
    </ul>
  </div>

  <!-- HEADER -->
  <header class="admin-header">
    <h1><i class="fa-solid fa-chart-column"></i> Reports</h1>
  </header>

  <!-- MAIN CONTENT -->
  <!-- MAIN CONTENT -->
  <main class="admin-main container-fluid">
    <div class="row">
      <div class="col-12">
        <!-- Report Config -->
        <section class="report-config-container">
          <h2>Report Configuration</h2>
          <div class="export-buttons">
            <button type="button" class="btn export-excel"><i class="fa-solid fa-file-excel"></i> Export Excel</button>
            <button type="button" class="btn export-pdf"><i class="fa-solid fa-file-pdf"></i> Export PDF</button>
          </div>
          <form id="report-form">
            <div class="row g-4 align-items-end">
              <div class="col-md-4">
                <label for="report-type" class="form-label">Report Type</label>
                <select id="report-type" class="form-select">
                  <option value="department-summary">Department Summary</option>
                  <option value="attendance">Attendance Report</option>
                  <option value="employment-type">Employment Type Report</option>
                  <option value="payroll">Payroll Summary</option>
                  <option value="training">Training & Certification Report</option>
                  <option value="leave-overtime">Leave and Overtime Report</option>
                  <option value="contract-expiration">Contract Expiration Report</option>
                </select>
              </div>

              <div id="dynamic-fields" class="col-md-8 row g-3"></div>

              <div class="col-12 text-end">
                <button type="submit" class="btn btn-primary">
                  <i class="fa-solid fa-magnifying-glass-chart"></i> Generate Report
                </button>
              </div>
            </div>
          </form>
        </section>
      </div>

      <div class="col-12 mt-4">
        <!-- Department Summary -->
        <section id="department-summary" class="report-section active">
          <h2 class="fw-bold mb-3">Department Summary</h2>
          <div class="table-responsive">
            <table class="table table-bordered table-hover align-middle text-center">
              <thead>
                <tr>
                  <th>Department</th>
                  <th>Total Employees</th>
                  <th>Regular</th>
                  <th>Contractual</th>
                  <th>On Leave</th>
                  <th>Newly Hired</th>
                </tr>
              </thead>
              <tbody>
                <!-- Backend will populate -->
              </tbody>
            </table>
          </div>
          <p class="total-employees">Total Employees: <strong><!-- Backend will populate --></strong></p>
        </section>

        <!-- Attendance Report -->
        <section id="attendance" class="report-section">
          <h2 class="fw-bold mb-3">Attendance Report</h2>
          <div class="table-responsive">
            <table class="table table-bordered table-hover align-middle text-center">
              <thead>
                <tr>
                  <th>Employee ID</th>
                  <th>Name</th>
                  <th>Department</th>
                  <th>Days Present</th>
                  <th>Days Absent</th>
                  <th>Lates</th>
                  <th>Overtime Hours</th>
                </tr>
              </thead>
              <tbody>
                <!-- Backend will populate -->
              </tbody>
            </table>
          </div>
        </section>

        <!-- Employment Type Report -->
        <section id="employment-type" class="report-section">
          <h2 class="fw-bold mb-3">Employment Type Report</h2>
          <div class="table-responsive">
            <table class="table table-bordered table-hover align-middle text-center">
              <thead>
                <tr>
                  <th>Employee ID</th>
                  <th>Name</th>
                  <th>Department</th>
                  <th>Position</th>
                  <th>Employment Type</th>
                </tr>
              </thead>
              <tbody>
                <!-- Backend will populate -->
              </tbody>
            </table>
          </div>
        </section>

        <!-- Payroll Summary -->
        <section id="payroll" class="report-section">
          <h2 class="fw-bold mb-3">Payroll Summary</h2>
          <div class="table-responsive">
            <table class="table table-bordered table-hover align-middle text-center">
              <thead>
                <tr>
                  <th>Employee ID</th>
                  <th>Name</th>
                  <th>Department</th>
                  <th>Basic Pay</th>
                  <th>Overtime Pay</th>
                  <th>Deduction</th>
                  <th>Net Pay</th>
                </tr>
              </thead>
              <tbody>
                <!-- Backend will populate -->
              </tbody>
            </table>
          </div>
        </section>

        <!-- Training & Certification Report -->
        <section id="training" class="report-section">
          <h2 class="fw-bold mb-3">Training & Certification Report</h2>
          <div class="table-responsive">
            <table class="table table-bordered table-hover align-middle text-center">
              <thead>
                <tr>
                  <th>Employee ID</th>
                  <th>Name</th>
                  <th>Department</th>
                  <th>Training/Certificate</th>
                  <th>Status</th>
                  <th>Expiration Date</th>
                </tr>
              </thead>
              <tbody>
                <!-- Backend will populate -->
              </tbody>
            </table>
          </div>
        </section>

        <!-- Leave & Overtime Report -->
        <section id="leave-overtime" class="report-section">
          <h2 class="fw-bold mb-3">Leave and Overtime Report</h2>
          <div class="table-responsive">
            <table class="table table-bordered table-hover align-middle text-center">
              <thead>
                <tr>
                  <th>Employee ID</th>
                  <th>Name</th>
                  <th>Department</th>
                  <th>Leave Type</th>
                  <th>Duration</th>
                  <th>Status</th>
                  <th>Overtime Hours</th>
                </tr>
              </thead>
              <tbody>
                <!-- Backend will populate -->
              </tbody>
            </table>
          </div>
        </section>

        <!-- Contract Expiration Report -->
        <section id="contract-expiration" class="report-section">
          <h2 class="fw-bold mb-3">Contract Expiration Report</h2>
          <div class="table-responsive">
            <table class="table table-bordered table-hover align-middle text-center">
              <thead>
                <tr>
                  <th>Employee ID</th>
                  <th>Name</th>
                  <th>Department</th>
                  <th>Contract Date End</th>
                  <th>Days Left</th>
                  <th>Remarks</th>
                </tr>
              </thead>
              <tbody>
                <!-- Backend will populate -->
              </tbody>
            </table>
          </div>
        </section>

      </div>
    </div>
  </main>


  <script>
    const reportDropdown = document.getElementById('report-type');
    const dynamicFields = document.getElementById('dynamic-fields');
    const sections = document.querySelectorAll('.report-section');

    // Define templates for each report type
    const configTemplates = {
      "department-summary": `
    <div class="col-md-6">
      <label class="form-label">Department</label>
      <select class="form-select">
        <option>All Departments</option>
        <option>Cardiology</option>
        <option>Maintenance</option>
        <option>IT</option>
        <option>HR</option>
      </select>
    </div>
  `,
      "attendance": `
    <div class="col-md-4">
      <label class="form-label">From Date</label>
      <input type="date" class="form-control">
    </div>
    <div class="col-md-4">
      <label class="form-label">To Date</label>
      <input type="date" class="form-control">
    </div>
    <div class="col-md-4">
      <label class="form-label">Employee Type</label>
      <select class="form-select">
        <option>All</option>
        <option>Regular</option>
        <option>Contractual</option>
      </select>
    </div>
  `,
      "employment-type": `
    <div class="col-md-6">
      <label class="form-label">Department</label>
      <select class="form-select">
        <option>All</option>
        <option>Cardiology</option>
        <option>Maintenance</option>
        <option>IT</option>
      </select>
    </div>
  `,
      "payroll": `
    <div class="col-md-4">
      <label class="form-label">Month</label>
      <input type="month" class="form-control">
    </div>
    <div class="col-md-4">
      <label class="form-label">Department</label>
      <select class="form-select">
        <option>All</option>
        <option>HR</option>
        <option>IT</option>
        <option>Cardiology</option>
      </select>
    </div>
  `,
      "training": `
    <div class="col-md-6">
      <label class="form-label">Certification Type</label>
      <select class="form-select">
        <option>All</option>
        <option>BLS</option>
        <option>ACLS</option>
        <option>First Aid</option>
      </select>
    </div>
    <div class="col-md-6">
      <label class="form-label">Expiry Before</label>
      <input type="date" class="form-control">
    </div>
  `,
      "leave-overtime": `
    <div class="col-md-4">
      <label class="form-label">From Date</label>
      <input type="date" class="form-control">
    </div>
    <div class="col-md-4">
      <label class="form-label">To Date</label>
      <input type="date" class="form-control">
    </div>
    <div class="col-md-4">
      <label class="form-label">Department</label>
      <select class="form-select">
        <option>All</option>
        <option>Cardiology</option>
        <option>HR</option>
      </select>
    </div>
  `,
      "contract-expiration": `
    <div class="col-md-6">
      <label class="form-label">Expiration Before</label>
      <input type="date" class="form-control">
    </div>
    <div class="col-md-6">
      <label class="form-label">Employee Type</label>
      <select class="form-select">
        <option>All</option>
        <option>Contractual</option>
        <option>Regular</option>
      </select>
    </div>
  `
    };

    // Function to update the fields dynamically
    function updateDynamicFields() {
      const selected = reportDropdown.value;
      dynamicFields.innerHTML = configTemplates[selected] || '';
    }

    // Function to show the corresponding table
    function updateReportSection() {
      const selected = reportDropdown.value;
      sections.forEach(s => s.classList.remove('active'));
      const active = document.getElementById(selected);
      if (active) active.classList.add('active');
    }

    // Update both when changing report type
    reportDropdown.addEventListener('change', () => {
      updateDynamicFields();
      updateReportSection();
    });

    // Initialize on page load
    updateDynamicFields();
    updateReportSection();

    // Handle report generation button
    document.getElementById('report-form').addEventListener('submit', e => {
      e.preventDefault();
      alert('Report generated with the selected configuration!');
    });
  </script>

</body>

</html>