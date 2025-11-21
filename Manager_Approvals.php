<?php
session_start();
require 'admin/db.connect.php';

// Manager name
$managername = $_SESSION['fullname'] ?? "Manager";


// MENUS
$menus = [
  "HR Director" => [
    "Dashboard" => "Manager_Dashboard.php",
    "Applicants" => "Manager_Applicants.php",
    "Pending Applicants" => "Manager_PendingApplicants.php",
    "Newly Hired" => "Newly-Hired.php",
    "Employees" => "Manager_Employees.php",
    "Requests" => "Manager_Request.php",
    "Vacancies" => "Manager_Vacancies.php",
    "Job Post" => "Manager-JobPosting.php",
    "Calendar" => "Manager_Calendar.php",
    "Approvals" => "Manager_Approvals.php",
    "Settings" => "Manager_LeaveSettings.php",
    "Logout" => "Login.php"
  ],

  "HR Manager" => [
    "Dashboard" => "Manager_Dashboard.php",
    "Applicants" => "Manager_Applicants.php",
    "Pending Applicants" => "Manager_PendingApplicants.php",
    "Newly Hired" => "Newly-Hired.php",
    "Employees" => "Manager_Employees.php",
    "Requests" => "Manager_Request.php",
    "Vacancies" => "Manager_Vacancies.php",
    "Job Post" => "Manager-JobPosting.php",
    "Calendar" => "Manager_Calendar.php",
    "Approvals" => "Manager_Approvals.php",
    "Settings" => "Manager_LeaveSettings.php",
    "Logout" => "Login.php"
  ],

  "Recruitment Manager" => [
    "Dashboard" => "Manager_Dashboard.php",
    "Applicants" => "Manager_Applicants.php",
    "Pending Applicants" => "Manager_PendingApplicants.php",
    "Newly Hired" => "Newly-Hired.php",
    "Vacancies" => "Manager_Vacancies.php",
    "Logout" => "Login.php"
  ],

  "HR Officer" => [
    "Dashboard" => "Manager_Dashboard.php",
    "Applicants" => "Manager_Applicants.php",
    "Pending Applicants" => "Manager_PendingApplicants.php",
    "Newly Hired" => "Newly-Hired.php",
    "Employees" => "Manager_Employees.php",
    "Logout" => "Login.php"
  ],

];

$role = $_SESSION['sub_role'] ?? "HR Manager";

// --- Handle Approve/Reject Actions ---
if (isset($_GET['action'], $_GET['id'])) {
  $request_id = intval($_GET['id']);
  $action = $_GET['action'];

  $status = ($action === 'accept') ? 'Approved' : (($action === 'reject') ? 'Rejected' : null);

  if ($status) {
    $stmt = $conn->prepare("UPDATE employee_request SET status = ? WHERE request_id = ?");
    $stmt->bind_param("si", $status, $request_id);
    $stmt->execute();
    $stmt->close();
  }

  // Redirect to prevent resubmission
  header("Location: Manager_Approvals.php");
  exit;
}

