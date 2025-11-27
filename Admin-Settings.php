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

// Fetch admin name
$adminnameQuery = $conn->query("SELECT fullname FROM user WHERE sub_role = 'Human Resource (HR) Admin' LIMIT 1");
$adminname = ($adminnameQuery && $row = $adminnameQuery->fetch_assoc()) ? $row['fullname'] : 'Human Resource (HR) Admin';

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
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
<style>
:root {
    --primary: #1E3A8A;
    --primary-light: #3B82F6;
    --primary-dark: #1E40AF;
    --secondary: #64748B;
    --success: #10B981;
    --warning: #F59E0B;
    --danger: #EF4444;
    --light: #F8FAFC;
    --dark: #1E293B;
    --gray-100: #F3F4F6;
    --gray-200: #E5E7EB;
    --gray-300: #D1D5DB;
    --card-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
    --hover-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
    --smooth-shadow: 0 2px 15px -3px rgba(0, 0, 0, 0.07), 0 10px 20px -2px rgba(0, 0, 0, 0.04);
}

body {
    font-family: 'Poppins', sans-serif;
    margin: 0;
    display: flex;
    background: linear-gradient(135deg, #f1f5fc 0%, #e2e8f0 100%);
    color: var(--dark);
    min-height: 100vh;
    font-weight: 400;
    line-height: 1.6;
}

.main-content {
    padding: 30px;
    margin-left: 220px;
    flex: 1;
    display: flex;
    flex-direction: column;
    width: calc(100% - 220px);
}

.main-content-header { 
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 30px;
    padding-bottom: 20px;
    border-bottom: 1px solid var(--gray-200);
}

.main-content-header h1 { 
    color: var(--primary);
    font-weight: 700;
    margin: 0;
    font-size: 2.2rem;
    position: relative;
}

.main-content-header h1::after {
    content: '';
    position: absolute;
    bottom: -10px;
    left: 0;
    width: 80px;
    height: 4px;
    background: linear-gradient(90deg, var(--primary), var(--primary-light));
    border-radius: 2px;
}

.welcome-text {
    color: var(--secondary);
    font-size: 1.1rem;
    margin-top: 10px;
    font-weight: 400;
}

/* Settings Container */
.settings-container {
    background: white;
    border-radius: 20px;
    padding: 40px;
    box-shadow: var(--smooth-shadow);
    border: 1px solid var(--gray-200);
    margin-bottom: 30px;
}

.settings-section {
    margin-bottom: 40px;
    padding-bottom: 30px;
    border-bottom: 1px solid var(--gray-200);
}

.settings-section:last-child {
    border-bottom: none;
    margin-bottom: 0;
}

.section-header {
    display: flex;
    align-items: center;
    gap: 12px;
    margin-bottom: 25px;
    padding-bottom: 15px;
    border-bottom: 2px solid var(--gray-200);
}

.section-title {
    color: var(--primary);
    font-weight: 600;
    margin: 0;
    font-size: 1.4rem;
}

.section-icon {
    color: var(--primary-light);
    font-size: 1.3rem;
}

/* Form Elements */
.form-row {
    display: flex;
    gap: 20px;
    margin-bottom: 20px;
}

.form-group {
    flex: 1;
    display: flex;
    flex-direction: column;
}

.form-label {
    font-weight: 600;
    color: var(--primary);
    margin-bottom: 8px;
    font-size: 0.95rem;
    display: flex;
    align-items: center;
    gap: 8px;
}

.form-label i {
    color: var(--primary-light);
    font-size: 1rem;
}

.form-control, .form-select {
    border-radius: 12px;
    border: 1px solid var(--gray-300);
    padding: 12px 16px;
    font-size: 1rem;
    font-weight: 400;
    transition: all 0.3s;
    font-family: 'Poppins', sans-serif;
    background: white;
}

.form-control:focus, .form-select:focus {
    border-color: var(--primary-light);
    box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
    transform: translateY(-2px);
}

textarea.form-control {
    resize: vertical;
    min-height: 120px;
}

/* File Upload Styling */
.file-upload-container {
    border: 2px dashed var(--gray-300);
    border-radius: 12px;
    padding: 20px;
    text-align: center;
    transition: all 0.3s;
    background: var(--light);
}

.file-upload-container:hover {
    border-color: var(--primary-light);
    background: var(--gray-100);
}

.file-upload-container.dragover {
    border-color: var(--primary);
    background: var(--primary-light);
    color: white;
}

.file-input {
    width: 100%;
    padding: 10px;
    border-radius: 8px;
    border: 1px solid var(--gray-300);
    margin-bottom: 10px;
}

.file-hint {
    font-size: 0.85rem;
    color: var(--secondary);
    margin-top: 5px;
}

/* Image Preview */
.preview-container {
    margin-top: 20px;
}

.preview-image {
    border-radius: 12px;
    overflow: hidden;
    box-shadow: var(--card-shadow);
    border: 1px solid var(--gray-200);
}

.preview-image img {
    width: 100%;
    height: auto;
    display: block;
}

.no-image {
    color: var(--secondary);
    font-style: italic;
    padding: 40px 20px;
    border: 2px dashed var(--gray-300);
    border-radius: 12px;
    text-align: center;
    background: var(--light);
}

/* Feature Items */
.features-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
    gap: 20px;
    margin-top: 20px;
}

