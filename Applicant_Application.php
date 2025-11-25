<?php
session_start();
require 'admin/db.connect.php';

$applicantID = $_SESSION['applicantID'];

// Fetch Applicant Basic Info
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

// Fetch applications with corrected JOINs
$applications = [];
$stmt = $conn->prepare("
    SELECT 
        a.id, 
        a.status, 
        a.applied_at, 
        a.job_title,
        a.department_name,
        a.type_name,
        jp.jobID,
        d.deptName,
        et.typeName as employment_type_name
    FROM applications a
    LEFT JOIN job_posting jp ON a.jobID = jp.jobID
    LEFT JOIN department d ON jp.department = d.deptID
    LEFT JOIN employment_type et ON jp.employment_type = et.emtypeID
    WHERE a.applicantID = ?
    ORDER BY a.applied_at DESC
");
$stmt->bind_param("s", $applicantID);
$stmt->execute();
$res = $stmt->get_result();
$applications = $res->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Fetch rejected applications
$rejected_applications = [];
$stmt = $conn->prepare("
    SELECT 
        ra.id,
        ra.reason,
        ra.rejected_at,
        jp.job_title,
        jp.job_description,
        d.deptName as department_name,
        et.typeName as employment_type_name
    FROM rejected_applications ra
    LEFT JOIN job_posting jp ON ra.jobID = jp.jobID
    LEFT JOIN department d ON jp.department = d.deptID
    LEFT JOIN employment_type et ON jp.employment_type = et.emtypeID
    WHERE ra.applicantID = ?
    ORDER BY ra.rejected_at DESC
");
$stmt->bind_param("s", $applicantID);
$stmt->execute();
$res = $stmt->get_result();
$rejected_applications = $res->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Count applications by status
$status_counts = [
    'Pending' => 0,
    'Under Review' => 0,
    'Interview' => 0,
    'Rejected' => 0,
    'Accepted' => 0
];

foreach ($applications as $app) {
    $status = $app['status'];
    if (isset($status_counts[$status])) {
        $status_counts[$status]++;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Applications</title>

    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">

    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/7.0.1/css/all.min.css">

    <!-- Sidebar CSS -->
    <link rel="stylesheet" href="admin-sidebar.css">

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
            --border-radius-lg: 20px;
            --box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
            --box-shadow-lg: 0 12px 40px rgba(0, 0, 0, 0.15);
            --transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            --gradient-primary: linear-gradient(135deg, #1E3A8A 0%, #3B82F6 100%);
            --gradient-success: linear-gradient(135deg, #10B981 0%, #34D399 100%);
            --gradient-warning: linear-gradient(135deg, #F59E0B 0%, #FBBF24 100%);
            --gradient-danger: linear-gradient(135deg, #EF4444 0%, #F87171 100%);
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
            padding: 20px 40px;
            display: flex;
            flex-direction: column;
            gap: 24px;
            margin-left: 260px;
           
            width: calc(100% - 260px);
        }

        /* Header Section */
        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 8px;
           
        }

        .header-content h1 {
            font-weight: 700;
            font-size: 32px;
            background: var(--gradient-primary);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            margin-bottom: 4px;
        }

        .header-content p {
            color: var(--gray);
            font-size: 14px;
            font-weight: 400;
        }

        .header-actions {
            display: flex;
            gap: 16px;
            align-items: center;
        }

        .search-box {
            display: flex;
            align-items: center;
            background: rgba(255, 255, 255, 0.9);
            backdrop-filter: blur(10px);
            border-radius: var(--border-radius-lg);
            padding: 12px 20px;
            width: 320px;
            box-shadow: var(--box-shadow);
            border: 1px solid rgba(255, 255, 255, 0.8);
            transition: var(--transition);
        }

        .search-box:focus-within {
            box-shadow: var(--box-shadow-lg);
            transform: translateY(-2px);
            border-color: var(--primary-light);
        }

        .search-box input {
            border: none;
            outline: none;
            background: none;
            width: 100%;
            padding-left: 12px;
            font-size: 14px;
            color: var(--dark);
            font-weight: 500;
        }

        .search-box input::placeholder {
            color: var(--gray);
            font-weight: 400;
        }

        .search-box i {
            color: var(--primary);
            font-size: 16px;
        }

        .filter-btn {
            background: white;
            border: 1px solid var(--gray-light);
            border-radius: var(--border-radius-lg);
            padding: 12px 20px;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 14px;
            font-weight: 500;
            color: var(--dark);
            transition: var(--transition);
            box-shadow: var(--box-shadow);
        }

        .filter-btn:hover {
            background: var(--primary);
            color: white;
            transform: translateY(-2px);
            box-shadow: var(--box-shadow-lg);
        }

        /* Stats Overview */
        .stats-overview {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 20px;
            animation: fadeInUp 0.6s ease-out 0.2s both;
        }

        .stat-item {
            background: rgba(255, 255, 255, 0.9);
            backdrop-filter: blur(10px);
            border-radius: var(--border-radius-lg);
            padding: 24px;
            box-shadow: var(--box-shadow);
            display: flex;
            align-items: center;
            gap: 16px;
            border: 1px solid rgba(255, 255, 255, 0.8);
            position: relative;
            overflow: hidden;
        }

        .stat-item::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: var(--gradient-primary);
        }

        .stat-item.pending::before { background: var(--gradient-warning); }
        .stat-item.review::before { background: var(--gradient-primary); }
        .stat-item.interview::before { background: var(--gradient-success); }
        .stat-item.rejected::before { background: var(--gradient-danger); }

        .stat-item:hover {
            transform: translateY(-5px);
            box-shadow: var(--box-shadow-lg);
        }

        .stat-icon {
            width: 60px;
            height: 60px;
            border-radius: 16px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 24px;
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.15);
        }

        .icon-pending { background: var(--gradient-warning); }
        .icon-review { background: var(--gradient-primary); }
        .icon-interview { background: var(--gradient-success); }
        .icon-rejected { background: var(--gradient-danger); }

        .stat-info h3 {
            font-size: 28px;
            font-weight: 700;
            color: var(--dark);
            margin: 0;
            line-height: 1;
        }

        .stat-info p {
            font-size: 14px;
            color: var(--gray);
            margin: 4px 0 0 0;
            font-weight: 500;
        }

        /* Applications Container */
        .applications-container {
            display: flex;
            flex-direction: column;
            gap: 20px;
           
        }

        .section-title {
            font-size: 20px;
            font-weight: 600;
            color: var(--dark);
            margin-bottom: 8px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .section-title i {
            color: var(--primary);
        }

        .application-card {
            background: rgba(255, 255, 255, 0.9);
            backdrop-filter: blur(10px);
            border-radius: var(--border-radius-lg);
            padding: 28px;
            box-shadow: var(--box-shadow);
            transition: var(--transition);
            border: 1px solid rgba(255, 255, 255, 0.8);
            position: relative;
            overflow: hidden;
        }

        .application-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            bottom: 0;
            width: 6px;
            background: var(--gradient-primary);
            transition: var(--transition);
        }

        .application-card.pending::before { background: var(--gradient-warning); }
        .application-card.review::before { background: var(--gradient-primary); }
        .application-card.interview::before { background: var(--gradient-success); }
        .application-card.rejected::before { background: var(--gradient-danger); }
        .application-card.accepted::before { background: var(--gradient-success); }

        .application-card:hover {
            transform: translateY(-3px);
            box-shadow: var(--box-shadow-lg);
        }

        .application-card:hover::before {
            width: 8px;
        }

        .card-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 16px;
        }

        .job-info h3 {
            font-size: 20px;
            font-weight: 600;
            color: var(--dark);
            margin-bottom: 8px;
            line-height: 1.3;
        }

        .job-meta {
            display: flex;
            gap: 24px;
            font-size: 14px;
            color: var(--gray);
        }

        .job-meta span {
            display: flex;
            align-items: center;
            gap: 8px;
            font-weight: 500;
        }

        .job-meta i {
            color: var(--primary);
            font-size: 16px;
            width: 16px;
        }

        .status-badge {
            padding: 8px 16px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
        }

        .status-pending { background: var(--gradient-warning); color: white; }
        .status-review { background: var(--gradient-primary); color: white; }
        .status-interview { background: var(--gradient-success); color: white; }
        .status-rejected { background: var(--gradient-danger); color: white; }
        .status-accepted { background: var(--gradient-success); color: white; }

        .card-footer {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-top: 20px;
            padding-top: 20px;
            border-top: 1px solid rgba(0, 0, 0, 0.06);
        }

        .applied-date {
            font-size: 14px;
            color: var(--gray);
            display: flex;
            align-items: center;
            gap: 8px;
            font-weight: 500;
        }

        .applied-date i {
            color: var(--primary);
        }

        .application-actions {
            display: flex;
            gap: 12px;
        }

        .action-btn {
            padding: 10px 20px;
            border-radius: var(--border-radius-sm);
            font-size: 13px;
            font-weight: 600;
            cursor: pointer;
            transition: var(--transition);
            border: none;
            display: flex;
            align-items: center;
            gap: 6px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
        }

        .view-btn {
            background: var(--gradient-primary);
            color: white;
        }

        .view-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(59, 130, 246, 0.4);
        }

        .withdraw-btn {
            background: rgba(239, 68, 68, 0.1);
            color: var(--danger);
            border: 1px solid rgba(239, 68, 68, 0.2);
        }

        .withdraw-btn:hover {
            background: var(--gradient-danger);
            color: white;
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(239, 68, 68, 0.4);
        }

        /* Rejection Reason Styles */
        .rejection-reason {
            background: rgba(239, 68, 68, 0.05);
            border-left: 4px solid var(--danger);
            padding: 16px;
            border-radius: var(--border-radius-sm);
            margin-top: 16px;
        }

        .rejection-reason h4 {
            color: var(--danger);
            font-size: 14px;
            font-weight: 600;
            margin-bottom: 8px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .rejection-reason p {
            color: var(--dark);
            font-size: 14px;
            line-height: 1.5;
            margin: 0;
        }

        /* Empty State */
        .empty-state {
            text-align: center;
            padding: 80px 40px;
            color: var(--gray);
            background: rgba(255, 255, 255, 0.9);
            backdrop-filter: blur(10px);
            border-radius: var(--border-radius-lg);
            box-shadow: var(--box-shadow);
            border: 1px solid rgba(255, 255, 255, 0.8);
            animation: fadeIn 0.6s ease-out;
        }

        .empty-icon {
            font-size: 80px;
            margin-bottom: 24px;
            color: var(--gray-light);
            opacity: 0.7;
        }

        .empty-state h3 {
            font-size: 24px;
            margin-bottom: 12px;
            color: var(--dark);
            font-weight: 600;
        }

        .empty-state p {
            margin-bottom: 32px;
            font-size: 16px;
            max-width: 400px;
            margin-left: auto;
            margin-right: auto;
            line-height: 1.6;
        }

        .browse-jobs-btn {
            background: var(--gradient-primary);
            color: white;
            border: none;
            padding: 14px 32px;
            border-radius: var(--border-radius-lg);
            font-weight: 600;
            cursor: pointer;
            transition: var(--transition);
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            font-size: 15px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            box-shadow: 0 8px 20px rgba(59, 130, 246, 0.3);
        }

        .browse-jobs-btn:hover {
            transform: translateY(-3px);
            box-shadow: 0 12px 30px rgba(59, 130, 246, 0.4);
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

        @keyframes fadeIn {
            from {
                opacity: 0;
            }
            to {
                opacity: 1;
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
            .header {
                flex-direction: column;
                gap: 16px;
                align-items: flex-start;
            }

            .header-actions {
                width: 100%;
                justify-content: space-between;
            }

            .search-box {
                width: 100%;
            }

            .stats-overview {
                grid-template-columns: 1fr 1fr;
                gap: 16px;
            }

            .card-header {
                flex-direction: column;
                gap: 12px;
            }

            .card-footer {
                flex-direction: column;
                gap: 16px;
                align-items: flex-start;
            }

            .application-actions {
                width: 100%;
                justify-content: space-between;
            }
        }

        @media (max-width: 576px) {
            .stats-overview {
                grid-template-columns: 1fr;
            }

            .job-meta {
                flex-direction: column;
                gap: 8px;
            }
            
            .main-content {
                padding: 16px;
            }
            
            .header-content h1 {
                font-size: 28px;
            }

            .application-card {
                padding: 20px;
            }
        }

        /* Loading Animation */
        .loading {
            opacity: 0.7;
            pointer-events: none;
        }

        .pulse {
            animation: pulse 1.5s ease-in-out infinite;
        }

        @keyframes pulse {
            0% { opacity: 1; }
            50% { opacity: 0.5; }
            100% { opacity: 1; }
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

        <?php $current = basename($_SERVER['PHP_SELF']); ?>
        <ul class="nav">
            <li<?php echo $current==='Applicant_Dashboard.php' ? ' class="active"' : ''; ?>><a href="Applicant_Dashboard.php"><i class="fa-solid fa-table-columns"></i>Dashboard</a></li>
            <li<?php echo $current==='Applicant_Application.php' ? ' class="active"' : ''; ?>><a href="Applicant_Application.php"><i class="fa-solid fa-file-lines"></i>Applications</a></li>
            <li<?php echo $current==='Applicant_Jobs.php' ? ' class="active"' : ''; ?>><a href="Applicant_Jobs.php"><i class="fa-solid fa-briefcase"></i>Jobs</a></li>
            <li><a href="Login.php"><i class="fa-solid fa-right-from-bracket"></i>Log Out</a></li>
        </ul>
    </div>

    <!-- Main Content -->
    <div class="main-content">
        <!-- Header -->
        <div class="header">
            <div class="header-content">
                <h1>My Applications</h1>
                <p>Track and manage your job applications</p>
            </div>
            <div class="header-actions">
                <div class="search-box">
                    <i class="fa-solid fa-magnifying-glass"></i>
                    <input type="text" placeholder="Search applications...">
                </div>
                <button class="filter-btn">
                    <i class="fa-solid fa-filter"></i>
                    Filter
                </button>
            </div>
        </div>

       

        <!-- Active Applications Container -->
        <div class="applications-container">
            <div class="section-title">
                <i class="fa-solid fa-list-check"></i>
                Active Applications (<?php echo count($applications); ?>)
            </div>
            
            <?php if (empty($applications)): ?>
                <div class="empty-state">
                    <div class="empty-icon">
                        <i class="fa-solid fa-file-circle-exclamation"></i>
                    </div>
                    <h3>No Applications Yet</h3>
                    <p>You haven't applied to any jobs yet. Start browsing available positions to begin your career journey.</p>
                    <a href="Applicant_Jobs.php" class="browse-jobs-btn">
                        <i class="fa-solid fa-briefcase"></i> Browse Available Jobs
                    </a>
                </div>
            <?php else: ?>
                <?php foreach ($applications as $app): ?>
                    <?php
                    // Use data from applications table first, fallback to joined tables
                    $title = $app['job_title'] ?? 'Untitled Role';
                    $status = $app['status'] ?? 'Unknown';
                    $department = $app['department_name'] ?? $app['deptName'] ?? 'Not specified';
                    $employmentType = $app['type_name'] ?? $app['employment_type_name'] ?? 'Not specified';
                    $appliedAt = (!empty($app['applied_at']) && $app['applied_at'] !== '0000-00-00 00:00:00') 
                        ? date('F j, Y', strtotime($app['applied_at'])) 
                        : 'Date not available';
                    
                    // Determine status class
                    $statusClass = strtolower(str_replace(' ', '-', $status));
                    ?>
                    <div class="application-card <?php echo $statusClass; ?>">
                        <div class="card-header">
                            <div class="job-info">
                                <h3><?php echo htmlspecialchars($title); ?></h3>
                                <div class="job-meta">
                                    <span><i class="fa-solid fa-building"></i> <?php echo htmlspecialchars($department); ?></span>
                                    <span><i class="fa-solid fa-briefcase"></i> <?php echo htmlspecialchars($employmentType); ?></span>
                                </div>
                            </div>
                            <div class="status-badge status-<?php echo $statusClass; ?>">
                                <i class="fa-solid fa-circle"></i> <?php echo htmlspecialchars($status); ?>
                            </div>
                        </div>
                        <div class="card-footer">
                            <div class="applied-date">
                                <i class="fa-solid fa-calendar"></i> Applied: <?php echo htmlspecialchars($appliedAt); ?>
                            </div>
                            
                              
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <!-- Rejected Applications Container -->
        <?php if (!empty($rejected_applications)): ?>
            <div class="applications-container">
                <div class="section-title">
                    <i class="fa-solid fa-ban"></i>
                    Rejected Applications (<?php echo count($rejected_applications); ?>)
                </div>
                
                <?php foreach ($rejected_applications as $rejected): ?>
                    <?php
                    $title = $rejected['job_title'] ?? 'Untitled Role';
                    $department = $rejected['department_name'] ?? 'Not specified';
                    $employmentType = $rejected['employment_type_name'] ?? 'Not specified';
                    $rejectedAt = (!empty($rejected['rejected_at']) && $rejected['rejected_at'] !== '0000-00-00 00:00:00') 
                        ? date('F j, Y', strtotime($rejected['rejected_at'])) 
                        : 'Date not available';
                    $reason = $rejected['reason'] ?? 'No reason provided';
                    ?>
                    <div class="application-card rejected">
                        <div class="card-header">
                            <div class="job-info">
                                <h3><?php echo htmlspecialchars($title); ?></h3>
                                <div class="job-meta">
                                    <span><i class="fa-solid fa-building"></i> <?php echo htmlspecialchars($department); ?></span>
                                    <span><i class="fa-solid fa-briefcase"></i> <?php echo htmlspecialchars($employmentType); ?></span>
                                </div>
                            </div>
                            <div class="status-badge status-rejected">
                                <i class="fa-solid fa-times-circle"></i> Rejected
                            </div>
                        </div>
                        
                        <!-- Rejection Reason Section -->
                        <div class="rejection-reason">
                            <h4><i class="fa-solid fa-comment-exclamation"></i> Reason for Rejection</h4>
                            <p><?php echo htmlspecialchars($reason); ?></p>
                        </div>
                        
                        <div class="card-footer">
                            <div class="applied-date">
                                <i class="fa-solid fa-calendar-times"></i> Rejected: <?php echo htmlspecialchars($rejectedAt); ?>
                            </div>
                            
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <script>
        // Enhanced interactive functionality
        document.addEventListener('DOMContentLoaded', function() {
            // Search functionality with debounce
            const searchInput = document.querySelector('.search-box input');
            const applicationCards = document.querySelectorAll('.application-card');
            let searchTimeout;
            
            searchInput.addEventListener('input', function() {
                clearTimeout(searchTimeout);
                searchTimeout = setTimeout(() => {
                    const searchTerm = this.value.toLowerCase().trim();
                    
                    applicationCards.forEach(card => {
                        const jobTitle = card.querySelector('.job-info h3').textContent.toLowerCase();
                        const department = card.querySelector('.job-meta span:first-child').textContent.toLowerCase();
                        const status = card.querySelector('.status-badge').textContent.toLowerCase();
                        
                        if (jobTitle.includes(searchTerm) || department.includes(searchTerm) || status.includes(searchTerm)) {
                            card.style.display = 'block';
                            card.style.animation = 'fadeInUp 0.4s ease-out';
                        } else {
                            card.style.display = 'none';
                        }
                    });
                }, 300);
            });

            // View details button functionality
            const viewButtons = document.querySelectorAll('.view-btn');
            viewButtons.forEach(button => {
                button.addEventListener('click', function() {
                    const card = this.closest('.application-card');
                    card.classList.add('loading', 'pulse');
                    
                    setTimeout(() => {
                        const jobTitle = card.querySelector('.job-info h3').textContent;
                        alert(`Viewing details for: ${jobTitle}\n\nThis would open a detailed view in a real implementation.`);
                        card.classList.remove('loading', 'pulse');
                    }, 800);
                });
            });

            // Withdraw button functionality
            const withdrawButtons = document.querySelectorAll('.withdraw-btn');
            withdrawButtons.forEach(button => {
                button.addEventListener('click', function() {
                    const card = this.closest('.application-card');
                    const jobTitle = card.querySelector('.job-info h3').textContent;
                    
                    if (confirm(`Are you sure you want to withdraw your application for "${jobTitle}"? This action cannot be undone.`)) {
                        card.classList.add('loading', 'pulse');
                        
                        setTimeout(() => {
                            card.style.opacity = '0.5';
                            card.style.transform = 'scale(0.98)';
                            alert(`Application for "${jobTitle}" has been withdrawn.\n\nThis would trigger an API call in a real implementation.`);
                            card.classList.remove('loading', 'pulse');
                        }, 1000);
                    }
                });
            });

            // Filter button functionality
            const filterBtn = document.querySelector('.filter-btn');
            filterBtn.addEventListener('click', function() {
                this.classList.add('pulse');
                setTimeout(() => {
                    alert('Filter options would appear here in a real implementation.');
                    this.classList.remove('pulse');
                }, 600);
            });

            // Add hover effects for stat items
            const statItems = document.querySelectorAll('.stat-item');
            statItems.forEach(item => {
                item.addEventListener('mouseenter', function() {
                    this.style.transform = 'translateY(-5px) scale(1.02)';
                });
                
                item.addEventListener('mouseleave', function() {
                    this.style.transform = 'translateY(0) scale(1)';
                });
            });
        });
    </script>
</body>
</html>