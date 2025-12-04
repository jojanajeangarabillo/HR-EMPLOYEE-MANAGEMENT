<?php
session_start();
require 'admin/db.connect.php';

$managername = $_SESSION['fullname'] ?? "Manager";
$employeeID = $_SESSION['applicant_employee_id'] ?? null;
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

// Handle New Job Post Creation
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['new_post'])) {
    $job_title = trim($_POST['job_title'] ?? '');
    $educational_level = trim($_POST['educational_level'] ?? '');
    $expected_salary = trim($_POST['expected_salary'] ?? '');
    $experience_years = intval($_POST['experience_years'] ?? 0);
    $job_description = trim($_POST['job_description'] ?? '');

    $closing_date = $_POST['closing_date'] ?? null;
    $skills = trim($_POST['skills'] ?? '');
    $vacancy_id = intval($_POST['vacancy_id'] ?? 0);
    $date_posted = date('Y-m-d');

    // Lookup department_id and employment_type_id
    $department_id = $employment_type_id = null;
    $vacancyStmt = $conn->prepare("SELECT department_id, employment_type_id, vacancy_count 
                               FROM vacancies WHERE id=? LIMIT 1");

    $vacancyStmt->bind_param("i", $vacancy_id);
    $vacancyStmt->execute();
    $vacRes = $vacancyStmt->get_result();
    if ($vacRes && $vacRes->num_rows > 0) {
        $vacRow = $vacRes->fetch_assoc();
        $department_id = intval($vacRow['department_id']);
        $employment_type_id = intval($vacRow['employment_type_id']);
        $vacancies = intval($vacRow['vacancy_count']);
    } else {
        echo "<script>alert('Invalid vacancy selected.'); window.location.href='Manager-JobPosting.php';</script>";
        exit;
    }

    // Normalize skills
    $skills_normalized = '';
    if ($skills !== '') {
        $parts = array_filter(array_map('trim', explode(',', $skills)), fn($v) => $v !== '');
        $parts = array_map('strtolower', $parts);
        $parts = array_unique($parts);
        $skills_normalized = implode(', ', array_map('ucwords', $parts));
    }

    $stmt = $conn->prepare("INSERT INTO job_posting (job_title, department, educational_level, expected_salary, experience_years, job_description, employment_type, vacancies, date_posted, closing_date, skills)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $types = "sissisiisss";
    $stmt->bind_param($types, $job_title, $department_id, $educational_level, $expected_salary, $experience_years, $job_description, $employment_type_id, $vacancies, $date_posted, $closing_date, $skills_normalized);
    if ($stmt->execute()) {
        $update = $conn->prepare("UPDATE vacancies SET status='On-Going' WHERE id=?");
        $update->bind_param("i", $vacancy_id);
        $update->execute();

        echo "<script>alert('Job post created successfully!'); window.location.href='Manager-JobPosting.php';</script>";
        exit;
    } else {
        echo "Error: " . htmlspecialchars($stmt->error);
    }
}

// Fetch Vacancies Available to Post
$vacancies = [];
$vacQuery = "
SELECT v.id, d.deptName, p.position_title, et.typeName AS employment_type, v.vacancy_count, v.status
FROM vacancies v
JOIN department d ON v.department_id=d.deptID
JOIN position p ON v.position_id=p.positionID
JOIN employment_type et ON v.employment_type_id=et.emtypeID
WHERE v.status='To Post'
ORDER BY v.created_at DESC
";
$result = $conn->query($vacQuery);
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc())
        $vacancies[] = $row;
}

// Pagination Setup
$limit = 5; // rows per page
$page = isset($_GET['page']) ? (int) $_GET['page'] : 1;
if ($page < 1) $page = 1;
$start = ($page - 1) * $limit;

// Fetch Recently Posted Jobs from job_posting table with proper pagination
// First, get total count
$countQuery = "SELECT COUNT(*) as total FROM job_posting";
$countResult = $conn->query($countQuery);
$totalRows = $countResult ? $countResult->fetch_assoc()['total'] : 0;
$pages = ceil($totalRows / $limit);

// Then fetch the paginated data
$recentQuery = "
SELECT 
    j.jobID,
    j.job_title,
    j.vacancies,
    j.date_posted,
    j.closing_date,
    d.deptName,
    et.typeName AS employment_type,
    (
        SELECT COUNT(*) 
        FROM applications a
        WHERE a.jobID = j.jobID
        AND a.status = 'Hired'
    ) AS hired_count
FROM job_posting j
JOIN department d ON j.department=d.deptID
JOIN employment_type et ON j.employment_type=et.emtypeID
ORDER BY j.date_posted DESC
LIMIT $start, $limit
";