$requests = [];
$stmt = $conn->prepare("SELECT r.request_id, e.empID, e.fullname, e.department, r.request_type_name, r.status, r.reason, r.requested_at 
                        FROM employee_request r
                        JOIN employee e ON r.empID = e.empID
                        LEFT JOIN department d ON e.department = d.deptID
                        ORDER BY r.requested_at DESC");
$stmt->execute();
$res = $stmt->get_result();
while ($row = $res->fetch_assoc()) {
  $requests[] = $row;
}
$stmt->close();

?>


<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Admin Dashboard</title>

  <link rel="stylesheet" href="manager-sidebar.css">

  <!-- Font Awesome Icons -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/7.0.1/css/all.min.css"
    crossorigin="anonymous" referrerpolicy="no-referrer" />

  <style>
    body {
      font-family: 'Poppins', 'Roboto', sans-serif;
      margin: 0;
      display: flex;
      background-color: #f1f5fc;
      color: #111827;
    }

    .main-content {
      padding: 40px 30px;
      margin-left: 220px;
      display: flex;
      flex-direction: column;
    }

    .main-content-header h1 {
      padding: 25px 30px;
      margin: 0;
      font-size: 2rem;
      margin-bottom: 40px;
      color: #1E3A8A;
    }

    /* --- TABLE & FILTER STYLING --- */
    .table-container {
      max-width: 1200px;
      margin: 0 auto;
      margin-left: 200px;
    }

    .controls-bar {
      display: flex;
      justify-content: flex-start;
      align-items: center;
      margin-bottom: 20px;
      gap: 20px;
      flex-wrap: wrap;
    }

    .search-box {
      display: flex;
      align-items: center;
      gap: 10px;
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
      transition: border-color 0.3s;
    }

    .search-box input:focus {
      outline: none;
      border-color: #1E3A8A;
    }

    .search-box button {
      background-color: #1E3A8A;
      color: white;
      border: none;
      border-radius: 6px;
      padding: 10px 15px;
      font-size: 14px;
      cursor: pointer;
      transition: all 0.3s;
    }

    .search-box button:hover {
      background-color: #1e40af;
      transform: translateY(-2px);
      box-shadow: 0 4px 8px rgba(30, 58, 138, 0.3);
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
      min-width: 150px;
      padding: 16px 12px;
      text-align: center;
      border: 1px solid #e0e0e0;
    }

    thead {
      background-color: #1E3A8A;
      color: #ffffff;
      font-weight: 600;
    }

    tbody tr:hover {
      background-color: #f8f9fa;
    }

    tbody tr:nth-child(even) {
      background-color: #fafafa;
    }

    .action-icons {
      display: flex;
      justify-content: center;
      gap: 15px;
    }

    .action-icons a {
      color: #333;
      text-decoration: none;
      font-size: 18px;
      transition: color 0.2s ease, transform 0.2s ease;
    }

    .action-icons a:hover {
      transform: scale(1.1);
    }

    .action-icons a.accept:hover {
      color: #10b981;
    }

    .action-icons a.reject:hover {
      color: #dc3545;
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
  </style>
</head>

<body>
  <!-- SIDEBAR -->
  <div class="sidebar">
    <div class="sidebar-logo">
      <img src="Images/hospitallogo.png" alt="Hospital Logo">
    </div>

    <div class="sidebar-name">
      <p><?php echo "Welcome, $managername"; ?></p>
    </div>

    <ul class="nav">
      <?php foreach ($menus[$role] as $label => $link): ?>
        <li><a href="<?php echo $link; ?>"><?php echo $label; ?></a></li>
      <?php endforeach; ?>
    </ul>
  </div>

  <!-- MAIN CONTENT -->
  <div class="main-content">
    <div class="main-content-header">
      <h1>Employee Requests</h1>
    </div>

    <div class="table-container">
      <div class="controls-bar">
        <div class="search-box">
          <input type="text" id="searchInput" placeholder="Search requests..." onkeyup="filterTable()">
          <button onclick="filterTable()"><i class="fa-solid fa-filter"></i> Filter</button>
        </div>
      </div>

      <div class="table-responsive">
        <table>
          <thead>
            <tr>
              <th>Employee ID</th>
              <th>Employee Name</th>
              <th>Department</th>
              <th>Request Type</th>
              <th>Reason</th>
              <th>Date</th>
              <th>Action</th>
            </tr>
          </thead>
          <tbody id="employeeTable">
            <?php if (!empty($requests)): ?>
              <?php foreach ($requests as $req): ?>
                <tr>
                  <td><?= htmlspecialchars($req['empID']) ?></td>
                  <td><?= htmlspecialchars($req['fullname']) ?></td>
                  <td><?= htmlspecialchars($req['department'] ?? 'N/A') ?></td>
                  <td><?= htmlspecialchars($req['request_type_name']) ?></td>
                  <td><?= htmlspecialchars($req['reason']) ?></td>
                  <td><?= date('Y-m-d', strtotime($req['requested_at'])) ?></td>
                  <td class="action-icons">
                    <?php if ($req['status'] === 'Approved'): ?>
                      <span style="color:green;font-weight:bold;">Approved</span>
                    <?php elseif ($req['status'] === 'Rejected'): ?>
                      <span style="color:red;font-weight:bold;">Rejected</span>
                    <?php else: ?>
                      <a href="?id=<?= $req['request_id'] ?>&action=accept" class="accept"><i
                          class="fa-solid fa-check"></i></a>
                      <a href="?id=<?= $req['request_id'] ?>&action=reject" class="reject"><i
                          class="fa-solid fa-xmark"></i></a>
                    <?php endif; ?>
                  </td>
                </tr>
              <?php endforeach; ?>
            <?php else: ?>
              <tr>
                <td colspan="7">No requests found.</td>
              </tr>
            <?php endif; ?>
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
  </script>
</body>

</html>