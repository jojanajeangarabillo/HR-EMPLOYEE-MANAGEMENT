<?php
session_start();
require 'admin/db.connect.php';

// Get logged-in user's sub-role and empID
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
$managername = $_SESSION['fullname'] ?? "Manager";
$employeeID = $_SESSION['applicant_employee_id'] ?? null; // Make sure empID is stored in session

if (!$employeeID) {
    die("No employee ID found in session.");
}

// Fetch employee data
$stmt = $conn->prepare("
    SELECT fullname, position, department, type_name, empID, profile_pic,
           contact_number, emergency_contact, date_of_birth, gender,
           email_address, home_address, pagibig_number, phil_health_number,
           SSS_number, TIN_number
    FROM employee 
    WHERE empID = ?
");
$stmt->bind_param("s", $employeeID);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    die("Employee not found.");
}

$employee = $result->fetch_assoc();

// Profile picture
$profile_picture = !empty($employee['profile_pic'])
    ? "uploads/employees/" . $employee['profile_pic']
    : "uploads/employees/default.png";

// Helper function for empty fields
function displayOrEmpty($value)
{
    return !empty($value) ? htmlspecialchars($value) : "<span style='color:#888;'>Not set</span>";
}

// Handle personal info update
if (isset($_POST['update_personal'])) {
    $stmt = $conn->prepare("
        UPDATE employee 
        SET contact_number=?, emergency_contact=?, date_of_birth=?, gender=?, email_address=?, home_address=? 
        WHERE empID=?
    ");
    $stmt->bind_param(
        "sssssss",
        $_POST['contact_number'],
        $_POST['emergency_contact'],
        $_POST['date_of_birth'],
        $_POST['gender'],
        $_POST['email_address'],
        $_POST['home_address'],
        $employeeID
    );
    $stmt->execute();
    header("Location:Manager_Profile.php");
    exit();
}

// Handle government info update
if (isset($_POST['update_gov'])) {
    $stmt = $conn->prepare("
        UPDATE employee 
        SET pagibig_number=?, phil_health_number=?, SSS_number=?, TIN_number=? 
        WHERE empID=?
    ");
    $stmt->bind_param(
        "sssss",
        $_POST['pagibig_number'],
        $_POST['phil_health_number'],
        $_POST['SSS_number'],
        $_POST['TIN_number'],
        $employeeID
    );
    $stmt->execute();
    header("Location: Manager_Profile.php");
    exit();
}

// Handle profile picture upload
if (isset($_POST['upload'])) {
    if (isset($_FILES['profile_pic']) && $_FILES['profile_pic']['error'] === 0) {
        $file_name = $_FILES['profile_pic']['name'];
        $file_tmp = $_FILES['profile_pic']['tmp_name'];
        $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));

        $allowed_extensions = ['jpg', 'jpeg', 'png'];
        if (in_array($file_ext, $allowed_extensions)) {
            $upload_dir = "uploads/employees/";
            if (!is_dir($upload_dir))
                mkdir($upload_dir, 0755, true);

            $new_filename = "employee_" . $employeeID . "." . $file_ext;
            $upload_path = $upload_dir . $new_filename;

            if (move_uploaded_file($file_tmp, $upload_path)) {
                $update = $conn->prepare("UPDATE employee SET profile_pic=? WHERE empID=?");
                $update->bind_param("ss", $new_filename, $employeeID);
                if ($update->execute()) {
                    $_SESSION['flash_success'] = 'Profile picture updated successfully.';
                } else {
                    $_SESSION['flash_error'] = 'Failed to update profile picture in database.';
                }
                $update->close();
            } else {
                $_SESSION['flash_error'] = 'Error uploading the file.';
            }
        } else {
            $_SESSION['flash_error'] = 'Invalid file type. Only JPG, JPEG, or PNG allowed.';
        }
    } else {
        $_SESSION['flash_error'] = 'No file selected or upload error.';
    }

    header("Location: Manager_Profile.php");
    exit();
}

// MENUS based on role
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