$res = $conn->query($recentQuery);
$recentJobs = [];

if ($res && $res->num_rows > 0) {
    while ($row = $res->fetch_assoc()) {
        // Calculate remaining vacancies
        $row['remaining_vacancies'] = $row['vacancies'] - $row['hired_count'];
        if ($row['remaining_vacancies'] < 0) {
            $row['remaining_vacancies'] = 0;
        }
        $recentJobs[] = $row;
    }
}

$conn->close();

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
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Manager - Job Posting</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/7.0.1/css/all.min.css">
    <link rel="stylesheet" href="manager-sidebar.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <style>
        body {
            margin: 0;
            padding: 0;
            font-family: 'Poppins', 'Roboto', sans-serif;
            background: #f1f5fc;
            display: flex;
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

        .job-postings-container {
            flex-grow: 1;
            margin-left: 220px;
            padding: 40px;
        }

        .main-content-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 25px;
        }

        .main-content-header h2 {
            font-size: 26px;
            font-weight: 700;
            color: #1f3c88;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        /* Table Section */
        .dashboard-section {
            background: white;
            border-radius: 12px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
            padding: 25px;
            margin-top: 30px;
        }

        .section-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 2px solid #f0f0f0;
        }

        .section-title {
            font-size: 22px;
            font-weight: 700;
            color: #1f3c88;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .section-title i {
            color: #3b82f6;
        }

        .view-all {
            color: #1f3c88;
            text-decoration: none;
            font-weight: 600;
            font-size: 14px;
            display: flex;
            align-items: center;
            transition: all 0.2s;
        }

        .view-all:hover {
            color: #3b82f6;
            transform: translateX(3px);
        }

        .table-container {
            overflow-x: auto;
        }

        .table {
            width: 100%;
            border-collapse: collapse;
        }

        .table thead {
            background: linear-gradient(135deg, #1f3c88, #3b82f6);
            color: white;
        }

        .table th {
            padding: 15px;
            text-align: left;
            font-weight: 600;
            font-size: 14px;
            border: none;
        }

        .table tbody tr {
            border-bottom: 1px solid #e5e7eb;
            transition: all 0.2s ease;
        }

        .table tbody tr:hover {
            background-color: #f8fafc;
        }

        .table td {
            padding: 15px;
            font-size: 14px;
            vertical-align: middle;
        }

        .badge {
            padding: 6px 12px;
            font-size: 12px;
            font-weight: 600;
            border-radius: 20px;
        }

        .bg-success {
            background-color: #10b981 !important;
        }

        .bg-danger {
            background-color: #ef4444 !important;
        }

        .bg-warning {
            background-color: #f59e0b !important;
        }

        /* Available Jobs Section */
        .available-jobs {
            background: white;
            border-radius: 12px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
            padding: 20px;
            margin-bottom: 30px;
        }

        .available-jobs h3 {
            font-size: 20px;
            font-weight: 700;
            color: #1f3c88;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 2px solid #f0f0f0;
        }

        .status-dropdown {
            font-size: 13px;
            padding: 5px 10px;
        }

        /* Pagination */
        .pagination {
            margin-top: 25px;
        }

        .page-link {
            color: #1f3c88;
            border: 1px solid #e5e7eb;
            padding: 8px 16px;
            margin: 0 4px;
            border-radius: 6px;
            transition: all 0.2s;
        }

        .page-link:hover {
            background-color: #f0f9ff;
            border-color: #3b82f6;
            color: #1f3c88;
        }

        .page-item.active .page-link {
            background: linear-gradient(135deg, #1f3c88, #3b82f6);
            border-color: #1f3c88;
            color: white;
        }

        .page-item.disabled .page-link {
            color: #9ca3af;
            background-color: #f9fafb;
            border-color: #e5e7eb;
        }

        /* Responsive */
        @media (max-width: 768px) {
            .job-postings-container {
                margin-left: 0;
                padding: 20px;
            }
            
            .section-header {
                flex-direction: column;
                gap: 15px;
                align-items: flex-start;
            }
            
            .view-all {
                align-self: flex-start;
            }
            
            .main-content-header {
                flex-direction: column;
                gap: 15px;
                align-items: flex-start;
            }
            
            .main-content-header h2 {
                font-size: 22px;
            }
            
            .available-jobs .row {
                font-size: 13px;
            }
            
            .available-jobs .col-4,
            .available-jobs .col-3,
            .available-jobs .col-2 {
                padding: 5px;
            }
        }
    </style>
</head>

<body>
    <!-- Sidebar -->
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

    <main class="job-postings-container">
        <div class="main-content-header">
            <h2><i class="fa-solid fa-bullhorn"></i> Job Posting Management</h2>
            <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#jobModal">
                <i class="fa-solid fa-plus"></i> Add New Job
            </button>
        </div>

        <!-- Available Jobs -->
        <div class="available-jobs">
            <h3><i class="fa-solid fa-upload me-2"></i> Available Jobs to Upload</h3>
            <?php if (!empty($vacancies)): ?>
                <div class="row fw-bold bg-light p-3 rounded mb-2">
                    <div class="col-4">Job Title</div>
                    <div class="col-3">Department</div>
                    <div class="col-2">Vacancies</div>
                    <div class="col-3">Status</div>
                </div>
                <?php foreach ($vacancies as $job): ?>
                    <div class="row align-items-center p-3 rounded mb-2 border">
                        <div class="col-4">
                            <strong><?= htmlspecialchars($job['position_title']); ?></strong>
                        </div>
                        <div class="col-3"><?= htmlspecialchars($job['deptName']); ?></div>
                        <div class="col-2">
                            <span class="badge bg-primary"><?= htmlspecialchars($job['vacancy_count']); ?></span>
                        </div>
                        <div class="col-3">
                            <select class="form-select status-dropdown" data-id="<?= (int) $job['id']; ?>">
                                <option value="To Post" <?= ($job['status'] == 'To Post') ? 'selected' : ''; ?>>To Post</option>
                                <option value="On-Going" <?= ($job['status'] == 'On-Going') ? 'selected' : ''; ?>>On-Going</option>
                            </select>
                        </div>
                    </div>
                <?php endforeach; else: ?>
                <div class="text-center py-4">
                    <i class="fa-solid fa-folder-open fa-2x text-muted mb-3"></i>
                    <p class="text-muted mb-0">No available jobs to upload.</p>
                </div>
            <?php endif; ?>
        </div>

        <!-- RECENT JOB POSTS SECTION -->
        <div class="dashboard-section">
            <div class="section-header">
                <h2 class="section-title">
                    <i class="fa-solid fa-clock-rotate-left"></i> Recent Job Posts
                </h2>
                <div class="d-flex align-items-center gap-3">
                    <span class="badge bg-primary px-3 py-2">
                        <i class="fa-solid fa-briefcase me-1"></i>
                        <?= $totalRows; ?> Total Jobs
                    </span>
                    <a href="Manager_Vacancies.php" class="view-all">
                        View All <i class="fas fa-chevron-right ms-1"></i>
                    </a>
                </div>
            </div>

            <div class="table-container">
                <table class="table table-hover mb-0">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Job Title</th>
                            <th>Department</th>
                            <th>Employment Type</th>
                            <th>Vacancies Count</th>
                            <th>Date Posted</th>
                           
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($recentJobs)): ?>
                            <?php 
                            $startNumber = ($page - 1) * $limit + 1;
                            foreach ($recentJobs as $index => $job): 
                                $isActive = strtotime($job['closing_date']) > time() || $job['closing_date'] === null;
                                $status = ($isActive && $job['remaining_vacancies'] > 0) ? "Active" : "Closed";
                                $badge = ($status === "Active") ? "bg-success" : "bg-danger";
                            ?>
                                <tr>
                                    <td><?= $startNumber + $index ?></td>
                                    <td><strong><?= htmlspecialchars($job['job_title']); ?></strong></td>
                                    <td><?= htmlspecialchars($job['deptName']); ?></td>
                                    <td><?= htmlspecialchars($job['employment_type']); ?></td>
                                    <td>
                                        <span class="badge bg-primary">
                                            <?= $job['remaining_vacancies']; ?>
                                        </span>
                                    </td>
                                    
                                    <td><?= date('M d, Y', strtotime($job['date_posted'])); ?></td>
                                    
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="8" class="text-center py-4">
                                    <i class="fas fa-briefcase fa-2x text-muted mb-2"></i>
                                    <p class="text-muted mb-0">No recent job posts available.</p>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <!-- PAGINATION -->
            <?php if ($pages > 1): ?>
                <nav class="mt-4">
                    <ul class="pagination justify-content-center">
                        <!-- Previous Button -->
                        <li class="page-item <?= ($page <= 1) ? 'disabled' : '' ?>">
                            <a class="page-link" href="?page=<?= $page - 1; ?>">
                                <i class="fas fa-chevron-left me-1"></i> Previous
                            </a>
                        </li>

                        <!-- Page Numbers -->
                        <?php for ($i = 1; $i <= $pages; $i++): ?>
                            <li class="page-item <?= ($i == $page) ? 'active' : '' ?>">
                                <a class="page-link" href="?page=<?= $i; ?>">
                                    <?= $i; ?>
                                </a>
                            </li>
                        <?php endfor; ?>

                        <!-- Next Button -->
                        <li class="page-item <?= ($page >= $pages) ? 'disabled' : '' ?>">
                            <a class="page-link" href="?page=<?= $page + 1; ?>">
                                Next <i class="fas fa-chevron-right ms-1"></i>
                            </a>
                        </li>
                    </ul>
                </nav>
            <?php endif; ?>
        </div>
    </main>

    <!-- Add Job Modal -->
    <div class="modal fade" id="jobModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content">
                <form id="addJobForm" method="POST">
                    <div class="modal-header bg-primary text-white">
                        <h5 class="modal-title">
                            <i class="fa-solid fa-plus me-2"></i> Create New Job Post
                        </h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <input type="hidden" name="new_post" value="1">
                        <input type="hidden" name="vacancy_id" id="vacancy_id">
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Job Title *</label>
                                <select name="job_title" id="job_title" class="form-select" required>
                                    <option value="">-- Select Job Title --</option>
                                    <?php foreach ($vacancies as $vac): ?>
                                        <option value="<?= htmlspecialchars($vac['position_title']); ?>"
                                            data-department="<?= htmlspecialchars($vac['deptName']); ?>"
                                            data-employment="<?= htmlspecialchars($vac['employment_type']); ?>"
                                            data-vacancies="<?= htmlspecialchars($vac['vacancy_count']); ?>"
                                            data-id="<?= (int) $vac['id']; ?>">
                                            <?= htmlspecialchars($vac['position_title']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Department</label>
                                <input type="text" id="department" class="form-control" readonly>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Employment Type</label>
                                <input type="text" id="employment_type" class="form-control" readonly>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Vacancies</label>
                                <input type="number" id="vacancies" class="form-control" readonly>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Educational Level *</label>
                                <input type="text" name="educational_level" class="form-control" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Experience (Years) *</label>
                                <input type="number" name="experience_years" class="form-control" required min="0">
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Skills (comma separated)</label>
                            <input type="text" name="skills" class="form-control" 
                                   placeholder="e.g., Communication, Leadership, Problem Solving">
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Expected Salary *</label>
                            <input type="text" name="expected_salary" class="form-control" required 
                                   placeholder="e.g., $40,000 - $50,000 annually">
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Job Description *</label>
                            <textarea name="job_description" class="form-control" rows="4" required 
                                      placeholder="Describe the responsibilities, qualifications, and expectations..."></textarea>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Closing Date *</label>
                            <input type="date" name="closing_date" class="form-control" required>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">
                            <i class="fa-solid fa-times me-2"></i> Cancel
                        </button>
                        <button type="submit" class="btn btn-primary">
                            <i class="fa-solid fa-paper-plane me-2"></i> Publish Job Post
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        $(document).ready(function () {
            // Auto-fill Add Job fields
            $('#job_title').change(function () {
                var selected = $(this).find(':selected');
                $('#department').val(selected.data('department') || '');
                $('#employment_type').val(selected.data('employment') || '');
                $('#vacancies').val(selected.data('vacancies') || '');
                $('#vacancy_id').val(selected.data('id') || '');
            });

            // AJAX status update
            $('.status-dropdown').change(function () {
                var status = $(this).val();
                var id = $(this).data('id');
                $.ajax({
                    url: 'update_vacancy_status.php',
                    type: 'POST',
                    data: { vacancy_id: id, status: status },
                    success: function (res) { 
                        console.log(res); 
                        showNotification('Status updated successfully!', 'success');
                    },
                    error: function () { 
                        showNotification('Error updating status.', 'error');
                    }
                });
            });

            // Set minimum date for closing date (tomorrow)
            const tomorrow = new Date();
            tomorrow.setDate(tomorrow.getDate() + 1);
            $('input[name="closing_date"]').attr('min', tomorrow.toISOString().split('T')[0]);
        });

        function showNotification(message, type) {
            const alertClass = type === 'success' ? 'alert-success' : 'alert-danger';
            const alert = $(`
                <div class="alert ${alertClass} alert-dismissible fade show position-fixed top-0 end-0 m-3" role="alert" style="z-index: 9999;">
                    ${message}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            `);
            $('body').append(alert);
            
            setTimeout(() => {
                alert.alert('close');
            }, 3000);
        }
    </script>
</body>

</html>