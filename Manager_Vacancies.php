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
        "Vacancies" => "Admin_Vacancies.php",
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
        "Vacancies" => "Admin_Vacancies.php",
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
        "Vacancies" => "Admin_Vacancies.php",
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
$posted_by = $_SESSION['fullname'] ?? "Manager";

$message = '';
$messageType = '';

// Handle Form Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $departmentID = $_POST['department'] ?? '';
    $positionID = $_POST['position'] ?? '';
    $vacancyCount = $_POST['vacancyCount'] ?? '';
    $employmentTypeID = $_POST['employment_type'] ?? '';

    if ($departmentID && $positionID && $employmentTypeID && $vacancyCount > 0) { // include employmentTypeID
        $stmt = $conn->prepare("INSERT INTO vacancies (department_id, position_id, employment_type_id, vacancy_count, posted_by) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("iiiss", $departmentID, $positionID, $employmentTypeID, $vacancyCount, $posted_by);

        if ($stmt->execute()) {
            header("Location: " . $_SERVER['PHP_SELF'] . "?success=1");
            exit;
        } else {
            header("Location: " . $_SERVER['PHP_SELF'] . "?error=db");
            exit;
        }
    } else {
        header("Location: " . $_SERVER['PHP_SELF'] . "?error=fields");
        exit;
    }
}

// Handle Alerts
if (isset($_GET['success'])) {
    $message = "✅ Vacancy successfully added!";
    $messageType = "success";
} elseif (isset($_GET['error'])) {
    if ($_GET['error'] === 'fields') {
        $message = "⚠️ Please fill in all required fields.";
    } elseif ($_GET['error'] === 'db') {
        $message = "❌ Database error occurred. Please try again.";
    }
    $messageType = "danger";
}

// Fetch Departments
$deptQuery = $conn->query("SELECT deptID, deptName FROM department");

// Fetch Positions
$posQuery = $conn->query("SELECT positionID, position_title, departmentID FROM position");
$positions = [];
while ($row = $posQuery->fetch_assoc()) {
    $positions[] = $row;
}

