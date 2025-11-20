<?php
session_start();
require 'admin/db.connect.php';

// Fetch admin name
$adminnameQuery = $conn->query("SELECT fullname FROM user WHERE role = 'Admin' LIMIT 1");
$adminname = ($adminnameQuery && $row = $adminnameQuery->fetch_assoc()) ? $row['fullname'] : 'Admin';

// ---------------------------
// --- INITIALIZE VARIABLES ---
// ---------------------------

$editDeptID = null;
$editDeptName = "";

$editPositionID = null;
$editPositionTitle = "";
$editPositionDeptID = "";

// ---------------------------
// --- HANDLE GET PARAMETERS ---
// ---------------------------

// EDIT Department
if(isset($_GET['editDeptID'])){
    $editDeptID = $_GET['editDeptID'];
    $stmt = $conn->prepare("SELECT deptName FROM department WHERE deptID=?");
    $stmt->bind_param("i", $editDeptID);
    $stmt->execute();
    $result = $stmt->get_result();
    if($row = $result->fetch_assoc()){
        $editDeptName = $row['deptName'];
    }
}

// EDIT Position
if(isset($_GET['editPositionID'])){
    $editPositionID = $_GET['editPositionID'];
    $stmt = $conn->prepare("SELECT departmentID, position_title FROM position WHERE positionID=?");
    $stmt->bind_param("i", $editPositionID);
    $stmt->execute();
    $result = $stmt->get_result();
    if($row = $result->fetch_assoc()){
        $editPositionTitle = $row['position_title'];
        $editPositionDeptID = $row['departmentID'];
    }
}

// ---------------------------
// --- HANDLE POST REQUESTS ---
// ---------------------------

// ADD Department
if(isset($_POST['addDept'])){
    $deptName = $_POST['deptName'];
    $stmt = $conn->prepare("INSERT INTO department (deptName) VALUES (?)");
    $stmt->bind_param("s", $deptName);
    if($stmt->execute()){
        $_SESSION['success'] = "Department '$deptName' added successfully!";
    } else {
        $_SESSION['error'] = "Failed to add department: ".$stmt->error;
    }
    header("Location: Admin_Departments.php#tabs-1");
    exit;
}

// EDIT Department
if(isset($_POST['editDept'])){
    $deptID = $_POST['deptID'];
    $deptName = $_POST['deptName'];
    $stmt = $conn->prepare("UPDATE department SET deptName=? WHERE deptID=?");
    $stmt->bind_param("si", $deptName, $deptID);
    if($stmt->execute()){
        $_SESSION['success'] = "Department updated successfully!";
    } else {
        $_SESSION['error'] = "Failed to update department: ".$stmt->error;
    }
    header("Location: Admin_Departments.php#tabs-1");
    exit;
}

// DELETE Department
if(isset($_GET['deleteDept'])){
    $deptID = $_GET['deleteDept'];
    $stmt = $conn->prepare("DELETE FROM department WHERE deptID=?");
    $stmt->bind_param("i", $deptID);
    if($stmt->execute()){
        $_SESSION['success'] = "Department deleted successfully!";
    } else {
        $_SESSION['error'] = "Failed to delete department: ".$stmt->error;
    }
    header("Location: Admin_Departments.php#tabs-1");
    exit;
}

// ADD Position
if(isset($_POST['addPosition'])){
    $departmentID = $_POST['departmentID'];
    $position_title = $_POST['position_title'];
    $stmt = $conn->prepare("INSERT INTO position (departmentID, position_title) VALUES (?, ?)");
    $stmt->bind_param("is", $departmentID, $position_title);
    if($stmt->execute()){
        $_SESSION['success'] = "Position '$position_title' added successfully!";
    } else {
        $_SESSION['error'] = "Failed to add position: ".$stmt->error;
    }
    header("Location: Admin_Departments.php#tabs-2");
    exit;
}

// EDIT Position
if(isset($_POST['editPosition'])){
    $positionID = $_POST['positionID'];
    $positionTitle = $_POST['position_title'];
    $departmentID = $_POST['departmentID'];
    $stmt = $conn->prepare("UPDATE position SET position_title=?, departmentID=? WHERE positionID=?");
    $stmt->bind_param("sii", $positionTitle, $departmentID, $positionID);
    if($stmt->execute()){
        $_SESSION['success'] = "Position updated successfully!";
    } else {
        $_SESSION['error'] = "Failed to update position: ".$stmt->error;
    }
    header("Location: Admin_Departments.php#tabs-2");
    exit;
}

// DELETE Position
if(isset($_GET['deletePosition'])){
    $positionID = $_GET['deletePosition'];
    $stmt = $conn->prepare("DELETE FROM position WHERE positionID=?");
    $stmt->bind_param("i", $positionID);
    if($stmt->execute()){
        $_SESSION['success'] = "Position deleted successfully!";
    } else {
        $_SESSION['error'] = "Failed to delete position: ".$stmt->error;
    }
    header("Location: Admin_Departments.php#tabs-2");
    exit;
}

