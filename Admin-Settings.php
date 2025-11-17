<?php
session_start();
require 'admin/db.connect.php';

$systemName = "";
$email = "";
$contact = "";
$aboutContent = "";
$coverImg = "";
$msg = "";

// GET ADMIN NAME
$adminQuery = $conn->query("SELECT fullname FROM user WHERE role = 'Admin' LIMIT 1");
if ($adminQuery && $row = $adminQuery->fetch_assoc()) {
    $adminname = $row['fullname'];
}

// GET EXISTING SETTINGS
$settingsQuery = $conn->query("SELECT * FROM system_settings LIMIT 1");
$settings = $settingsQuery ? $settingsQuery->fetch_assoc() : null;

if ($settings) {
    $systemName = $settings['system_name'];
    $email = $settings['email'];
    $contact = $settings['contact'];
    $aboutContent = $settings['about'];
    $coverImg = $settings['cover_image'];
}

// HANDLE SUBMISSION
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $systemName = $conn->real_escape_string($_POST['systemName']);
    $email = $conn->real_escape_string($_POST['email']);
    $contact = $conn->real_escape_string($_POST['contact']);
    $aboutContent = $conn->real_escape_string($_POST['aboutContent']);

    $upload_path = "";

    // HANDLE IMAGE UPLOAD
    if (isset($_FILES['imageUpload']) && $_FILES['imageUpload']['error'] == 0) {

        $target_dir = "uploads/";

        if (!is_dir($target_dir)) {
            mkdir($target_dir, 0777, true);
        }

        $filename = time() . "_" . basename($_FILES["imageUpload"]["name"]);
        $target_file = $target_dir . $filename;
        $imageFileType = strtolower(pathinfo($target_file, PATHINFO_EXTENSION));

        $check = getimagesize($_FILES["imageUpload"]["tmp_name"]);
        if ($check !== false) {
            if ($_FILES["imageUpload"]["size"] <= 5000000) {
                if (in_array($imageFileType, ['jpg', 'jpeg', 'png', 'gif'])) {
                    if (move_uploaded_file($_FILES["imageUpload"]["tmp_name"], $target_file)) {
                        $upload_path = $target_file;
                    } else {
                        $msg = "Error uploading file.";
                    }
                } else {
                    $msg = "Only JPG, PNG, and GIF images allowed.";
                }
            } else {
                $msg = "File too large. Max 5MB.";
            }
        } else {
            $msg = "Uploaded file is not an image.";
        }
    }

    if (empty($msg)) {
        // UPDATE IF EXISTS
        if ($settings) {

            $sql = "
                UPDATE system_settings 
                SET system_name = '$systemName',
                    email = '$email',
                    contact = '$contact',
                    about = '$aboutContent'
            ";

            if (!empty($upload_path)) {
                $sql .= ", cover_image = '$upload_path'";
            }

            $sql .= " WHERE system_id = " . $settings['system_id'];

        } else {
            // INSERT IF TABLE EMPTY
            $imgVal = !empty($upload_path) ? "'$upload_path'" : "NULL";

            $sql = "
                INSERT INTO system_settings (system_name, email, contact, about, cover_image)
                VALUES ('$systemName', '$email', '$contact', '$aboutContent', $imgVal)
            ";
        }

        if ($conn->query($sql)) {
            $msg = "System settings updated successfully.";
            // Update the coverImg variable to reflect the new image after successful update
            if (!empty($upload_path)) {
                $coverImg = $upload_path;
            }
        } else {
            $msg = "Database error: " . $conn->error;
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
            margin-left: 450px;
            /* keeps space for sidebar */
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
            width: 200%;
            margin-left: 30px;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
            margin-bottom: 350px;
        }

        form {
            display: flex;
            flex-direction: column;
            gap: 15px;
        }

        .form-row {
            display: flex;
            gap: 20px;
        }

        .form-group {
            flex: 1;
            display: flex;
            flex-direction: column;
        }

        .form-group label {
            font-weight: 600;
            margin-bottom: 5px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .form-group input[type="text"],
        .form-group input[type="email"],
        .form-group input[type="file"],
        .form-group textarea {
            width: 100%;
            padding: 10px;
            border: none;
            border-radius: 5px;
            background-color: #fff;
            font-family: 'Poppins', sans-serif;
            font-size: 14px;
        }

        .form-group textarea {
            resize: none;
        }

        .form-actions {
            display: flex;
            justify-content: flex-end;
            align-items: center;
            margin-top: 10px;
        }

        .form-actions button {
            background-color: #1e3a8a;
            color: white;
            border: none;
            border-radius: 5px;
            padding: 8px 25px;
            font-size: 14px;
            cursor: pointer;
        }

        .form-actions button:hover {
            background-color: #1d4ed8;
        }

        /* Icon color */
        .form-group label i {
            color: #1e3a8a;
        }

        .file-hint {
            font-size: 12px;
            color: #6b7280;
            margin-top: 5px;
        }

        .preview-image img {
            max-width: 300px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }

        .no-image {
            color: #6b7280;
            font-style: italic;
            padding: 20px;
            border: 1px dashed #d1d5db;
            border-radius: 5px;
            text-align: center;
        }
    </style>
</head>

<body class="admin-dashboard">
    <!-- SIDEBAR -->
    <div class="sidebar">
        <div class="sidebar-logo">
            <img src="Images/hospitallogo.png" alt="Hospital Logo">
        </div>

        <div class="sidebar-name">
            <p><?php echo "Welcome, $adminname"; ?></p>
        </div>

        <ul class="nav flex-column">
            <li><a href="Admin_Dashboard.php"><i class="fa-solid fa-table-columns"></i>Dashboard</a></li>

            <li><a href="Admin_Vacancies.php"><i class="fa-solid fa-briefcase"></i>Vacancies</a></li>
            <li><a href="Admin-request.php"><i class="fa-solid fa-code-pull-request"></i>Requests</a></li>
            <li><a href="Admin_Reports.php"><i class="fa-solid fa-chart-simple"></i>Reports</a></li>
            <li class="active"><a href="Admin-Settings.php"><i class="fa-solid fa-gear"></i>Settings</a></li>
            <li><a href="Login.php"><i class="fa-solid fa-right-from-bracket"></i>Logout</a></li>
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

                <div class="form-group">
                    <label>Logo / Cover Image</label>
                    <input type="file" name="imageUpload" accept="image/*" id="imageUpload">
                    <p class="file-hint">Recommended: 1200x400px. Max 5MB</p>

                    <div class="preview-image mt-2" id="previewContainer">
                        <?php if (!empty($coverImg)): ?>
                            <img src="<?php echo htmlspecialchars($coverImg); ?>" alt="Cover Image" id="previewImg">
                        <?php else: ?>
                            <div class="no-image" id="noImage">No image uploaded</div>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="form-actions">
                    <button type="submit"><i class="fa-solid fa-rotate-right"></i> Apply</button>
                </div>

            </form>

        </div>
    </div>

    <script>
        document.getElementById('imageUpload').addEventListener('change', function (event) {
            const file = event.target.files[0];
            const previewContainer = document.getElementById('previewContainer');
            const previewImg = document.getElementById('previewImg');
            const noImage = document.getElementById('noImage');

            if (file) {
                const reader = new FileReader();
                reader.onload = function (e) {
                    if (previewImg) {
                        previewImg.src = e.target.result;
                        previewImg.style.display = 'block';
                    } else {
                        const img = document.createElement('img');
                        img.src = e.target.result;
                        img.alt = 'Preview Image';
                        img.style.maxWidth = '300px';
                        img.style.borderRadius = '8px';
                        img.style.boxShadow = '0 2px 4px rgba(0, 0, 0, 0.1)';
                        img.id = 'previewImg';
                        previewContainer.innerHTML = '';
                        previewContainer.appendChild(img);
                    }
                    if (noImage) noImage.style.display = 'none';
                };
                reader.readAsDataURL(file);
            } else {
                // Revert to original state
                if (previewImg) {
                    previewImg.style.display = 'none';
                }
                if (noImage) {
                    noImage.style.display = 'block';
                } else {
                    const div = document.createElement('div');
                    div.className = 'no-image';
                    div.id = 'noImage';
                    div.textContent = 'No image uploaded';
                    previewContainer.innerHTML = '';
                    previewContainer.appendChild(div);
                }
            }
        });
    </script>
</body>

</html>