<?php
session_start();  // Ensure session is started at the top
// Include database connection
include 'admin/db.connect.php';
// Check if user is logged in; redirect to login if not
if (!isset($_SESSION['applicantID']) || empty($_SESSION['applicantID'])) {
    header("Location: Login.php");
    exit();
}
$user_id = $_SESSION['applicantID'];
// Flash messages support
$flash_success = '';
$flash_error = '';
if (isset($_SESSION['flash_success'])) {
    $flash_success = $_SESSION['flash_success'];
    unset($_SESSION['flash_success']);
}
if (isset($_SESSION['flash_error'])) {
    $flash_error = $_SESSION['flash_error'];
    unset($_SESSION['flash_error']);
}
// Handle profile update (from edit modal)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    // Only phone and home location are editable here
    $new_phone = trim($_POST['phoneNumber'] ?? '');
    $new_location = trim($_POST['homeLocation'] ?? '');

    // Server-side validation: both fields required
    if ($new_phone === '' || $new_location === '') {
        $_SESSION['flash_error'] = 'Please fill all profile fields.';
        header("Location: Applicant_Profile.php");
        exit();
    }

    $update_sql = "UPDATE applicant SET contact_number = ?, home_address = ? WHERE applicantID = ?";
    $update_stmt = $conn->prepare($update_sql);
    if ($update_stmt) {
        $update_stmt->bind_param("sss", $new_phone, $new_location, $user_id);
        if ($update_stmt->execute()) {
            $_SESSION['flash_success'] = 'Profile updated successfully.';
        } else {
            $_SESSION['flash_error'] = 'Failed to update profile.';
        }
        $update_stmt->close();
    } else {
        $_SESSION['flash_error'] = 'Server error (prepare failed).';
    }

    header("Location: Applicant_Profile.php");
    exit();
}
// Handle adding a role (from role modal)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_role'])) {
    $job_title_input = trim($_POST['job_title'] ?? '');
    $company_input = trim($_POST['company_name'] ?? '');
    $role_desc = trim($_POST['role_description'] ?? '');

    // Server-side validation: all fields required
    if ($job_title_input === '' || $company_input === '' || $role_desc === '') {
        $_SESSION['flash_error'] = 'Please fill all role fields.';
        header("Location: Applicant_Profile.php");
        exit();
    }

    $ins = $conn->prepare("INSERT INTO applicant_roles (applicantID, job_title, company_name, description) VALUES (?, ?, ?, ?)");
    if ($ins) {
        $ins->bind_param("ssss", $user_id, $job_title_input, $company_input, $role_desc);
        if ($ins->execute()) {
            $_SESSION['flash_success'] = 'Role added.';
        } else {
            $_SESSION['flash_error'] = 'Failed to add role.';
        }
        $ins->close();
    } else {
        $_SESSION['flash_error'] = 'Server error (prepare failed).';
    }
    header("Location: Applicant_Profile.php");
    exit();
}

// Handle editing a role
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_role'])) {
    $role_id = intval($_POST['role_id'] ?? 0);
    $job_title_input = trim($_POST['job_title_edit'] ?? '');
    $company_input = trim($_POST['company_name_edit'] ?? '');
    $role_desc = trim($_POST['role_description_edit'] ?? '');

    if ($role_id <= 0 || $job_title_input === '' || $company_input === '' || $role_desc === '') {
        $_SESSION['flash_error'] = 'Please fill all role fields.';
        header("Location: Applicant_Profile.php");
        exit();
    }

    $upd = $conn->prepare("UPDATE applicant_roles SET job_title = ?, company_name = ?, description = ? WHERE id = ? AND applicantID = ?");
    if ($upd) {
        $upd->bind_param("sssis", $job_title_input, $company_input, $role_desc, $role_id, $user_id);
        if ($upd->execute()) {
            $_SESSION['flash_success'] = 'Role updated.';
        } else {
            $_SESSION['flash_error'] = 'Failed to update role.';
        }
        $upd->close();
    } else {
        $_SESSION['flash_error'] = 'Server error (prepare failed).';
    }
    header("Location: Applicant_Profile.php");
    exit();
}

