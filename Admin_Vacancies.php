<?php
session_start();
require 'admin/db.connect.php';

$adminanmeQuery = $conn->query("SELECT fullname FROM user WHERE role = 'Admin'");
if ($adminanmeQuery && $row = $adminanmeQuery->fetch_assoc()) {
    $adminname = $row['fullname'];
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Vacancies</title>
    <link rel="stylesheet" href="admin-sidebar.css">
    <!--For icons-->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/7.0.1/css/all.min.css"
        integrity="sha512-2SwdPD6INVrV/lHTZbO2nodKhrnDdJK9/kg2XD1r9uGqPo1cUbujc+IYdlYdEErWNu69gVcYgdxlmVmzTWnetw=="
        crossorigin="anonymous" referrerpolicy="no-referrer" />
    <style>
        body {
            font-family: 'Poppins', 'Roboto', sans-serif;
            margin: 0;
            display: flex;
            background-color: #f1f5fc;
            color: #111827;
        }

        .sidebar-logo {
            display: flex;
            justify-content: center;
            margin-bottom: 50px;
        }

        .sidebar-logo img {
            height: 120px;
            width: 120px;
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


        .main-content {
            padding: 40px 30px;
            margin-left: 250px;
            display: flex;
            flex-direction: column;

        }

        .main-content-header h1 {
            margin: 0;
            font-size: 2rem;
            margin-bottom: 40px;
            margin-left: 50px;
            color: #1E3A8A;
        }

        .set-vacancies {
            background-color: #1E3A8A;
            display: flex;
            justify-content: center;
            gap: 40px;
            flex-wrap: wrap;
            margin-left: 50px;
            border-radius: 20px;
            padding: 30px;
            width: fit-content;
        }

        .select-options {
            display: grid;
            align-items: center;
            width: 400px;
        }

        .select-options select {
            font-size: 20px;
            padding: 10px;
            border-radius: 10px;
            border: none;
        }

        .set-vacancies button {
            border-style: solid;
            border-color: white;
            background-color: #1E3A8A;
            color: white;
            font-size: 18px;
            padding: 10px 30px;
            border-radius: 10px;
            cursor: pointer;
            transition: 0.3s;
        }

        .set-vacancies button:hover {
            border-style: solid;
            border-color: #1E3A8A;
            background-color: white;
            color: #1E3A8A;
        }

        /* ========== MODAL STYLES ========== */
        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            overflow: auto;
            background-color: rgba(0, 0, 0, 0.5);
            justify-content: center;
            align-items: center;
        }

        .modal-content {
            background-color: white;
            padding: 30px;
            border-radius: 15px;
            width: 400px;
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.3);
            text-align: center;
            animation: fadeIn 0.3s ease;
        }

        @keyframes fadeIn {
            from {
                transform: scale(0.95);
                opacity: 0;
            }

            to {
                transform: scale(1);
                opacity: 1;
            }
        }

        .modal h2 {
            margin-bottom: 20px;
            color: #1E3A8A;
        }

        .modal input {
            width: 100%;
            padding: 10px;
            font-size: 18px;
            border-radius: 10px;
            border: 1px solid #ccc;
            margin-bottom: 20px;
        }

        .modal button {
            padding: 10px 20px;
            font-size: 16px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            margin: 5px;
        }

        .confirm-btn {
            background-color: #1E3A8A;
            color: white;
        }

        .confirm-btn:hover {
            background-color: #162c63;
        }

        .cancel-btn {
            background-color: #1E3A8A;
        }

        .cancel-btn:hover {
            background-color: #162c63;
        }
    </style>
</head>

<body>
    <div class="sidebar">
        <div class="sidebar-logo">
            <img src="Images/hospitallogo.png" alt="">
        </div>
        <div class="sidebar-name">
            <p><?php echo "Welcome, $adminname"; ?></p>
        </div>

        <ul class="nav">
            <li><a href="Admin_Dashboard.php"><i class="fa-solid fa-table-columns"></i>Dashboard</a></li>
            <li><a href="Admin_Employee.php"><i class="fa-solid fa-user-group"></i>Employees</a></li>
            <li><a href="Admin-Applicants.php"><i class="fa-solid fa-user-group"></i>Applicants</a></li>
            <li><a href="Admin-Pending-Applicants.php"><i class="fa-solid fa-user-group"></i>Pending Applicants</a></li>
            <li class="active"><a href="Admin_Vacancies"><i class="fa-solid fa-briefcase"></i>Vacancies</a></li>
            <li><a href="Admin-request.php"><i class="fa-solid fa-code-pull-request"></i>Requests</a></li>
            <li><a href="#"><i class="fa-solid fa-chart-simple"></i>Reports</a></li>
            <li><a href="Admin-Settings.php"><i class="fa-solid fa-gear"></i>Settings</a></li>
            <li><a href="Login.php"><i class="fa-solid fa-right-from-bracket"></i>Logout</a></li>
        </ul>
    </div>

    <main class="main-content">
        <div class="main-content-header">
            <h1>Upload Vacancies</h1>
        </div>


        <div class="set-vacancies">
            <div class="select-options">
                <select name="department" id="department">
                    <option value="" disabled selected hidden>Department</option>
                    <option value="Test">Test</option>
                    <option value="Test2">Test2</option>
                </select>
            </div>
            <div class="select-options">
                <select name="position" id="position">
                    <option value="" disabled selected hidden>Position</option>
                    <option value="Test3">Test3</option>
                    <option value="Test4">Test4</option>
                </select>
            </div>
            <button type="button" id="openModalBtn">Set</button>
        </div>
    </main>

    <!-- ========== MODAL ========== -->
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

    <script>
        const modal = document.getElementById('vacancyModal');
        const openModalBtn = document.getElementById('openModalBtn');
        const cancelBtn = document.getElementById('cancelBtn');
        const confirmBtn = document.getElementById('confirmBtn');
        const vacancyCount = document.getElementById('vacancyCount');

        openModalBtn.onclick = function () {
            modal.style.display = 'flex';
        };

        cancelBtn.onclick = function () {
            modal.style.display = 'none';
            vacancyCount.value = '';
        };

        confirmBtn.onclick = function () {
            const department = document.getElementById('department').value;
            const position = document.getElementById('position').value;
            const count = vacancyCount.value;

            if (!count || count <= 0) {
                alert('Please enter a valid number of vacancies.');
                return;
            }

            alert(`Vacancies set:\nDepartment: ${department}\nPosition: ${position}\nNumber of Vacancies: ${count}`);
            modal.style.display = 'none';
            vacancyCount.value = '';
        };

        window.onclick = function (event) {
            if (event.target == modal) {
                modal.style.display = 'none';
            }
        };
    </script>
</body>

</html>