.feature-card {
    background: var(--light);
    border-radius: 16px;
    padding: 25px;
    border: 1px solid var(--gray-200);
    transition: all 0.3s;
    position: relative;
}

.feature-card:hover {
    transform: translateY(-5px);
    box-shadow: var(--hover-shadow);
}

.feature-header {
    display: flex;
    align-items: center;
    gap: 15px;
    margin-bottom: 15px;
}

.icon-preview {
    width: 50px;
    height: 50px;
    border-radius: 12px;
    background: linear-gradient(135deg, var(--primary), var(--primary-light));
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-size: 1.3rem;
}

.feature-inputs {
    display: flex;
    flex-direction: column;
    gap: 12px;
}

.feature-number {
    position: absolute;
    top: -10px;
    right: -10px;
    background: var(--primary);
    color: white;
    width: 30px;
    height: 30px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 0.8rem;
    font-weight: 600;
}

/* Buttons */
.form-actions {
    display: flex;
    justify-content: flex-end;
    align-items: center;
    margin-top: 30px;
    padding-top: 20px;
    border-top: 1px solid var(--gray-200);
}

.btn {
    border-radius: 12px;
    padding: 12px 28px;
    font-weight: 500;
    transition: all 0.3s;
    font-family: 'Poppins', sans-serif;
    font-size: 1rem;
    border: none;
    display: inline-flex;
    align-items: center;
    gap: 8px;
    cursor: pointer;
}

.btn-primary {
    background: linear-gradient(135deg, var(--primary), var(--primary-light));
    color: white;
    box-shadow: 0 4px 12px rgba(30, 64, 175, 0.3);
}

.btn-primary:hover {
    background: linear-gradient(135deg, var(--primary-dark), var(--primary));
    transform: translateY(-3px);
    box-shadow: 0 6px 20px rgba(30, 64, 175, 0.4);
}

.btn-secondary {
    background: var(--gray-200);
    color: var(--dark);
    margin-right: 15px;
}

.btn-secondary:hover {
    background: var(--gray-300);
    transform: translateY(-2px);
}

/* Alert Styling */
.alert {
    border-radius: 12px;
    border: none;
    padding: 16px 20px;
    font-weight: 500;
    font-family: 'Poppins', sans-serif;
    margin-bottom: 24px;
}

