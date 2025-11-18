<?php
session_start();
require 'admin/db.connect.php';

// Fetch admin name
$adminnameQuery = $conn->query("SELECT fullname FROM user WHERE role = 'Admin' LIMIT 1");
$adminname = ($adminnameQuery && $row = $adminnameQuery->fetch_assoc()) ? $row['fullname'] : 'Admin';

// --- HANDLE CRUD ACTIONS WITH MESSAGES ---

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
    header("Location: Admin_Departments.php");
    exit;
}

// EDIT Department
$editDeptID = null;
$editDeptName = "";

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
    header("Location:  Admin_Departments.php");
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
    header("Location: Admin_Departments.php");
    exit;
}

// EDIT Position
$editPositionID = null;
$editPositionTitle = "";
$editPositionDeptID = "";

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
    header("Location:  Admin_Departments.php");
    exit;
}

// FETCH data
$departments = $conn->query("SELECT * FROM department ORDER BY deptID ASC");
$positions = $conn->query("SELECT p.positionID, p.position_title, d.deptName
                           FROM position p
                           JOIN department d ON p.departmentID = d.deptID ORDER BY p.positionID ASC");

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
.main-content { padding:40px 30px; margin-left:220px; flex:1; }
.sidebar { width:220px; background:#1E3A8A; color:#fff; height:100vh; position:fixed; padding-top:20px; }
.sidebar ul.nav li a { display:block; color:#fff; padding:10px 20px; text-decoration:none; }
.sidebar ul.nav li a:hover, .sidebar ul.nav li a.active { background:#111E5A; }
.main-content-header h1 { padding:25px 0; margin-bottom:40px; color:#1E3A8A; }

.ui-tabs .ui-tabs-nav {
   display:flex; gap:40px; flex-wrap:wrap; margin-left:0;;
}
#tabs { display:flex; gap:40px; flex-wrap:wrap; margin-left:-00px; margin-top: 180px;}
.ui-tabs .ui-tabs-panel {
    padding: 20px;
    background: white;
    border-radius: 10px;
    box-shadow: 0 2px 6px rgba(0,0,0,0.1);
}
</style>
</head>
<body>
<div class="sidebar">
    <div class="sidebar-logo"><img src="Images/hospitallogo.png" alt="Logo"></div>
    <div class="sidebar-name"><p><?php echo "Welcome, $adminname"; ?></p></div>
    <ul class="nav flex-column">
        <li><a href="Admin_Dashboard.php"><i class="fa-solid fa-table-columns"></i> Dashboard</a></li>
        <li><a href="Admin_UserManagement.php"><i class="fa-solid fa-users"></i> User Management</a></li>
        <li class="active"><a href="Admin_Departments.php"><i class="fa-building-columns"></i> Departments</a></li>
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
            <a href="Admin_Departments.php" class="btn btn-secondary">Cancel</a>
        <?php endif; ?>
    </div>
</form>


            <table class="table table-striped table-bordered">
                <thead class="table-primary">
                    <tr>
                        <th>ID</th>
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
                            <a href="?deleteDept=<?php echo $d['deptID']; ?>" class="btn btn-sm btn-danger" onclick="return confirm('Delete this department?')"><i class="fa fa-trash"></i></a>
                        </td>
                    </tr>
                <?php endwhile; ?>
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
            $departments->data_seek(0);
            while($d = $departments->fetch_assoc()){
                $selected = ($editPositionDeptID == $d['deptID']) ? "selected" : "";
                echo "<option value='{$d['deptID']}' $selected>{$d['deptName']}</option>";
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
            <a href="Admin_Departments.php" class="btn btn-secondary">Cancel</a>
        <?php endif; ?>
    </div>
</form>


            <table class="table table-striped table-bordered">
                <thead class="table-primary">
                    <tr>
                        <th>ID</th>
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
                            <a href="?deletePosition=<?php echo $p['positionID']; ?>" class="btn btn-sm btn-danger" onclick="return confirm('Delete this position?')"><i class="fa fa-trash"></i></a>
                        </td>
                    </tr>
                <?php endwhile; ?>
                </tbody>
            </table>
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
</script>
</body>
</html>
