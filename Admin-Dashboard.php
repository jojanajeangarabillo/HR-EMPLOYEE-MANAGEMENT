<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document</title>
    <link rel="stylesheet" href="stylesheet.css">
    <!--For icons-->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/7.0.1/css/all.min.css" integrity="sha512-2SwdPD6INVrV/lHTZbO2nodKhrnDdJK9/kg2XD1r9uGqPo1cUbujc+IYdlYdEErWNu69gVcYgdxlmVmzTWnetw==" crossorigin="anonymous" referrerpolicy="no-referrer" />
</head>
</head>
<body class="admin-dashboard">
    <header class="admin-header">
        <h1 class="admin-header-text">Human Resource</h1>
    </header>

    <aside class="admin-sidebar">
        <div class="sidebar-logo">
            <img src="Images/hospitallogo.png" alt="happy" >
        </div>
        <nav class="sidebar-nav">
            <!--Primary top nav-->
            <ul class="primary-top-nav">
                <li class="nav-item">
                    <a href="#" class="nav-link">
                        <i class="fa-solid fa-grip"></i>
                        <span class="nav-label">Dashboard</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="Admin_Employee.php" class="nav-link">
                        <i class="fa-solid fa-user-group"></i>
                        <span class="nav-label">Employees</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="Admin_Applicants.php" class="nav-link">
                        <i class="fa-solid fa-user-group"></i>
                        <span class="nav-label">Applicants</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="Admin-request.php" class="nav-link">
                        <i class="fa-solid fa-code-pull-request"></i>
                        <span class="nav-label">Requests</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="Admin-JobPosting.php" class="nav-link">
                        <i class="fa-solid fa-folder"></i>
                        <span class="nav-label">Job Post</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="#" class="nav-link">
                        <i class="fa-solid fa-chart-simple"></i>
                        <span class="nav-label">Reports</span>
                    </a>
                </li>
                
                
            </ul>
            <!--Secondary bottom nav-->
            <ul class="secondary-buttom-nav">
                <li class="nav-item">
                    <a href="Admin-Settings.php" class="nav-link">
                        <i class="fa-solid fa-gear"></i>
                        <span class="nav-label">Settings</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="Login.php" class="nav-link">
                        <i class="fa-solid fa-right-from-bracket"></i>
                        <span class="nav-label">Logout</span>
                    </a>
                </li>
            </ul>

            
        </nav>

    </aside>
    <main class="admin-main">
        <div class="banner-card"></div>
        <div class="employee-card">
            <i class="fa-solid fa-user-group"></i>
            <label for="employees" class="employee-label">Employees</label>
            <h3 class="employee-count">0</h3>
        </div>
        <div class="applicant-card"></div>
        <div class="request-card"></div>
        <div class="hiring-card"></div>
        <div class="recent-job-post-card"></div>
        <div class="newly-hired-card"></div>

    </main>
    
</body>
</html>