// Handle deleting a role
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_role'])) {
    $role_id = intval($_POST['role_id'] ?? 0);
    if ($role_id <= 0) {
        $_SESSION['flash_error'] = 'Invalid role selected.';
        header("Location: Applicant_Profile.php");
        exit();
    }
    $del = $conn->prepare("DELETE FROM applicant_roles WHERE id = ? AND applicantID = ?");
    if ($del) {
        $del->bind_param("is", $role_id, $user_id);
        if ($del->execute()) {
            $_SESSION['flash_success'] = 'Role deleted.';
        } else {
            $_SESSION['flash_error'] = 'Failed to delete role.';
        }
        $del->close();
    } else {
        $_SESSION['flash_error'] = 'Server error (prepare failed).';
    }
    header("Location: Applicant_Profile.php");
    exit();
}

// Handle adding education (from education modal)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_education'])) {
    $school = trim($_POST['school'] ?? '');
    $degree = trim($_POST['degree'] ?? '');

    // Server-side validation: all fields required
    if ($school === '' || $degree === '') {
        $_SESSION['flash_error'] = 'Please fill all education fields.';
        header("Location: Applicant_Profile.php");
        exit();
    }

    $ins = $conn->prepare("INSERT INTO applicant_education (applicantID, school, degree) VALUES (?, ?, ?)");
    if ($ins) {
        $ins->bind_param("sss", $user_id, $school, $degree);
        if ($ins->execute()) {
            $_SESSION['flash_success'] = 'Education added.';
        } else {
            $_SESSION['flash_error'] = 'Failed to add education.';
        }
        $ins->close();
    } else {
        $_SESSION['flash_error'] = 'Server error (prepare failed).';
    }
    header("Location: Applicant_Profile.php");
    exit();
}

// Handle editing education
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_education'])) {
    $edu_id = intval($_POST['education_id'] ?? 0);
    $school_edit = trim($_POST['school_edit'] ?? '');
    $degree_edit = trim($_POST['degree_edit'] ?? '');

    if ($edu_id <= 0 || $school_edit === '' || $degree_edit === '') {
        $_SESSION['flash_error'] = 'Please fill all education fields.';
        header("Location: Applicant_Profile.php");
        exit();
    }

    $updEdu = $conn->prepare("UPDATE applicant_education SET school = ?, degree = ? WHERE id = ? AND applicantID = ?");
    if ($updEdu) {
        $updEdu->bind_param("ssis", $school_edit, $degree_edit, $edu_id, $user_id);
        if ($updEdu->execute()) {
            $_SESSION['flash_success'] = 'Education updated.';
        } else {
            $_SESSION['flash_error'] = 'Failed to update education.';
        }
        $updEdu->close();
    } else {
        $_SESSION['flash_error'] = 'Server error (prepare failed).';
    }
    header("Location: Applicant_Profile.php");
    exit();
}

// Handle deleting education
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_education'])) {
    $edu_id = intval($_POST['education_id'] ?? 0);
    if ($edu_id <= 0) {
        $_SESSION['flash_error'] = 'Invalid education selected.';
        header("Location: Applicant_Profile.php");
        exit();
    }
    $delEdu = $conn->prepare("DELETE FROM applicant_education WHERE id = ? AND applicantID = ?");
    if ($delEdu) {
        $delEdu->bind_param("is", $edu_id, $user_id);
        if ($delEdu->execute()) {
            $_SESSION['flash_success'] = 'Education deleted.';
        } else {
            $_SESSION['flash_error'] = 'Failed to delete education.';
        }
        $delEdu->close();
    } else {
        $_SESSION['flash_error'] = 'Server error (prepare failed).';
    }
    header("Location: Applicant_Profile.php");
    exit();
}

