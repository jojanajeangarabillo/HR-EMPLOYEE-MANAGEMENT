<?php
session_start();
require 'admin/db.connect.php';

// Fetch admin name
$adminnameQuery = $conn->query("SELECT fullname FROM user WHERE sub_role = 'Human Resource (HR) Admin' LIMIT 1");
$adminname = ($adminnameQuery && $row = $adminnameQuery->fetch_assoc()) ? $row['fullname'] : 'Human Resource (HR) Admin';

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
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">

  <style>
    :root {
      --primary: #1E3A8A;
      --primary-light: #3B82F6;
      --primary-dark: #1E40AF;
      --secondary: #64748B;
      --success: #10B981;
      --danger: #EF4444;
      --warning: #F59E0B;
      --info: #3B82F6;
      --purple: #8B5CF6;
      --light: #F8FAFC;
      --dark: #1F2937;
      --gray: #6B7280;
      --gray-light: #E5E7EB;
      --border-radius: 12px;
      --shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
      --shadow-light: 0 2px 10px rgba(0, 0, 0, 0.05);
      --transition: all 0.3s ease;
    }

    body {
      font-family: 'Poppins', 'Roboto', sans-serif;
      margin: 0;
      display: flex;
      background-color: #f8fafc;
      color: var(--dark);
      line-height: 1.6;
    }

    .main-content {
      padding: 30px;
      margin-left: 250px;
      flex: 1;
      display: flex;
      flex-direction: column;
      min-height: 100vh;
    }

    .main-content-header {
      margin-bottom: 30px;
      padding-bottom: 20px;
      border-bottom: 1px solid var(--gray-light);
    }

    .main-content-header h1 {
      margin: 0;
      font-size: 2.2rem;
      font-weight: 700;
      color: var(--primary);
      display: flex;
      align-items: center;
      gap: 12px;
    }

    .main-content-header h1::before {
      content: '';
      display: block;
      width: 6px;
      height: 40px;
      background: linear-gradient(to bottom, var(--primary), var(--primary-light));
      border-radius: 4px;
    }

    .content-card {
      background: white;
      border-radius: var(--border-radius);
      box-shadow: var(--shadow);
      padding: 25px;
      margin-bottom: 25px;
    }

    .controls-container {
      display: flex;
      justify-content: space-between;
      align-items: center;
      margin-bottom: 25px;
      flex-wrap: wrap;
      gap: 15px;
    }

    .search-box {
      position: relative;
      max-width: 350px;
      flex: 1;
    }

    .search-box input {
      padding-left: 45px;
      border-radius: 30px;
      border: 1px solid var(--gray-light);
      height: 48px;
      font-size: 0.95rem;
      transition: var(--transition);
    }

    .search-box input:focus {
      border-color: var(--primary);
      box-shadow: 0 0 0 3px rgba(30, 58, 138, 0.1);
    }

    .search-icon {
      position: absolute;
      left: 18px;
      top: 50%;
      transform: translateY(-50%);
      color: var(--gray);
      z-index: 5;
    }

    .filter-container {
      display: flex;
      gap: 15px;
      align-items: center;
    }

    .filter-select {
      max-width: 220px;
      border-radius: 30px;
      height: 48px;
      border: 1px solid var(--gray-light);
      padding: 0 20px;
      font-size: 0.95rem;
      transition: var(--transition);
    }

    .filter-select:focus {
      border-color: var(--primary);
      box-shadow: 0 0 0 3px rgba(30, 58, 138, 0.1);
    }

    .stats-container {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
      gap: 20px;
      margin-bottom: 30px;
    }

    .stat-card {
      background: white;
      border-radius: var(--border-radius);
      box-shadow: var(--shadow-light);
      padding: 20px;
      text-align: center;
      transition: var(--transition);
      border-top: 4px solid var(--primary);
      cursor: pointer;
    }

    .stat-card:hover {
      transform: translateY(-5px);
      box-shadow: var(--shadow);
    }

    .stat-card.interviewed {
      border-top-color: var(--success);
    }

    .stat-card.rejected {
      border-top-color: var(--danger);
    }

    .stat-card.pending {
      border-top-color: var(--warning);
    }

    .stat-card.initial-interview {
      border-top-color: var(--info);
    }

    .stat-card.assessment {
      border-top-color: var(--purple);
    }

    .stat-card.final-interview {
      border-top-color: #EC4899;
    }

    .stat-card.requirements {
      border-top-color: #F97316;
    }

    .stat-card.archived {
      border-top-color: #6B7280;
    }

    .stat-value {
      font-size: 2.2rem;
      font-weight: 700;
      margin: 10px 0;
      color: var(--primary);
    }

    .stat-card.interviewed .stat-value {
      color: var(--success);
    }

    .stat-card.rejected .stat-value {
      color: var(--danger);
    }

    .stat-card.pending .stat-value {
      color: var(--warning);
    }

    .stat-card.initial-interview .stat-value {
      color: var(--info);
    }

    .stat-card.assessment .stat-value {
      color: var(--purple);
    }

    .stat-card.final-interview .stat-value {
      color: #EC4899;
    }

    .stat-card.requirements .stat-value {
      color: #F97316;
    }

    .stat-card.archived .stat-value {
      color: #6B7280;
    }

    .stat-label {
      font-size: 0.9rem;
      color: var(--secondary);
      font-weight: 500;
    }

    .table-container {
      background: white;
      border-radius: var(--border-radius);
      box-shadow: var(--shadow);
      overflow: hidden;
    }

    .table-header {
      padding: 20px 25px 0;
      display: flex;
      justify-content: space-between;
      align-items: center;
    }

    .table-title {
      font-size: 1.3rem;
      font-weight: 600;
      color: var(--primary);
      margin: 0;
    }

    .table-responsive {
      overflow-x: auto;
    }

    table {
      border-collapse: collapse;
      width: 100%;
    }

    th,
    td {
      padding: 16px 14px;
      text-align: left;
      border-bottom: 1px solid var(--gray-light);
    }

    thead {
      background-color: var(--primary);
      color: white;
    }

    thead th {
      font-weight: 600;
      padding: 18px 14px;
    }

    thead th:first-child {
      border-top-left-radius: 0;
    }

    thead th:last-child {
      border-top-right-radius: 0;
    }

    tbody tr {
      transition: var(--transition);
    }

    tbody tr:hover {
      background-color: rgba(30, 58, 138, 0.03);
    }

    tbody tr:nth-child(even) {
      background-color: #fafbfc;
    }

    tbody tr:nth-child(even):hover {
      background-color: rgba(30, 58, 138, 0.05);
    }

    /* View Button */
    .view-btn {
      background-color: var(--primary) !important;
      color: white !important;
      border: none !important;
      border-radius: 6px !important;
      padding: 8px 16px !important;
      font-size: 0.85rem !important;
      font-weight: 500 !important;
      cursor: pointer !important;
      display: inline-flex !important;
      align-items: center !important;
      gap: 6px !important;
      transition: var(--transition) !important;
    }

    .view-btn:hover {
      background-color: var(--primary-dark) !important;
      transform: translateY(-2px) !important;
      box-shadow: 0 4px 8px rgba(30, 58, 138, 0.2) !important;
    }

    .status {
      display: inline-block;
      padding: 6px 14px;
      border-radius: 20px;
      font-weight: 600;
      font-size: 0.8rem;
      text-transform: capitalize;
    }

    .status.interviewed {
      background-color: rgba(16, 185, 129, 0.1);
      color: var(--success);
      border: 1px solid rgba(16, 185, 129, 0.3);
    }

    .status.rejected {
      background-color: rgba(239, 68, 68, 0.1);
      color: var(--danger);
      border: 1px solid rgba(239, 68, 68, 0.3);
    }

    .status.pending {
      background-color: rgba(245, 158, 11, 0.1);
      color: var(--warning);
      border: 1px solid rgba(245, 158, 11, 0.3);
    }

    .status.initial-interview {
      background-color: rgba(59, 130, 246, 0.1);
      color: var(--info);
      border: 1px solid rgba(59, 130, 246, 0.3);
    }

    .status.assessment {
      background-color: rgba(139, 92, 246, 0.1);
      color: var(--purple);
      border: 1px solid rgba(139, 92, 246, 0.3);
    }

    .status.final-interview {
      background-color: rgba(236, 72, 153, 0.1);
      color: #EC4899;
      border: 1px solid rgba(236, 72, 153, 0.3);
    }

    .status.requirements {
      background-color: rgba(249, 115, 22, 0.1);
      color: #F97316;
      border: 1px solid rgba(249, 115, 22, 0.3);
    }

    .status.archived {
      background-color: rgba(107, 114, 128, 0.1);
      color: #6B7280;
      border: 1px solid rgba(107, 114, 128, 0.3);
    }

    /* Pagination */
    .pagination-container {
      display: flex;
      justify-content: space-between;
      align-items: center;
      padding: 20px 25px;
      border-top: 1px solid var(--gray-light);
    }

    .pagination-info {
      color: var(--secondary);
      font-size: 0.9rem;
    }

    .pagination {
      margin: 0;
    }

    .page-item .page-link {
      border-radius: 8px;
      margin: 0 4px;
      color: var(--primary);
      border: 1px solid var(--gray-light);
      padding: 8px 14px;
      font-weight: 500;
    }

    .page-item.active .page-link {
      background-color: var(--primary);
      border-color: var(--primary);
      color: white;
    }

    /* ===== MODAL ===== */
    .modal-overlay {
      position: fixed;
      inset: 0;
      background: rgba(0, 0, 0, 0.5);
      display: none;
      justify-content: center;
      align-items: center;
      z-index: 9999;
      backdrop-filter: blur(4px);
    }

    .modal-overlay.active {
      display: flex;
      animation: fadeIn 0.3s ease;
    }

    @keyframes fadeIn {
      from { opacity: 0; }
      to { opacity: 1; }
    }

    .modal-container {
      background: white;
      border-radius: 16px;
      width: 550px;
      max-width: 90%;
      max-height: 90vh;
      overflow: hidden;
      box-shadow: 0 10px 40px rgba(0, 0, 0, 0.2);
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
      background: linear-gradient(135deg, var(--primary), var(--primary-light));
      color: white;
      padding: 22px 30px;
    }

    .modal-header h2 {
      margin: 0;
      font-size: 1.4rem;
      display: flex;
      align-items: center;
      gap: 10px;
      font-weight: 600;
    }

    .close-btn {
      background: transparent;
      border: none;
      color: white;
      font-size: 22px;
      cursor: pointer;
      transition: var(--transition);
      width: 36px;
      height: 36px;
      border-radius: 50%;
      display: flex;
      align-items: center;
      justify-content: center;
    }

    .close-btn:hover {
      background: rgba(255, 255, 255, 0.2);
    }

    .modal-body {
      padding: 30px;
      font-size: 0.95rem;
      color: var(--dark);
    }

    .info-row {
      display: flex;
      margin-bottom: 18px;
      padding-bottom: 18px;
      border-bottom: 1px solid var(--gray-light);
    }

    .info-row:last-child {
      border-bottom: none;
      margin-bottom: 0;
      padding-bottom: 0;
    }

    .info-row strong {
      color: var(--primary);
      display: inline-block;
      width: 140px;
      font-weight: 600;
    }

    .info-row span {
      flex: 1;
    }

    .modal-footer {
      text-align: center;
      padding: 20px 30px;
      border-top: 1px solid var(--gray-light);
    }

    .close-btn-footer {
      background: var(--primary);
      color: white;
      padding: 12px 35px;
      border: none;
      border-radius: 8px;
      cursor: pointer;
      font-weight: 600;
      transition: var(--transition);
      font-size: 0.95rem;
    }

    .close-btn-footer:hover {
      background: var(--primary-dark);
      transform: translateY(-2px);
      box-shadow: 0 4px 10px rgba(30, 58, 138, 0.3);
    }

    /* Responsive adjustments */
    @media (max-width: 1200px) {
      .stats-container {
        grid-template-columns: repeat(2, 1fr);
      }
    }

    @media (max-width: 992px) {
      .main-content {
        margin-left: 0;
        padding: 20px;
      }
      
      .controls-container {
        flex-direction: column;
        align-items: stretch;
      }
      
      .search-box {
        max-width: 100%;
      }
      
      .filter-container {
        width: 100%;
        justify-content: space-between;
      }
    }

    @media (max-width: 768px) {
      .stats-container {
        grid-template-columns: 1fr;
      }
      
      .table-header {
        flex-direction: column;
        align-items: flex-start;
        gap: 15px;
      }
      
      .pagination-container {
        flex-direction: column;
        gap: 15px;
      }
      
      .info-row {
        flex-direction: column;
        gap: 5px;
      }
      
      .info-row strong {
        width: 100%;
      }
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
      <p><?php echo "Welcome Admin, $adminname"; ?></p>
    </div>
    <ul class="nav flex-column">
      <li><a href="Admin_Dashboard.php"><i class="fa-solid fa-table-columns"></i> Dashboard</a></li>
      <li><a href="Admin_UserManagement.php"><i class="fa-solid fa-users"></i> User Management</a></li>
      <li><a href="Admin_Departments.php"><i class="fa-solid fa-building-columns"></i> Departments</a></li>
      <li><a href="Admin_RequestSetting.php"><i class="fa-solid fa-clipboard-list"></i> Request Setting</a></li>
      <li class="active"><a href="Admin-Applicants.php"><i class="fa-solid fa-user-check"></i> Applicants</a></li>
      <li><a href="Admin_Reports.php"><i class="fa-solid fa-chart-simple"></i> Reports</a></li>
      <li><a href="Admin-Settings.php"><i class="fa-solid fa-gear"></i> Settings</a></li>
      <li><a href="Login.php"><i class="fa-solid fa-right-from-bracket"></i> Logout</a></li>
    </ul>
  </div>

  <!-- MAIN CONTENT -->
  <main class="main-content">
    <div class="main-content-header">
      <h1>Applicant List</h1>
    </div>

    <!-- Stats Cards -->
    <div class="stats-container">
      <div class="stat-card">
        <div class="stat-label">Total Applicants</div>
        <div class="stat-value"><?php echo count($applicants); ?></div>
        <div class="stat-desc">All applications</div>
      </div>
      <div class="stat-card pending">
        <div class="stat-label">Pending</div>
        <div class="stat-value">
          <?php 
          $pending = 0;
          foreach($applicants as $a) {
            if(strtolower($a['status']) === 'pending') $pending++;
          }
          echo $pending;
          ?>
        </div>
        <div class="stat-desc">Awaiting review</div>
      </div>
      <div class="stat-card initial-interview">
        <div class="stat-label">Initial Interview</div>
        <div class="stat-value">
          <?php 
          $initialInterview = 0;
          foreach($applicants as $a) {
            if(strtolower($a['status']) === 'initial interview') $initialInterview++;
          }
          echo $initialInterview;
          ?>
        </div>
        <div class="stat-desc">First round</div>
      </div>
      <div class="stat-card assessment">
        <div class="stat-label">Assessment</div>
        <div class="stat-value">
          <?php 
          $assessment = 0;
          foreach($applicants as $a) {
            if(strtolower($a['status']) === 'assessment') $assessment++;
          }
          echo $assessment;
          ?>
        </div>
        <div class="stat-desc">Skills evaluation</div>
      </div>
      <div class="stat-card final-interview">
        <div class="stat-label">Final Interview</div>
        <div class="stat-value">
          <?php 
          $finalInterview = 0;
          foreach($applicants as $a) {
            if(strtolower($a['status']) === 'final interview') $finalInterview++;
          }
          echo $finalInterview;
          ?>
        </div>
        <div class="stat-desc">Final round</div>
      </div>
      <div class="stat-card requirements">
        <div class="stat-label">Requirements</div>
        <div class="stat-value">
          <?php 
          $requirements = 0;
          foreach($applicants as $a) {
            if(strtolower($a['status']) === 'requirements') $requirements++;
          }
          echo $requirements;
          ?>
        </div>
        <div class="stat-desc">Document submission</div>
      </div>
      <div class="stat-card interviewed">
        <div class="stat-label">Hired</div>
        <div class="stat-value">
          <?php 
          $hired = 0;
          foreach($applicants as $a) {
            if(strtolower($a['status']) === 'interviewed') $hired++;
          }
          echo $hired;
          ?>
        </div>
        <div class="stat-desc">Successfully hired</div>
      </div>
      <div class="stat-card rejected">
        <div class="stat-label">Rejected</div>
        <div class="stat-value">
          <?php 
          $rejected = 0;
          foreach($applicants as $a) {
            if(strtolower($a['status']) === 'rejected') $rejected++;
          }
          echo $rejected;
          ?>
        </div>
        <div class="stat-desc">Not selected</div>
      </div>
      <div class="stat-card archived">
        <div class="stat-label">Archived</div>
        <div class="stat-value">
          <?php 
          $archived = 0;
          foreach($applicants as $a) {
            if(strtolower($a['status']) === 'archived') $archived++;
          }
          echo $archived;
          ?>
        </div>
        <div class="stat-desc">Inactive records</div>
      </div>
    </div>

    <div class="content-card">
      <div class="controls-container">
        <div class="search-box">
          <i class="fas fa-search search-icon"></i>
          <input type="text" id="searchInput" class="form-control" placeholder="Search by Applicant ID, Name, or Email">
        </div>
        
        <div class="filter-container">
          <select id="statusFilter" class="form-select filter-select">
            <option value="">All Status</option>
            <option value="pending">Pending</option>
            <option value="initial interview">Initial Interview</option>
            <option value="assessment">Assessment</option>
            <option value="final interview">Final Interview</option>
            <option value="requirements">Requirements</option>
            <option value="interviewed">Hired</option>
            <option value="rejected">Rejected</option>
            <option value="archived">Archived</option>
          </select>
        </div>
      </div>

      <div class="table-container">
        <div class="table-header">
          <h3 class="table-title">Applicant Details</h3>
          <div class="table-actions">
            <span class="text-muted" id="tableInfo">Showing <?php echo min(10, count($applicants)); ?> of <?php echo count($applicants); ?> applicants</span>
          </div>
        </div>
        
        <div class="table-responsive">
          <table id="applicantTable">
            <thead>
              <tr>
                <th>Applicant ID</th>
                <th>Full Name</th>
                <th>Email</th>
                <th>Action</th>
                <th>Application Status</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($applicants as $applicant): ?>
                <tr>
                  <td><?php echo htmlspecialchars($applicant['applicantID']); ?></td>
                  <td><?php echo htmlspecialchars($applicant['fullName']); ?></td>
                  <td><?php echo htmlspecialchars($applicant['email']); ?></td>
                  <td>
                    <button class="view-btn"
                      onclick="viewApplicant('<?php echo htmlspecialchars($applicant['applicantID']); ?>', '<?php echo htmlspecialchars($applicant['fullName']); ?>', '<?php echo htmlspecialchars($applicant['email']); ?>', '<?php echo htmlspecialchars($applicant['status']); ?>')">
                      <i class="fa-solid fa-eye"></i> View
                    </button>
                  </td>
                  <td>
                    <?php
                    $statusClass = '';
                    $status = strtolower($applicant['status']);
                    if ($status === 'interviewed') {
                      $statusClass = 'interviewed';
                    } elseif ($status === 'rejected') {
                      $statusClass = 'rejected';
                    } elseif ($status === 'pending') {
                      $statusClass = 'pending';
                    } elseif ($status === 'initial interview') {
                      $statusClass = 'initial-interview';
                    } elseif ($status === 'assessment') {
                      $statusClass = 'assessment';
                    } elseif ($status === 'final interview') {
                      $statusClass = 'final-interview';
                    } elseif ($status === 'requirements') {
                      $statusClass = 'requirements';
                    } elseif ($status === 'archived') {
                      $statusClass = 'archived';
                    } ?>
                    <span class="status <?php echo $statusClass; ?>">
                      <?php echo htmlspecialchars($applicant['status']); ?>
                    </span>
                  </td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
        
        <div class="pagination-container">
          <div class="pagination-info" id="paginationInfo"></div>
          <div class="pagination" id="pagination"></div>
        </div>
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
        <div class="info-row">
          <strong>Applicant ID:</strong> 
          <span id="modalApplicantID"></span>
        </div>
        <div class="info-row">
          <strong>Full Name:</strong> 
          <span id="modalApplicantName"></span>
        </div>
        <div class="info-row">
          <strong>Email:</strong> 
          <span id="modalApplicantEmail"></span>
        </div>
        <div class="info-row">
          <strong>Status:</strong> 
          <span id="modalApplicantStatus"></span>
        </div>
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
      
      // Create status element with appropriate class
      const statusElement = document.getElementById("modalApplicantStatus");
      statusElement.textContent = status || "N/A";
      statusElement.className = "";
      
      if (status) {
        const statusClass = status.toLowerCase().replace(' ', '-');
        statusElement.classList.add("status", statusClass);
      }
      
      document.getElementById("modalOverlay").classList.add("active");
    }

    function closeModal() {
      document.getElementById("modalOverlay").classList.remove("active");
    }

    // Close modal when clicking outside
    document.getElementById('modalOverlay').addEventListener('click', function(e) {
      if (e.target === this) {
        closeModal();
      }
    });

    // Table filtering and pagination
    const rowsPerPage = 10;
    let currentPage = 1;

    function filterTable() {
      let search = document.getElementById("searchInput").value.toLowerCase();
      let filter = document.getElementById("statusFilter").value.toLowerCase();
      let rows = document.querySelectorAll("#applicantTable tbody tr");

      let visibleCount = 0;
      
      rows.forEach(row => {
        let id = row.children[0].innerText.toLowerCase();
        let name = row.children[1].innerText.toLowerCase();
        let email = row.children[2].innerText.toLowerCase();
        let status = row.children[4].innerText.toLowerCase();

        let matchesSearch = id.includes(search) || name.includes(search) || email.includes(search);
        let matchesFilter = filter === "" || status.includes(filter);

        if (matchesSearch && matchesFilter) {
          row.style.display = "";
          visibleCount++;
        } else {
          row.style.display = "none";
        }
      });

      updateTableInfo(visibleCount);
      paginateTable();
    }

    function updateTableInfo(visibleCount) {
      const total = document.querySelectorAll("#applicantTable tbody tr").length;
      document.getElementById("tableInfo").textContent = `Showing ${Math.min(rowsPerPage, visibleCount)} of ${visibleCount} applicants`;
      document.getElementById("paginationInfo").textContent = `${visibleCount} applicants found`;
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
      rows.forEach(r => r.style.display = "none");

      // Determine which rows to show
      let start = (currentPage - 1) * rowsPerPage;
      let end = start + rowsPerPage;

      rows.slice(start, end).forEach(r => r.style.display = "");

      // Build pagination buttons
      if (totalPages > 1) {
        // Previous button
        if (currentPage > 1) {
          let prevBtn = document.createElement("button");
          prevBtn.className = "btn btn-sm btn-outline-primary mx-1";
          prevBtn.innerHTML = '<i class="fas fa-chevron-left"></i>';
          prevBtn.addEventListener("click", () => {
            currentPage--;
            paginateTable();
          });
          pagination.appendChild(prevBtn);
        }

        // Page buttons
        for (let i = 1; i <= totalPages; i++) {
          if (i === 1 || i === totalPages || (i >= currentPage - 1 && i <= currentPage + 1)) {
            let btn = document.createElement("button");
            btn.className = "btn btn-sm mx-1 " + (i === currentPage ? "btn-primary" : "btn-outline-primary");
            btn.innerText = i;
            btn.addEventListener("click", () => {
              currentPage = i;
              paginateTable();
            });
            pagination.appendChild(btn);
          } else if (i === currentPage - 2 || i === currentPage + 2) {
            let dots = document.createElement("span");
            dots.className = "mx-1";
            dots.innerText = "...";
            pagination.appendChild(dots);
          }
        }

        // Next button
        if (currentPage < totalPages) {
          let nextBtn = document.createElement("button");
          nextBtn.className = "btn btn-sm btn-outline-primary mx-1";
          nextBtn.innerHTML = '<i class="fas fa-chevron-right"></i>';
          nextBtn.addEventListener("click", () => {
            currentPage++;
            paginateTable();
          });
          pagination.appendChild(nextBtn);
        }
      }
    }

    // Initialize
    window.onload = function() {
      updateTableInfo(document.querySelectorAll("#applicantTable tbody tr").length);
      paginateTable();
      
      // Add click handlers for stat cards to filter by status
      document.querySelectorAll('.stat-card').forEach(card => {
        card.addEventListener('click', function() {
          const status = this.classList[1]; // Get the status class
          let filterValue = '';
          
          // Map CSS classes to filter values
          switch(status) {
            case 'pending': filterValue = 'pending'; break;
            case 'initial-interview': filterValue = 'initial interview'; break;
            case 'assessment': filterValue = 'assessment'; break;
            case 'final-interview': filterValue = 'final interview'; break;
            case 'requirements': filterValue = 'requirements'; break;
            case 'interviewed': filterValue = 'interviewed'; break;
            case 'rejected': filterValue = 'rejected'; break;
            case 'archived': filterValue = 'archived'; break;
          }
          
          if (filterValue) {
            document.getElementById('statusFilter').value = filterValue;
            filterTable();
          }
        });
      });
    };
  </script>
</body>
</html>