<?php
session_start();
require 'admin/db.connect.php';

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

// Fetch Recently Posted Jobs with department & employment type names
$recentJobs = [];
$recentQuery = "
SELECT j.*, d.deptName, et.typeName AS employment_type
FROM job_posting j
JOIN department d ON j.department=d.deptID
JOIN employment_type et ON j.employment_type=et.emtypeID
ORDER BY j.date_posted DESC
";
$res = $conn->query($recentQuery);
if ($res && $res->num_rows > 0) {
    while ($row = $res->fetch_assoc())
        $recentJobs[] = $row;
}

$today = date('Y-m-d');

$recentQuery = "
SELECT 
    j.jobID,
    j.job_title,
    j.vacancies AS original_vacancies,
    d.deptName,
    et.typeName AS employment_type,
    j.date_posted,
    j.closing_date,

    -- count hired
    (
        SELECT COUNT(*) 
        FROM applications a
        WHERE a.jobID = j.jobID
        AND a.status = 'Hired'
    ) AS hired_count,

    -- compute remaining vacancies
    (
        j.vacancies - (
            SELECT COUNT(*) 
            FROM applications a
            WHERE a.jobID = j.jobID
            AND a.status = 'Hired'
        )
    ) AS remaining_vacancies

FROM job_posting j
JOIN department d ON j.department=d.deptID
JOIN employment_type et ON j.employment_type=et.emtypeID
WHERE j.closing_date IS NULL OR j.closing_date >= '$today'
ORDER BY j.date_posted DESC
";

$res = $conn->query($recentQuery);
$recentJobs = [];

if ($res && $res->num_rows > 0) {
    while ($row = $res->fetch_assoc()) {
        $recentJobs[] = $row;
    }
}

$conn->close();

// Manager name



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

        .job-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 25px;
        }

        .job-header h2 {
            font-size: 26px;
            font-weight: 700;
            color: #1f3c88;
            display: flex;
            align-items: center;
            gap: 10px;
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
        <div class="job-header">
            <h2><i class="fa-solid fa-briefcase"></i> Job Posting</h2>
            <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#jobModal">+ Add New Job</button>
        </div>

        <!-- Available Jobs -->
        <div class="available-jobs mb-4">
            <h3 style="color:#1f3c88;">Available Jobs to Upload</h3>
            <?php if (!empty($vacancies)): ?>
                <div class="row fw-bold bg-light p-2 rounded mb-2">
                    <div class="col-4">Job Title</div>
                    <div class="col-3">Department</div>
                    <div class="col-2">Vacancies</div>
                    <div class="col-3">Status</div>
                </div>
                <?php foreach ($vacancies as $job): ?>
                    <div class="row align-items-center bg-white p-2 rounded mb-1">
                        <div class="col-4"><?= htmlspecialchars($job['position_title']); ?></div>
                        <div class="col-3"><?= htmlspecialchars($job['deptName']); ?></div>
                        <div class="col-2"><?= htmlspecialchars($job['vacancy_count']); ?></div>
                        <div class="col-3">
                            <select class="form-select status-dropdown" data-id="<?= (int) $job['id']; ?>">
                                <option value="To Post" <?= ($job['status'] == 'To Post') ? 'selected' : ''; ?>>To Post</option>
                                <option value="On-Going" <?= ($job['status'] == 'On-Going') ? 'selected' : ''; ?>>On-Going</option>
                            </select>
                        </div>
                    </div>
                <?php endforeach; else: ?>
                <p>No available jobs found.</p>
            <?php endif; ?>
        </div>

        <!-- Recently Posted -->
        <div class="job-table">
            <h3 style="color:#1f3c88;">Recently Posted</h3>
            <?php if (!empty($recentJobs)): ?>
                <!-- Scrollable container -->
                <div style="max-height: 400px; overflow-y: auto;">
                    <div class="row fw-bold bg-primary text-white p-2 rounded mb-2">
                        <div class="col-3">Job Title</div>
                        <div class="col-2">Department</div>
                        <div class="col-2">Vacancies</div>
                        <div class="col-2">Date Posted</div>
                        <div class="col-3">Closing Date</div>
                    </div>
                    <?php foreach ($recentJobs as $job): ?>
                        <div class="row bg-white p-2 rounded mb-1">
                            <div class="col-3"><?= htmlspecialchars($job['job_title']); ?></div>
                            <div class="col-2"><?= htmlspecialchars($job['deptName']); ?></div>
                            <div class="col-2"><?= htmlspecialchars($job['remaining_vacancies']); ?></div>
                            <div class="col-2"><?= htmlspecialchars($job['date_posted']); ?></div>
                            <div class="col-3"><?= htmlspecialchars($job['closing_date']); ?></div>

                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <p>No job posts yet.</p>
            <?php endif; ?>
        </div>

    </main>

    <!-- Add Job Modal -->
    <div class="modal fade" id="jobModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" style="max-width: 700px;" style="max-height: 100px;"
            style="font-size:15px;">
            <div class="modal-content">
                <form id="addJobForm" method="POST">
                    <div class="modal-header bg-primary text-white">
                        <h5 class="modal-title">Add New Job</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <input type="hidden" name="new_post" value="1">
                        <input type="hidden" name="vacancy_id" id="vacancy_id">
                        <div class="mb-3">
                            <label>Job Title</label>
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
                        <div class="mb-3"><label>Department</label><input type="text" id="department"
                                class="form-control" readonly></div>
                        <div class="mb-3"><label>Employment Type</label><input type="text" id="employment_type"
                                class="form-control" readonly></div>
                        <div class="mb-3"><label>Vacancies</label><input type="number" id="vacancies"
                                class="form-control" readonly></div>
                        <div class="mb-3"><label>Educational Level</label><input type="text" name="educational_level"
                                class="form-control" required></div>
                        <div class="mb-3"><label>Skills</label><input type="text" name="skills" class="form-control">
                        </div>
                        <div class="mb-3"><label>Expected Salary</label><input type="text" name="expected_salary"
                                class="form-control" required></div>
                        <div class="mb-3"><label>Experience in Years</label><input type="text" name="experience_years"
                                class="form-control" required></div>
                        <div class="mb-3"><label>Job Description</label><textarea name="job_description"
                                class="form-control" rows="4" required></textarea></div>
                        <div class="mb-3"><label>Closing Date</label><input type="date" name="closing_date"
                                class="form-control" required></div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-danger" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-success">Post Job</button>
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
                    success: function (res) { console.log(res); },
                    error: function () { alert('Error updating status.'); }
                });
            });
        });
    </script>
</body>

</html>