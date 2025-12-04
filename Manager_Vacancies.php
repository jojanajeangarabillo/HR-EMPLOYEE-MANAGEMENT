<?php
session_start();
require 'admin/db.connect.php';

// Enable error reporting for debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Handle Vacancy Archiving via AJAX
if (isset($_POST['archive_vacancy_id'])) {
    $vacancyID = intval($_POST['archive_vacancy_id']);
    try {
        $conn->begin_transaction();

        // Fetch the vacancy
        $stmt = $conn->prepare("SELECT department_id, position_id, employment_type_id, vacancy_count, posted_by, status, created_at FROM vacancies WHERE id = ?");
        $stmt->bind_param("i", $vacancyID);
        $stmt->execute();
        $vacancy = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if (!$vacancy)
            throw new Exception("Vacancy not found.");

        // Insert into archive table
        $archiveStmt = $conn->prepare("
            INSERT INTO vacancies_archive
            (department_id, position_id, employement_type_id, vacancy_count, posted_by, status, archived_at)
            VALUES (?, ?, ?, ?, ?, ?, NOW())
        ");
        $archiveStmt->bind_param(
            "iiiiss",
            $vacancy['department_id'],
            $vacancy['position_id'],
            $vacancy['employment_type_id'],
            $vacancy['vacancy_count'],
            $vacancy['posted_by'],
            $vacancy['status']
        );

        if (!$archiveStmt->execute())
            throw new Exception($archiveStmt->error);
        $archiveStmt->close();

        // Delete the vacancy
        $delStmt = $conn->prepare("DELETE FROM vacancies WHERE id = ?");
        $delStmt->bind_param("i", $vacancyID);
        if (!$delStmt->execute())
            throw new Exception($delStmt->error);
        $delStmt->close();

        $conn->commit();
        echo json_encode(['success' => true, 'message' => 'Vacancy archived successfully.']);
    } catch (Exception $e) {
        $conn->rollback();
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
    exit;
}

// Manager and profile info
$managername = $_SESSION['fullname'] ?? "Manager";
$employeeID = $_SESSION['applicant_employee_id'] ?? null;
$profile_picture = "uploads/employees/default.png";

if ($employeeID) {
    $stmt = $conn->prepare("SELECT profile_pic FROM employee WHERE empID = ?");
    $stmt->bind_param("s", $employeeID);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    if (!empty($row['profile_pic']))
        $profile_picture = "uploads/employees/" . $row['profile_pic'];
    $stmt->close();
}

// MENUS

// MENUS
$menus = [
    "HR Director" => [
        "Dashboard" => "Manager_Dashboard.php",
        "Applicants" => "Manager_Applicants.php",
        "Pending Applicants" => "Manager_PendingApplicants.php",
        "Newly Hired" => "Newly-Hired.php",
        "Employees" => "Manager_Employees.php",
        "Requests" => "Manager_Request.php",
        "Shift Scheduling"  => "Manager_Scheduling.php",
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
        "Shift Scheduling"  => "Manager_Scheduling.php",
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
    "Shift Scheduling" => "fa-clock-rotate-left",
    "Vacancies" => "fa-briefcase",
    "Job Post" => "fa-bullhorn",
    "Calendar" => "fa-calendar-days",
    "Approvals" => "fa-square-check",
    "Reports" => "fa-chart-column",
    "Settings" => "fa-gear",
    "Logout" => "fa-right-from-bracket"
];

$posted_by = $_SESSION['fullname'] ?? "Manager";
$message = '';
$messageType = '';

// Handle Form Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !isset($_POST['archive_vacancy_id'])) {
    $departmentID = $_POST['department'] ?? '';
    $positionID = $_POST['position'] ?? '';
    $vacancyCount = $_POST['vacancyCount'] ?? '';
    $employmentTypeID = $_POST['employment_type'] ?? '';

    if ($departmentID && $positionID && $employmentTypeID && $vacancyCount > 0) {
        $stmt = $conn->prepare("INSERT INTO vacancies (department_id, position_id, employment_type_id, vacancy_count, posted_by) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("iiiss", $departmentID, $positionID, $employmentTypeID, $vacancyCount, $posted_by);
        if ($stmt->execute())
            header("Location: " . $_SERVER['PHP_SELF'] . "?success=1");
        else
            header("Location: " . $_SERVER['PHP_SELF'] . "?error=db");
        exit;
    } else {
        header("Location: " . $_SERVER['PHP_SELF'] . "?error=fields");
        exit;
    }
}

// Alerts
if (isset($_GET['success'])) {
    $message = "✅ Vacancy successfully added!";
    $messageType = "success";
} elseif (isset($_GET['error'])) {
    if ($_GET['error'] === 'fields')
        $message = "⚠️ Please fill in all required fields.";
    elseif ($_GET['error'] === 'db')
        $message = "❌ Database error occurred. Please try again.";
    $messageType = "danger";
}

// Fetch data
$deptQuery = $conn->query("SELECT deptID, deptName FROM department");
$posQuery = $conn->query("SELECT positionID, position_title, departmentID FROM position");
$positions = $posQuery->fetch_all(MYSQLI_ASSOC);
$etypeQuery = $conn->query("SELECT emtypeID, typeName FROM employment_type ORDER BY typeName ASC");
$employmentTypes = $etypeQuery->fetch_all(MYSQLI_ASSOC);

// Fetch recent vacancies
$recentQuery = $conn->query("
    SELECT 
        v.id, v.vacancy_count, v.status, d.deptName, p.position_title, e.typeName AS employment_type,
        (SELECT COUNT(*) FROM applications a JOIN job_posting j ON a.jobID = j.jobID WHERE j.job_title = p.position_title AND a.status='Hired') AS hired_count,
        (v.vacancy_count - (SELECT COUNT(*) FROM applications a JOIN job_posting j ON a.jobID = j.jobID WHERE j.job_title = p.position_title AND a.status='Hired')) AS remaining_vacancies
    FROM vacancies v
    JOIN department d ON v.department_id = d.deptID
    JOIN position p ON v.position_id = p.positionID
    JOIN employment_type e ON v.employment_type_id = e.emtypeID
    ORDER BY v.id DESC
    LIMIT 10
");

// Fetch Employment Types
$etypeQuery = $conn->query("SELECT emtypeID, typeName FROM employment_type ORDER BY typeName ASC");
$employmentTypes = [];
while ($row = $etypeQuery->fetch_assoc())
    $employmentTypes[] = $row;
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manager Vacancies</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/7.0.1/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.7.1/jquery.min.js"></script>
    <link rel="stylesheet" href="manager-sidebar.css">
    <style>
        body {
            font-family: 'Poppins', 'Roboto', sans-serif;
            margin: 0;
            display: flex;
            background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%);
            color: #1e293b;
        }

        .main-content {
            padding: 40px 30px;
            margin-left: 250px;
            flex: 1;
            display: flex;
            flex-direction: column;
            overflow-y: auto;
            max-height: 100vh;
        }

        .main-content-header {
            margin-bottom: 30px;
            padding-left: 20px;
        }

        .main-content-header h1 {
            color: #1E3A8A;
            margin-bottom: 10px;
            font-weight: 700;
            font-size: 2rem;
        }

        .main-content-header p {
            color: #64748b;
            font-size: 1.1rem;
        }

        .set-vacancies-container {
            background: white;
            border-radius: 16px;
            padding: 30px;
            margin: 0 20px 40px 20px;
            box-shadow: 0 4px 20px rgba(30, 58, 138, 0.08);
            border: 1px solid #e2e8f0;
        }

        .set-vacancies {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 24px;
            align-items: end;
        }

        .form-group {
            margin-bottom: 0;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            color: #475569;
            font-weight: 600;
            font-size: 0.95rem;
        }

        .form-control {
            width: 100%;
            padding: 12px 16px;
            border: 2px solid #e2e8f0;
            border-radius: 10px;
            font-size: 1rem;
            transition: all 0.3s ease;
            background: white;
            color: #334155;
        }

        .form-control:focus {
            border-color: #3b82f6;
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.15);
            outline: none;
        }

        .set-btn {
            background: linear-gradient(135deg, #1E3A8A 0%, #3b82f6 100%);
            color: white;
            border: none;
            padding: 14px 32px;
            border-radius: 10px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            height: 48px;
            align-self: end;
        }

        .set-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(59, 130, 246, 0.3);
            background: linear-gradient(135deg, #172554 0%, #2563eb 100%);
        }

        .recent-section {
            background: white;
            border-radius: 16px;
            padding: 30px;
            margin: 0 20px;
            box-shadow: 0 4px 20px rgba(30, 58, 138, 0.08);
            border: 1px solid #e2e8f0;
        }

        .section-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 25px;
            padding-bottom: 15px;
            border-bottom: 2px solid #f1f5f9;
        }

        .section-header h2 {
            color: #1e293b;
            font-weight: 700;
            font-size: 1.5rem;
            margin: 0;
        }

        .vacancies-table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
        }

        .vacancies-table thead th {
            background: #f8fafc;
            color: #475569;
            font-weight: 600;
            padding: 16px;
            border-bottom: 2px solid #e2e8f0;
            text-transform: uppercase;
            font-size: 0.85rem;
            letter-spacing: 0.5px;
        }

        .vacancies-table tbody tr {
            transition: all 0.2s ease;
        }

        .vacancies-table tbody tr:hover {
            background: #f8fafc;
            transform: translateY(-1px);
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
        }

        .vacancies-table tbody td {
            padding: 18px 16px;
            border-bottom: 1px solid #f1f5f9;
            color: #475569;
            vertical-align: middle;
        }

        .badge {
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
            letter-spacing: 0.3px;
        }

        .badge.bg-success {
            background: #10b981 !important;
        }

        .badge.bg-danger {
            background: #ef4444 !important;
        }

        .badge.bg-primary {
            background: #3b82f6 !important;
        }

        .btn-sm {
            padding: 6px 16px;
            border-radius: 8px;
            font-size: 0.85rem;
            font-weight: 600;
            transition: all 0.3s ease;
        }

        .btn-warning {
            background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);
            border: none;
            color: white;
        }

        .btn-warning:hover {
            background: linear-gradient(135deg, #d97706 0%, #b45309 100%);
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(245, 158, 11, 0.3);
        }

        .text-muted {
            color: #94a3b8 !important;
            font-style: italic;
        }

        .vacancy-modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background: rgba(15, 23, 42, 0.7);
            backdrop-filter: blur(4px);
            justify-content: center;
            align-items: center;
        }

        .vacancy-modal-content {
            background: white;
            padding: 40px;
            border-radius: 20px;
            width: 100%;
            max-width: 450px;
            text-align: center;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.2);
            animation: modalSlideIn 0.3s ease;
        }

        @keyframes modalSlideIn {
            from {
                opacity: 0;
                transform: translateY(-20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .vacancy-modal h2 {
            color: #1e293b;
            margin-bottom: 24px;
            font-size: 1.5rem;
            font-weight: 700;
        }

        .modal-actions {
            display: flex;
            gap: 16px;
            margin-top: 32px;
        }

        .modal-btn {
            flex: 1;
            padding: 14px 24px;
            border-radius: 10px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            border: none;
        }

        .confirm-btn {
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
            color: white;
        }

        .cancel-btn {
            background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
            color: white;
        }

        .confirm-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(16, 185, 129, 0.3);
        }

        .cancel-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(239, 68, 68, 0.3);
        }

        .alert {
            border-radius: 12px;
            border: none;
            padding: 16px 24px;
            margin: 20px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
        }

        .alert-success {
            background: linear-gradient(135deg, #d1fae5 0%, #a7f3d0 100%);
            color: #065f46;
            border-left: 4px solid #10b981;
        }

        .alert-danger {
            background: linear-gradient(135deg, #fee2e2 0%, #fecaca 100%);
            color: #7f1d1d;
            border-left: 4px solid #ef4444;
        }

        .sidebar-profile-img {
            width: 130px;
            height: 130px;
            border-radius: 50%;
            object-fit: cover;
            margin-bottom: 20px;
            transition: transform 0.3s ease;
            border: 4px solid #e2e8f0;
        }

        .sidebar-profile-img:hover {
            transform: scale(1.05);
            border-color: #3b82f6;
        }

        ::-webkit-scrollbar {
            width: 8px;
            height: 8px;
        }

        ::-webkit-scrollbar-track {
            background: #f1f5f9;
            border-radius: 4px;
        }

        ::-webkit-scrollbar-thumb {
            background: #cbd5e1;
            border-radius: 4px;
        }

        ::-webkit-scrollbar-thumb:hover {
            background: #94a3b8;
        }

        @media (max-width: 1200px) {
            .set-vacancies {
                grid-template-columns: repeat(2, 1fr);
            }
        }

        @media (max-width: 768px) {
            .main-content {
                margin-left: 0;
                padding: 20px;
            }
            
            .set-vacancies {
                grid-template-columns: 1fr;
            }
            
            .set-vacancies-container,
            .recent-section {
                margin: 0;
            }
        }
    </style>
</head>

<body>

    <div class="sidebar">
        <div class="sidebar-logo">
            <a href="Manager_Profile.php" class="profile">
                <img src="<?= htmlspecialchars($profile_picture); ?>" alt="Profile" class="sidebar-profile-img">
            </a>
        </div>
        <div class="sidebar-name">
            <p><?= "Welcome, $managername"; ?></p>
        </div>
        <ul class="nav">
            <?php foreach ($menus[$role] as $label => $link): ?>
                <li><a href="<?= $link; ?>"><i class="fa-solid <?= $icons[$label] ?? 'fa-circle'; ?>"></i><?= $label; ?></a>
                </li>
            <?php endforeach; ?>
        </ul>
    </div>

    <main class="main-content">
        <div class="main-content-header">
            <h1>Upload Vacancies</h1>
            <p>Add new job openings and manage existing vacancies</p>
            <?php if ($message): ?>
                <div class="alert alert-<?= $messageType ?> alert-dismissible fade show" role="alert">
                    <?= htmlspecialchars($message) ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>
        </div>

        <!-- Set Vacancy Form -->
        <div class="set-vacancies-container">
            <form method="POST" id="vacancyForm">
                <div class="set-vacancies">
                    <div class="form-group">
                        <label for="department">Department</label>
                        <select name="department" id="department" class="form-control" required>
                            <option value="" disabled selected>Select Department</option>
                            <?php while ($dept = $deptQuery->fetch_assoc()): ?>
                                <option value="<?= $dept['deptID'] ?>"><?= htmlspecialchars($dept['deptName']) ?></option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="position">Position</label>
                        <select name="position" id="position" class="form-control" required>
                            <option value="" disabled selected>Select Position</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="employment_type">Employment Type</label>
                        <select name="employment_type" id="employment_type" class="form-control" required>
                            <option value="" disabled selected>Select Employment Type</option>
                            <?php foreach ($employmentTypes as $etype): ?>
                                <option value="<?= $etype['emtypeID'] ?>"><?= htmlspecialchars($etype['typeName']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <button type="button" id="openModalBtn" class="set-btn">
                        <i class="fas fa-plus-circle me-2"></i>Set Vacancies
                    </button>
                </div>
                <input type="hidden" name="vacancyCount" id="vacancyCountInput">
            </form>
        </div>

        <!-- Recently Uploaded Vacancies -->
        <div class="recent-section">
            <div class="section-header">
                <h2>Recent Vacancies</h2>
            </div>
            <div style="overflow-x: auto;">
                <table class="vacancies-table">
                    <thead>
                        <tr>
                            <th>Department</th>
                            <th>Position</th>
                            <th>Vacancies</th>
                            <th>Employment Type</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($recentQuery && $recentQuery->num_rows > 0): ?>
                            <?php while ($row = $recentQuery->fetch_assoc()): ?>
                                <tr>
                                    <td><?= htmlspecialchars($row['deptName']) ?></td>
                                    <td><strong><?= htmlspecialchars($row['position_title']) ?></strong></td>
                                    <td>
                                        <span class="badge bg-primary">
                                            <?= max(0, htmlspecialchars($row['remaining_vacancies'])) ?>
                                        </span>
                                    </td>
                                    <td><?= htmlspecialchars($row['employment_type']) ?></td>
                                    <td>
                                        <span class="badge <?= $row['status'] === 'On-Going' ? 'bg-success' : ($row['status'] === 'Closed' ? 'bg-danger' : 'bg-primary') ?>">
                                            <?= htmlspecialchars($row['status']) ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php if ($row['hired_count'] >= $row['vacancy_count']): ?>
                                            <span class="text-muted">Filled</span>
                                        <?php else: ?>
                                            <button class="btn btn-sm btn-warning archive-btn" data-id="<?= $row['id'] ?>">
                                                <i class="fas fa-archive me-1"></i>Archive
                                            </button>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="6" class="text-center text-muted py-4">
                                    <i class="fas fa-inbox fa-2x mb-3 d-block"></i>
                                    No vacancies uploaded yet
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </main>

    <!-- Vacancy Count Modal -->
    <div id="vacancyModal" class="vacancy-modal">
        <div class="vacancy-modal-content">
            <h2>Set Number of Vacancies</h2>
            <input type="number" id="vacancyCount" class="form-control" placeholder="Enter number of vacancies" min="1" autofocus>
            <div class="modal-actions">
                <button class="modal-btn confirm-btn" id="confirmBtn">
                    <i class="fas fa-check me-2"></i>Confirm
                </button>
                <button class="modal-btn cancel-btn" id="cancelBtn">
                    <i class="fas fa-times me-2"></i>Cancel
                </button>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        const allPositions = <?= json_encode($positions); ?>;
        const deptSelect = document.getElementById('department');
        const posSelect = document.getElementById('position');
        const modal = document.getElementById('vacancyModal');
        const openModalBtn = document.getElementById('openModalBtn');
        const cancelBtn = document.getElementById('cancelBtn');
        const confirmBtn = document.getElementById('confirmBtn');
        const vacancyInput = document.getElementById('vacancyCount');
        const vacancyHidden = document.getElementById('vacancyCountInput');
        const form = document.getElementById('vacancyForm');

        // Filter positions by department
        deptSelect.addEventListener('change', () => {
            const deptID = deptSelect.value;
            posSelect.innerHTML = '<option value="" disabled selected>Select Position</option>';
            allPositions.forEach(pos => {
                if (pos.departmentID == deptID) {
                    const opt = document.createElement('option');
                    opt.value = pos.positionID;
                    opt.textContent = pos.position_title;
                    posSelect.appendChild(opt);
                }
            });
        });

        // Modal logic
        openModalBtn.onclick = () => {
            if (!deptSelect.value || !posSelect.value) { showAlertModal("Select department and position first."); return; }
            modal.style.display = 'flex';
        };
        cancelBtn.onclick = () => { modal.style.display = 'none'; vacancyInput.value = ''; };
        confirmBtn.onclick = () => {
            const count = vacancyInput.value.trim();
            if (!count || isNaN(count) || count <= 0) { showAlertModal("Enter a valid number."); return; }
            vacancyHidden.value = count;
            modal.style.display = 'none';
            form.submit();
        };
        window.onclick = e => { if (e.target === modal) modal.style.display = 'none'; };

        // AJAX archive
        document.addEventListener('DOMContentLoaded', function () {
            document.querySelectorAll('.archive-btn').forEach(function (btn) {
                btn.addEventListener('click', async function () {
                    const vacancyID = this.getAttribute('data-id');
                    const ok = await showConfirmModal("Archive this vacancy?");
                    if (!ok) return;
                    const params = new URLSearchParams({ archive_vacancy_id: vacancyID });
                    try {
                        const resp = await fetch('', {
                            method: 'POST',
                            headers: { 'Content-Type': 'application/x-www-form-urlencoded', 'X-Requested-With': 'XMLHttpRequest' },
                            body: params.toString()
                        });
                        const res = await resp.json();
                        if (res.success) {
                            showAlertModal(res.message);
                            const row = btn.closest('tr');
                            if (row) row.remove();
                        } else {
                            showAlertModal("Error: " + (res.message || 'Unknown error'));
                        }
                    } catch (err) {
                        showAlertModal("AJAX error: " + (err && err.message ? err.message : err));
                    }
                });
            });
        });

        // Alert & Confirm modals
        (function setupManagerModals() {
            const html = `
            <div class="modal fade" id="managerAlertModal" tabindex="-1" aria-hidden="true">
              <div class="modal-dialog">
                <div class="modal-content">
                  <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title">Notice</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                  </div>
                  <div class="modal-body"><div id="managerAlertMessage"></div></div>
                  <div class="modal-footer">
                    <button type="button" class="btn btn-primary" data-bs-dismiss="modal">OK</button>
                  </div>
                </div>
              </div>
            </div>
            <div class="modal fade" id="managerConfirmModal" tabindex="-1" aria-hidden="true">
              <div class="modal-dialog">
                <div class="modal-content">
                  <div class="modal-header bg-warning">
                    <h5 class="modal-title">Confirm</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                  </div>
                  <div class="modal-body"><div id="managerConfirmMessage"></div></div>
                  <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal" id="managerConfirmCancel">Cancel</button>
                    <button type="button" class="btn btn-warning" id="managerConfirmOk">OK</button>
                  </div>
                </div>
              </div>
            </div>`;
            document.body.insertAdjacentHTML('beforeend', html);
            const alertEl = document.getElementById('managerAlertModal');
            const alertModal = new bootstrap.Modal(alertEl);
            const confirmEl = document.getElementById('managerConfirmModal');
            const confirmModal = new bootstrap.Modal(confirmEl);
            window.showAlertModal = function (message) {
                document.getElementById('managerAlertMessage').textContent = message;
                alertModal.show();
            };
            window.showConfirmModal = function (message) {
                return new Promise(resolve => {
                    document.getElementById('managerConfirmMessage').textContent = message;
                    const okBtn = document.getElementById('managerConfirmOk');
                    const cancelBtn = document.getElementById('managerConfirmCancel');
                    const cleanup = () => {
                        okBtn.replaceWith(okBtn.cloneNode(true));
                        cancelBtn.replaceWith(cancelBtn.cloneNode(true));
                    };
                    confirmModal.show();
                    document.getElementById('managerConfirmOk').addEventListener('click', () => { cleanup(); confirmModal.hide(); resolve(true); });
                    document.getElementById('managerConfirmCancel').addEventListener('click', () => { cleanup(); confirmModal.hide(); resolve(false); });
                });
            };
            const nativeAlert = window.alert; const nativeConfirm = window.confirm;
            window.alert = (msg) => window.showAlertModal(msg);
            window.confirm = (msg) => { console.warn('Use showConfirmModal instead of confirm for async flow.'); return nativeConfirm(msg); };
        })();
    </script>
</body>

</html>