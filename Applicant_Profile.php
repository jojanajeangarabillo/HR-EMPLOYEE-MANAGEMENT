<?php
session_start();
require 'admin/db.connect.php';

// -------------------------------
// 1. Get Applicant ID from Session
// -------------------------------
$applicantID = $_SESSION['applicant_employee_id'] ?? null;

if (!$applicantID) {
    die("Applicant ID not found in session.");
}

// -------------------------------
// 2. Fetch Applicant Basic Info (Full Name + Picture)
// -------------------------------
$stmt = $conn->prepare("SELECT fullName, profile_pic FROM applicant WHERE applicantID = ?");
$stmt->bind_param("s", $applicantID);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $row = $result->fetch_assoc();
    $applicantname = $row['fullName'];
    $profile_picture = !empty($row['profile_pic'])
        ? "uploads/applicants/" . $row['profile_pic']
        : "uploads/employees/default.png";
} else {
    $applicantname = "Applicant";
    $profile_picture = "uploads/employees/default.png";
}

// -------------------------------
// 3. Fetch ALL Applicant Details
// -------------------------------
$stmt = $conn->prepare("
    SELECT applicantID, fullName, position_applied, department, type_name, date_applied,
           contact_number, email_address, home_address,
           university, course, year_graduated,
           job_title, company_name, years_experience, in_role,
           summary, skills, status, hired_at, profile_pic
    FROM applicant
    WHERE applicantID = ?
");
$stmt->bind_param("s", $applicantID);
$stmt->execute();
$result = $stmt->get_result();

$applicant = $result->fetch_assoc();

// Helper for displaying empty fields
function displayOrEmpty($value) {
    return !empty($value) ? htmlspecialchars($value) : "<span style='color:#888;'>Not set</span>";
}
// Handle applicant profile picture upload
if (isset($_POST['upload_pic'])) {

    if (isset($_FILES['profile_pic']) && $_FILES['profile_pic']['error'] === 0) {

        $file_name = $_FILES['profile_pic']['name'];
        $file_tmp = $_FILES['profile_pic']['tmp_name'];
        $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));

        $allowed_extensions = ['jpg', 'jpeg', 'png'];

        if (in_array($file_ext, $allowed_extensions)) {

            // Directory for applicants
            $upload_dir = "uploads/applicants/";
            if (!is_dir($upload_dir)) {
                mkdir($upload_dir, 0755, true);
            }

            // Rename the file
            $new_filename = "applicant_" . $applicantID . "." . $file_ext;
            $upload_path = $upload_dir . $new_filename;

            // Move uploaded file
            if (move_uploaded_file($file_tmp, $upload_path)) {

                // Update database
                $update = $conn->prepare("UPDATE applicant SET profile_pic = ? WHERE applicantID = ?");
                $update->bind_param("ss", $new_filename, $applicantID);

                if ($update->execute()) {
                    $_SESSION['flash_success'] = 'Profile picture updated successfully.';
                } else {
                    $_SESSION['flash_error'] = 'Failed to update profile picture in the database.';
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

    header("Location: Applicant_Profile.php");
    exit();
}

// -------------------------------
// 4. UPDATE: PERSONAL INFORMATION
// -------------------------------
if (isset($_POST['save_personal'])) {
    $stmt = $conn->prepare("
        UPDATE applicant 
        SET fullName=?, contact_number=?, email_address=?, home_address=? 
        WHERE applicantID=?
    ");
    $stmt->bind_param("sssss",
        $_POST['fullName'],
        $_POST['contact_number'],
        $_POST['email_address'],
        $_POST['home_address'],
        $applicantID
    );
    $stmt->execute();
    header("Location: Applicant_Profile.php");
    exit();
}


// -------------------------------
// 5. UPDATE: EDUCATION
// -------------------------------
if (isset($_POST['save_education'])) {
    $stmt = $conn->prepare("
        UPDATE applicant 
        SET university=?, course=?, year_graduated=?
        WHERE applicantID=?
    ");
    $stmt->bind_param("ssss",
        $_POST['university'],
        $_POST['course'],
        $_POST['year_graduated'],
        $applicantID
    );
    $stmt->execute();
    header("Location: Applicant_Profile.php");
    exit();
}


// -------------------------------
// 6. UPDATE: WORK EXPERIENCE
// -------------------------------
if (isset($_POST['save_job'])) {
    $stmt = $conn->prepare("
        UPDATE applicant 
        SET job_title=?, company_name=?, years_experience=?
        WHERE applicantID=?
    ");
    $stmt->bind_param("ssss",
        $_POST['job_title'],
        $_POST['company_name'],
        $_POST['years_experience'],
        $applicantID
    );
    $stmt->execute();
    header("Location: Applicant_Profile.php");
    exit();
}


// -------------------------------
// 7. UPDATE: SKILLS & SUMMARY
// -------------------------------
if (isset($_POST['save_skills'])) {
    // Use 'skill[]' from the form
    $skills = isset($_POST['skill']) ? implode(", ", array_map('trim', $_POST['skill'])) : '';
    $summary = $_POST['summary'] ?? '';

    $stmt = $conn->prepare("
        UPDATE applicant 
        SET skills=?, summary=?
        WHERE applicantID=?
    ");
    $stmt->bind_param("sss", $skills, $summary, $applicantID);
    $stmt->execute();

    header("Location: Applicant_Profile.php");
    exit();
}




?>



<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Applicant Profile</title>
<link rel="stylesheet" href="admin-sidebar.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/7.0.1/css/all.min.css">
<style>
    :root {
        --primary: #1E3A8A;
        --primary-light: #3B82F6;
        --primary-dark: #1E40AF;
        --secondary: #2563EB;
        --accent: #10B981;
        --warning: #F59E0B;
        --danger: #EF4444;
        --success: #10B981;
        --light: #F8FAFC;
        --dark: #111827;
        --gray: #6B7280;
        --gray-light: #E5E7EB;
        --border-radius: 16px;
        --border-radius-sm: 8px;
        --box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
        --box-shadow-lg: 0 12px 40px rgba(0, 0, 0, 0.15);
        --transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    }

    * {
        margin: 0;
        padding: 0;
        box-sizing: border-box;
    }

    body {
        font-family: 'Poppins', sans-serif;
        display: flex;
        background: linear-gradient(135deg, #f8fafc 0%, #e2e8f0 100%);
        color: var(--dark);
        min-height: 100vh;
        line-height: 1.6;
    }

    .main-content {
        flex: 1;
        padding: 30px 40px;
        display: flex;
        flex-direction: column;
        gap: 30px;
        margin-left: 260px;
        transition: var(--transition);
        width: calc(100% - 260px);
    }

    /* Header Section */
    .profile-header {
        text-align: center;
        margin-bottom: 10px;
       
    }

    .profile-header h1 {
        font-weight: 700;
        font-size: 32px;
        background: linear-gradient(135deg, #1E3A8A 0%, #3B82F6 100%);
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
        background-clip: text;
        margin-bottom: 8px;
    }

    .profile-header p {
        color: var(--gray);
        font-size: 16px;
        font-weight: 400;
    }

    /* Profile Sections Grid */
    .profile-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(400px, 1fr));
        gap: 25px;
       
    }

    .profile-section {
        background: rgba(255, 255, 255, 0.9);
        backdrop-filter: blur(10px);
        border-radius: var(--border-radius);
        padding: 30px;
        box-shadow: var(--box-shadow);
        transition: var(--transition);
        border: 1px solid rgba(255, 255, 255, 0.8);
        position: relative;
        overflow: hidden;
        height: fit-content;
    }

    .profile-section::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        height: 4px;
        background: linear-gradient(135deg, var(--primary) 0%, var(--primary-light) 100%);
    }

    .profile-section:hover {
        transform: translateY(-5px);
        box-shadow: var(--box-shadow-lg);
    }

    .section-header {
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        margin-bottom: 20px;
    }

    .section-title {
        font-size: 20px;
        font-weight: 600;
        color: var(--dark);
        display: flex;
        align-items: center;
        gap: 10px;
    }

    .section-title i {
        color: var(--primary);
        font-size: 22px;
    }

    .edit-btn {
        background: rgba(59, 130, 246, 0.1);
        border: 1px solid rgba(59, 130, 246, 0.2);
        color: var(--primary);
        padding: 8px 16px;
        border-radius: var(--border-radius-sm);
        font-size: 14px;
        font-weight: 500;
        cursor: pointer;
        transition: var(--transition);
        display: flex;
        align-items: center;
        gap: 6px;
    }

    .edit-btn:hover {
        background: var(--primary);
        color: white;
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(59, 130, 246, 0.3);
    }

    /* Profile Info Items */
    .info-grid {
        display: grid;
        gap: 16px;
    }

    .info-item {
        display: flex;
        flex-direction: column;
        gap: 4px;
    }

    .info-label {
        font-size: 12px;
        color: var(--gray);
        font-weight: 500;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }

    .info-value {
        font-size: 15px;
        color: var(--dark);
        font-weight: 500;
    }

    .empty-value {
        color: var(--gray);
        font-style: italic;
    }

    /* Profile Picture Section */
    .profile-picture-section {
        text-align: center;
        padding: 25px;
    }

    .profile-picture-container {
        position: relative;
        display: inline-block;
        margin-bottom: 20px;
    }

    .profile-picture {
        width: 150px;
        height: 150px;
        border-radius: 50%;
        object-fit: cover;
        border: 4px solid rgba(59, 130, 246, 0.2);
        box-shadow: 0 8px 25px rgba(0, 0, 0, 0.15);
        transition: var(--transition);
    }

    .profile-picture:hover {
        border-color: var(--primary);
        transform: scale(1.05);
    }

    .upload-overlay {
        position: absolute;
        bottom: 10px;
        right: 10px;
        background: var(--primary);
        color: white;
        width: 40px;
        height: 40px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        cursor: pointer;
        transition: var(--transition);
        opacity: 0;
    }

    .profile-picture-container:hover .upload-overlay {
        opacity: 1;
        transform: scale(1.1);
    }

    .upload-btn {
        background: linear-gradient(135deg, var(--primary) 0%, var(--primary-light) 100%);
        color: white;
        border: none;
        padding: 10px 24px;
        border-radius: var(--border-radius-sm);
        font-weight: 500;
        cursor: pointer;
        transition: var(--transition);
        display: inline-flex;
        align-items: center;
        gap: 8px;
    }

    .upload-btn:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 20px rgba(59, 130, 246, 0.4);
    }

    /* Skills Badges */
    .skills-container {
        display: flex;
        flex-wrap: wrap;
        gap: 8px;
        margin-top: 8px;
    }

    .skill-badge {
        background: linear-gradient(135deg, var(--primary) 0%, var(--primary-light) 100%);
        color: white;
        padding: 6px 12px;
        border-radius: 20px;
        font-size: 12px;
        font-weight: 500;
        box-shadow: 0 2px 8px rgba(59, 130, 246, 0.2);
    }

    /* Summary Section */
    .summary-text {
        background: var(--light);
        padding: 16px;
        border-radius: var(--border-radius-sm);
        border-left: 4px solid var(--primary);
        font-size: 14px;
        line-height: 1.6;
        color: var(--dark);
    }

    /* Enhanced Modals */
    .modal-content {
        border-radius: var(--border-radius);
        border: none;
        box-shadow: var(--box-shadow-lg);
    }

    .modal-header {
        background: linear-gradient(135deg, var(--primary) 0%, var(--primary-light) 100%);
        color: white;
        border-bottom: none;
        padding: 20px 25px;
        border-top-left-radius: var(--border-radius);
        border-top-right-radius: var(--border-radius);
    }

    .modal-title {
        font-weight: 600;
        font-size: 1.3rem;
    }

    .btn-close {
        filter: brightness(0) invert(1);
    }

    .modal-body {
        padding: 25px;
    }

    .form-label {
        font-weight: 600;
        color: var(--dark);
        margin-bottom: 8px;
    }

    .form-control {
        padding: 12px 16px;
        border-radius: var(--border-radius-sm);
        border: 1px solid var(--gray-light);
        font-size: 15px;
        transition: var(--transition);
    }

    .form-control:focus {
        border-color: var(--primary);
        box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.15);
    }

    .modal-footer {
        padding: 20px 25px;
        border-top: 1px solid var(--gray-light);
    }

    .btn-primary {
        background: linear-gradient(135deg, var(--primary) 0%, var(--primary-light) 100%);
        border: none;
        border-radius: var(--border-radius-sm);
        padding: 10px 24px;
        font-weight: 500;
        transition: var(--transition);
    }

    .btn-primary:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 20px rgba(59, 130, 246, 0.4);
    }

    .btn-secondary {
        border-radius: var(--border-radius-sm);
        padding: 10px 24px;
    }

    /* Skills Input */
    .skill-input-group {
        display: flex;
        gap: 10px;
        margin-bottom: 10px;
    }

    .skill-input-group input {
        flex: 1;
    }

    .add-skill-btn {
        background: var(--success);
        color: white;
        border: none;
        border-radius: var(--border-radius-sm);
        padding: 0 15px;
        cursor: pointer;
        transition: var(--transition);
    }

    .add-skill-btn:hover {
        background: var(--accent);
        transform: scale(1.05);
    }

    /* Animations */
    @keyframes fadeInDown {
        from {
            opacity: 0;
            transform: translateY(-20px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    @keyframes fadeInUp {
        from {
            opacity: 0;
            transform: translateY(20px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    /* Responsive Design */
    @media (max-width: 1200px) {
        .main-content {
            margin-left: 0;
            padding: 20px;
            width: 100%;
        }
    }

    @media (max-width: 768px) {
        .profile-grid {
            grid-template-columns: 1fr;
            gap: 20px;
        }
        
        .profile-section {
            padding: 20px;
        }
        
        .section-header {
            flex-direction: column;
            gap: 15px;
        }
        
        .edit-btn {
            align-self: flex-end;
        }
    }

    @media (max-width: 576px) {
        .main-content {
            padding: 15px;
        }
        
        .profile-header h1 {
            font-size: 28px;
        }
        
        .profile-picture {
            width: 120px;
            height: 120px;
        }
    }

    /* Sidebar Styles */
    .sidebar-profile-img {
        width: 130px;
        height: 130px;
        border-radius: 50%;
        object-fit: cover;
        border: 4px solid rgba(255, 255, 255, 0.2);
        margin-bottom: 20px;
        transition: transform 0.3s ease;
    }

    .sidebar-profile-img:hover {
        transform: scale(1.05);
        border-color: rgba(255, 255, 255, 0.4);
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
</style>
</head>
<body>

<!-- Sidebar -->
<div class="sidebar">
    <a href="Applicant_Profile.php" class="profile">
        <img src="<?php echo !empty($profile_picture) ? htmlspecialchars($profile_picture) : 'uploads/employees/default.png'; ?>" 
        alt="Profile" class="sidebar-profile-img">
    </a>

    <div class="sidebar-name">
        <p><?php echo "Welcome, $applicantname"; ?></p>
    </div>

    <ul class="nav">
        <li><a href="Applicant_Dashboard.php"><i class="fa-solid fa-table-columns"></i>Dashboard</a></li>
        <li><a href="Applicant_Application.php"><i class="fa-solid fa-file-lines"></i>Applications</a></li>
        <li><a href="Applicant_Jobs.php"><i class="fa-solid fa-briefcase"></i>Jobs</a></li>
        <li><a href="Login.php"><i class="fa-solid fa-right-from-bracket"></i>Log Out</a></li>
    </ul>
</div>

<main class="main-content">
    <!-- Profile Header -->
    <div class="profile-header">
        <h1>Applicant Profile</h1>
        <p>Manage your personal information and professional details</p>
    </div>

    <!-- Profile Grid -->
    <div class="profile-grid">
        <!-- Profile Picture Section -->
        <div class="profile-section profile-picture-section">
            <div class="profile-picture-container">
                <img id="profile-preview" src="<?php echo htmlspecialchars($profile_picture); ?>" alt="Profile" class="profile-picture">
                <div class="upload-overlay" onclick="document.getElementById('profile-upload').click()">
                    <i class="fas fa-camera"></i>
                </div>
            </div>
            <form action="" method="post" enctype="multipart/form-data">
                <input type="file" name="profile_pic" id="profile-upload" style="display:none;" accept="image/*">
                <button type="button" class="upload-btn" onclick="document.getElementById('profile-upload').click()">
                    <i class="fas fa-upload"></i> Upload New Photo
                </button>
                <button type="submit" name="upload_pic" id="submit-btn" style="display:none;"></button>
            </form>
        </div>

        <!-- Personal Information -->
        <div class="profile-section">
            <div class="section-header">
                <h3 class="section-title"><i class="fas fa-user-circle"></i> Personal Information</h3>
                <button class="edit-btn" data-bs-toggle="modal" data-bs-target="#personalModal">
                    <i class="fas fa-edit"></i> Edit
                </button>
            </div>
            <div class="info-grid">
                <div class="info-item">
                    <span class="info-label">Full Name</span>
                    <span class="info-value"><?php echo displayOrEmpty($applicant['fullName']); ?></span>
                </div>
                <div class="info-item">
                    <span class="info-label">Applicant ID</span>
                    <span class="info-value"><?php echo htmlspecialchars($applicantID); ?></span>
                </div>
                <div class="info-item">
                    <span class="info-label">Contact Number</span>
                    <span class="info-value"><?php echo displayOrEmpty($applicant['contact_number']); ?></span>
                </div>
                <div class="info-item">
                    <span class="info-label">Email Address</span>
                    <span class="info-value"><?php echo displayOrEmpty($applicant['email_address']); ?></span>
                </div>
                <div class="info-item">
                    <span class="info-label">Home Address</span>
                    <span class="info-value"><?php echo displayOrEmpty($applicant['home_address']); ?></span>
                </div>
            </div>
        </div>

        <!-- Professional Experience -->
        <div class="profile-section">
            <div class="section-header">
                <h3 class="section-title"><i class="fas fa-briefcase"></i> Professional Experience</h3>
                <button class="edit-btn" data-bs-toggle="modal" data-bs-target="#jobModal">
                    <i class="fas fa-edit"></i> Edit
                </button>
            </div>
            <div class="info-grid">
                <div class="info-item">
                    <span class="info-label">Job Title</span>
                    <span class="info-value"><?php echo displayOrEmpty($applicant['job_title']); ?></span>
                </div>
                <div class="info-item">
                    <span class="info-label">Company</span>
                    <span class="info-value"><?php echo displayOrEmpty($applicant['company_name']); ?></span>
                </div>
                <div class="info-item">
                    <span class="info-label">Years of Experience</span>
                    <span class="info-value"><?php echo displayOrEmpty($applicant['years_experience']); ?></span>
                </div>
            </div>
        </div>

        <!-- Education -->
        <div class="profile-section">
            <div class="section-header">
                <h3 class="section-title"><i class="fas fa-graduation-cap"></i> Education</h3>
                <button class="edit-btn" data-bs-toggle="modal" data-bs-target="#educationModal">
                    <i class="fas fa-edit"></i> Edit
                </button>
            </div>
            <div class="info-grid">
                <div class="info-item">
                    <span class="info-label">University</span>
                    <span class="info-value"><?php echo displayOrEmpty($applicant['university']); ?></span>
                </div>
                <div class="info-item">
                    <span class="info-label">Course/Degree</span>
                    <span class="info-value"><?php echo displayOrEmpty($applicant['course']); ?></span>
                </div>
                <div class="info-item">
                    <span class="info-label">Year Graduated</span>
                    <span class="info-value"><?php echo displayOrEmpty($applicant['year_graduated']); ?></span>
                </div>
            </div>
        </div>

        <!-- Skills & Summary -->
        <div class="profile-section">
            <div class="section-header">
                <h3 class="section-title"><i class="fas fa-star"></i> Skills & Summary</h3>
                <button class="edit-btn" data-bs-toggle="modal" data-bs-target="#skillsModal">
                    <i class="fas fa-edit"></i> Edit
                </button>
            </div>
            <div class="info-grid">
                <div class="info-item">
                    <span class="info-label">Skills</span>
                    <div class="skills-container">
                        <?php
                        $skills_list = array_filter(array_map('trim', explode(',', $applicant['skills'] ?? '')));
                        if (!empty($skills_list)) {
                            foreach ($skills_list as $skill) {
                                echo '<span class="skill-badge">' . htmlspecialchars($skill) . '</span>';
                            }
                        } else {
                            echo '<span class="empty-value">No skills added</span>';
                        }
                        ?>
                    </div>
                </div>
                <div class="info-item">
                    <span class="info-label">Professional Summary</span>
                    <div class="summary-text">
                        <?php echo displayOrEmpty($applicant['summary']); ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</main>

<!-- Modals (keep your existing modal code, it will work with the enhanced styling) -->
<!-- Personal Info Modal -->
<div class="modal fade" id="personalModal" tabindex="-1" aria-labelledby="personalModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <form method="post">
        <div class="modal-header">
          <h5 class="modal-title" id="personalModalLabel">Edit Personal Information</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <div class="mb-3">
            <label class="form-label">Full Name</label>
            <input type="text" class="form-control" name="fullName" value="<?php echo htmlspecialchars($applicant['fullName']); ?>">
          </div>
          <div class="mb-3">
            <label class="form-label">Contact Number</label>
            <input type="text" class="form-control" name="contact_number" value="<?php echo htmlspecialchars($applicant['contact_number']); ?>">
          </div>
          <div class="mb-3">
            <label class="form-label">Email</label>
            <input type="email" class="form-control" name="email_address" value="<?php echo htmlspecialchars($applicant['email_address']); ?>">
          </div>
          <div class="mb-3">
            <label class="form-label">Home Address</label>
            <input type="text" class="form-control" name="home_address" value="<?php echo htmlspecialchars($applicant['home_address']); ?>">
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" name="save_personal" class="btn btn-primary">Save Changes</button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- Job / Experience Modal -->
<div class="modal fade" id="jobModal" tabindex="-1" aria-labelledby="jobModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <form method="post">
        <div class="modal-header">
          <h5 class="modal-title" id="jobModalLabel">Edit Professional Experience</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <div class="mb-3">
            <label class="form-label">Job Title</label>
            <input type="text" class="form-control" name="job_title" value="<?php echo htmlspecialchars($applicant['job_title']); ?>">
          </div>
          <div class="mb-3">
            <label class="form-label">Company</label>
            <input type="text" class="form-control" name="company_name" value="<?php echo htmlspecialchars($applicant['company_name']); ?>">
          </div>
          <div class="mb-3">
            <label class="form-label">Years Experience</label>
            <input type="number" class="form-control" name="years_experience" value="<?php echo htmlspecialchars($applicant['years_experience']); ?>">
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" name="save_job" class="btn btn-primary">Save Changes</button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- Education Modal -->
<div class="modal fade" id="educationModal" tabindex="-1" aria-labelledby="educationModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <form method="post">
        <div class="modal-header">
          <h5 class="modal-title" id="educationModalLabel">Edit Education</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <div class="mb-3">
            <label class="form-label">University</label>
            <input type="text" class="form-control" name="university" value="<?php echo htmlspecialchars($applicant['university']); ?>">
          </div>
          <div class="mb-3">
            <label class="form-label">Course</label>
            <input type="text" class="form-control" name="course" value="<?php echo htmlspecialchars($applicant['course']); ?>">
          </div>
          <div class="mb-3">
            <label class="form-label">Year Graduated</label>
            <input type="number" class="form-control" name="year_graduated" value="<?php echo htmlspecialchars($applicant['year_graduated']); ?>">
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" name="save_education" class="btn btn-primary">Save Changes</button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- Skills & Summary Modal -->
<div class="modal fade" id="skillsModal" tabindex="-1" aria-labelledby="skillsModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <form method="post">
        <div class="modal-header">
          <h5 class="modal-title" id="skillsModalLabel">Edit Skills & Summary</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <div class="mb-3">
            <label class="form-label">Skills</label>
            <div id="skills-container">
                <?php
                $existing_skills = array_filter(array_map('trim', explode(',', $applicant['skills'] ?? '')));
                for ($i = 0; $i < 5; $i++) {
                    $value = $existing_skills[$i] ?? '';
                    echo '<div class="skill-input-group">';
                    echo '<input type="text" name="skill[]" class="form-control" value="' . htmlspecialchars($value) . '" placeholder="Skill ' . ($i + 1) . '">';
                    echo '</div>';
                }
                ?>
            </div>
          </div>
          <div class="mb-3">
            <label class="form-label">Professional Summary</label>
            <textarea class="form-control" name="summary" rows="4" placeholder="Tell us about your professional background and career goals..."><?php echo htmlspecialchars($applicant['summary']); ?></textarea>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" name="save_skills" class="btn btn-primary">Save Changes</button>
        </div>
      </form>
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
// Profile Upload
const fileInput = document.getElementById('profile-upload');
const submitBtn = document.getElementById('submit-btn');
const imgPreview = document.getElementById('profile-preview');

fileInput.addEventListener('change', function() {
    const file = this.files[0];
    if(file){
        const reader = new FileReader();
        reader.onload = e => {
            imgPreview.src = e.target.result;
            submitBtn.click();
        };
        reader.readAsDataURL(file);
    }
});

// Add hover effects to profile sections
document.querySelectorAll('.profile-section').forEach(section => {
    section.addEventListener('mouseenter', function() {
        this.style.transform = 'translateY(-5px)';
    });
    
    section.addEventListener('mouseleave', function() {
        this.style.transform = 'translateY(0)';
    });
});
</script>
</body>
</html>