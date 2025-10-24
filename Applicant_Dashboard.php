<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Applicant Dashboard</title>

    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600&family=Roboto:wght@400;500&display=swap" rel="stylesheet">

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

    /* Welcome Box */
    .welcome-box {
        background-color: #1E3A8A;
        color: white;
        padding: 33px 30px;
        margin-left: 200px;
        border-radius: 15px;
        width: 1200px;
        height: 100px;
        box-shadow: 0 4px 6px rgba(0, 0, 0, 0.15);
        font-size: 20px;
        font-weight: 500;
    }

    /* Application Status */
    .status-section {
        display: flex;
        margin-left: 200px;
        flex-direction: column;
        gap: 20px;
    }

    .status-section h3 {
        margin-left: 200px;
        font-weight: 600;
        font-size: 18px;
        margin: 0;
    }

    .status-cards {
        display: flex;
        gap: 40px;
    }

    .status-card {
        
        background-color: #2563EB;
        color: #fff;
        width: 140px;
        height: 110px;
        border-radius: 18px;
        text-align: center;
        font-weight: 500;
        display: flex;
        flex-direction: column;
        justify-content: center;
        align-items: center;
        box-shadow: 0 3px 6px rgba(0, 0, 0, 0.1);
        transition: transform 0.2s ease;
    }

    .status-card:hover {
        transform: translateY(-3px);
    }

    .status-card p {
        margin: 0;
        font-size: 15px;
        font-weight: 500;
    }

    .status-card h2 {
        margin: 6px 0 0 0;
        font-size: 24px;
        font-weight: 600;
    }

    /* Notifications */
    .notifications-section {
        height: 200px;
        width: 1200px;
        margin-left: 200px;
        display: flex;
        flex-direction: column;
        gap: 20px;
    }

    .notifications-section h3 {
        margin-left: 200px;
        font-weight: 600;
        font-size: 18px;
        margin: 0;
    }

    .notification-box {
        background-color: #dbe2f0;
        border-radius: 15px;
        height: 100px;
        width: 1200px;
        box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
    }
</style>


</head>

<body>
    <!-- Sidebar -->
   <!-- Sidebar -->
<div class="sidebar">
    <div class="profile">
        <i class="fa-solid fa-user"></i>
    </div>

    <ul class="nav">
      <li class="active"><a href="Applicant_Dashboard.php"><i class="fa-solid fa-table-columns"></i>Dashboard</a></li>
      <li><a href="Applicant_Application.php"><i class="fa-solid fa-file-lines"></i>Applications</a></li>
      <li ><a href="Applicant_Jobs.php"><i class="fa-solid fa-briefcase"></i>Jobs</a></li>
      <li><a href="Login.php"><i class="fa-solid fa-right-from-bracket"></i>Log Out</a></li>
    </ul>
  </div>

</div>


    <!-- Main Content -->
    <div class="main-content">
        <div class="welcome-box">
            Welcome back, User!
        </div>

        <div class="status-section">
            <h3>Application status</h3>
            <div class="status-cards">
                <div class="status-card">
                    <p>Pending</p>
                    <h2>0</h2>
                </div>
                <div class="status-card">
                    <p>Interview</p>
                    <h2>0</h2>
                </div>
                <div class="status-card">
                    <p>Rejected</p>
                    <h2>0</h2>
                </div>
            </div>
        </div>

        <div class="notifications-section">
            <h3>Notifications</h3>
            <div class="notification-box"></div>
        </div>
    </div>
</body>
</html>
