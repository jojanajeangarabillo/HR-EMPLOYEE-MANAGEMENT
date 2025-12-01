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
            background: #f1f5fc;
            color: #111827;
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

        .main-content-header h1 {
            color: #1E3A8A;
            margin-bottom: 20px;
            margin-left: 50px;
        }

        .set-vacancies {
            background: #1E3A8A;
            display: flex;
            align-items: center;
            gap: 40px;
            flex-wrap: wrap;
            border-radius: 20px;
            padding: 30px 50px;
            width: fit-content;
            margin-left: 50px;
            margin-bottom: 50px;
        }

        .recent-section {
            margin-left: 50px;
            width: 90%;
            max-height: 400px;
            overflow-y: auto;
        }

        .recent-section table {
            width: 100%;
            background: white;
            border-radius: 10px;
            overflow: hidden;
        }

        .recent-section::-webkit-scrollbar {
            width: 8px;
        }

        .recent-section::-webkit-scrollbar-thumb {
            background: #1E3A8A;
            border-radius: 10px;
        }

        .select-options {
            display: flex;
            flex-direction: column;
            width: 300px;
        }

        .select-options select,
        input {
            font-size: 18px;
            padding: 10px;
            border-radius: 10px;
            border: none;
            outline: none;
        }

        button {
            border: 2px solid white;
            background: #1E3A8A;
            color: white;
            font-size: 18px;
            padding: 12px 30px;
            border-radius: 10px;
            cursor: pointer;
        }

        button:hover {
            background: white;
            color: #1E3A8A;
        }

        .vacancy-modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
            justify-content: center;
            align-items: center;
        }

        .vacancy-modal-content {
            background: white;
            padding: 30px;
            border-radius: 15px;
            width: 400px;
            text-align: center;
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.3);
        }

        .vacancy-modal h2 {
            color: #1E3A8A;
            margin-bottom: 20px;
        }

        .vacancy-modal input {
            width: 100%;
            padding: 10px;
            margin-bottom: 20px;
        }

        .confirm-btn {
            background: #1E3A8A;
            color: white;
        }

        .cancel-btn {
            background: red;
            color: white;
        }

        .confirm-btn:hover {
            background: #162c63;
        }

        .cancel-btn:hover {
            background: #8b0000;
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
            <?php if ($message): ?>
                <div class="alert alert-<?= $messageType ?> alert-dismissible fade show" role="alert">
                    <?= htmlspecialchars($message) ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>
        </div>

        <!-- Set Vacancy Form -->
        <form method="POST" id="vacancyForm">
            <div class="set-vacancies">
                <div class="select-options">
                    <select name="department" id="department" required>
                        <option value="" disabled selected>Select Department</option>
                        <?php while ($dept = $deptQuery->fetch_assoc()): ?>
                            <option value="<?= $dept['deptID'] ?>"><?= htmlspecialchars($dept['deptName']) ?></option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <div class="select-options">
                    <select name="position" id="position" required>
                        <option value="" disabled selected>Select Position</option>
                    </select>
                </div>
                <div class="select-options">
                    <select name="employment_type" id="employment_type" required>
                        <option value="" disabled selected>Select Employment Type</option>
                        <?php foreach ($employmentTypes as $etype): ?>
                            <option value="<?= $etype['emtypeID'] ?>"><?= htmlspecialchars($etype['typeName']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <button type="button" id="openModalBtn">Set</button>
            </div>
            <input type="hidden" name="vacancyCount" id="vacancyCountInput">
        </form>

        <!-- Recently Uploaded Vacancies -->
        <div class="recent-section">
            <h2>Recently Uploaded</h2>
            <table class="table table-bordered table-striped w-75">
                <thead class="table-primary">
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
                                <td><?= htmlspecialchars($row['position_title']) ?></td>
                                <td><?= max(0, htmlspecialchars($row['remaining_vacancies'])) ?></td>
                                <td><?= htmlspecialchars($row['employment_type']) ?></td>
                                <td>
                                    <span
                                        class="badge <?= $row['status'] === 'On-Going' ? 'bg-success' : ($row['status'] === 'Closed' ? 'bg-danger' : 'bg-primary') ?>">
                                        <?= htmlspecialchars($row['status']) ?>
                                    </span>
                                </td>
                                <td>
                                    <?php if ($row['hired_count'] >= $row['vacancy_count']): ?>
                                        <span class="text-muted">Filled</span>
                                    <?php else: ?>
                                        <button class="btn btn-sm btn-warning archive-btn"
                                            data-id="<?= $row['id'] ?>">Archive</button>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="6" class="text-center text-muted">No vacancies uploaded yet.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </main>

    <!-- Vacancy Count Modal -->
    <div id="vacancyModal" class="vacancy-modal">
        <div class="vacancy-modal-content">
            <h2>Set Number of Vacancies</h2>
            <input type="number" id="vacancyCount" placeholder="Enter number of vacancies" min="1">
            <div>
                <button class="confirm-btn" id="confirmBtn">Confirm</button>
                <button class="cancel-btn" id="cancelBtn">Cancel</button>
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