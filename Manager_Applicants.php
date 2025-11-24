<?php
session_start();
require 'admin/db.connect.php';

$applicant_id = '';
$fullname = '';
$email = '';
$status = '';

$applicant_query = $conn->query("SELECT a.applicantID, a.fullName, u.email, a.status FROM applicant a LEFT JOIN user u ON a.applicantID = u.applicant_employee_id");
if ($applicant_query) {
  while ($row = $applicant_query->fetch_assoc()) {
    $applicants[] = $row;
  }
}

// Manager name
$managername = $_SESSION['fullname'] ?? "Manager";
$employeeID = $_SESSION['applicant_employee_id'] ?? null; // Make sure empID is stored in session
if ($employeeID) {
  $stmt = $conn->prepare("SELECT profile_pic FROM employee WHERE empID = ?");
  $stmt->bind_param("s", $employeeID);
  $stmt->execute();
  $result = $stmt->get_result();

  if ($result->num_rows > 0) {
    $row = $result->fetch_assoc();
    $profile_picture = !empty($row['profile_pic'])
      ? "uploads/employees/" . $row['profile_pic']
      : "uploads/employees/default.png";
  } else {

    $profile_picture = "uploads/employees/default.png";
  }
} else {
  $profile_picture = "uploads/employees/default.png";
}


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
    "Reports" => "Manager_Reports.php",
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
    "Reports" => "Manager_Reports.php",
    "Settings" => "Manager_LeaveSettings.php",
    "Logout" => "Login.php"
  ],

  "Recruitment Manager" => [
    "Dashboard" => "Manager_Dashboard.php",
    "Applicants" => "Manager_Applicants.php",
    "Pending Applicants" => "Manager_PendingApplicants.php",
    "Newly Hired" => "Newly-Hired.php",
    "Vacancies" => "Manager_Vacancies.php",
    "Requests" => "Manager_Request.php",
    "Reports" => "Manager_Reports.php",
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
$icons = [
  "Dashboard" => "fa-table-columns",
  "Applicants" => "fa-user",
  "Pending Applicants" => "fa-clock",
  "Newly Hired" => "fa-user-check",
  "Employees" => "fa-users",
  "Requests" => "fa-file-lines",
  "Vacancies" => "fa-briefcase",
  "Job Post" => "fa-bullhorn",
  "Calendar" => "fa-calendar-days",
  "Approvals" => "fa-square-check",
  "Reports" => "fa-chart-column",
  "Settings" => "fa-gear",
  "Logout" => "fa-right-from-bracket"
];

?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Manager Applicants</title>
  <link rel="stylesheet" href="manager-sidebar.css">
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

    .sidebar-profile-img {
      width: 130px;
      height: 130px;
      border-radius: 50%;
      object-fit: cover;
      margin-bottom: 20px;
      transition: transform 0.3s ease;
    }

    .sidebar-profile-img:hover {
      transform: scale(1.05);
    }

    .main-content {
      padding: 40px 30px;
      margin-left: 220px;
      display: flex;
      flex-direction: column;
    }

    .main-content-header h1 {
      margin: 0;
      font-size: 26px;
      font-weight: 700;
      margin-bottom: 40px;
      color: #1E3A8A;
      margin-left: 40px;
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

    body {
      background-color: #f8fbff;
    }

    .main-content {
      gap: 20px;
    }

    .main-content-header h1 {
      color: #1E3A8A;
      margin-bottom: 24px;
    }

    .header {
      display: flex;
      justify-content: space-between;
      align-items: center;
      margin-bottom: 20px;
    }

    .search-filter {
      display: flex;
      align-items: center;
      gap: 15px;
      margin-left: 40px;
      width: 100%;
    }

    .search-box {
      position: relative;
      flex: 1;
      min-width: 260px;
      max-width: 420px;
    }

    .search-box input {
      width: 100%;
      padding: 10px 40px;
      border: 1px solid #d1d5db;
      border-radius: 25px;
      font-size: 14px;
      background: #fff;
    }

    .search-box input:focus {
      outline: none;
      border-color: #1e3a8a;
      box-shadow: 0 0 0 3px rgba(30, 58, 138, 0.12);
    }

    .search-box i {
      position: absolute;
      left: 15px;
      top: 50%;
      transform: translateY(-50%);
      color: #6b7280;
      font-size: 14px;
    }

    select {
      border-radius: 25px;
      padding: 10px 18px;
      border: 1px solid #d1d5db;
      background: #fff;
      font-size: 14px;
      color: #333;
    }

    .filter-box select {
      padding: 12px 14px;
      border: 1px solid #e0e0e0;
      border-radius: 8px;
      font-size: 14px;
      min-width: 200px;
    }

    .table-custom {
      box-shadow: 0 3px 6px rgba(0, 0, 0, 0.08);
      border-radius: 10px;
      margin-left: 40px;
      width: 100%;
      table-layout: fixed;
    }

    .table-custom th,
    .table-custom td {
      padding: 16px 20px;
      border-bottom: 1px solid #e0e0e0;
      word-break: break-word;
      white-space: normal;
    }

    .table-custom thead {
      background: #1E3A8A;
      color: #fff;
    }

    .table-custom tbody tr:hover {
      background-color: #f8f9fa;
    }

    .table-custom tbody tr:nth-child(even) {
      background-color: #fbfdff;
    }

    .view-btn {
      border-radius: 6px !important;
      padding: 8px 12px !important;
      font-size: 13px !important;
      gap: 8px !important;
    }

    .view-btn:hover {
      transform: translateY(-1px) !important;
      box-shadow: 0 8px 16px rgba(30, 64, 175, 0.18) !important;
    }

    .status.pending {
      background-color: #fbbf241a;
      color: #f59e0b;
      border: 1px solid #f59e0b;
    }

    .modal-header {
      background: linear-gradient(90deg, #1E3A8A, #2743a6);
    }

    .modal-container {
      width: 560px;
      max-width: 92%;
      box-shadow: 0 10px 30px rgba(0, 0, 0, 0.25);
    }

    .modal-body {
      padding: 24px 28px;
    }

    .modal-footer {
      padding: 18px 20px;
    }

    .close-btn-footer {
      border-radius: 8px;
    }
  </style>
</head>

<body>
  <!-- SIDEBAR -->
  <div class="sidebar">
    <div class="sidebar-logo">
      <a href="Manager_Profile.php" class="profile">
        <img src="<?php echo htmlspecialchars($profile_picture); ?>" alt="Profile" class="sidebar-profile-img">
      </a>
    </div>

    <div class="sidebar-name">
      <p><?php echo "Welcome, $managername"; ?></p>
    </div>

    <ul class="nav">
      <?php foreach ($menus[$role] as $label => $link): ?>
        <li><a href="<?php echo $link; ?>"><i
              class="fa-solid <?php echo $icons[$label] ?? 'fa-circle'; ?>"></i><?php echo $label; ?></a></li>
      <?php endforeach; ?>
    </ul>
  </div>
  <!-- MAIN CONTENT -->
  <main class="main-content">
    <div class="main-content-header">
      <h1>Applicants</h1>
    </div>

    <div class="header">
      <div class="search-filter">
        <div class="search-box">
          <i class="fa-solid fa-magnifying-glass"></i>
          <input autocomplete="off" type="text" id="searchInput" placeholder="Search applicants...">
        </div>
        <select id="statusFilter">
          <option value="pending" selected>Pending</option>
          <option value="archived">Archived</option>
          <option value="interviewed">Interviewed</option>
          <option value="rejected">Rejected</option>
          <option value="">All Status</option>
        </select>
      </div>
    </div>


    <div class="table-container">
      <div>
        <table id="applicantTable" class="table table-custom">
          <thead>
            <tr>
              <th>Applicant ID</th>
              <th>Full Name</th>
              <th>Action</th>
              <th>Application Status</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($applicants as $applicant): ?>
              <tr>
                <td><?php echo htmlspecialchars($applicant['applicantID']); ?></td>
                <td><?php echo htmlspecialchars($applicant['fullName']); ?></td>
                <td>
                  <button class="view-btn"
                    onclick="viewApplicant('<?php echo htmlspecialchars($applicant['applicantID']); ?>', '<?php echo htmlspecialchars($applicant['fullName']); ?>', '<?php echo htmlspecialchars($applicant['email']); ?>', '<?php echo htmlspecialchars($applicant['status']); ?>')">
                    <i class="fa-solid fa-eye"></i> View
                  </button>
                </td>
                <td>
                  <?php
                  $statusClass = '';
                  if (strtolower($applicant['status']) === 'interviewed') {
                    $statusClass = 'interviewed';
                  } elseif (strtolower($applicant['status']) === 'rejected') {
                    $statusClass = 'rejected';
                  } ?>
                  <span class="status <?php echo $statusClass; ?>">
                    <?php echo htmlspecialchars($applicant['status']); ?></span>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
        <div class="d-flex justify-content-center mt-3" id="pagination"></div>

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
    function viewApplicant(id, name, email, status) {
      document.getElementById("modalApplicantID").textContent = id;
      document.getElementById("modalApplicantName").textContent = name;
      document.getElementById("modalApplicantEmail").textContent = email || "N/A";
      document.getElementById("modalApplicantStatus").textContent = status || "N/A";
      document.getElementById("modalOverlay").classList.add("active");
    }

    function closeModal() {
      document.getElementById("modalOverlay").classList.remove("active");
    }
    const rowsPerPage = 10;
    let currentPage = 1;

    function filterTable() {
      let search = document.getElementById("searchInput").value.toLowerCase();
      let filter = document.getElementById("statusFilter").value.toLowerCase();
      let rows = document.querySelectorAll("#applicantTable tbody tr");

      rows.forEach(row => {
        let id = row.children[0].innerText.toLowerCase();
        let name = row.children[1].innerText.toLowerCase();
        let status = row.children[3].innerText.toLowerCase();

        let matchesSearch = id.includes(search) || name.includes(search);
        let matchesFilter = filter === "" || status.includes(filter);

        row.style.display = (matchesSearch && matchesFilter) ? "" : "none";
      });

      paginateTable();
    }

    document.getElementById("searchInput").addEventListener("input", filterTable);
    document.getElementById("statusFilter").addEventListener("change", filterTable);

    function paginateTable() {
      let rows = Array.from(document.querySelectorAll("#applicantTable tbody tr"))
        .filter(r => r.style.display !== "none");

      let totalPages = Math.ceil(rows.length / rowsPerPage);
      let pagination = document.getElementById("pagination");
      pagination.innerHTML = "";

      // Hide all rows first
      rows.forEach(r => r.style.visibility = "hidden");

      // Determine which rows to show
      let start = (currentPage - 1) * rowsPerPage;
      let end = start + rowsPerPage;

      rows.slice(start, end).forEach(r => r.style.visibility = "visible");

      // Build pagination buttons
      if (totalPages > 1) {
        for (let i = 1; i <= totalPages; i++) {
          let btn = document.createElement("button");
          btn.className = "btn btn-sm mx-1 " + (i === currentPage ? "btn-primary" : "btn-outline-primary");
          btn.innerText = i;

          btn.addEventListener("click", () => {
            currentPage = i;
            paginateTable();
          });

          pagination.appendChild(btn);
        }
      }
    }

    window.onload = function () {
      const statusSelect = document.getElementById('statusFilter');
      if (statusSelect) statusSelect.value = 'pending';
      filterTable();
      paginateTable();
    };

    // Generic Alert Modal (for consistency across manager pages)
    (function setupAlertModal() {
      const modalHtml = `
      <div class="modal fade" id="alertModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
          <div class="modal-content">
            <div class="modal-header bg-primary text-white">
              <h5 class="modal-title">Notice</h5>
              <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body"><div id="alertModalMessage"></div></div>
            <div class="modal-footer">
              <button type="button" class="btn btn-primary" data-bs-dismiss="modal">OK</button>
            </div>
          </div>
        </div>
      </div>`;
      document.body.insertAdjacentHTML('beforeend', modalHtml);
      const alertModalEl = document.getElementById('alertModal');
      const alertModal = alertModalEl ? new bootstrap.Modal(alertModalEl) : null;
      window.showAlertModal = function (message) {
        const msgEl = document.getElementById('alertModalMessage');
        if (msgEl) msgEl.textContent = message;
        if (alertModal) alertModal.show();
      };
      const nativeAlert = window.alert;
      window.alert = function (message) {
        if (alertModal) return window.showAlertModal(message);
        nativeAlert(message);
      };
    })();
  </script>
</body>

</html>