.alert-success {
    background: linear-gradient(135deg, #D1FAE5, #ECFDF5);
    color: var(--success);
    border-left: 4px solid var(--success);
}

.alert-info {
    background: linear-gradient(135deg, #DBEAFE, #EFF6FF);
    color: var(--primary);
    border-left: 4px solid var(--primary);
}

/* Responsive */
@media (max-width: 768px) {
    .main-content {
        margin-left: 0;
        width: 100%;
        padding: 20px 15px;
    }
    
    .form-row {
        flex-direction: column;
        gap: 15px;
    }
    
    .features-grid {
        grid-template-columns: 1fr;
    }
    
    .form-actions {
        flex-direction: column;
        gap: 10px;
    }
    
    .btn {
        width: 100%;
        justify-content: center;
    }
}

/* Animation */
@keyframes fadeInUp {
    from { opacity: 0; transform: translateY(30px); }
    to { opacity: 1; transform: translateY(0); }
}

.fade-in {
    animation: fadeInUp 0.6s ease;
}
</style>
</head>
<body>
<!-- SIDEBAR -->
<div class="sidebar">
    <div class="sidebar-logo"><img src="Images/hospitallogo.png" alt="Logo"></div>
    <div class="sidebar-name"><p><?php echo "Welcome Admin, $adminname"; ?></p></div>
    <ul class="nav flex-column">
        <li><a href="Admin_Dashboard.php"><i class="fa-solid fa-table-columns"></i> Dashboard</a></li>
        <li><a href="Admin_UserManagement.php"><i class="fa-solid fa-users"></i> User Management</a></li>
        <li><a href="Admin_Departments.php"><i class="fa-solid fa-building-columns"></i> Departments</a></li>
        <li><a href="Admin_RequestSetting.php"><i class="fa-solid fa-clipboard-list"></i> Request Setting</a></li>
        <li><a href="Admin-Applicants.php"><i class="fa-solid fa-user-check"></i> Applicants</a></li>
        <li><a href="Admin_Reports.php"><i class="fa-solid fa-chart-simple"></i> Reports</a></li>
        <li class="active"><a href="Admin-Settings.php"><i class="fa-solid fa-gear"></i> Settings</a></li>
        <li><a href="Login.php"><i class="fa-solid fa-right-from-bracket"></i> Logout</a></li>
    </ul>
</div>

<!-- MAIN CONTENT -->
<div class="main-content">
    <div class="main-content-header">
        <div>
            <h1>System Settings</h1>
            <p class="welcome-text">Manage the hospital's system landing page appearance</p>
        </div>
    </div>

    <?php if (!empty($msg)): ?>
        <div class="alert alert-info fade-in">
            <i class="fa-solid fa-circle-info me-2"></i><?= $msg ?>
        </div>
    <?php endif; ?>

    <div class="settings-container fade-in">
        <form action="" method="POST" enctype="multipart/form-data">
            <!-- Basic Information Section -->
            <div class="settings-section">
                <div class="section-header">
                    <i class="fa-solid fa-building section-icon"></i>
                    <h2 class="section-title">Basic Information</h2>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">
                            <i class="fa-solid fa-computer"></i>System Name
                        </label>
                        <input type="text" name="systemName" class="form-control" value="<?= htmlspecialchars($systemName) ?>" placeholder="Enter system name">
                    </div>
                    <div class="form-group">
                        <label class="form-label">
                            <i class="fa-solid fa-envelope"></i>Email Address
                        </label>
                        <input type="email" name="email" class="form-control" value="<?= htmlspecialchars($email) ?>" placeholder="Enter contact email">
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label">
                        <i class="fa-solid fa-phone"></i>Contact Number
                    </label>
                    <input type="text" name="contact" class="form-control" value="<?= htmlspecialchars($contact) ?>" placeholder="Enter contact number">
                </div>

                <div class="form-group">
                    <label class="form-label">
                        <i class="fa-solid fa-circle-info"></i>About System
                    </label>
                    <textarea name="aboutContent" class="form-control" rows="5" placeholder="Describe your system..."><?= htmlspecialchars($aboutContent) ?></textarea>
                </div>
            </div>

            <!-- Brand Assets Section -->
            <div class="settings-section">
                <div class="section-header">
                    <i class="fa-solid fa-palette section-icon"></i>
                    <h2 class="section-title">Brand Assets</h2>
                </div>

                <div class="form-row">
                    <!-- Logo Upload -->
                    <div class="form-group">
                        <label class="form-label">
                            <i class="fa-solid fa-image"></i>System Logo
                        </label>
                        <div class="file-upload-container" id="logoUploadContainer">
                            <input type="file" name="logoUpload" accept="image/*" id="logoUpload" class="file-input">
                            <p class="file-hint">Recommended: 200x200px PNG • Max 5MB</p>
                            <small class="text-muted">Drag & drop or click to upload</small>
                        </div>
                        <div class="preview-container">
                            <div id="logoPreviewContainer">
                                <?php if (!empty($logoImg)): ?>
                                    <div class="preview-image">
                                        <img src="<?= htmlspecialchars($logoImg) ?>" alt="Logo Preview" id="logoPreview">
                                    </div>
                                <?php else: ?>
                                    <div class="no-image" id="noLogoImage">
                                        <i class="fa-solid fa-image fa-2x mb-3"></i>
                                        <p>No logo uploaded</p>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>

                    <!-- Cover Image Upload -->
                    <div class="form-group">
                        <label class="form-label">
                            <i class="fa-solid fa-vector-square"></i>Cover Image
                        </label>
                        <div class="file-upload-container" id="coverUploadContainer">
                            <input type="file" name="coverUpload" accept="image/*" id="coverUpload" class="file-input">
                            <p class="file-hint">Recommended: 1200x400px JPG • Max 5MB</p>
                            <small class="text-muted">Drag & drop or click to upload</small>
                        </div>
                        <div class="preview-container">
                            <div id="coverPreviewContainer">
                                <?php if (!empty($coverImg)): ?>
                                    <div class="preview-image">
                                        <img src="<?= htmlspecialchars($coverImg) ?>" alt="Cover Preview" id="coverPreview">
                                    </div>
                                <?php else: ?>
                                    <div class="no-image" id="noCoverImage">
                                        <i class="fa-solid fa-image-landscape fa-2x mb-3"></i>
                                        <p>No cover image uploaded</p>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Work With Us Section -->
            <div class="settings-section">
                <div class="section-header">
                    <i class="fa-solid fa-briefcase section-icon"></i>
                    <h2 class="section-title">Work With Us Features</h2>
                </div>

                <div class="features-grid">
                    <?php
                    $featuresArr = json_decode($workWithUs, true) ?? [];
                    for($i=0;$i<3;$i++):
                        $feature = $featuresArr[$i] ?? ['title'=>'','description'=>'','icon'=>'fa-star'];
                    ?>
                    <div class="feature-card fade-in">
                        <div class="feature-number"><?= $i+1 ?></div>
                        <div class="feature-header">
                            <div class="icon-preview">
                                <i class="fa-solid <?= htmlspecialchars($feature['icon']) ?>" id="iconPreview<?= $i ?>"></i>
                            </div>
                            <div class="feature-inputs" style="flex:1;">
                                <input type="text" name="feature_title[]" value="<?= htmlspecialchars($feature['title']) ?>" 
                                       placeholder="Feature Title" class="form-control" oninput="updateIconPreview(<?= $i ?>)">
                                <input type="text" name="feature_icon[]" value="<?= htmlspecialchars($feature['icon']) ?>" 
                                       placeholder="fa-icon-name" class="form-control icon-input" oninput="updateIconPreview(<?= $i ?>)">
                                <textarea name="feature_description[]" rows="3" placeholder="Feature description..." 
                                          class="form-control"><?= htmlspecialchars($feature['description']) ?></textarea>
                            </div>
                        </div>
                    </div>
                    <?php endfor; ?>
                </div>
            </div>

            <!-- Form Actions -->
            <div class="form-actions">
                <button type="reset" class="btn btn-secondary">
                    <i class="fa-solid fa-rotate-left"></i>Reset Changes
                </button>
                <button type="submit" class="btn btn-primary">
                    <i class="fa-solid fa-floppy-disk"></i>Save Settings
                </button>
            </div>
        </form>
    </div>
</div>

<script>
// Image preview functionality
function setupImagePreview(inputId, previewId, noImageId, containerId) {
    const input = document.getElementById(inputId);
    const container = document.getElementById(containerId);
    
    if (input && container) {
        // Click to upload
        container.addEventListener('click', () => input.click());
        
        // Drag and drop
        container.addEventListener('dragover', (e) => {
            e.preventDefault();
            container.classList.add('dragover');
        });
        
        container.addEventListener('dragleave', () => {
            container.classList.remove('dragover');
        });
        
        container.addEventListener('drop', (e) => {
            e.preventDefault();
            container.classList.remove('dragover');
            if (e.dataTransfer.files.length) {
                input.files = e.dataTransfer.files;
                handleFileSelect(e.dataTransfer.files[0], previewId, noImageId);
            }
        });
        
        // File input change
        input.addEventListener('change', (e) => {
            if (e.target.files.length) {
                handleFileSelect(e.target.files[0], previewId, noImageId);
            }
        });
    }
}

function handleFileSelect(file, previewId, noImageId) {
    if (file && file.type.startsWith('image/')) {
        const reader = new FileReader();
        reader.onload = (e) => {
            const preview = document.getElementById(previewId);
            const noImage = document.getElementById(noImageId);
            const container = document.getElementById(previewId + 'Container');
            
            if (!preview) {
                // Create preview image if it doesn't exist
                const img = document.createElement('img');
                img.id = previewId;
                img.src = e.target.result;
                img.alt = 'Preview';
                img.className = 'preview-image';
                
                if (noImage) noImage.style.display = 'none';
                container.innerHTML = '';
                container.appendChild(img);
            } else {
                preview.src = e.target.result;
                if (noImage) noImage.style.display = 'none';
            }
        };
        reader.readAsDataURL(file);
    }
}

// Icon preview functionality
function updateIconPreview(index) {
    const iconInput = document.querySelectorAll('.icon-input')[index];
    const iconPreview = document.getElementById('iconPreview' + index);
    if (iconInput && iconPreview) {
        iconPreview.className = 'fa-solid ' + iconInput.value.trim();
    }
}

// Initialize image previews
document.addEventListener('DOMContentLoaded', function() {
    setupImagePreview('logoUpload', 'logoPreview', 'noLogoImage', 'logoUploadContainer');
    setupImagePreview('coverUpload', 'coverPreview', 'noCoverImage', 'coverUploadContainer');
    
    // Initialize icon previews
    document.querySelectorAll('.icon-input').forEach((input, index) => {
        updateIconPreview(index);
    });
    
    // Add hover effects to feature cards
    const featureCards = document.querySelectorAll('.feature-card');
    featureCards.forEach(card => {
        card.addEventListener('mouseenter', function() {
            this.style.transform = 'translateY(-5px)';
        });
        
        card.addEventListener('mouseleave', function() {
            this.style.transform = 'translateY(0)';
        });
    });
});
</script>
</body>
</html>