// ---------------------------
// --- FETCH DATA FOR TABLES ---
// ---------------------------

// Pagination settings
$records_per_page = 10;

// Departments pagination
$dept_page = isset($_GET['dept_page']) ? max(1, intval($_GET['dept_page'])) : 1;
$dept_offset = ($dept_page - 1) * $records_per_page;
$departments_total = $conn->query("SELECT COUNT(*) AS total FROM department")->fetch_assoc()['total'];
$departments = $conn->query("SELECT * FROM department ORDER BY deptID ASC LIMIT $records_per_page OFFSET $dept_offset");
$departments_total_pages = ceil($departments_total / $records_per_page);

// Positions pagination
$pos_page = isset($_GET['pos_page']) ? max(1, intval($_GET['pos_page'])) : 1;
$pos_offset = ($pos_page - 1) * $records_per_page;
$positions_total = $conn->query("SELECT COUNT(*) AS total FROM position")->fetch_assoc()['total'];
$positions = $conn->query("SELECT p.positionID, p.position_title, d.deptName
                           FROM position p
                           JOIN department d ON p.departmentID = d.deptID
                           ORDER BY p.positionID ASC
                           LIMIT $records_per_page OFFSET $pos_offset");
$positions_total_pages = ceil($positions_total / $records_per_page);

// Fetch all departments for dropdown
$allDepartments = $conn->query("SELECT * FROM department ORDER BY deptName ASC");
?>


<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Departments & Positions Management</title>
<link rel="stylesheet" href="admin-sidebar.css">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<link rel="stylesheet" href="https://code.jquery.com/ui/1.13.2/themes/base/jquery-ui.css">
<style>
 body { font-family: 'Poppins', sans-serif; margin:0; display:flex; background-color:#f1f5fc; color:#111827; }
.main-content {
    padding: 40px 30px;
    margin-left: 220px; /* sidebar width */
    flex: 1;
    display: flex;
    flex-direction: column; /* stack header and tabs */
}
.main-content-header h1 { padding:25px 0; margin-bottom:40px; color:#1E3A8A; }

.main-content-header {
    width: 100%;
    margin-bottom: 25px;
}

/* Tab panels min height to prevent jump */
.ui-tabs .ui-tabs-panel {
    padding: 25px;
    background: #fff;
    border-radius: 0 0 10px 10px;
    box-shadow: 0 2px 12px rgba(0,0,0,0.08);
    min-height: 500px; /* consistent height */
}
#tabs {
    display: block;   /* block ensures it’s below header */
    margin-top: 15px; /* spacing under header */
     margin-left: 0;   /* remove negative margin */
    width: 100%;
}


/* Tabs container */
.ui-tabs {
    width: 100%;
    max-width: 1000px;
    margin: 20px auto 40px auto;
    font-family: 'Poppins', sans-serif;
}

/* Tabs navigation */
.ui-tabs .ui-tabs-nav {
    display: flex;
    gap: 10px;
    padding: 0;
    margin-bottom: 20px;
    border-bottom: 2px solid #e2e8f0;
}

/* Tab items */
.ui-tabs .ui-tabs-nav li {
    list-style: none;
}

.ui-tabs .ui-tabs-nav li a {
    display: block;
    padding: 10px 25px;
    background-color: #f8fafc;
    color: #1e3a8a;
    font-weight: 500;
    border-radius: 10px 10px 0 0;
    text-decoration: none;
    box-shadow: 0 2px 6px rgba(0,0,0,0.05);
}

/* Active tab */
.ui-tabs .ui-tabs-nav li.ui-tabs-active a {
    background-color: #1e3a8a;
    color: #fff;
    box-shadow: 0 4px 12px rgba(0,0,0,0.15);
}

/* Tab panels */
.ui-tabs .ui-tabs-panel {
    padding: 25px;
    background: #fff;
    border-radius: 0 0 10px 10px;
    box-shadow: 0 2px 12px rgba(0,0,0,0.08);
    margin-top: -5px;
}

/* Forms inside tabs */
.ui-tabs form .form-control {
    border-radius: 8px;
    border: 1px solid #cbd5e1;
    padding: 10px;
   
}

.ui-tabs form .form-control:focus {
    border-color: #1e3a8a;
    box-shadow: 0 0 5px rgba(30,58,138,0.2);
}

/* Buttons */
.ui-tabs .btn {
    border-radius: 8px;
    padding: 8px 18px;
    font-weight: 500;
    
}

.ui-tabs .btn-primary {
    background: #1e3a8a;
    border: none;
}

.ui-tabs .btn-primary:hover {
    background: #2747b0;
}

.ui-tabs .btn-secondary {
    background: #6b7280;
    border: none;
}

.ui-tabs .btn-secondary:hover {
    background: #4b5563;
}

/* Table styling */
.table {
    border-radius: 10px;
    overflow: hidden;
}

.table thead {
    background: #1e3a8a;
    color: #fff;
}

.table-striped tbody tr:nth-of-type(odd) {
    background-color: #f8fafc;
}

/* Pagination styling */
.pagination .page-link {
    border-radius: 50%;
    margin: 0 3px;
    color: #1e3a8a;
    font-weight: 500;
}

.pagination .page-item.active .page-link {
    background-color: #1e3a8a;
    border-color: #1e3a8a;
    color: #fff;
}


</style>
</head>
<body>
<div class="sidebar">
    <div class="sidebar-logo"><img src="Images/hospitallogo.png" alt="Logo"></div>
    <div class="sidebar-name"><p><?php echo "Welcome Admin, $adminname"; ?></p></div>
    <ul class="nav flex-column">
        <li><a href="Admin_Dashboard.php"><i class="fa-solid fa-table-columns"></i> Dashboard</a></li>
        <li><a href="Admin_UserManagement.php"><i class="fa-solid fa-users"></i> User Management</a></li>
        <li class="active"><a href="Admin_Departments.php"><i class="fa-solid fa-building-columns"></i> Departments</a></li>
        <li><a href="Admin_Vacancies.php"><i class="fa-solid fa-briefcase"></i> Vacancies</a></li>
        <li><a href="Admin-Applicants.php"><i class="fa-solid fa-user-check"></i> Applicants</a></li>
        <li><a href="Admin_Reports.php"><i class="fa-solid fa-chart-simple"></i> Reports</a></li>
        <li><a href="Admin-Settings.php"><i class="fa-solid fa-gear"></i> Settings</a></li>
        <li><a href="Login.php"><i class="fa-solid fa-right-from-bracket"></i> Logout</a></li>
    </ul>
</div>

<main class="main-content">
     <div class="main-content-header">
            <h1>Department and Position Management</h1>
        </div>

    <!-- SUCCESS / ERROR ALERTS -->
    <?php if(isset($_SESSION['success'])): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <?php echo $_SESSION['success']; unset($_SESSION['success']); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>
    <?php if(isset($_SESSION['error'])): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <?php echo $_SESSION['error']; unset($_SESSION['error']); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <div id="tabs">
        <ul>
            <li><a href="#tabs-1">Departments</a></li>
            <li><a href="#tabs-2">Positions</a></li>
        </ul>

        <!-- Departments Tab -->
        <div id="tabs-1">
            <form class="row g-2 mb-3" method="POST">
    <div class="col-md-6">
        <input type="text" name="deptName" class="form-control" placeholder="Department Name" 
               value="<?php echo htmlspecialchars($editDeptName); ?>" required>
        <?php if($editDeptID): ?>
            <input type="hidden" name="deptID" value="<?php echo $editDeptID; ?>">
        <?php endif; ?>
    </div>
    <div class="col-auto">
        <button type="submit" name="<?php echo $editDeptID ? 'editDept' : 'addDept'; ?>" class="btn btn-primary">
            <?php echo $editDeptID ? 'Update Department' : 'Add Department'; ?>
        </button>
        <?php if($editDeptID): ?>
            <a href="Admin_Departments.php#tabs-1" class="btn btn-secondary">Cancel</a>
        <?php endif; ?>

    </div>
</form>


            <table class="table table-striped table-bordered">
                <thead class="table-primary">
                    <tr>
                        <th>Department Name</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                <?php
                $departments->data_seek(0);
                while($d = $departments->fetch_assoc()):
                ?>
                    <tr>
                        <td><?php echo $d['deptID']; ?></td>
                        <td><?php echo $d['deptName']; ?></td>
                        <td>
                            <a href="?editDeptID=<?php echo $d['deptID']; ?>" class="btn btn-sm btn-warning"><i class="fa fa-edit"></i></a>
                           <a href="?deleteDept=<?php echo $d['deptID']; ?>" 
   class="btn btn-sm btn-danger deleteBtn" 
   data-bs-toggle="modal" 
   data-bs-target="#deleteModal"
   data-href="?deleteDept=<?php echo $d['deptID']; ?>">
   <i class="fa fa-trash"></i>
</a>

                        </td>
                    </tr>
                <?php endwhile; ?>

                <nav>
  <ul class="pagination justify-content-center">
    <?php for($i=1; $i<=$departments_total_pages; $i++): ?>
      <li class="page-item <?php if($i==$dept_page) echo 'active'; ?>">
        <a class="page-link" href="?dept_page=<?php echo $i; ?>#tabs-1"><?php echo $i; ?></a>
      </li>
    <?php endfor; ?>
  </ul>
</nav>
                </tbody>
            </table>
        </div>

        <!-- Positions Tab -->
        <div id="tabs-2">
           <form class="row g-2 mb-3" method="POST">
    <div class="col-md-4">
      <select name="departmentID" class="form-control" required>
    <option value="">Select Department</option>
    <?php
    if($allDepartments){
        while($d = $allDepartments->fetch_assoc()){
            $selected = ($editPositionDeptID == $d['deptID']) ? "selected" : "";
            echo "<option value='{$d['deptID']}' $selected>{$d['deptName']}</option>";
        }
    }
    ?>
</select>

    </div>
    <div class="col-md-4">
        <input type="text" name="position_title" class="form-control" placeholder="Position Title"
               value="<?php echo htmlspecialchars($editPositionTitle); ?>" required>
        <?php if($editPositionID): ?>
            <input type="hidden" name="positionID" value="<?php echo $editPositionID; ?>">
        <?php endif; ?>
    </div>
    <div class="col-auto">
        <button type="submit" name="<?php echo $editPositionID ? 'editPosition' : 'addPosition'; ?>" class="btn btn-primary">
            <?php echo $editPositionID ? 'Update Position' : 'Add Position'; ?>
        </button>
        <?php if($editPositionID): ?>
    <a href="Admin_Departments.php#tabs-2" class="btn btn-secondary">Cancel</a>
<?php endif; ?>

        
    </div>
</form>


            <table class="table table-striped table-bordered">
                <thead class="table-primary">
                    <tr>
                        <th>Position Title</th>
                        <th>Department</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                <?php
                $positions->data_seek(0);
                while($p = $positions->fetch_assoc()):
                ?>
                    <tr>
                        <td><?php echo $p['positionID']; ?></td>
                        <td><?php echo $p['position_title']; ?></td>
                        <td><?php echo $p['deptName']; ?></td>
                        <td>
                            <a href="?editPositionID=<?php echo $p['positionID']; ?>" class="btn btn-sm btn-warning"><i class="fa fa-edit"></i></a>
                            <a href="?deletePosition=<?php echo $p['positionID']; ?>" 
   class="btn btn-sm btn-danger deleteBtn" 
   data-bs-toggle="modal" 
   data-bs-target="#deleteModal"
   data-href="?deletePosition=<?php echo $p['positionID']; ?>">
   <i class="fa fa-trash"></i>
</a>

                        </td>
                    </tr>
                <?php endwhile; ?>

                <nav>
  <ul class="pagination justify-content-center">
    <?php for($i=1; $i<=$positions_total_pages; $i++): ?>
      <li class="page-item <?php if($i==$pos_page) echo 'active'; ?>">
        <a class="page-link" href="?pos_page=<?php echo $i; ?>#tabs-2"><?php echo $i; ?></a>
      </li>
    <?php endfor; ?>
  </ul>
</nav>
                </tbody>
            </table>
        </div>
    </div>

<!-- Delete Confirmation Modal -->
<div class="modal fade" id="deleteModal" tabindex="-1" aria-labelledby="deleteModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered"> <!-- vertically centered -->
    <div class="modal-content border-0 shadow-sm rounded-3">
      <div class="modal-header bg-danger text-white rounded-top-3">
        <h5 class="modal-title" id="deleteModalLabel"><i class="fa fa-exclamation-triangle me-2"></i>Confirm Deletion</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body fs-6">
        Are you sure you want to delete <span id="itemName" class="fw-bold"></span>?
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary w-50 rounded-2" data-bs-dismiss="modal">Cancel</button>
        <a href="#" id="confirmDeleteBtn" class="btn btn-danger w-50 rounded-2">Delete</a>
      </div>
    </div>
  </div>
</div>


</main>

<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://code.jquery.com/ui/1.13.2/jquery-ui.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
$(function() {
    $("#tabs").tabs(); // initialize jQuery UI tabs
});

$(function() {
    var tabs = $("#tabs").tabs();

    // Check URL for editDeptID or editPositionID
    const urlParams = new URLSearchParams(window.location.search);
    if(urlParams.has('editPositionID')){
        tabs.tabs("option", "active", 1); // 0 = Departments, 1 = Positions
    } else if(urlParams.has('editDeptID')){
        tabs.tabs("option", "active", 0);
    }
});


$(document).ready(function() {
    var deleteModal = document.getElementById('deleteModal');
    deleteModal.addEventListener('show.bs.modal', function (event) {
        var button = event.relatedTarget; // Button that triggered the modal
        var href = button.getAttribute('data-href'); // Get the href to delete
        var confirmBtn = document.getElementById('confirmDeleteBtn');
        confirmBtn.setAttribute('href', href); // Set it dynamically
    });
});

</script>
</body>
</html>