// Fetch 10 Recently Uploaded Vacancies 
$recentQuery = $conn->query("
    SELECT v.id, v.vacancy_count, v.status, d.deptName, p.position_title, e.typeName AS employment_type
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
while ($row = $etypeQuery->fetch_assoc()) {
    $employmentTypes[] = $row;
}



?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manager Vacancies</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/7.0.1/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <link rel="stylesheet" href="manager-sidebar.css">
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
            background-color: #1E3A8A;
            display: flex;
            align-items: center;
            gap: 40px;
            flex-wrap: wrap;
            border-radius: 20px;
            padding: 30px 50px;
            width: fit-content;
            margin-left: 50px;
            margin-top: 0;
            margin-bottom: 50px;
        }


        .recent-section {
            margin-left: 50px;
            margin-top: 0;
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
            background-color: #1E3A8A;
            color: white;
            font-size: 18px;
            padding: 12px 30px;
            border-radius: 10px;
            cursor: pointer;
        }

        button:hover {
            background-color: white;
            color: #1E3A8A;
        }

        .modal {
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

        .modal-content {
            background-color: white;
            padding: 30px;
            border-radius: 15px;
            width: 400px;
            text-align: center;
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.3);
        }

        .modal h2 {
            color: #1E3A8A;
            margin-bottom: 20px;
        }

        .modal input {
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


        /* Reuse existing modal base styles */
        #alertModal .modal-content {
            text-align: center;
            padding: 25px;
            border-radius: 15px;
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.3);
        }

        #alertModal h2 {
            color: #1E3A8A;
            margin-bottom: 10px;
        }

        #alertModal p {
            color: #333;
        }

        #alertModal .confirm-btn {
            background-color: #1E3A8A;
            color: white;
            padding: 10px 25px;
            border-radius: 10px;
            border: none;
            cursor: pointer;
            transition: 0.2s;
        }

        #alertModal .confirm-btn:hover {
            background-color: #162c63;
        }

        .custom-alert {
            animation: fadeInSlide 0.5s ease;
        }

        @keyframes fadeInSlide {
            from {
                opacity: 0;
                transform: translateY(-5px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        #alertModal .confirm-btn {
            background-color: #1E3A8A;
            color: white;
            font-size: 16px;
            padding: 8px 20px;
            border-radius: 8px;
            border: none;
            cursor: pointer;
            transition: 0.2s;
            width: auto;
            min-width: 100px;
            display: inline-block;
        }

        #alertModal .confirm-btn:hover {
            background-color: #162c63;
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


    <main class="main-content">
        <div class="main-content-header">
            <h1>Upload Vacancies</h1>

            <?php if ($message): ?>
                <div class="alert alert-<?= $messageType ?> alert-dismissible fade show custom-alert" role="alert">
                    <?= htmlspecialchars($message) ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
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

        <!-- ✅ Recently Uploaded Vacancies -->
        <div class="recent-section">
            <h2>Recently Uploaded</h2>
            <table class="table table-bordered table-striped w-75">
                <thead class="table-primary">
                    <tr>
                        <th>Department</th>
                        <th>Position</th>
                        <th>Number of Vacancies</th>
                        <th>Employment Type</th>
                        <th>Status</th>
                        <th>Actions</th> <!-- New Column -->
                    </tr>
                </thead>
                <tbody>
                    <?php if ($recentQuery && $recentQuery->num_rows > 0): ?>
                        <?php while ($row = $recentQuery->fetch_assoc()): ?>
                            <tr>
                                <td><?= htmlspecialchars($row['deptName']) ?></td>
                                <td><?= htmlspecialchars($row['position_title']) ?></td>
                                <td><?= htmlspecialchars($row['vacancy_count']) ?></td>
                                <td><?= htmlspecialchars($row['employment_type']) ?></td>
                                <td>
                                    <?php if ($row['status'] === 'On-Going'): ?>
                                        <span class="badge bg-success"><?= htmlspecialchars($row['status']) ?></span>
                                    <?php elseif ($row['status'] === 'Closed'): ?>
                                        <span class="badge bg-danger"><?= htmlspecialchars($row['status']) ?></span>
                                    <?php else: ?>
                                        <span class="badge bg-secondary"><?= htmlspecialchars($row['status']) ?></span>
                                    <?php endif; ?>
                                </td>
                                <td>

                                    <a href="archive_vacancy.php?id=<?= $row['id'] ?>"
                                        class="btn btn-sm btn-warning">Archive</a>
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

    <!-- Modal -->
    <div id="vacancyModal" class="modal">
        <div class="modal-content">
            <h2>Set Number of Vacancies</h2>
            <input type="number" id="vacancyCount" placeholder="Enter number of vacancies" min="1">
            <div>
                <button class="confirm-btn" id="confirmBtn">Confirm</button>
                <button class="cancel-btn" id="cancelBtn">Cancel</button>
            </div>

        </div>
    </div>

    <div id="alertModal" class="modal">
        <div class="modal-content" style="width: 350px;">
            <h2 id="alertTitle">Notice</h2>
            <p id="alertMessage" style="margin: 15px 0; font-size: 16px;"></p>
            <button class="confirm-btn" id="alertOkBtn">OK</button>

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

                deptSelect.addEventListener('change', function () {
                    const deptID = this.value;
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

                openModalBtn.onclick = () => {
                    if (!deptSelect.value) {
                        showAlert("Missing Field", "Please select a department first.");
                        return;
                    }
                    if (!posSelect.value) {
                        showAlert("Missing Field", "Please select a position first.");
                        return;
                    }
                    modal.style.display = 'flex';
                };

                cancelBtn.onclick = () => { modal.style.display = 'none'; vacancyInput.value = ''; };
                confirmBtn.onclick = () => {
                    const count = vacancyInput.value.trim();
                    if (!count || isNaN(count) || count <= 0) return alert("Please enter a valid number of vacancies.");
                    vacancyHidden.value = count;
                    modal.style.display = 'none';
                    form.submit();
                };
                window.onclick = e => { if (e.target === modal) modal.style.display = 'none'; };



                const alertModal = document.getElementById('alertModal');
                const alertTitle = document.getElementById('alertTitle');
                const alertMessage = document.getElementById('alertMessage');
                const alertOkBtn = document.getElementById('alertOkBtn');

                function showAlert(title, message) {
                    alertTitle.textContent = title;
                    alertMessage.textContent = message;
                    alertModal.style.display = 'flex';
                }

                alertOkBtn.onclick = () => {
                    alertModal.style.display = 'none';
                };

                // Allow clicking outside to close alert
                window.addEventListener('click', (e) => {
                    if (e.target === alertModal) alertModal.style.display = 'none';
                });

            </script>


</body>

</html>