// Handle adding skills (from skills modal)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_skills'])) {
    // Server-side: require all five skills to be filled
    $skills = [];
    $missing = false;
    for ($i = 1; $i <= 5; $i++) {
        $k = trim($_POST['skill' . $i] ?? '');
        if ($k === '') {
            $missing = true;
            break;
        }
        $skills[] = $k;
    }
    if ($missing) {
        $_SESSION['flash_error'] = 'Please fill in all five skills before saving.';
        header("Location: Applicant_Profile.php");
        exit();
    }

    $skills_str = implode(', ', $skills);

    $update_sql = "UPDATE applicant SET skills = ? WHERE applicantID = ?";
    $update_stmt = $conn->prepare($update_sql);
    if ($update_stmt) {
        $update_stmt->bind_param("ss", $skills_str, $user_id);
        if ($update_stmt->execute()) {
            $_SESSION['flash_success'] = 'Skills updated.';
        } else {
            $_SESSION['flash_error'] = 'Failed to update skills.';
        }
        $update_stmt->close();
    } else {
        $_SESSION['flash_error'] = 'Server error (prepare failed).';
    }

    header("Location: Applicant_Profile.php");
    exit();
}

// Handle summary modal
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_summary'])) {
    $summary_text = trim($_POST['summary_text'] ?? '');

    // Server-side validation: summary required
    if ($summary_text === '') {
        $_SESSION['flash_error'] = 'Please write a summary before saving.';
        header("Location: Applicant_Profile.php");
        exit();
    }

    $update_sql = "UPDATE applicant SET summary = ? WHERE applicantID = ?";
    $update_stmt = $conn->prepare($update_sql);
    if ($update_stmt) {
        $update_stmt->bind_param("ss", $summary_text, $user_id);
        if ($update_stmt->execute()) {
            $_SESSION['flash_success'] = 'Summary saved.';
        } else {
            $_SESSION['flash_error'] = 'Failed to save summary.';
        }
        $update_stmt->close();
    } else {
        $_SESSION['flash_error'] = 'Server error (prepare failed).';
    }

    header("Location: Applicant_Profile.php");
    exit();
}
$sql = "SELECT fullName, email_address, contact_number, home_address, skills, summary FROM applicant WHERE applicantID = ?";
$stmt = $conn->prepare($sql);
// applicantID is stored as VARCHAR; bind as string
$stmt->bind_param("s", $user_id);
$stmt->execute();
$result = $stmt->get_result();
if ($result->num_rows > 0) {
    $row = $result->fetch_assoc();
    $name = htmlspecialchars($row['fullName']);
    $email = htmlspecialchars($row['email_address']);
    // correct keys from DB: contact_number and home_address
    $phone = htmlspecialchars($row['contact_number'] ?? 'Not provided');  // Default if null
    $location = htmlspecialchars($row['home_address'] ?? 'Not provided');  // Default if null
    $skills_str = htmlspecialchars($row['skills'] ?? '');
    $summary_text = htmlspecialchars($row['summary'] ?? '');
    // build array for prefill/display; split on comma
    $skills_array = [];
    if (!empty($skills_str)) {
        // support both ", " and "," separators
        $skills_array = array_map('trim', preg_split('/\s*,\s*/', $skills_str));
    }
} else {
    // Fallback if no data found (shouldn't happen if session is valid)
    $name = "Unknown";
    $email = "Not available";
    $phone = "Not available";
    $location = "Not available";
}

// Fetch roles and education entries for this applicant
$roles = [];
$edus = [];
$rstmt = $conn->prepare("SELECT id, job_title, company_name, description, created_at FROM applicant_roles WHERE applicantID = ? ORDER BY created_at DESC");
if (!$rstmt) {
    error_log('Applicant_Profile: applicant_roles prepare failed: ' . $conn->error);
    $_SESSION['flash_error'] = 'Server error while loading roles.';
}
if ($rstmt) {
    $rstmt->bind_param("s", $user_id);
    $rstmt->execute();
    $rres = $rstmt->get_result();
    while ($r = $rres->fetch_assoc())
        $roles[] = $r;
    $rstmt->close();
}

