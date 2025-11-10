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
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">

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
      max-width: 1200px;
      margin: 0 auto;
    }

    .table-responsive {
      overflow-x: auto;
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
      padding: 14px 12px;
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

    /* ===== Updated View Button for Manager Style ===== */
    .main-content .view-btn {
      background-color: #1E3A8A !important;
      color: white !important;
      border: none !important;
      border-radius: 4px !important;
      padding: 3px 8px !important;
      font-size: 11px !important;
      cursor: pointer !important;
      display: inline-flex !important;
      align-items: center !important;
      gap: 4px !important;
      transition: all 0.2s !important;
    }

    .main-content .view-btn i {
      font-size: 12px !important;
    }

    .main-content .view-btn:hover {
      background-color: #1e40af !important;
      transform: translateY(0) !important;
      box-shadow: none !important;
    }

    .status {
      display: inline-block;
      padding: 6px 12px;
      border-radius: 20px;
      font-weight: 600;
      font-size: 13px;
    }

    .status.interviewed {
      background-color: #10b9811a;
      color: #10b981;
      border: 1px solid #10b981;
    }

    .status.rejected {
      background-color: #ef44441a;
      color: #ef4444;
      border: 1px solid #ef4444;
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
  <div class="sidebar d-flex flex-column align-items-center position-fixed top-0 start-0 h-100 p-3">
  <div class="text-center mb-4">
    <img src="Images/hospitallogo.png" alt="Hospital Logo" class="img-fluid rounded-circle mb-3" style="width:75px; height:75px;">
    <p class="text-white fw-semibold mb-0">
      <?php echo "Welcome, $adminname"; ?>
    </p>
  </div>

  <nav class="nav flex-column w-100">
    <a href="Admin_Dashboard.php" class="nav-link  d-flex align-items-center text-white py-2 px-3">
      <i class="fa-solid fa-table-columns me-2"></i>Dashboard
    </a>
    <a href="Admin_Employee.php" class="nav-link d-flex align-items-center text-white py-2 px-3">
      <i class="fa-solid fa-user-group me-2"></i>Employees
    </a>
    <a href="Admin-Applicants.php" class="nav-link active d-flex align-items-center text-white py-2 px-3">
      <i class="fa-solid fa-user-group me-2"></i>Applicants
    </a>
    <a href="Admin-Pending-Applicants.php" class="nav-link d-flex align-items-center text-white py-2 px-3">
      <i class="fa-solid fa-user-clock me-2"></i>Pending Applicants
    </a>
    <a href="Admin_Vacancies.php" class="nav-link d-flex align-items-center text-white py-2 px-3">
      <i class="fa-solid fa-briefcase me-2"></i>Vacancies
    </a>
    <a href="Admin-request.php" class="nav-link d-flex align-items-center text-white py-2 px-3">
      <i class="fa-solid fa-code-pull-request me-2"></i>Requests
    </a>
    <a href="#" class="nav-link d-flex align-items-center text-white py-2 px-3">
      <i class="fa-solid fa-chart-simple me-2"></i>Reports
    </a>
    <a href="Admin-Settings.php" class="nav-link d-flex align-items-center text-white py-2 px-3">
      <i class="fa-solid fa-gear me-2"></i>Settings
    </a>
    <a href="Login.php" class="nav-link d-flex align-items-center text-white py-2 px-3">
      <i class="fa-solid fa-right-from-bracket me-2"></i>Logout
    </a>
  </nav>
</div>


  <!-- MAIN CONTENT -->
  <main class="main-content">
    <div class="main-content-header">
      <h1>Applicant List</h1>
    </div>

    <div class="table-container">
      <div class="table-responsive">
        <table>
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
              <td>
                <button class="view-btn" onclick="viewApplicant('25-0001','John Smith')">
                  <i class="fa-solid fa-eye"></i> View
                </button>
              </td>
              <td><span class="status interviewed">Interviewed</span></td>
            </tr>
            <tr>
              <td>25-0002</td>
              <td>Garabillo, Jojana Jean</td>
              <td>
                <button class="view-btn" onclick="viewApplicant('25-0002','Garabillo, Jojana Jean')">
                  <i class="fa-solid fa-eye"></i> View
                </button>
              </td>
              <td><span class="status rejected">Rejected</span></td>
            </tr>
          </tbody>
        </table>
      </div>
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
    function viewApplicant(id, name) {
      const applicantData = {
        "25-0001": { email: "john.smith@example.com", status: "Pending" },
        "25-0002": { email: "jojana.garabillo@example.com", status: "Pending" }
      };
      const data = applicantData[id] || {};
      document.getElementById("modalApplicantID").textContent = id;
      document.getElementById("modalApplicantName").textContent = name;
      document.getElementById("modalApplicantEmail").textContent = data.email || "N/A";
      document.getElementById("modalApplicantStatus").textContent = data.status || "N/A";
      document.getElementById("modalOverlay").classList.add("active");
    }

    function closeModal() {
      document.getElementById("modalOverlay").classList.remove("active");
    }
  </script>
</body>

</html>
