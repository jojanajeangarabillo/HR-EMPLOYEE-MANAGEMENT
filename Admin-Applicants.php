<?php
session_start();
require 'admin/db.connect.php';

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
  <title>Admin Applicants</title>
  <link rel="stylesheet" href="admin-sidebar.css">
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
      color: #1e3a8a;
      display: flex;
      flex-direction: column;
    }

    .main-content-header h1 {
      margin: 0;
      font-size: 2rem;
      margin-bottom: 40px;
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
      width: 100%;
      border-collapse: collapse;
      min-width: 800px;
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

    .table tbody td {
      padding: 15px;
      font-size: 13px;
      border-bottom: 1px solid #e5e7eb;
    }

    .table-hover tbody tr:hover {
      background-color: #f9fafb;
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
      padding: 6px 10px;
      border-radius: 6px;
    }

    .status-interview {
      background: #16a34a;
      color: white;
    }

    .status-rejected {
      background: #dc2626;
      color: white;
    }

    /* ===== MODAL ===== */
    .modal-overlay {
      position: fixed;
      inset: 0;
      background: rgba(0, 0, 0, 0.4);
      display: none;
      justify-content: center;
      align-items: center;
      z-index: 999;
      backdrop-filter: blur(2px);
    }

    .modal-overlay.active {
      display: flex;
      animation: fadeIn 0.3s ease;
    }

    @keyframes fadeIn {
      from {
        opacity: 0;
        transform: scale(0.95);
      }

      to {
        opacity: 1;
        transform: scale(1);
      }
    }

    .modal-container {
      background: white;
      border-radius: 12px;
      width: 550px;
      max-width: 90%;
      box-shadow: 0 4px 20px rgba(0, 0, 0, 0.2);
      animation: slideUp 0.3s ease;
    }

    @keyframes slideUp {
      from {
        transform: translateY(40px);
        opacity: 0;
      }

      to {
        transform: translateY(0);
        opacity: 1;
      }
    }

    .modal-header {
      display: flex;
      justify-content: space-between;
      align-items: center;
      background: #1e40af;
      color: white;
      padding: 20px;
      border-top-left-radius: 12px;
      border-top-right-radius: 12px;
    }

    .modal-header h2 {
      margin: 0;
      font-size: 20px;
      display: flex;
      align-items: center;
      gap: 10px;
    }

    .close-btn {
      background: transparent;
      border: none;
      color: white;
      font-size: 20px;
      cursor: pointer;
      transition: color 0.3s;
    }

    .close-btn:hover {
      color: #fbbf24;
    }

    .modal-body {
      padding: 25px 30px;
      font-size: 14px;
      color: #1f2937;
    }

    .info-row {
      margin-bottom: 12px;
    }

    .info-row strong {
      color: #1e40af;
      display: inline-block;
      width: 150px;
    }

    .modal-footer {
      text-align: center;
      padding: 20px;
      border-top: 1px solid #e5e7eb;
    }

    .close-btn-footer {
      background: #1e40af;
      color: white;
      padding: 10px 30px;
      border: none;
      border-radius: 6px;
      cursor: pointer;
      font-weight: 600;
      transition: background 0.3s;
    }

    .close-btn-footer:hover {
      background: #1e3a8a;
    }
  </style>
</head>

<body>
  <!-- SIDEBAR -->
  <div class="sidebar">
    <div class="sidebar-logo">
      <img src="Images/hospitallogo.png" alt="">
    </div>

    <div class="sidebar-name">
      <p><?php echo "Welcome, $adminname"; ?></p>
    </div>
    <ul class="nav">
      <li><a href="Admin_Dashboard.php"><i class="fa-solid fa-table-columns"></i>Dashboard</a></li>
      <li><a href="Admin_Employee.php"><i class="fa-solid fa-user-group"></i>Employees</a></li>
      <li class="active"><a href="Admin-Applicants.php"><i class="fa-solid fa-user-group"></i>Applicants</a></li>
      <li><a href="Admin-Pending-Applicants.php"><i class="fa-solid fa-user-group"></i>Pending Applicants</a></li>
      <li><a href="Admin_Vacancies.php"><i class="fa-solid fa-briefcase"></i>Vacancies</a></li>
      <li><a href="Admin-request.php"><i class="fa-solid fa-code-pull-request"></i>Requests</a></li>
      <li><a href="#"><i class="fa-solid fa-chart-simple"></i>Reports</a></li>
      <li><a href="Admin-Settings.php"><i class="fa-solid fa-gear"></i>Settings</a></li>
      <li><a href="Login.php"><i class="fa-solid fa-right-from-bracket"></i>Logout</a></li>
    </ul>
  </div>

  <!-- MAIN CONTENT -->
  <main class="main-content">
    <div class="main-content-header">
      <h1>Applicant List</h1>
    </div>

    <div class="table-container">
      <table class="table table-hover">
        <thead>
          <tr>
            <th>Applicant ID</th>
            <th>Full Name</th>
            <th>Action</th>
            <th>Application Status</th>
          </tr>
        </thead>
        <tbody>
          <tr>
            <td>25-0001</td>
            <td>John Smith</td>
            <td><button class="view-btn" onclick="viewApplicant('25-0001', 'John Smith')">View Applicant</button></td>
            <td><span class="status-badge status-interview">Interview</span></td>
          </tr>
          <tr>
            <td>25-0002</td>
            <td>Garabillo, Jojana Jean</td>
            <td><button class="view-btn" onclick="viewApplicant('25-0002', 'Garabillo, Jojana Jean')">View
                Applicant</button></td>
            <td><span class="status-badge status-rejected">Rejected</span></td>
          </tr>
        </tbody>
      </table>
    </div>
  </main>

  <!-- VIEW APPLICANT MODAL -->
  <div class="modal-overlay" id="modalOverlay">
    <div class="modal-container">
      <div class="modal-header">
        <h2><i class="fa-solid fa-id-card"></i> Applicant Information</h2>
        <button class="close-btn" onclick="closeModal()"><i class="fa-solid fa-xmark"></i></button>
      </div>

      <div class="modal-body">
        <div class="info-row"><strong>Applicant ID:</strong> <span id="modalApplicantID"></span></div>
        <div class="info-row"><strong>Full Name:</strong> <span id="modalApplicantName"></span></div>
        <div class="info-row"><strong>Email:</strong> <span id="modalApplicantEmail"></span></div>
        <div class="info-row"><strong>Status:</strong> <span id="modalApplicantStatus"></span></div>
      </div>

      <div class="modal-footer">
        <button class="close-btn-footer" onclick="closeModal()">Close</button>
      </div>
    </div>
  </div>

  <script>
    // Open modal and populate info
    function viewApplicant(id, name) {
      const applicantData = {
        "25-0001": {
          email: "john.smith@example.com",
          status: "Pending",

        },
        "25-0002": {
          email: "jojana.garabillo@example.com",
          status: "Pending",

        }
      };

      const data = applicantData[id] || {};

      document.getElementById("modalApplicantID").textContent = id;
      document.getElementById("modalApplicantName").textContent = name;
      document.getElementById("modalApplicantEmail").textContent = data.email || "N/A";
      document.getElementById("modalApplicantStatus").textContent = data.status || "N/A";

      document.getElementById("modalOverlay").classList.add("active");
    }

    // Close modal
    function closeModal() {
      document.getElementById("modalOverlay").classList.remove("active");
    }
  </script>
</body>

</html>