<?php
session_start();
require 'admin/db.connect.php';

$systemName = "";
$email = "";
$contact = "";
$aboutContent = "";
$coverImg = "";
$logoImg = "";
$workWithUs = "";
$msg = "";

// GET ADMIN NAME
$adminQuery = $conn->query("SELECT fullname FROM user WHERE role = 'Admin' LIMIT 1");
$adminname = ($adminQuery && $row = $adminQuery->fetch_assoc()) ? $row['fullname'] : 'Admin';

// GET EXISTING SETTINGS
$settingsQuery = $conn->query("SELECT * FROM system_settings LIMIT 1");
$settings = $settingsQuery ? $settingsQuery->fetch_assoc() : null;

if ($settings) {
    $systemName = $settings['system_name'];
    $email = $settings['email'];
    $contact = $settings['contact'];
    $aboutContent = $settings['about'];
    $coverImg = $settings['cover_image'];
    $logoImg = $settings['logo'];
    $workWithUs = $settings['work_with_us'];
}

// Function to handle image upload
function handleUpload($fileField, $existingFile = null) {
    global $msg;
    if (isset($_FILES[$fileField]) && $_FILES[$fileField]['error'] == 0) {
        $target_dir = "uploads/";
        if (!is_dir($target_dir)) mkdir($target_dir, 0777, true);

        $ext = strtolower(pathinfo($_FILES[$fileField]["name"], PATHINFO_EXTENSION));
        $filename = time() . "_" . uniqid() . "." . $ext;
        $target_file = $target_dir . $filename;

        $check = getimagesize($_FILES[$fileField]["tmp_name"]);
        if ($check !== false) {
            if ($_FILES[$fileField]["size"] <= 5*1024*1024) {
                if (in_array($ext, ['jpg','jpeg','png','gif'])) {
                    if (move_uploaded_file($_FILES[$fileField]["tmp_name"], $target_file)) {
                        return $target_file;
                    } else {
                        $msg = "Error uploading file: $fileField";
                    }
                } else {
                    $msg = "Invalid file type for $fileField.";
                }
            } else {
                $msg = "File too large for $fileField.";
            }
        } else {
            $msg = "Uploaded file is not an image: $fileField";
        }
    }
    return $existingFile;
}