$estmt = $conn->prepare("SELECT id, school, degree, created_at FROM applicant_education WHERE applicantID = ? ORDER BY created_at DESC");
if (!$estmt) {
    error_log('Applicant_Profile: applicant_education prepare failed: ' . $conn->error);
    $_SESSION['flash_error'] = 'Server error while loading education.';
}
if ($estmt) {
    $estmt->bind_param("s", $user_id);
    $estmt->execute();
    $eres = $estmt->get_result();
    while ($e = $eres->fetch_assoc())
        $edus[] = $e;
    $estmt->close();
}
?>


<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Applicant Dashboard</title>

    <!-- Google Fonts -->
    <link
        href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600&family=Roboto:wght@400;500&display=swap"
        rel="stylesheet">

    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

    <!-- Sidebar CSS -->
    <link rel="stylesheet" href="applicant.css">

    <!-- Internal CSS for dashboard contents -->
    <style>
        body {
            font-family: 'Poppins', 'Roboto', sans-serif;
            margin: 0;
            display: flex;
            background-color: #f1f5fc;
            color: #111827;
        }

        .main-content {
            flex: 1;
            padding: 30px 80px;
            display: flex;
            flex-direction: column;
            gap: 40px;
        }

        .profile-header {
            padding: 5px 10px;
            margin-left: 200px;
            font-size: 40px;
        }

        /* Personal info box */
        .personal-info {
            background-color: #1E3A8A;
            color: white;
            padding: 30px 30px;
            margin-left: 200px;
            border-radius: 15px;
            width: 1550px;
            height: 340px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.15);
            font-size: 20px;
            font-weight: 500;
        }

        .info {
            display: flex;
            margin-left: 50px;
            gap: 15px;
            flex-direction: column;
        }

        .phone,
        .location,
        .email {
            display: flex;
        }

        p {
            margin-left: 20px;
        }

        .edit-btn {
            height: 42px;
            width: 93px;
            border-color: white;
            border-style: solid;
            background-color: #1E3A8A;
        }

        .edit-btn:hover {
            background-color: #e5edfb;
            color: #1E3A8A;
        }

        .section {
            padding: 30px 30px;
            margin-left: 200px;
            border-radius: 15px;
            width: 1550px;

        }

        .reminder {
            display: flex;
            align-items: center;
            background-color: #e5ebf7;
            height: 86px;
            color: #1E3A8A;
            border-radius: 20px;
        }

        .add-btn {
            height: 51px;
            width: 213px;
            border-color: #1E3A8A;
            border-style: solid;
            background-color: white;
            color: #1E3A8A;
        }

        .add-btn:hover {
            color: white;
        }

        .complete-box {
            padding: 30px 30px;
            margin-left: 200px;
            border-radius: 15px;
            width: 1550px;
            background-color: #e5ebf7;
            color: #1E3A8A;
        }

        .complete-btn {
            background-color: #1E3A8A;
            border-style: solid;
        }

        .complete-btn:hover {
            background-color: #e5edfb;
            color: #1E3A8A;
            border-color: #1E3A8A;
        }

        /* Modals */
        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            overflow: auto;
            background-color: rgba(0, 0, 0, 0.4);
            justify-content: center;
            align-items: center;
        }

        .modal-content {
            background-color: white;
            padding: 25px;
            border-radius: 10px;
            width: 400px;
            max-width: 90%;
            text-align: center;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.2);
        }

        .modal-content input,
        .modal-content textarea {
            width: 100%;
            padding: 8px;
            margin-top: 10px;
            margin-bottom: 15px;
            border: 1px solid #ccc;
            border-radius: 5px;
            font-family: 'Poppins', sans-serif;
        }

        .modal-content button {
            padding: 8px 16px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-weight: 500;
        }

        .save-btn {
            background-color: #1E3A8A;
            color: white;
        }

        .cancel-btn {
            background-color: #ccc;
            margin-left: 8px;
        }

        /* Skill chip styles */
        .skill-chip {
            display: inline-block;
            background: linear-gradient(135deg, #eef2ff 0%, #e6f0ff 100%);
            color: #0f172a;
            padding: 6px 12px;
            border-radius: 999px;
            margin: 4px;
            font-size: 14px;
            box-shadow: 0 1px 2px rgba(16, 24, 40, 0.05);
        }

        /* Flash messages */
        .flash-success {
            background: #d1fae5;
            color: #065f46;
            padding: 12px;
            border-radius: 8px;
            margin-left: 200px;
            max-width: 1200px;
            box-shadow: 0 2px 6px rgba(16, 24, 40, 0.06);
        }

        .flash-error {
            background: #fee2e2;
            color: #991b1b;
            padding: 12px;
            border-radius: 8px;
            margin-left: 200px;
            max-width: 1200px;
            box-shadow: 0 2px 6px rgba(16, 24, 40, 0.06);
        }
    </style>


</head>

<body>
    <!-- Sidebar -->
    <!-- Sidebar -->
    <div class="sidebar">
        <a href="Applicant_Profile.php" class="profile">
            <i class="fa-solid fa-user"></i>
        </a>

        <ul class="nav">
            <li><a href="Applicant_Dashboard.php"><i class="fa-solid fa-table-columns"></i>Dashboard</a>
            </li>
            <li><a href="Applicant_Application.php"><i class="fa-solid fa-file-lines"></i>Applications</a></li>
            <li><a href="Applicant_Jobs.php"><i class="fa-solid fa-briefcase"></i>Jobs</a></li>
            <li><a href="Login.php"><i class="fa-solid fa-right-from-bracket"></i>Log Out</a></li>
        </ul>
    </div>

    </div>


    <!-- Main Content -->
    <main class="main-content">

        <h1 class="profile-header">Profile</h1>

        <?php if (!empty($flash_success)): ?>
            <div class="flash-success">
                <?php echo htmlspecialchars($flash_success); ?>
            </div>
        <?php endif; ?>
        <?php if (!empty($flash_error)): ?>
            <div class="flash-error">
                <?php echo htmlspecialchars($flash_error); ?>
            </div>
        <?php endif; ?>

        <div class="personal-info">
            <div class="info">
                <h1 name="full-name">
                    <?php echo $name; ?>
                </h1>
                <div class="phone">
                    <i class="fa-solid fa-phone"></i>
                    <p name="phone"><?php echo $phone; ?></p>
                </div>
                <div class="location">
                    <i class="fa-solid fa-location-dot"></i>
                    <p name="location"><?php echo $location; ?></p>
                </div>
                <div class="email">
                    <i class="fa-solid fa-envelope"></i>
                    <p name="email"><?php echo $email; ?></p>
                </div>
                <button onclick="openModal('editModal')" class="edit-btn"><i class="fa-solid fa-pen"></i>
                    Edit</button>
            </div>
        </div>
        <div class="section">
            <div class="career">
                <h3>Career history</h3>
                <div class="reminder">
                    <p><i class="fa-solid fa-circle-exclamation"></i> Please add your most recent roles</p>
                </div>
                <button onclick="openModal('roleModal')" class="add-btn">Add role</button>
                <?php if (!empty($roles)): ?>
                    <div style="margin-top:12px;margin-left:10px;">
                        <?php foreach ($roles as $r): ?>
                            <div
                                style="background:#fff;padding:10px;border-radius:6px;margin-bottom:8px;max-width:1200px;position:relative;">
                                <strong><?php echo htmlspecialchars($r['job_title']); ?></strong>
                                <?php if (!empty($r['company_name'])): ?>
                                    <div style="font-size:14px;color:#555;"><?php echo htmlspecialchars($r['company_name']); ?>
                                    </div>
                                <?php endif; ?>
                                <?php if (!empty($r['description'])): ?>
                                    <div style="margin-top:6px;color:#333;">
                                        <?php echo nl2br(htmlspecialchars($r['description'])); ?>
                                    </div>
                                <?php endif; ?>
                                <div style="position:absolute;right:10px;top:10px;display:flex;gap:6px;">
                                    <button type="button" class="save-btn"
                                        onclick="openRoleEditModal(<?php echo (int) $r['id']; ?>, <?php echo json_encode($r['job_title']); ?>, <?php echo json_encode($r['company_name']); ?>, <?php echo json_encode($r['description']); ?>)">Edit</button>
                                    <form method="POST" style="display:inline;" onsubmit="return confirm('Delete this role?');">
                                        <input type="hidden" name="delete_role" value="1">
                                        <input type="hidden" name="role_id" value="<?php echo (int) $r['id']; ?>">
                                        <button type="submit" class="cancel-btn">Delete</button>
                                    </form>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        <div class="section">
            <div class="education">
                <h3>Education</h3>
                <div class="reminder">
                    <p><i class="fa-solid fa-circle-exclamation"></i> Please add your most recent qualifications</p>
                </div>
                <button onclick="openModal('educationModal')" class="add-btn">Add education</button>
                <?php if (!empty($edus)): ?>
                    <div style="margin-top:12px;margin-left:10px;">
                        <?php foreach ($edus as $e): ?>
                            <div
                                style="background:#fff;padding:10px;border-radius:6px;margin-bottom:8px;max-width:1200px;position:relative;">
                                <strong><?php echo htmlspecialchars($e['school']); ?></strong>
                                <?php if (!empty($e['degree'])): ?>
                                    <div style="font-size:14px;color:#555;"><?php echo htmlspecialchars($e['degree']); ?></div>
                                <?php endif; ?>
                                <div style="position:absolute;right:10px;top:10px;display:flex;gap:6px;">
                                    <button type="button" class="save-btn"
                                        onclick="openEducationEditModal(<?php echo (int) $e['id']; ?>, <?php echo json_encode($e['school']); ?>, <?php echo json_encode($e['degree']); ?>)">Edit</button>
                                    <form method="POST" style="display:inline;"
                                        onsubmit="return confirm('Delete this education entry?');">
                                        <input type="hidden" name="delete_education" value="1">
                                        <input type="hidden" name="education_id" value="<?php echo (int) $e['id']; ?>">
                                        <button type="submit" class="cancel-btn">Delete</button>
                                    </form>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        <div class="section">
            <h3>Skills</h3>
            <div class="reminder">
                <p><i class="fa-solid fa-circle-exclamation"></i> Please add at least five skills</p>
            </div>
            <button onclick="openModal('skillsModal'); prefillSkillsModal();" class="add-btn">Add / Edit skills</button>
            <?php if (!empty($skills_array)): ?>
                <div style="margin-top:12px;margin-left:10px;">
                    <div style="background:#fff;padding:10px;border-radius:6px;margin-bottom:8px;max-width:1200px;">
                        <?php foreach ($skills_array as $sk): ?>
                            <span
                                style="display:inline-block;background:#eef2ff;color:#1e3a8a;padding:6px 10px;border-radius:999px;margin:4px;font-size:14px;"><?php echo htmlspecialchars($sk); ?></span>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>
        </div>
        <div class="section">
            <h3>Summary</h3>
            <div class="reminder">
                <p><i class="fa-solid fa-circle-exclamation"></i> Please add a summary</p>
            </div>
            <button onclick="openModal('summaryModal'); prefillSummaryModal();" class="add-btn">Add / Edit
                summary</button>
            <?php if (!empty($summary_text)): ?>
                <div
                    style="margin-top:12px;margin-left:10px;max-width:1200px;background:#fff;padding:12px;border-radius:8px;">
                    <?php echo nl2br(htmlspecialchars($summary_text)); ?>
                </div>
            <?php endif; ?>
        </div>

        <div class="complete-box">
            <p>Ensure all information are correct, your resume will be created</p>
            <button class="complete-btn">Complete</button>
        </div>

    </main>

    <!--Modals-->
    <div id="editModal" class="modal">
        <div class="modal-content">
            <h3>Edit Profile</h3>
            <form method="POST">
                <input type="hidden" name="update_profile" value="1">
                <div style="text-align:left;margin-bottom:8px;">
                    <label style="font-weight:600;">Name</label>
                    <div style="background:#f3f4f6;padding:8px;border-radius:6px;margin-top:4px;">
                        <?php echo htmlspecialchars($name); ?>
                    </div>
                </div>
                <div style="text-align:left;margin-bottom:8px;">
                    <label style="font-weight:600;">Email</label>
                    <div style="background:#f3f4f6;padding:8px;border-radius:6px;margin-top:4px;">
                        <?php echo htmlspecialchars($email); ?>
                    </div>
                </div>
                <input type="text" name="phoneNumber" placeholder="Phone number" required
                    value="<?php echo htmlspecialchars($phone); ?>">
                <input type="text" name="homeLocation" placeholder="Home location" required
                    value="<?php echo htmlspecialchars($location); ?>">
                <div style="display:flex;justify-content:center;gap:8px;">
                    <button type="submit" class="save-btn">Save</button>
                    <button type="button" class="cancel-btn" onclick="closeModal('editModal')">Cancel</button>
                </div>
            </form>
        </div>
    </div>

    <div id="roleModal" class="modal">
        <div class="modal-content">
            <h3>Add Role</h3>
            <form method="POST">
                <input type="hidden" name="add_role" value="1">
                <input type="text" name="job_title" placeholder="Job Title" required>
                <input type="text" name="company_name" placeholder="Company Name" required>
                <textarea name="role_description" rows="3" placeholder="Description" required></textarea>
                <div style="display:flex;justify-content:center;gap:8px;">
                    <button type="submit" class="save-btn">Save</button>
                    <button type="button" class="cancel-btn" onclick="closeModal('roleModal')">Cancel</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Edit Role Modal -->
    <div id="editRoleModal" class="modal">
        <div class="modal-content">
            <h3>Edit Role</h3>
            <form method="POST">
                <input type="hidden" name="edit_role" value="1">
                <input type="hidden" name="role_id" id="role_id_edit" value="0">
                <input type="text" name="job_title_edit" id="job_title_edit" placeholder="Job Title" required>
                <input type="text" name="company_name_edit" id="company_name_edit" placeholder="Company Name" required>
                <textarea name="role_description_edit" id="role_description_edit" rows="3" placeholder="Description"
                    required></textarea>
                <div style="display:flex;justify-content:center;gap:8px;">
                    <button type="submit" class="save-btn">Save</button>
                    <button type="button" class="cancel-btn" onclick="closeModal('editRoleModal')">Cancel</button>
                </div>
            </form>
        </div>
    </div>

    <div id="educationModal" class="modal">
        <div class="modal-content">
            <h3>Add Education</h3>
            <form method="POST">
                <input type="hidden" name="add_education" value="1">
                <input type="text" name="school" placeholder="School / University" required>
                <input type="text" name="degree" placeholder="Degree / Course" required>
                <div style="display:flex;justify-content:center;gap:8px;">
                    <button type="submit" class="save-btn">Save</button>
                    <button type="button" class="cancel-btn" onclick="closeModal('educationModal')">Cancel</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Edit Education Modal -->
    <div id="editEducationModal" class="modal">
        <div class="modal-content">
            <h3>Edit Education</h3>
            <form method="POST">
                <input type="hidden" name="edit_education" value="1">
                <input type="hidden" name="education_id" id="education_id_edit" value="0">
                <input type="text" name="school_edit" id="school_edit" placeholder="School / University" required>
                <input type="text" name="degree_edit" id="degree_edit" placeholder="Degree / Course" required>
                <div style="display:flex;justify-content:center;gap:8px;">
                    <button type="submit" class="save-btn">Save</button>
                    <button type="button" class="cancel-btn" onclick="closeModal('editEducationModal')">Cancel</button>
                </div>
            </form>
        </div>
    </div>

    <div id="skillsModal" class="modal">
        <div class="modal-content">
            <h3>Add Skills</h3>
            <form method="POST">
                <input type="hidden" name="add_skills" value="1">
                <input type="text" id="skill1" name="skill1" placeholder="Skill 1" required>
                <input type="text" id="skill2" name="skill2" placeholder="Skill 2" required>
                <input type="text" id="skill3" name="skill3" placeholder="Skill 3" required>
                <input type="text" id="skill4" name="skill4" placeholder="Skill 4" required>
                <input type="text" id="skill5" name="skill5" placeholder="Skill 5" required>
                <div style="display:flex;justify-content:center;gap:8px;">
                    <button type="submit" class="save-btn">Save</button>
                    <button type="button" class="cancel-btn" onclick="closeModal('skillsModal')">Cancel</button>
                </div>
            </form>
        </div>
    </div>

    <div id="summaryModal" class="modal">
        <div class="modal-content">
            <h3>Add Summary</h3>
            <form method="POST">
                <input type="hidden" name="add_summary" value="1">
                <textarea id="summary_text" name="summary_text" rows="4"
                    placeholder="Write a short professional summary..." required></textarea>
                <div style="display:flex;justify-content:center;gap:8px;">
                    <button type="submit" class="save-btn">Save</button>
                    <button type="button" class="cancel-btn" onclick="closeModal('summaryModal')">Cancel</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function openModal(id) {
            document.getElementById(id).style.display = 'flex';
        }

        function closeModal(id) {
            document.getElementById(id).style.display = 'none';
        }

        // Close modal when clicking outside
        window.onclick = function (event) {
            const modals = document.querySelectorAll('.modal');
            modals.forEach(modal => {
                if (event.target == modal) {
                    modal.style.display = 'none';
                }
            });
        }

        // Open edit role modal and prefill values
        function openRoleEditModal(id, title, company, desc) {
            document.getElementById('role_id_edit').value = id;
            document.getElementById('job_title_edit').value = title || '';
            document.getElementById('company_name_edit').value = company || '';
            document.getElementById('role_description_edit').value = desc || '';
            openModal('editRoleModal');
        }

        // Prefill skills modal with existing skills from PHP variable
        function prefillSkillsModal() {
            try {
                const skills = <?php echo json_encode($skills_array ?? []); ?>;
                for (let i = 1; i <= 5; i++) {
                    const el = document.getElementById('skill' + i);
                    if (el) el.value = skills[i - 1] || '';
                }
                openModal('skillsModal');
            } catch (e) {
                openModal('skillsModal');
            }
        }

        // Prefill summary modal with existing summary
        function prefillSummaryModal() {
            try {
                const text = <?php echo json_encode($summary_text ?? ''); ?>;
                const el = document.getElementById('summary_text');
                if (el) el.value = text;
                openModal('summaryModal');
            } catch (e) {
                openModal('summaryModal');
            }
        }

        // Open edit education modal and prefill values
        function openEducationEditModal(id, school, degree) {
            document.getElementById('education_id_edit').value = id;
            document.getElementById('school_edit').value = school || '';
            document.getElementById('degree_edit').value = degree || '';
            openModal('editEducationModal');
        }
    </script>


</body>

</html>