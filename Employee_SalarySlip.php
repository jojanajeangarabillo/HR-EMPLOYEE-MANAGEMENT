<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Employee Salary Slip</title>
  <link rel="stylesheet" href="employee-sidebar.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/7.0.1/css/all.min.css">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

  <style>
    
    .table thead th {
      background-color: #1f3b83;
      color: white;
      text-align: center;
    }

    .table tbody td {
      text-align: center;
      vertical-align: middle;
    }

    .btn-view {
      background-color: #00c853;
      color: white;
      border: none;
      padding: 5px 15px;
      border-radius: 5px;
      cursor: pointer;
      transition: background-color 0.2s ease;
    }

    .btn-view:hover {
      background-color: #009624;
    }

    .table-container {
      background: white;
      border-radius: 10px;
      box-shadow: 0 2px 6px rgba(0, 0, 0, 0.1);
      padding: 20px;
    }

    .table-title {
      display: flex;
      align-items: center;
      gap: 10px;
      color: #1f3b83;
      font-weight: bold;
      font-size: 28px;
      margin-bottom: 20px;
    }

    /* Salary Details Page Styles */
    .details-page {
      display: none;
    }

    .details-page.active {
      display: block;
    }

    .overview-page.hidden {
      display: none;
    }

    :root {
      --color-primary: #1f3b83;
      --color-primary-dark: #142b66;
      --color-white: #ffffff;
      --color-bg: #f5f5f5;
      --color-text: #333333;
      --color-border: #ddd;
      --color-success: #00c853;
      --font-family: 'Roboto', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
      --shadow-md: 0 4px 6px rgba(0, 0, 0, 0.1);
      --radius-md: 10px;
    }

    .details-container {
      background: var(--color-white);
      border-radius: var(--radius-md);
      box-shadow: var(--shadow-md);
      overflow: hidden;
    }

    .header-section {
      display: flex;
      justify-content: space-between;
      align-items: center;
      padding: 30px;
      border-bottom: 2px solid var(--color-border);
    }

    .header-title {
      display: flex;
      align-items: center;
      gap: 15px;
    }

    .header-title h2 {
      font-size: 32px;
      color: var(--color-text);
      font-weight: 600;
    }

    .header-title i {
      color: var(--color-primary);
      font-size: 32px;
    }

    .export-buttons {
      display: flex;
      gap: 5px;
      align-items: center;
    }

    .export-label {
      font-size: 14px;
      color: var(--color-text);
      font-weight: 500;
    }

    .btn-export {
      padding: 6px 10px;
      border: 1px solid var(--color-border);
      background: var(--color-white);
      border-radius: 6px;
      cursor: pointer;
      font-size: 16px;
      display: flex;
      align-items: center;
      transition: all 0.2s ease;
    }

    .btn-export:hover {
      background-color: var(--color-bg);
    }

    .btn-export i {
      font-size: 16px;
    }

    .content-wrapper {
      display: grid;
      grid-template-columns: 350px 1fr;
      padding: 30px;
      gap: 30px;
    }

    /* Left side - Employee Info and Pay Info */
    .left-section {
      display: flex;
      flex-direction: column;
      gap: 20px;
    }

    .info-card {
      background: var(--color-white);
      border: 2px solid var(--color-primary);
      border-radius: 8px;
      margin-bottom: 20px;
      overflow: hidden;
    }

    .info-card-header {
      background-color: var(--color-primary);
      color: var(--color-white);
      padding: 12px 20px;
      font-weight: 600;
      font-size: 16px;
    }

    .info-card-body {
      padding: 20px;
    }

    .info-row {
      display: flex;
      justify-content: space-between;
      padding: 10px 0;
      border-bottom: 1px solid var(--color-border);
    }

    .info-row:last-child {
      border-bottom: none;
    }

    .info-label {
      color: #666;
      font-size: 14px;
      font-weight: 500;
      text-transform: uppercase;
    }

    .info-value {
      color: var(--color-text);
      font-size: 14px;
      font-weight: 600;
    }

    .received-by-card {
      background: var(--color-white);
      border: 2px solid var(--color-primary);
      border-radius: 8px;
      overflow: hidden;
    }

    .received-by-header {
      background-color: var(--color-primary);
      color: var(--color-white);
      padding: 12px 20px;
      font-weight: 600;
      font-size: 16px;
      text-align: center;
    }

    .received-by-body {
      padding: 30px;
      text-align: center;
      font-size: 16px;
      font-weight: 600;
      color: var(--color-text);
    }

    /* Right side - Salary Slip */
    .right-section {
      flex: 1;
    }

    .salary-slip-card {
      background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
      border-radius: var(--radius-md);
      padding: 25px;
      color: var(--color-white);
      box-shadow: 0 8px 20px rgba(102, 126, 234, 0.4);
      height: fit-content;
    }

    .slip-header {
      display: flex;
      justify-content: space-between;
      align-items: center;
      margin-bottom: 25px;
      padding-bottom: 15px;
      border-bottom: 2px solid rgba(255, 255, 255, 0.3);
    }

    .slip-header-text {
      flex: 1;
    }

    .slip-header h3 {
      font-size: 32px;
      font-weight: 700;
      margin: 0;
      margin-bottom: 3px;
    }

    .slip-header p {
      font-size: 18px;
      opacity: 0.95;
      margin: 0;
    }

    .slip-header .hospital-logo {
      background: var(--color-white);
      border-radius: 50%;
      width: 55px;
      height: 55px;
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 22px;
      color: var(--color-primary);
      flex-shrink: 0;
    }

    .salary-table {
      background: rgba(255, 255, 255, 0.15);
      border-radius: 8px;
      overflow: hidden;
    }

    .salary-table table {
      width: 100%;
      border-collapse: collapse;
    }

    .salary-table thead {
      background: rgba(0, 0, 0, 0.2);
    }

    .salary-table th {
      padding: 10px 8px;
      text-align: center;
      font-weight: 600;
      font-size: 12px;
      text-transform: uppercase;
      border-bottom: 2px solid rgba(255, 255, 255, 0.3);
    }

    .salary-table td {
      padding: 8px;
      text-align: center;
      font-size: 13px;
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
      font-size: 14px !important;
    }

    .total-row td {
      padding: 12px 8px !important;
      border-top: 2px solid rgba(255, 255, 255, 0.3) !important;
    }

    .btn-back {
      margin-top: 30px;
      padding: 12px 30px;
      background-color: var(--color-primary);
      color: var(--color-white);
      border: none;
      border-radius: 6px;
      font-size: 16px;
      cursor: pointer;
      transition: all 0.2s ease;
      box-shadow: var(--shadow-md);
    }

    .btn-back:hover {
      background-color: var(--color-primary-dark);
      transform: translateY(-2px);
      box-shadow: 0 6px 12px rgba(0, 0, 0, 0.15);
    }

    @media print {

      .sidebar,
      .export-buttons,
      .btn-back {
        display: none;
      }

      .main-content {
        margin-left: 0;
        width: 100%;
      }
    }
  </style>