// HANDLE FORM SUBMISSION
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $systemName = $_POST['systemName'] ?? '';
    $email = $_POST['email'] ?? '';
    $contact = $_POST['contact'] ?? '';
    $aboutContent = $_POST['aboutContent'] ?? '';

    // Handle Work With Us Features
    $featureTitles = $_POST['feature_title'] ?? [];
    $featureIcons = $_POST['feature_icon'] ?? [];
    $featureDescriptions = $_POST['feature_description'] ?? [];
    $features = [];
    for($i=0; $i<3; $i++){
        $features[] = [
            'title' => $featureTitles[$i] ?? '',
            'icon' => $featureIcons[$i] ?? '',
            'description' => $featureDescriptions[$i] ?? ''
        ];
    }
    $workWithUs = json_encode($features);

    $coverImg = handleUpload('coverUpload', $coverImg);
    $logoImg  = handleUpload('logoUpload', $logoImg);

    if (empty($msg)) {
        if ($settings) {
            $stmt = $conn->prepare("UPDATE system_settings SET system_name=?, email=?, contact=?, about=?, cover_image=?, logo=?, work_with_us=? WHERE system_id=?");
            $stmt->bind_param("sssssssi", $systemName, $email, $contact, $aboutContent, $coverImg, $logoImg, $workWithUs, $settings['system_id']);
            $msg = $stmt->execute() ? "System settings updated successfully." : "Database error: " . $stmt->error;
            $stmt->close();
        } else {
            $stmt = $conn->prepare("INSERT INTO system_settings (system_name,email,contact,about,cover_image,logo,work_with_us) VALUES (?,?,?,?,?,?,?)");
            $stmt->bind_param("sssssss", $systemName, $email, $contact, $aboutContent, $coverImg, $logoImg, $workWithUs);
            $msg = $stmt->execute() ? "System settings saved successfully." : "Database error: " . $stmt->error;
            $stmt->close();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>System Settings</title>
<link rel="stylesheet" href="admin-sidebar.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/7.0.1/css/all.min.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
<style>
body {
    font-family: 'Poppins', 'Roboto', sans-serif;
    margin: 0;
    display: flex;
    background-color: #f1f5fc;
    color: #111827;
}
.main-content {
    margin-left: 250px;
    margin-top: 50px;
    display: flex;
    flex-direction: column;
    background-color: #f1f5fc;
    min-height: 100vh;
}
.main-content h2 {
    color: #1e3a8a;
    margin-bottom: 50px;
    font-size: 26px;
    display: flex;
    align-items: center;
    gap: 10px;
    font-weight: 700;
}
.settings-container {
    background-color: #e5e7eb;
    padding: 60px;
    border-radius: 10px;
    width: 1100px;
    margin-left: 30px;
    box-shadow: 0 4px 10px rgba(0,0,0,0.1);
    margin-bottom: 50px;
}
form { display: flex; flex-direction: column; gap: 15px; }
.form-row { display: flex; gap: 20px; }
.form-group { flex: 1; display: flex; flex-direction: column; }
.form-group label { font-weight: 600; margin-bottom: 5px; display: flex; align-items: center; gap: 8px; }
.form-group input[type="text"], .form-group input[type="email"], .form-group input[type="file"], .form-group textarea {
    width: 100%; padding: 10px; border: none; border-radius: 5px; background-color: #fff; font-family: 'Poppins', sans-serif; font-size: 14px;
}
.form-group textarea { resize: none; }
.form-actions { display: flex; justify-content: flex-end; align-items: center; margin-top: 10px; }
.form-actions button { background-color: #1e3a8a; color: white; border: none; border-radius: 5px; padding: 8px 25px; font-size: 14px; cursor: pointer; }
.form-actions button:hover { background-color: #1d4ed8; }
.form-group label i { color: #1e3a8a; }
.file-hint { font-size: 12px; color: #6b7280; margin-top: 5px; }
.preview-image img { max-width: 300px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
.no-image { color: #6b7280; font-style: italic; padding: 20px; border: 1px dashed #d1d5db; border-radius: 5px; text-align: center; }

.icon-preview i { color: #1E3A8A; font-size:24px; }
.feature-item input, .feature-item textarea { margin-bottom: 5px; }
.feature-item { display: flex; align-items: center; gap: 10px; }
</style>
</head>
<body class="admin-dashboard">
<!-- SIDEBAR -->
<div class="sidebar">
    <div class="sidebar-logo"><img src="Images/hospitallogo.png" alt="Logo"></div>
    <div class="sidebar-name"><p><?php echo "Welcome Admin, $adminname"; ?></p></div>
    <ul class="nav flex-column">
        <li><a href="Admin_Dashboard.php"><i class="fa-solid fa-table-columns"></i> Dashboard</a></li>
        <li><a href="Admin_UserManagement.php"><i class="fa-solid fa-users"></i> User Management</a></li>
        <li><a href="Admin_Departments.php"><i class="fa-solid fa-building-columns"></i> Departments</a></li>
        <li><a href="Admin_Vacancies.php"><i class="fa-solid fa-briefcase"></i> Vacancies</a></li>
        <li><a href="Admin-Applicants.php"><i class="fa-solid fa-user-check"></i> Applicants</a></li>
        <li><a href="Admin_Reports.php"><i class="fa-solid fa-chart-simple"></i> Reports</a></li>
        <li class="active"><a href="Admin-Settings.php"><i class="fa-solid fa-gear"></i> Settings</a></li>
        <li><a href="Login.php"><i class="fa-solid fa-right-from-bracket"></i> Logout</a></li>
    </ul>
</div>

<!-- MAIN CONTENT -->
<div class="main-content">
    <h2><i class="fa-solid fa-gear"></i> System Settings</h2>

    <?php if (!empty($msg)): ?>
        <div class="alert alert-info"><?= $msg ?></div>
    <?php endif; ?>

    <div class="settings-container">
        <form action="" method="POST" enctype="multipart/form-data">
            <div class="form-row">
                <div class="form-group">
                    <label><i class="fa-solid fa-computer"></i> System Name:</label>
                    <input type="text" name="systemName" value="<?= htmlspecialchars($systemName) ?>">
                </div>
                <div class="form-group">
                    <label><i class="fa-solid fa-envelope"></i> Email:</label>
                    <input type="email" name="email" value="<?= htmlspecialchars($email) ?>">
                </div>
            </div>

            <div class="form-group">
                <label><i class="fa-solid fa-phone"></i> Contact:</label>
                <input type="text" name="contact" value="<?= htmlspecialchars($contact) ?>">
            </div>

            <div class="form-group">
                <label><i class="fa-solid fa-circle-info"></i> About:</label>
                <textarea name="aboutContent" rows="5"><?= htmlspecialchars($aboutContent) ?></textarea>
            </div>

            <!-- Logo -->
            <div class="form-group">
                <label>Logo</label>
                <input type="file" name="logoUpload" accept="image/*" id="logoUpload">
                <p class="file-hint">Recommended: 200x200px. Max 5MB</p>
                <div class="preview-image mt-2" id="logoPreviewContainer">
                    <?php if (!empty($logoImg)): ?>
                        <img src="<?= htmlspecialchars($logoImg) ?>" alt="Logo" id="logoPreview">
                    <?php else: ?>
                        <div class="no-image" id="noLogoImage">No logo uploaded</div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Cover -->
            <div class="form-group">
                <label>Cover Image</label>
                <input type="file" name="coverUpload" accept="image/*" id="coverUpload">
                <p class="file-hint">Recommended: 1200x400px. Max 5MB</p>
                <div class="preview-image mt-2" id="coverPreviewContainer">
                    <?php if (!empty($coverImg)): ?>
                        <img src="<?= htmlspecialchars($coverImg) ?>" alt="Cover Image" id="coverPreview">
                    <?php else: ?>
                        <div class="no-image" id="noCoverImage">No image uploaded</div>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Work With Us Main Paragraph -->
<div class="form-group">
    <label><i class="fa-solid fa-lightbulb"></i> Why Work With Us Paragraph:</label>
    <textarea name="work_with_us_paragraph" rows="4" placeholder="Enter the main paragraph here" class="form-control"><?= htmlspecialchars($workWithUsParagraph ?? '') ?></textarea>
</div>
            <!-- Work With Us Features -->
            <h4>Work With Us Features (3)</h4>
            <?php
            $featuresArr = json_decode($workWithUs, true) ?? [];
            for($i=0;$i<3;$i++):
                $feature = $featuresArr[$i] ?? ['title'=>'','description'=>'','icon'=>''];
            ?>
            <div class="feature-item mb-3 p-3 border rounded">
                <div class="icon-preview"><i class="fa-solid <?= htmlspecialchars($feature['icon']) ?>"></i></div>
                <div style="flex:1;">
                    <input type="text" name="feature_title[]" value="<?= htmlspecialchars($feature['title']) ?>" placeholder="Title" class="form-control mb-1">
                    <input type="text" name="feature_icon[]" value="<?= htmlspecialchars($feature['icon']) ?>" placeholder="Font Awesome Icon Class" class="form-control mb-1 icon-input">
                    <textarea name="feature_description[]" rows="2" placeholder="Description" class="form-control"><?= htmlspecialchars($feature['description']) ?></textarea>
                </div>
            </div>
            <?php endfor; ?>

            <div class="form-actions">
                <button type="submit"><i class="fa-solid fa-rotate-right"></i> Apply</button>
            </div>
        </form>
    </div>
</div>

<!-- JS -->
<script>
function setupPreview(fileInputId, previewImgId, noImgId) {
    document.getElementById(fileInputId).addEventListener('change', function (event) {
        const file = event.target.files[0];
        const previewImg = document.getElementById(previewImgId);
        const noImage = document.getElementById(noImgId);
        const container = previewImg ? previewImg.parentNode : null;

        if (file) {
            const reader = new FileReader();
            reader.onload = function (e) {
                if (previewImg) previewImg.src = e.target.result;
                if (noImage) noImage.style.display = 'none';
            };
            reader.readAsDataURL(file);
        } else {
            if (previewImg) previewImg.style.display = 'none';
            if (noImage) noImage.style.display = 'block';
        }
    });
}
setupPreview('logoUpload', 'logoPreview', 'noLogoImage');
setupPreview('coverUpload', 'coverPreview', 'noCoverImage');

// Live icon preview
document.querySelectorAll('.feature-item').forEach(function(item){
    const iconInput = item.querySelector('.icon-input');
    const preview = item.querySelector('.icon-preview i');

    if(iconInput.value){
        preview.className = 'fa-solid ' + iconInput.value;
    }

    iconInput.addEventListener('input', function(){
        preview.className = 'fa-solid ' + iconInput.value.trim();
    });
});
</script>
</body>
</html>