// Logged-in user's name
$employeename = $employee['fullname'];

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manager Profile</title>
    <link rel="stylesheet" href="manager-sidebar.css">
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet"
        href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700&family=Roboto:wght@400;500;700&display=swap">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/7.0.1/css/all.min.css">
    <style>
        h1 {
            font-family: 'Roboto', sans-serif;
            font-size: 35px;
            color: white;
            text-align: center;
        }

        .menu-board-title {
            font-size: 18px;
            font-weight: bold;
            margin: 15px 0 5px 15px;
            text-transform: uppercase;
            color: white;
        }

        .sidebar-logo {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 30px;
            margin-right: 10px;
        }

        .sidebar-logo img {
            width: 100px;
            height: 100px;
            border-radius: 50%;
            border: 3px solid white;
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

        body {
            font-family: 'Poppins', 'Roboto', sans-serif;
            margin: 0;
            display: flex;
            background-color: #f1f5fc;
            color: #111827;
        }

        .main-content {
            margin-left: 250px;
            padding: 40px 30px;
            background-color: #f1f5fc;
            flex-grow: 1;
            box-sizing: border-box;
        }

        /* Heading with Icon */
        .heading-container {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .main-heading {
            font-size: 2rem;
            font-weight: 700;
            color: #25306d;
            margin: 0;
        }

        .heading-icon {
            width: 28px;
            height: 28px;
        }

        .main-heading-line {
            border: 0;
            height: 2px;
            background: #224288;
            width: 100%;
            margin: 15px 0 30px 0;
        }

        /* Profile Header */
        .profile-header {
            display: flex;
            align-items: flex-start;
            gap: 32px;
            margin-bottom: 25px;
        }

        .profile-photo-upload {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 12px;
        }

        .profile-header img {
            border-radius: 50%;
            border: 2px solid #224288;
            width: 110px;
            height: 110px;
            object-fit: cover;
            background: #fff;
            margin: 0;
        }

        .upload-btn {
            padding: 6px 18px;
            background: #274ea0;
            color: #fff;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-size: 1em;
            font-weight: 500;
            transition: background 0.2s;
        }

        .upload-btn:hover {
            background: #193568;
        }

        input[type="file"] {
            display: none;
        }

        /* Profile Info */
        .profile-info {
            display: flex;
            flex-direction: column;
            gap: 6px;
        }

        .employee-name {
            font-size: 1.9em;
            font-weight: 700;
            color: #25306d;
        }

        .profile-info span {
            font-size: 1em;
            color: #2c2c2c;
            line-height: 1.5;
        }

        .profile-info .label {
            font-weight: 600;
            color: #25306d;
        }

        /* Sections */
        section {
            background: #ffffff;
            padding: 25px 30px;
            border-radius: 12px;
            margin-bottom: 24px;
            box-shadow: 0 2px 6px rgba(0, 0, 0, 0.05);
        }

        section h2 {
            text-align: center;
            font-size: 30px;
            font-weight: 700;
            color: black;
            margin-bottom: 20px;
        }

        /* Info Flex */
        .info-flex {
            display: flex;
            flex-wrap: wrap;
            gap: 40px;
        }

        .info-block {
            min-width: 240px;
            font-size: 0.95em;
            color: #2c2c2c;
        }

        .info-block strong {
            font-weight: 600;
            color: #25306d;
        }

        /* Government IDs */
        .gov-ids-flex {
            display: flex;
            flex-wrap: wrap;
            gap: 50px;
        }

        .gov-id-block {
            min-width: 150px;
            font-size: 0.95em;
            color: #2c2c2c;
        }

        .gov-id-block strong {
            font-weight: 700;
            color: #25306d;
        }

        /* Responsive */
        @media (max-width: 900px) {
            .main-content {
                margin-left: 0;
                padding: 20px;
                width: 100%;
            }

            .profile-header {
                flex-direction: column;
                gap: 14px;
                align-items: center;
                text-align: center;
            }

            .profile-info span {
                text-align: center;
            }

            .info-flex,
            .gov-ids-flex {
                flex-direction: column;
                gap: 15px;
            }

            section {
                padding: 15px 12px;
            }
        }

        .edit-btn {
            position: absolute;
            bottom: 15px;
            right: 15px;
            border: none;
            background: transparent;
            cursor: pointer;
            font-size: 1.5em;
            color: #224288;
            transition: color 0.2s;
        }

        .edit-btn:hover {
            color: #274ea0;
        }

        .section-container {
            position: relative;
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
    <!-- SIDEBAR -->
    <div class="sidebar">
        <a href="Manager_Profile.php" class="profile">
            <img src="<?php echo htmlspecialchars($profile_picture); ?>" alt="Profile" class="sidebar-profile-img">
        </a>

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
    <!-- Main Content -->
    <main class="main-content">
        <div class="heading-container">
            <h1 class="main-heading">Profile<i class="fa-solid fa-circle-user"></i></h1>
        </div>
        <hr class="main-heading-line">

        <!-- Profile Header -->
        <div class="profile-header">
            <div class="profile-photo-upload">
                <img id="profile-preview" src="<?php echo htmlspecialchars($profile_picture); ?>" alt="Profile">

                <form action="" method="post" enctype="multipart/form-data">
                    <input type="file" name="profile_pic" id="profile-upload" accept="image/*">
                    <button type="button" class="upload-btn" id="upload-btn">Upload Photo</button>
                    <button type="submit" name="upload" id="submit-btn" style="display:none;"></button>
                </form>
            </div>


            <div class="profile-info">
                <div class="employee-name">
                    <p><?php echo "$employeename"; ?>
                </div>
                <span>Position: <span class="label"><?php echo $employee["position"]; ?></span></span>
                <span>Department: <span class="label"><?php echo $employee["department"]; ?></span></span>
                <span>Employment Status: <span class="label"><?php echo $employee["type_name"]; ?></span></span>
                <span>Employee ID: <span class="label"><?php echo $employee["empID"]; ?></span></span>
            </div>
        </div>

        <!-- Personal Information Section -->
        <div class="section-container">
            <section>
                <h2>Personal Information</h2>
                <div class="info-flex">
                    <div class="info-block">
                        <strong>CONTACT NUMBER</strong><br>
                        <?php echo displayOrEmpty($employee["contact_number"]); ?><br><br>

                        <strong>EMERGENCY CONTACT NUMBER</strong><br>
                        <?php echo displayOrEmpty($employee["emergency_contact"]); ?>
                    </div>

                    <div class="info-block">
                        <strong>DATE OF BIRTH</strong><br>
                        <?php echo displayOrEmpty($employee["date_of_birth"]); ?><br><br>

                        <strong>GENDER</strong><br>
                        <?php echo displayOrEmpty($employee["gender"]); ?><br><br>

                        <strong>EMAIL ADDRESS</strong><br>
                        <?php echo displayOrEmpty($employee["email_address"]); ?>
                    </div>

                    <div class="info-block" style="flex:2;">
                        <strong>HOME ADDRESS</strong><br>
                        <?php echo displayOrEmpty($employee["home_address"]); ?>
                    </div>
                </div>
                <button class="edit-btn" data-bs-toggle="modal" data-bs-target="#personalInfoModal">
                    <i class="fa-solid fa-pen-to-square"></i>
                </button>
            </section>
        </div>



        <!-- Government IDs Section -->
        <div class="section-container">
            <section>
                <h2>Government Identification Numbers</h2>
                <div class="gov-ids-flex">
                    <div class="gov-id-block">
                        <strong>PAG-IBIG</strong><br>
                        <?php echo displayOrEmpty($employee["pagibig_number"]); ?>
                    </div>

                    <div class="gov-id-block">
                        <strong>PHILHEALTH</strong><br>
                        <?php echo displayOrEmpty($employee["phil_health_number"]); ?>
                    </div>

                    <div class="gov-id-block">
                        <strong>SSS</strong><br>
                        <?php echo displayOrEmpty($employee["SSS_number"]); ?>
                    </div>

                    <div class="gov-id-block">
                        <strong>TIN</strong><br>
                        <?php echo displayOrEmpty($employee["TIN_number"]); ?>
                    </div>
                </div>
                <button class="edit-btn" data-bs-toggle="modal" data-bs-target="#govIdModal">
                    <i class="fa-solid fa-pen-to-square"></i>
                </button>
            </section>
        </div>


    </main>

    <!-- Personal Info Modal -->
    <div class="modal fade" id="personalInfoModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <form action="Manager_Profile.php" method="post">
                    <div class="modal-header">
                        <h5 class="modal-title">Edit Personal Information</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <input type="hidden" name="empID" value="<?php echo $employeeID; ?>">
                        <label>Contact Number</label>
                        <input type="text" name="contact_number" class="form-control"
                            value="<?php echo htmlspecialchars($employee['contact_number']); ?>">
                        <label>Emergency Contact</label>
                        <input type="text" name="emergency_contact" class="form-control"
                            value="<?php echo htmlspecialchars($employee['emergency_contact']); ?>">
                        <label>Date of Birth</label>
                        <input type="date" name="date_of_birth" class="form-control"
                            value="<?php echo $employee['date_of_birth']; ?>">
                        <label>Gender</label>
                        <select name="gender" class="form-control">
                            <option value="">Select</option>
                            <option value="Male" <?php if ($employee['gender'] == 'Male')
                                echo 'selected'; ?>>Male</option>
                            <option value="Female" <?php if ($employee['gender'] == 'Female')
                                echo 'selected'; ?>>Female
                            </option>
                        </select>
                        <label>Email Address</label>
                        <input type="email" name="email_address" class="form-control"
                            value="<?php echo htmlspecialchars($employee['email_address']); ?>">
                        <label>Home Address</label>
                        <textarea name="home_address"
                            class="form-control"><?php echo htmlspecialchars($employee['home_address']); ?></textarea>
                    </div>
                    <div class="modal-footer">
                        <button type="submit" name="update_personal" class="btn btn-primary">Save Changes</button>
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Government IDs Modal -->
    <div class="modal fade" id="govIdModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <form action="Manager_Profile.php" method="post">
                    <div class="modal-header">
                        <h5 class="modal-title">Edit Government IDs</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <input type="hidden" name="empID" value="<?php echo $employeeID; ?>">
                        <label>PAG-IBIG</label>
                        <input type="text" name="pagibig_number" class="form-control"
                            value="<?php echo htmlspecialchars($employee['pagibig_number']); ?>">
                        <label>PHILHEALTH</label>
                        <input type="text" name="phil_health_number" class="form-control"
                            value="<?php echo htmlspecialchars($employee['phil_health_number']); ?>">
                        <label>SSS</label>
                        <input type="text" name="SSS_number" class="form-control"
                            value="<?php echo htmlspecialchars($employee['SSS_number']); ?>">
                        <label>TIN</label>
                        <input type="text" name="TIN_number" class="form-control"
                            value="<?php echo htmlspecialchars($employee['TIN_number']); ?>">
                    </div>
                    <div class="modal-footer">
                        <button type="submit" name="update_gov" class="btn btn-primary">Save Changes</button>
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS Bundle (includes Popper.js) -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

    <script>
        // Profile Upload
        // Correct elements
        const fileInput = document.getElementById('profile-upload'); // input element
        const uploadBtn = document.getElementById('upload-btn'); // your button
        const submitBtn = document.getElementById('submit-btn'); // hidden submit button
        const imgPreview = document.getElementById('profile-preview');

        // When upload button is clicked, open file explorer
        uploadBtn.addEventListener('click', () => {
            fileInput.click();
        });

        // When a file is selected, preview it and auto-submit form
        fileInput.addEventListener('change', function () {
            const file = this.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = function (e) {
                    imgPreview.src = e.target.result; // show preview
                }
                reader.readAsDataURL(file);

                submitBtn.click(); // auto-submit form
            }
        });

    </script>



</body>

</html>