</head>

<body>
  <!-- Sidebar -->
  <aside class="sidebar">
    <h1>Welcome</h1>
    <img src="images/profile.png" alt="Profile" width="80" height="80">

    <ul class="sidebar-menu">
      <li><a href="Employee_Profile.php" style="display: block; text-align: center; padding-right: 75px;">My Profile</a></li>
      <li class="menu-title">Menu Board</li>
      <li><a href="Employee_Dashboard.php"><i class="fa-solid fa-grip"></i> Dashboard</a></li>
      <li><a href="Employee_SalarySlip.php" class="active"><i class="fa-solid fa-user-group"></i> Salary Slip</a></li>
      <li><a href="Employee_Requests.php"><i class="fa-solid fa-code-branch"></i> Requests</a></li>
      <li><a href="#"><i class="fa-solid fa-right-from-bracket"></i> Logout</a></li>
    </ul>
  </aside>

  <!-- Main Content -->
  <main class="main-content">
    <!-- Overview Page -->
    <div class="overview-page" id="overviewPage">
      <div class="table-container">
        <div class="table-title">
          <h2>Salary Overview <i class="fa-solid fa-folder"></i></h2>
        </div>

        <table class="table table-bordered table-hover align-middle">
          <thead>
            <tr>
              <th>Date</th>
              <th>Basic Pay</th>
              <th>Overtime Pay</th>
              <th>Deduction</th>
              <th>Net Pay</th>
              <th>Action</th>
            </tr>
          </thead>
          <tbody>
            <tr>
              <td>October 15, 2025</td>
              <td>₱60,000.00</td>
              <td>₱3,500.00</td>
              <td>₱2,200.00</td>
              <td>₱61,300.00</td>
              <td><button class="btn-view" onclick="showDetails()">View</button></td>
            </tr>
            <tr>
              <td>October 30, 2025</td>
              <td>₱60,000.00</td>
              <td>₱0.00</td>
              <td>₱2,200.00</td>
              <td>₱57,800.00</td>
              <td><button class="btn-view" onclick="showDetails()">View</button></td>
            </tr>
            <tr>
              <td>September 15, 2025</td>
              <td>₱58,000.00</td>
              <td>₱2,800.00</td>
              <td>₱1,900.00</td>
              <td>₱58,900.00</td>
              <td><button class="btn-view" onclick="showDetails()">View</button></td>
            </tr>
            <tr>
              <td>September 30, 2025</td>
              <td>₱58,000.00</td>
              <td>₱1,200.00</td>
              <td>₱1,800.00</td>
              <td>₱57,400.00</td>
              <td><button class="btn-view" onclick="showDetails()">View</button></td>
            </tr>
            <tr>
              <td>August 15, 2025</td>
              <td>₱56,500.00</td>
              <td>₱2,000.00</td>
              <td>₱2,100.00</td>
              <td>₱56,400.00</td>
              <td><button class="btn-view" onclick="showDetails()">View</button></td>
            </tr>
            <tr>
              <td>August 30, 2025</td>
              <td>₱56,500.00</td>
              <td>₱0.00</td>
              <td>₱2,000.00</td>
              <td>₱54,500.00</td>
              <td><button class="btn-view" onclick="showDetails()">View</button></td>
            </tr>
            <tr>
              <td>July 15, 2025</td>
              <td>₱55,000.00</td>
              <td>₱1,500.00</td>
              <td>₱2,200.00</td>
              <td>₱54,300.00</td>
              <td><button class="btn-view" onclick="showDetails()">View</button></td>
            </tr>
            <tr>
              <td>July 30, 2025</td>
              <td>₱55,000.00</td>
              <td>₱1,800.00</td>
              <td>₱1,900.00</td>
              <td>₱54,900.00</td>
              <td><button class="btn-view" onclick="showDetails()">View</button></td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <!-- Details Page -->
    <div class="details-page" id="detailsPage">
      <div class="details-container">
        <div class="header-section">
          <div class="header-title">
            <h2>Salary Details</h2>
            <i class="fa-solid fa-folder"></i>
          </div>
          <div class="export-buttons">
            <span class="export-label">Export As</span>
            <button class="btn-export" onclick="window.print()" title="Export as PDF">
              <i class="fa-solid fa-file-pdf" style="color: #d32f2f;"></i>
            </button>
            <button class="btn-export" onclick="window.print()" title="Export as Image">
              <i class="fa-solid fa-image" style="color: #1976d2;"></i>
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
                  <span class="info-value">RIVER FUENTABELLA</span>
                </div>
                <div class="info-row">
                  <span class="info-label">ID</span>
                  <span class="info-value">25-0001</span>
                </div>
                <div class="info-row">
                  <span class="info-label">Position</span>
                  <span class="info-value">DOCTOR</span>
                </div>
                <div class="info-row">
                  <span class="info-label">Status</span>
                  <span class="info-value">REGULAR</span>
                </div>
              </div>
            </div>

            <!-- Pay Out Information -->
            <div class="info-card">
              <div class="info-card-header">PAY OUT INFORMATION</div>
              <div class="info-card-body">
                <div class="info-row">
                  <span class="info-label">Pay Date</span>
                  <span class="info-value">OCTOBER 15, 2025</span>
                </div>
                <div class="info-row">
                  <span class="info-label">Pay Type</span>
                  <span class="info-value">15/30</span>
                </div>
                <div class="info-row">
                  <span class="info-label">Period</span>
                  <span class="info-value">SEPTEMBER 26 - OCTOBER 10, 2025</span>
                </div>
                <div class="info-row">
                  <span class="info-label">Monthly Rate</span>
                  <span class="info-value">₱ 60,000.00</span>
                </div>
                <div class="info-row">
                  <span class="info-label">Daily Rate</span>
                  <span class="info-value">₱ 2,000.00</span>
                </div>
                <div class="info-row">
                  <span class="info-label">Hourly Rate</span>
                  <span class="info-value">₱ 250.00</span>
                </div>
                <div class="info-row">
                  <span class="info-label">Payment Method</span>
                  <span class="info-value">BANK TRANSFER</span>
                </div>
              </div>
            </div>

            <!-- Approved By -->
            <div class="info-card">
              <div class="info-card-header">APPROVED BY</div>
              <div class="info-card-body">
                <div class="received-by-body">HR</div>
              </div>
            </div>

            <!-- Received By -->
            <div class="received-by-card">
              <div class="received-by-header">RECEIVED BY</div>
              <div class="received-by-body">RIVER FUENTABELLA</div>
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
                      <td>30</td>
                      <td>₱ 60,000.00</td>
                      <td>SSS</td>
                      <td>₱200.00</td>
                    </tr>
                    <tr>
                      <td>Basic Pay:</td>
                      <td></td>
                      <td>₱ 60,000.00</td>
                      <td>PhilHealth</td>
                      <td>₱500.00</td>
                    </tr>
                    <tr>
                      <td>Absent:</td>
                      <td>0</td>
                      <td>-</td>
                      <td>Pag-ibig</td>
                      <td>₱500.00</td>
                    </tr>
                    <tr>
                      <td>Under time:</td>
                      <td>0</td>
                      <td>-</td>
                      <td>Tax</td>
                      <td>₱1,000.00</td>
                    </tr>
                    <tr>
                      <td>Over Time Pay:</td>
                      <td>6</td>
                      <td>₱ 3,500.00</td>
                      <td>SSS Loan</td>
                      <td>-</td>
                    </tr>
                    <tr>
                      <td>Legal Holiday:</td>
                      <td>-</td>
                      <td>-</td>
                      <td>Cash Advance</td>
                      <td>-</td>
                    </tr>
                    <tr>
                      <td>Special Non Working Holiday:</td>
                      <td>-</td>
                      <td>-</td>
                      <td>Other Deduction:</td>
                      <td>-</td>
                    </tr>
                    <tr>
                      <td>Holiday OT Pay:</td>
                      <td>-</td>
                      <td>-</td>
                      <td></td>
                      <td></td>
                    </tr>
                    <tr>
                      <td>Rest Day Pay:</td>
                      <td>-</td>
                      <td>-</td>
                      <td></td>
                      <td></td>
                    </tr>
                    <tr class="total-row">
                      <td colspan="2">Gross Pay:</td>
                      <td>₱ 60,000.00</td>
                      <td>Total Deduction:</td>
                      <td>₱2,200.00</td>
                    </tr>
                    <tr class="total-row">
                      <td colspan="2">Net Pay:</td>
                      <td colspan="3">₱61,300.00</td>
                    </tr>
                  </tbody>
                </table>
              </div>
            </div>

            <button class="btn-back" onclick="showOverview()">Back</button>
          </div>
        </div>
      </div>
    </div>
  </main>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
  <script>
    function showDetails() {
      document.getElementById('overviewPage').classList.add('hidden');
      document.getElementById('detailsPage').classList.add('active');
    }

    function showOverview() {
      document.getElementById('overviewPage').classList.remove('hidden');
      document.getElementById('detailsPage').classList.remove('active');
    }
  </script>
</body>

</html>