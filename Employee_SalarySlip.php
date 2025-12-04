<?php
session_start();
require 'admin/db.connect.php';

// Fetch employee name
$employeenameQuery = $conn->query("
    SELECT fullname 
    FROM user 
    WHERE role = 'Employee' AND (sub_role IS NULL OR sub_role != 'HR Manager')
");
$employeename = ($employeenameQuery && $row = $employeenameQuery->fetch_assoc()) ? $row['fullname'] : 'Employee';

$employeeID = $_SESSION['applicant_employee_id'] ?? null;
$sessionEmail = $_SESSION['email'] ?? null;
$employeename = "Employee";

if (!$employeeID && $sessionEmail) {
  $stmt = $conn->prepare("SELECT applicant_employee_id FROM user WHERE email = ? LIMIT 1");
  $stmt->bind_param("s", $sessionEmail);
  $stmt->execute();
  $res = $stmt->get_result();
  if ($u = $res->fetch_assoc()) {
    $employeeID = $u['applicant_employee_id'];
  }
}

if ($employeeID) {
  $stmt = $conn->prepare("SELECT fullname FROM user WHERE applicant_employee_id = ?");
  $stmt->bind_param("s", $employeeID);
  $stmt->execute();
  $result = $stmt->get_result();

  if ($row = $result->fetch_assoc()) {
    $employeename = $row['fullname'];
  }
}

// Fetch employee name and profile picture
if ($employeeID) {
  $stmt = $conn->prepare("SELECT fullname, profile_pic FROM employee WHERE empID = ?");
  $stmt->bind_param("s", $employeeID);
  $stmt->execute();
  $result = $stmt->get_result();

  if ($result->num_rows > 0) {
    $row = $result->fetch_assoc();
    $employeename = $row['fullname'];
    $profile_picture = !empty($row['profile_pic'])
      ? "uploads/employees/" . $row['profile_pic']
      : "uploads/employees/default.png";
  } else {
    $employeename = $_SESSION['fullname'] ?? "Employee";
    $profile_picture = "uploads/employees/default.png";
  }
} else {
  $employeename = $_SESSION['fullname'] ?? "Employee";
  $profile_picture = "uploads/employees/default.png";
}

?>



<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Employee Salary Slip</title>
  <link rel="stylesheet" href="manager-sidebar.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/7.0.1/css/all.min.css">

  <style>
    :root {
      --primary: #6674cc;
      --primary-dark: #4c5ecf;
      --primary-light: #f0f2ff;
      --secondary: #3b82f6;
      --accent-green: #10b981;
      --accent-red: #dc2626;
      --accent-orange: #f59e0b;
      --text-dark: #111827;
      --text-light: #6b7280;
      --bg-light: #f8fafc;
      --card-bg: #ffffff;
      --border-color: #e5e7eb;
      --shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
      --shadow-hover: 0 8px 24px rgba(0, 0, 0, 0.12);
      --border-radius: 12px;
    }

    body {
      font-family: 'Poppins', 'Roboto', sans-serif;
      margin: 0;
      display: flex;
      background-color: var(--bg-light);
      color: var(--text-dark);
      line-height: 1.6;
    }




    .sidebar-logo {
      padding: 30px 20px 10px;
      text-align: center;
      border-bottom: 1px solid rgba(255, 255, 255, 0.1);
    }


    .sidebar-logo img:hover {
      border-color: rgba(255, 255, 255, 0.5);
      transform: scale(1.05);
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

    .menu-board-title {
      font-size: 14px;
      font-weight: 600;
      margin: 15px 0 5px 20px;
      text-transform: uppercase;
      color: var(--light-blue-dark);
      letter-spacing: 1px;
      color: white;
    }


    h1 {
      font-family: 'Roboto', sans-serif;
      font-size: 35px;
      color: white;
      text-align: center;
    }




    /* Main Content */
    .main-content {
      margin-left: 280px;
      padding: 30px;
      flex-grow: 1;
      box-sizing: border-box;
      min-height: 100vh;
    }

    /* Page Header */
    .page-header {
      display: flex;
      justify-content: space-between;
      align-items: center;
      margin-bottom: 30px;
    }

    .page-title {
      display: flex;
      align-items: center;
      gap: 15px;
    }

    .page-title h1 {
      font-size: 28px;
      font-weight: 700;
      color: var(--text-dark);
      margin: 0;
    }

    .page-title i {
      color: var(--primary);
      font-size: 28px;
    }

    .filter-container {
      display: flex;
      gap: 15px;
      align-items: center;
    }

    .filter-select {
      padding: 10px 15px;
      border-radius: 8px;
      border: 1px solid var(--border-color);
      background-color: white;
      font-family: 'Poppins', sans-serif;
      font-size: 14px;
      cursor: pointer;
      transition: all 0.2s ease;
    }

    .filter-select:focus {
      outline: none;
      border-color: var(--primary);
      box-shadow: 0 0 0 3px rgba(102, 116, 204, 0.1);
    }

    /* Overview Page */
    .overview-page {
      transition: all 0.3s ease;
    }

    .overview-page.hidden {
      display: none;
    }

    .stats-cards {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
      gap: 20px;
      margin-bottom: 30px;
    }

    .stat-card {
      background: var(--card-bg);
      border-radius: var(--border-radius);
      padding: 25px;
      box-shadow: var(--shadow);
      display: flex;
      align-items: center;
      transition: all 0.3s ease;
    }

    .stat-card:hover {
      transform: translateY(-5px);
      box-shadow: var(--shadow-hover);
    }

    .stat-icon {
      width: 60px;
      height: 60px;
      border-radius: 12px;
      display: flex;
      align-items: center;
      justify-content: center;
      margin-right: 20px;
      font-size: 24px;
      color: white;
    }

    .stat-info h3 {
      font-size: 14px;
      font-weight: 600;
      color: var(--text-light);
      margin: 0 0 5px 0;
      text-transform: uppercase;
      letter-spacing: 0.5px;
    }

    .stat-info p {
      font-size: 24px;
      font-weight: 700;
      color: var(--text-dark);
      margin: 0;
    }

    .stat-card.total-earnings .stat-icon {
      background: linear-gradient(135deg, var(--accent-green), #059669);
    }

    .stat-card.average-pay .stat-icon {
      background: linear-gradient(135deg, var(--secondary), #2563eb);
    }

    .stat-card.total-deductions .stat-icon {
      background: linear-gradient(135deg, var(--accent-red), #b91c1c);
    }

    .stat-card.pay-periods .stat-icon {
      background: linear-gradient(135deg, var(--accent-orange), #d97706);
    }

    .table-container {
      background: var(--card-bg);
      border-radius: var(--border-radius);
      box-shadow: var(--shadow);
      overflow: hidden;
    }

    .salary-overview-table {
      width: 100%;
      border-collapse: collapse;
    }

    .salary-overview-table thead {
      background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
    }

    .salary-overview-table th {
      padding: 18px 20px;
      text-align: left;
      font-weight: 600;
      color: white;
      font-size: 15px;
    }

    .salary-overview-table tbody tr {
      border-bottom: 1px solid var(--border-color);
      transition: all 0.2s ease;
    }

    .salary-overview-table tbody tr:hover {
      background-color: var(--primary-light);
    }

    .salary-overview-table td {
      padding: 16px 20px;
      color: var(--text-dark);
    }

    .btn-view {
      background-color: var(--accent-green);
      color: white;
      border: none;
      padding: 8px 16px;
      border-radius: 6px;
      cursor: pointer;
      font-weight: 500;
      transition: all 0.2s ease;
      display: flex;
      align-items: center;
      gap: 5px;
    }

    .btn-view:hover {
      background-color: #059669;
      transform: translateY(-2px);
    }

    /* Details Page */
    .details-page {
      display: none;
      animation: fadeIn 0.5s ease forwards;
    }

    .details-page.active {
      display: block;
    }

    .details-container {
      background: var(--card-bg);
      border-radius: var(--border-radius);
      box-shadow: var(--shadow);
      overflow: hidden;
    }

    .header-section {
      display: flex;
      justify-content: space-between;
      align-items: center;
      padding: 30px;
      border-bottom: 1px solid var(--border-color);
      background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
      color: white;
    }

    .header-title {
      display: flex;
      align-items: center;
      gap: 15px;
    }

    .header-title h2 {
      font-size: 28px;
      font-weight: 600;
      margin: 0;
    }

    .header-title i {
      font-size: 28px;
    }

    .export-buttons {
      display: flex;
      gap: 10px;
      align-items: center;
    }

    .export-label {
      font-size: 14px;
      font-weight: 500;
    }

    .btn-export {
      padding: 10px 15px;
      border: none;
      background: rgba(255, 255, 255, 0.2);
      border-radius: 8px;
      cursor: pointer;
      font-size: 16px;
      display: flex;
      align-items: center;
      gap: 8px;
      color: white;
      transition: all 0.2s ease;
      font-weight: 500;
    }

    .btn-export:hover {
      background: rgba(255, 255, 255, 0.3);
      transform: translateY(-2px);
    }

    .content-wrapper {
      display: grid;
      grid-template-columns: 350px 1fr;
      padding: 30px;
      gap: 30px;
    }

    /* Left Section */
    .left-section {
      display: flex;
      flex-direction: column;
      gap: 25px;
    }

    .info-card {
      background: var(--card-bg);
      border-radius: var(--border-radius);
      overflow: hidden;
      box-shadow: var(--shadow);
    }

    .info-card-header {
      background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
      color: white;
      padding: 15px 20px;
      font-weight: 600;
      font-size: 16px;
    }

    .info-card-body {
      padding: 20px;
    }

    .info-row {
      display: flex;
      justify-content: space-between;
      padding: 12px 0;
      border-bottom: 1px solid var(--border-color);
    }

    .info-row:last-child {
      border-bottom: none;
    }

    .info-label {
      color: var(--text-light);
      font-size: 14px;
      font-weight: 500;
    }

    .info-value {
      color: var(--text-dark);
      font-size: 14px;
      font-weight: 600;
      text-align: right;
    }

    .received-by-card {
      background: var(--card-bg);
      border-radius: var(--border-radius);
      overflow: hidden;
      box-shadow: var(--shadow);
    }

    .received-by-header {
      background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
      color: white;
      padding: 15px 20px;
      font-weight: 600;
      font-size: 16px;
      text-align: center;
    }

    .received-by-body {
      padding: 30px;
      text-align: center;
      font-size: 16px;
      font-weight: 600;
      color: var(--text-dark);
    }

    /* Right Section */
    .right-section {
      display: flex;
      flex-direction: column;
      gap: 25px;
    }

    .salary-slip-card {
      background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
      border-radius: var(--border-radius);
      padding: 30px;
      color: white;
      box-shadow: 0 8px 25px rgba(102, 126, 234, 0.4);
    }

    .slip-header {
      display: flex;
      justify-content: space-between;
      align-items: center;
      margin-bottom: 25px;
      padding-bottom: 20px;
      border-bottom: 2px solid rgba(255, 255, 255, 0.3);
    }

    .slip-header-text {
      flex: 1;
    }

    .slip-header h3 {
      font-size: 32px;
      font-weight: 700;
      margin: 0 0 5px 0;
    }

    .slip-header p {
      font-size: 18px;
      opacity: 0.95;
      margin: 0;
    }

    .slip-header .hospital-logo {
      background: white;
      border-radius: 50%;
      width: 60px;
      height: 60px;
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 24px;
      color: var(--primary);
      flex-shrink: 0;
      box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
    }

    .salary-table {
      background: rgba(255, 255, 255, 0.15);
      border-radius: 10px;
      overflow: hidden;
      backdrop-filter: blur(10px);
    }

    .salary-table table {
      width: 100%;
      border-collapse: collapse;
    }

    .salary-table thead {
      background: rgba(0, 0, 0, 0.2);
    }

    .salary-table th {
      padding: 14px 12px;
      text-align: center;
      font-weight: 600;
      font-size: 13px;
      text-transform: uppercase;
      border-bottom: 2px solid rgba(255, 255, 255, 0.3);
    }

    .salary-table td {
      padding: 12px;
      text-align: center;
      font-size: 14px;
      border-bottom: 1px solid rgba(255, 255, 255, 0.1);
    }

    .salary-table tbody tr:last-child td {
      border-bottom: none;
    }

    .salary-table tbody tr:hover {
      background: rgba(255, 255, 255, 0.05);
    }

    .total-row {
      background: rgba(0, 0, 0, 0.25) !important;
      font-weight: 700 !important;
      font-size: 15px !important;
    }

    .total-row td {
      padding: 16px 12px !important;
      border-top: 2px solid rgba(255, 255, 255, 0.3) !important;
    }

    .btn-back {
      align-self: flex-start;
      padding: 12px 30px;
      background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
      color: white;
      border: none;
      border-radius: 8px;
      font-size: 16px;
      font-weight: 500;
      cursor: pointer;
      transition: all 0.3s ease;
      box-shadow: var(--shadow);
      display: flex;
      align-items: center;
      gap: 8px;
    }

    .btn-back:hover {
      transform: translateY(-3px);
      box-shadow: var(--shadow-hover);
    }



    /* Responsive Design */
    @media (max-width: 1200px) {
      .content-wrapper {
        grid-template-columns: 1fr;
      }

      .left-section {
        order: 2;
      }

      .right-section {
        order: 1;
      }
    }

    @media (max-width: 992px) {


      .sidebar .nav li a {
        justify-content: center;
        padding: 15px;
      }

      .sidebar .nav li a i {
        margin-right: 0;
        font-size: 20px;
      }

      .main-content {
        margin-left: 80px;
      }

      .stats-cards {
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
      }
    }

    @media (max-width: 768px) {
      .main-content {
        padding: 20px;
      }

      .page-header {
        flex-direction: column;
        align-items: flex-start;
        gap: 15px;
      }

      .filter-container {
        width: 100%;
        justify-content: space-between;
      }

      .filter-select {
        flex: 1;
      }

      .header-section {
        flex-direction: column;
        gap: 15px;
        align-items: flex-start;
      }

      .export-buttons {
        align-self: flex-end;
      }
    }

    @media (max-width: 576px) {
      .sidebar {
        width: 0;
        transform: translateX(-100%);
      }

      .main-content {
        margin-left: 0;
      }

      .stats-cards {
        grid-template-columns: 1fr;
      }

      .salary-overview-table {
        font-size: 14px;
      }

      .salary-overview-table th,
      .salary-overview-table td {
        padding: 12px 10px;
      }

      .content-wrapper {
        padding: 20px;
      }
    }

    @media print {

      .sidebar,
      .export-buttons,
      .btn-back,
      .page-header,
      .stats-cards {
        display: none;
      }

      .main-content {
        margin-left: 0;
        width: 100%;
        padding: 0;
      }

      .details-container {
        box-shadow: none;
      }
    }
  </style>
</head>

<body>
  <!-- Sidebar -->
  <div class="sidebar">
    <div class="sidebar-logo">
      <a href="Employee_Profile.php" class="profile">
        <img src="<?php echo htmlspecialchars($profile_picture); ?>" alt="Profile" class="sidebar-profile-img">
      </a>
      <div class="sidebar-name">
        <p><?php echo "Welcome, $employeename"; ?></p>
      </div>
    </div>

    <ul class="nav">
      <h4 class="menu-board-title">Menu Board</h4>
      <li><a href="Employee_Dashboard.php"><i class="fa-solid fa-grip"></i> <span>Dashboard</span></a></li>
      <li class="active"><a href="Employee_SalarySlip.php"><i class="fa-solid fa-file-invoice-dollar"></i> <span>Salary
            Slip</span></a></li>
      <li><a href="Employee_Requests.php"><i class="fa-solid fa-code-branch"></i> <span>Requests</span></a></li>
      <li><a href="Login.php"><i class="fa-solid fa-right-from-bracket"></i> <span>Logout</span></a></li>
    </ul>
  </div>

  <!-- Main Content -->
  <main class="main-content">
    <!-- Overview Page -->
    <div class="overview-page" id="overviewPage">
      <div class="page-header">
        <div class="page-title">
          <h1>Salary Overview</h1>
          <i class="fa-solid fa-file-invoice-dollar"></i>
        </div>
        <div class="filter-container">
          <select class="filter-select" id="yearFilter">
            <option value="2025">2025</option>
            <option value="2024">2024</option>
            <option value="2023">2023</option>
          </select>
          <select class="filter-select" id="monthFilter">
            <option value="all">All Months</option>
            <option value="january">January</option>
            <option value="february">February</option>
            <option value="march">March</option>
            <option value="april">April</option>
            <option value="may">May</option>
            <option value="june">June</option>
            <option value="july">July</option>
            <option value="august">August</option>
            <option value="september">September</option>
            <option value="october">October</option>
            <option value="november">November</option>
            <option value="december">December</option>
          </select>
        </div>
      </div>

      <div class="stats-cards">
        <div class="stat-card total-earnings">
          <div class="stat-icon">
            <i class="fa-solid fa-money-bill-wave"></i>
          </div>
          <div class="stat-info">
            <h3>Total Earnings</h3>
            <p id="totalEarnings">₱0.00</p>
          </div>
        </div>
        <div class="stat-card average-pay">
          <div class="stat-icon">
            <i class="fa-solid fa-chart-line"></i>
          </div>
          <div class="stat-info">
            <h3>Average Pay</h3>
            <p id="averagePay">₱0.00</p>
          </div>
        </div>
        <div class="stat-card total-deductions">
          <div class="stat-icon">
            <i class="fa-solid fa-hand-holding-usd"></i>
          </div>
          <div class="stat-info">
            <h3>Total Deductions</h3>
            <p id="totalDeductions">₱0.00</p>
          </div>
        </div>
        <div class="stat-card pay-periods">
          <div class="stat-icon">
            <i class="fa-solid fa-calendar-alt"></i>
          </div>
          <div class="stat-info">
            <h3>Pay Periods</h3>
            <p id="payPeriods">0</p>
          </div>
        </div>
      </div>

      <div class="table-container">
        <table class="salary-overview-table">
          <thead>
            <tr>
              <th>Date</th>
              <th>Gross Salary</th>
              <th>Overtime Pay</th>
              <th>Deduction</th>
              <th>Net Pay</th>
              <th>Action</th>
            </tr>
          </thead>
          <tbody id="salaryTableBody"></tbody>
        </table>
      </div>
    </div>

    <!-- Details Page -->
    <div class="details-page" id="detailsPage">
      <div class="details-container">
        <div class="header-section">
          <div class="header-title">
            <h2>Salary Details</h2>
            <i class="fa-solid fa-file-invoice"></i>
          </div>
          <div class="export-buttons">
            <span class="export-label">Export As</span>
            <button class="btn-export" onclick="window.print()" title="Export as PDF">
              <i class="fa-solid fa-file-pdf"></i> PDF
            </button>
            <button class="btn-export" onclick="exportAsImage()" title="Export as Image">
              <i class="fa-solid fa-image"></i> Image
            </button>
          </div>
        </div>

        <div class="content-wrapper">
          <!-- Left Section -->
          <div class="left-section">
            <!-- Employee Information -->
            <div class="info-card">
              <div class="info-card-header">EMPLOYEE INFORMATION</div>
              <div class="info-card-body">
                <div class="info-row">
                  <span class="info-label">Name</span>
                  <span class="info-value full_name"></span>
                </div>
                <div class="info-row">
                  <span class="info-label">ID</span>
                  <span class="info-value empID"></span>
                </div>
                <div class="info-row">
                  <span class="info-label">Position</span>
                  <span class="info-value position"></span>
                </div>
                <div class="info-row">
                  <span class="info-label">Status</span>
                  <span class="info-value status"></span>
                </div>
              </div>
            </div>

            <!-- Pay Out Information -->
            <div class="info-card">
              <div class="info-card-header">PAY OUT INFORMATION</div>
              <div class="info-card-body">
                <div class="info-row">
                  <span class="info-label">Pay Date</span>
                  <span class="info-value payDate"></span>
                </div>
                <div class="info-row">
                  <span class="info-label">Pay Type</span>
                  <span class="info-value payType"></span>
                </div>
                <div class="info-row">
                  <span class="info-label">Period</span>
                  <span class="info-value period"></span>
                </div>
                <div class="info-row">
                  <span class="info-label">Absent Days (attendance)</span>
                  <span class="info-value absentDaysCount"></span>
                </div>
                <div class="info-row">
                  <span class="info-label">Monthly Rate</span>
                  <span class="info-value monthlyRate"></span>
                </div>
                <div class="info-row">
                  <span class="info-label">Daily Rate</span>
                  <span class="info-value dailyRate"></span>
                </div>
                <div class="info-row">
                  <span class="info-label">Hourly Rate</span>
                  <span class="info-value hourlyRate"></span>
                </div>
                <div class="info-row">
                  <span class="info-label">Payment Method</span>
                  <span class="info-value paymentMethod"></span>
                </div>
              </div>
            </div>

            <!-- Approved By -->
            <div class="info-card">
              <div class="info-card-header">APPROVED BY</div>
              <div class="info-card-body">
                <div class="received-by-body approvedBy"></div>
              </div>
            </div>

            <!-- Received By -->
            <div class="received-by-card">
              <div class="received-by-header">RECEIVED BY</div>
              <div class="received-by-body receivedBy"></div>
            </div>
          </div>

          <!-- Right Section -->
          <div class="right-section">
            <div class="salary-slip-card">
              <div class="slip-header">
                <div class="slip-header-text">
                  <h3>SALARY SLIP</h3>
                  <p>HOSPITAL</p>
                </div>
                <div class="hospital-logo">
                  <i class="fa-solid fa-hospital"></i>
                </div>
              </div>

              <div class="salary-table">
                <table>
                  <thead>
                    <tr>
                      <th>EARNINGS</th>
                      <th>TOTAL</th>
                      <th>AMOUNT</th>
                      <th>DEDUCTIONS</th>
                      <th>AMOUNT</th>
                    </tr>
                  </thead>
                  <tbody>
                    <tr>
                      <td>Sub-Basic Pay:</td>
                      <td class="subBasicPayTotal"></td>
                      <td class="subBasicPay"></td>
                      <td>SSS</td>
                      <td class="sss"></td>
                    </tr>
                    <tr>
                      <td>Basic Pay:</td>
                      <td class="basicPayTotal"></td>
                      <td class="basicPay"></td>
                      <td>PhilHealth</td>
                      <td class="philHealth"></td>
                    </tr>
                    <tr>
                      <td>Absent:</td>
                      <td class="absent"></td>
                      <td class="absentPay"></td>
                      <td>Pag-ibig</td>
                      <td class="pagIbig"></td>
                    </tr>
                    <tr>
                      <td>Under time:</td>
                      <td class="underTime"></td>
                      <td class="underTimePay"></td>
                      <td>Tax</td>
                      <td class="tax"></td>
                    </tr>
                    <tr>
                      <td>Over Time Pay:</td>
                      <td class="overTimePay"></td>
                      <td class="overTimePayPay"></td>
                      <td>SSS Loan</td>
                      <td>-</td>
                    </tr>
                    <tr>
                      <td>Legal Holiday:</td>
                      <td class="legalHoliday"></td>
                      <td class="legalHolidayPay"></td>
                      <td>Cash Advance</td>
                      <td>-</td>
                    </tr>
                    <tr>
                      <td>Special Non Working Holiday:</td>
                      <td class="specialNonWorkingHoliday"></td>
                      <td class="specialNonWorkingHolidayPay"></td>
                      <td>Other Deduction:</td>
                      <td class="otherDeduction"></td>
                    </tr>
                    <tr>
                      <td>Holiday OT Pay:</td>
                      <td class="holidayOTPay"></td>
                      <td class="holidayOTPayPay"></td>
                      <td></td>
                      <td></td>
                    </tr>
                    <tr>
                      <td>Rest Day Pay:</td>
                      <td class="restDayPay"></td>
                      <td class="restDayPayPay"></td>
                      <td></td>
                      <td></td>
                    </tr>
                    <tr class="total-row">
                      <td colspan="2">Basic Pay:</td>
                      <td class="grossPay"></td>
                      <td>Total Deduction:</td>
                      <td class="totalDeduction"></td>
                    </tr>
                    <tr class="total-row">
                      <td colspan="2">Net Pay:</td>
                      <td class="netPay"></td>
                      <td></td>
                      <td></td>
                    </tr>
                  </tbody>
                </table>
              </div>
            </div>

            <button class="btn-back" onclick="showOverview()"><i class="fa-solid fa-arrow-left"></i> Back to
              Overview</button>
          </div>
        </div>
      </div>
    </div>
  </main>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
  <script>
    const empID = '<?php echo addslashes($employeeID ?? ""); ?>';
    let rowsAll = [];

    function filterRowsByUI(rows) {
      const yearSel = document.getElementById('yearFilter').value;
      const monthSel = document.getElementById('monthFilter').value; // e.g., 'january', 'all'
      if (!rows || !rows.length) return [];
      return rows.filter(r => {
        const d = new Date(r.period_end || r.pay_date || r.date || '');
        if (isNaN(d)) return true;
        const yOk = !yearSel || String(d.getFullYear()) === String(yearSel);
        const mOk = monthSel === 'all' || monthSel === '' || d.toLocaleString('en-US', { month: 'long' }).toLowerCase() === monthSel.toLowerCase();
        return yOk && mOk;
      });
    }

    function renderRows(rows) {
      try {
        const filtered = filterRowsByUI(rows);
        const tbody = document.getElementById('salaryTableBody');
        tbody.innerHTML = '';
        let totalGross = 0;
        let totalNet = 0;
        let totalDed = 0;
        filtered.forEach((r, i) => {
          const date = r.period_end || r.pay_date || r.date || '';
          const basic = Number(r.basic_pay || r.basic || 0);
          const overtime = Number(r.ot_pay || r.overtime_pay || r.overtime || 0);
          const deduction = Number(r.total_deduction || r.deduction || 0);
          const net = Number(r.net_pay || r.net || 0);
          const gross = Number(r.gross_pay || basic + overtime + Number(r.allowances || 0));
          totalGross += Number.isFinite(gross) ? gross : 0;
          totalNet += Number.isFinite(net) ? net : 0;
          totalDed += Number.isFinite(deduction) ? deduction : 0;
          const tr = document.createElement('tr');
          tr.innerHTML = `
            <td>${date}</td>
            <td>₱${basic.toLocaleString('en-PH', { minimumFractionDigits: 2 })}</td>
            <td>₱${overtime.toLocaleString('en-PH', { minimumFractionDigits: 2 })}</td>
            <td>₱${deduction.toLocaleString('en-PH', { minimumFractionDigits: 2 })}</td>
            <td>₱${net.toLocaleString('en-PH', { minimumFractionDigits: 2 })}</td>
            <td><button class="btn-view" data-index="${i}"><i class="fa-solid fa-eye"></i> View</button></td>
          `;
          tbody.appendChild(tr);
        });
        document.getElementById('totalEarnings').textContent = '₱' + totalGross.toLocaleString('en-PH', { minimumFractionDigits: 2 });
        document.getElementById('averagePay').textContent = '₱' + (filtered.length ? (totalNet / filtered.length) : 0).toLocaleString('en-PH', { minimumFractionDigits: 2 });
        document.getElementById('totalDeductions').textContent = '₱' + totalDed.toLocaleString('en-PH', { minimumFractionDigits: 2 });
        document.getElementById('payPeriods').textContent = String(filtered.length);
        tbody.querySelectorAll('.btn-view').forEach(btn => {
          btn.addEventListener('click', () => {
            const index = Number(btn.dataset.index);
            const rec = filtered[index];
            if (rec) showDetails(rec);
          });
        });
      } catch (e) {
        console.error(e);
      }
    }
    async function loadPayroll() {
      try {
        const res = await fetch('/HR-EMPLOYEE-MANAGEMENT/API/consumer_payroll.php', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
          body: JSON.stringify({ emp_code: empID })
        });
        const resp = await res.json();
        const payload = resp.data || resp.payload || [];
        rowsAll = Array.isArray(payload) ? payload : (payload.data || payload.records || []);
        rowsAll = rowsAll.filter(r => String(r.emp_code || '').trim() === String(empID || '').trim());
        renderRows(rowsAll);
      } catch (e) {
        console.error(e);
      }
    }
    loadPayroll();

    let attendanceAnalytics = null;
    async function loadAttendanceSummary() {
      try {
        const res = await fetch('/HR-EMPLOYEE-MANAGEMENT/API/consumer_attendance.php', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
          body: JSON.stringify({ emp_code: empID })
        });
        const data = await res.json();
        attendanceAnalytics = (data && data.analytics) ? data.analytics : null;
      } catch (e) { attendanceAnalytics = null; }
    }
    loadAttendanceSummary();

    document.getElementById('yearFilter').addEventListener('change', () => renderRows(rowsAll));
    document.getElementById('monthFilter').addEventListener('change', () => renderRows(rowsAll));

    function showDetails(data) {

      document.getElementById('overviewPage').classList.add('hidden');
      document.getElementById('detailsPage').classList.add('active');

      const fmt = v => '₱ ' + Number(v || 0).toLocaleString('en-PH', { minimumFractionDigits: 2 });
      const set = (sel, val) => { const el = document.querySelector(sel); if (el) el.textContent = val ?? '—'; };

      set('.full_name', data.full_name);
      set('.empID', data.emp_code);
      set('.position', data.position);
      set('.status', (data.employment_type || '').toUpperCase());

      set('.payDate', data.period_end);
      set('.payType', '15/30');
      set('.period', (data.period_start || '') + ' - ' + (data.period_end || ''));
      set('.monthlyRate', fmt(data.basic_pay));
      set('.dailyRate', '');
      set('.hourlyRate', '');
      set('.paymentMethod', 'BANK TRANSFER');

      set('.approvedBy', 'HR');
      set('.receivedBy', data.full_name);

      const basic = Number(data.basic_pay || data.basic || 0);
      const subBasic = Number(data.sub_basic_pay || 0);
      const overtime = Number(data.ot_pay || data.overtime_pay || data.overtime || 0);
      const legalHoliday = Number(data.legal_holiday_pay || 0);
      const specialHoliday = Number(data.special_holiday_pay || data.special_non_working_holiday_pay || 0);
      const holidayOT = Number(data.holiday_ot_pay || 0);
      const restDay = Number(data.rest_day_pay || 0);
      const absentUnits = Number(data.absent_days || data.absent_hours || 0);
      const absentAmt = Number(data.absent_deduction || data.absent_amount || 0);
      const underUnits = Number(data.undertime_hours || data.under_time || 0);
      const underAmt = Number(data.undertime_deduction || data.under_time_amount || 0);

      const sss = Number(data.sss || data.sss_contrib || 0);
      const phil = Number(data.philhealth || data.phil_health || 0);
      const pagibig = Number(data.pagibig || data.pag_ibig || 0);
      const tax = Number(data.tax || data.withholding_tax || 0);
      const otherDed = Number(data.other_deductions || data.other_deduction || 0);

      const allowances = Number(data.allowances || 0);
      const gross = Number(data.gross_pay || (basic + subBasic + overtime + legalHoliday + specialHoliday + holidayOT + restDay + allowances));
      const totalDed = Number(data.total_deduction || (sss + phil + pagibig + tax + otherDed + absentAmt + underAmt));
      const net = Number(data.net_pay || (gross - totalDed));

      set('.subBasicPayTotal', fmt(subBasic));
      set('.subBasicPay', fmt(subBasic));
      set('.basicPayTotal', fmt(basic));
      set('.basicPay', fmt(basic));
      set('.absent', String(absentUnits));
      set('.absentPay', fmt(absentAmt));
      set('.underTime', String(underUnits));
      set('.underTimePay', fmt(underAmt));
      set('.overTimePay', fmt(overtime));
      set('.overTimePayPay', fmt(overtime));
      set('.legalHoliday', fmt(legalHoliday));
      set('.legalHolidayPay', fmt(legalHoliday));
      set('.specialNonWorkingHoliday', fmt(specialHoliday));
      set('.specialNonWorkingHolidayPay', fmt(specialHoliday));
      set('.holidayOTPay', fmt(holidayOT));
      set('.holidayOTPayPay', fmt(holidayOT));
      set('.restDayPay', fmt(restDay));
      set('.restDayPayPay', fmt(restDay));
      set('.sss', fmt(sss));
      set('.philHealth', fmt(phil));
      set('.pagIbig', fmt(pagibig));
      set('.tax', fmt(tax));
      set('.otherDeduction', fmt(otherDed));
      set('.grossPay', fmt(gross));
      set('.totalDeduction', fmt(totalDed));
      set('.netPay', fmt(net));

      const absCnt = Number((attendanceAnalytics && (attendanceAnalytics.absences_count ?? attendanceAnalytics.dashboard_stats?.absent_days)) || 0);
      set('.absentDaysCount', String(Number.isFinite(absCnt) ? absCnt : 0));
    }

    function showOverview() {
      document.getElementById('overviewPage').classList.remove('hidden');
      document.getElementById('detailsPage').classList.remove('active');
    }

    function exportAsImage() {
      alert('Export as Image functionality would be implemented here');
      // In a real implementation, this would use a library like html2canvas
      // to capture the salary slip as an image
    }

    // Highlight active sidebar link
    const currentPage = window.location.pathname.split("/").pop();
    document.querySelectorAll(".sidebar .nav li a").forEach(link => {
      if (link.getAttribute("href") === currentPage) {
        link.parentElement.classList.add("active");
      }
    });

    // Add some interactivity to filter selects
    document.querySelectorAll('.filter-select').forEach(select => {
      select.addEventListener('change', function () {
        // In a real implementation, this would filter the salary data
        console.log(`Filter changed: ${this.id} = ${this.value}`);
      });
    });

    function runSalarySlipTests() {
      const sample = {
        full_name: 'Test User',
        emp_code: 'EMP-001',
        position: 'Engineer',
        employment_type: 'Regular',
        period_start: '2025-12-01',
        period_end: '2025-12-15',
        basic_pay: 10000,
        sub_basic_pay: 2000,
        ot_pay: 1500,
        allowances: 500,
        sss: 300,
        philhealth: 200,
        pagibig: 100,
        tax: 400,
        other_deductions: 50,
        absent_deduction: 100,
        undertime_deduction: 50
      };
      showDetails(sample);
      attendanceAnalytics = { absences_count: 4, dashboard_stats: { absent_days: 4 } };
      const absentDays = document.querySelector('.absentDaysCount')?.textContent || '0';
      console.log('TEST absentDaysCount', absentDays === '4' ? 'PASS' : 'FAIL', absentDays);
      const getNum = sel => {
        const t = (document.querySelector(sel)?.textContent || '').replace(/[^0-9.\-]/g, '');
        const n = parseFloat(t);
        return isNaN(n) ? 0 : n;
      };
      const grossExpected = 10000 + 2000 + 1500 + 500;
      const dedExpected = 300 + 200 + 100 + 400 + 50 + 100 + 50;
      const netExpected = grossExpected - dedExpected;
      const okGross = Math.abs(getNum('.grossPay') - grossExpected) < 0.01;
      const okDed = Math.abs(getNum('.totalDeduction') - dedExpected) < 0.01;
      const okNet = Math.abs(getNum('.netPay') - netExpected) < 0.01;
      console.log('TEST grossPay', okGross ? 'PASS' : 'FAIL', getNum('.grossPay'), grossExpected);
      console.log('TEST totalDeduction', okDed ? 'PASS' : 'FAIL', getNum('.totalDeduction'), dedExpected);
      console.log('TEST netPay', okNet ? 'PASS' : 'FAIL', getNum('.netPay'), netExpected);
    }
    try {
      const params = new URLSearchParams(window.location.search);
      if (params.get('test') === '1') runSalarySlipTests();
    } catch (e) { }
  </script>
</body>

</html>