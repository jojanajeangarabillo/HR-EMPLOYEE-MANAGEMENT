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


// Fetch application counts by status
$status_counts = [
    'Pending' => 0,
    'Initial Interview' => 0,
    'Final Interview' => 0,
    'Rejected' => 0
];

// Get counts from applications table
$stmt = $conn->prepare("
    SELECT status, COUNT(*) as count 
    FROM applications 
    WHERE applicantID = ? 
    GROUP BY status
");
$stmt->bind_param("s", $applicantID);
$stmt->execute();
$result = $stmt->get_result();

while ($row = $result->fetch_assoc()) {
    if (isset($status_counts[$row['status']])) {
        $status_counts[$row['status']] = $row['count'];
    }
}


// Rejected count from rejected_applications table
$stmt = $conn->prepare("
    SELECT COUNT(*) as count 
    FROM rejected_applications 
    WHERE applicantID = ?
");
$stmt->bind_param("s", $applicantID);
$stmt->execute();
$result = $stmt->get_result();
if ($row = $result->fetch_assoc()) {
    $status_counts['Rejected'] = $row['count'];
}


// Fetch total number of positions for Hirings (status = 'On-Going' or 'To Post')
$hiringsQuery = $conn->query("
    SELECT SUM(vacancy_count) AS count 
    FROM vacancies 
    WHERE status IN ('On-Going', 'To Post')
");
if ($hiringsQuery && $row = $hiringsQuery->fetch_assoc()) {
    $hirings = $row['count'] ?? 0; 
}

// Pagination Setup
$limit = 5; // rows per page
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$start = ($page > 1) ? ($page * $limit) - $limit : 0;

// Count total rows
$totalQuery = $conn->query("SELECT COUNT(*) AS total FROM vacancies WHERE status = 'On-Going'");
$total = ($totalQuery && $row = $totalQuery->fetch_assoc()) ? $row['total'] : 0;

$pages = ceil($total / $limit);


// Fetch paginated vacancies
$recentVacanciesQuery = $conn->query("
    SELECT v.id, v.vacancy_count, v.status, d.deptName, p.position_title, e.typeName AS employment_type
    FROM vacancies v
    JOIN department d ON v.department_id = d.deptID
    JOIN position p ON v.position_id = p.positionID
    JOIN employment_type e ON v.employment_type_id = e.emtypeID
    WHERE v.status = 'On-Going'
    ORDER BY v.id DESC
    LIMIT $start, $limit
");
?>






<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Applicant Dashboard</title>

    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&family=Roboto:wght@400;500&display=swap" rel="stylesheet">

    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/7.0.1/css/all.min.css">

    <!-- Sidebar CSS -->
    <link rel="stylesheet" href="admin-sidebar.css">
    <!-- Internal CSS for dashboard contents -->
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
            --border-radius: 12px;
            --border-radius-sm: 8px;
            --box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
            --box-shadow-lg: 0 10px 25px rgba(0, 0, 0, 0.1);
            --transition: all 0.3s ease;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Poppins', 'Roboto', sans-serif;
            display: flex;
            background-color: #f1f5fc;
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

        /* Welcome Box */
        .welcome-box {
            background: linear-gradient(135deg, var(--primary), var(--primary-light));
            color: white;
            padding: 25px 30px;
            border-radius: var(--border-radius);
            box-shadow: var(--box-shadow);
            display: flex;
            justify-content: space-between;
            align-items: center;
            position: relative;
            overflow: hidden;
        }

        .welcome-box::before {
            content: '';
            position: absolute;
            top: -50%;
            right: -50%;
            width: 100%;
            height: 200%;
            background: rgba(255, 255, 255, 0.1);
            transform: rotate(30deg);
        }

        .welcome-text h1 {
            font-size: 24px;
            font-weight: 600;
            margin-bottom: 5px;
        }

        .welcome-text p {
            font-size: 14px;
            opacity: 0.9;
            font-weight: 400;
        }

        .welcome-icon {
            font-size: 40px;
            opacity: 0.8;
            z-index: 1;
        }

        /* Stats Section */
        .stats-section {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
            gap: 20px;
        }

        .stat-card {
            background: white;
            border-radius: var(--border-radius);
            padding: 20px;
            box-shadow: var(--box-shadow);
            display: flex;
            flex-direction: column;
            transition: var(--transition);
            border-top: 4px solid var(--primary);
            position: relative;
            overflow: hidden;
        }

        .stat-card::after {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 4px;
            background: var(--primary);
        }

        .stat-card.pending::after { background: var(--warning); }
        .stat-card.initial::after { background: var(--primary-light); }
        .stat-card.final::after { background: var(--accent); }
        .stat-card.rejected::after { background: var(--danger); }

        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: var(--box-shadow-lg);
        }

        .stat-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
        }

        .stat-title {
            font-size: 14px;
            font-weight: 500;
            color: var(--gray);
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .stat-icon {
            width: 40px;
            height: 40px;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
        }

        .icon-pending { background-color: var(--warning); }
        .icon-initial { background-color: var(--primary-light); }
        .icon-final { background-color: var(--accent); }
        .icon-rejected { background-color: var(--danger); }

        .stat-value {
            font-size: 28px;
            font-weight: 600;
            color: var(--dark);
            margin-bottom: 5px;
        }

        .stat-change {
            font-size: 12px;
            color: var(--gray);
        }

        .positive { color: var(--success); }
        .negative { color: var(--danger); }

        /* Quick Actions */
        .quick-actions {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
        }

        .action-card {
            background: white;
            border-radius: var(--border-radius);
            padding: 20px;
            box-shadow: var(--box-shadow);
            display: flex;
            flex-direction: column;
            align-items: center;
            text-align: center;
            transition: var(--transition);
            cursor: pointer;
            border: 1px solid transparent;
        }

        .action-card:hover {
            transform: translateY(-3px);
            box-shadow: var(--box-shadow-lg);
            border-color: var(--primary-light);
        }

        .action-icon {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            background: var(--light);
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 15px;
            color: var(--primary);
            font-size: 20px;
            transition: var(--transition);
        }

        .action-card:hover .action-icon {
            background: var(--primary);
            color: white;
        }

        .action-title {
            font-size: 16px;
            font-weight: 500;
            margin-bottom: 5px;
            color: var(--dark);
        }

        .action-desc {
            font-size: 12px;
            color: var(--gray);
        }

        /* Recent Jobs Section */
        .recent-jobs {
            background: white;
            border-radius: var(--border-radius);
            padding: 25px;
            box-shadow: var(--box-shadow);
        }

        .section-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }

        .section-title {
            font-size: 18px;
            font-weight: 600;
            color: var(--dark);
        }

        .view-all {
            font-size: 14px;
            color: var(--primary);
            text-decoration: none;
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 5px;
            transition: var(--transition);
        }

        .view-all:hover {
            text-decoration: underline;
            gap: 8px;
        }

        .jobs-table {
            width: 100%;
            border-collapse: collapse;
        }

        .jobs-table th {
            text-align: left;
            padding: 12px 15px;
            border-bottom: 1px solid var(--gray-light);
            font-weight: 500;
            color: var(--gray);
            font-size: 14px;
        }

        .jobs-table td {
            padding: 15px;
            border-bottom: 1px solid var(--gray-light);
            vertical-align: middle;
        }

        .jobs-table tr:last-child td {
            border-bottom: none;
        }

        .jobs-table tr:hover {
            background-color: var(--light);
        }

        .job-title {
            font-weight: 500;
            color: var(--dark);
        }

        .job-department {
            font-size: 14px;
            color: var(--gray);
        }

        .job-type {
            display: inline-block;
            padding: 4px 10px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 500;
            background: var(--light);
            color: var(--primary);
        }

        .job-vacancy {
            font-weight: 500;
            color: var(--dark);
        }

        .apply-btn {
            background: var(--primary);
            color: white;
            border: none;
            padding: 8px 15px;
            border-radius: var(--border-radius-sm);
            font-size: 13px;
            font-weight: 500;
            cursor: pointer;
            transition: var(--transition);
        }

        .apply-btn:hover {
            background: var(--primary-dark);
        }

        /* Pagination */
        .pagination {
            display: flex;
            justify-content: center;
            margin-top: 20px;
            gap: 5px;
        }

        .page-link {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 36px;
            height: 36px;
            border-radius: var(--border-radius-sm);
            background: white;
            border: 1px solid var(--gray-light);
            color: var(--dark);
            text-decoration: none;
            font-size: 14px;
            transition: var(--transition);
        }

        .page-link:hover {
            background: var(--light);
        }

        .page-link.active {
            background: var(--primary);
            color: white;
            border-color: var(--primary);
        }

        /* Empty State */
        .empty-state {
            text-align: center;
            padding: 40px 20px;
            color: var(--gray);
        }

        .empty-icon {
            font-size: 48px;
            margin-bottom: 15px;
            color: var(--gray-light);
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
            .stats-section {
                grid-template-columns: 1fr 1fr;
            }
            
            .quick-actions {
                grid-template-columns: 1fr;
            }
            
            .jobs-table {
                display: block;
                overflow-x: auto;
            }
            
            .welcome-box {
                flex-direction: column;
                text-align: center;
                gap: 15px;
            }
            
            .welcome-icon {
                order: -1;
            }
        }

        @media (max-width: 576px) {
            .stats-section {
                grid-template-columns: 1fr;
            }
            
            .section-header {
                flex-direction: column;
                align-items: flex-start;
                gap: 10px;
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
    <?php $current = basename($_SERVER['PHP_SELF']); ?>
    <div class="sidebar">
        <a href="Applicant_Profile.php" class="profile">
            <img src="<?php echo !empty($profile_picture) ? htmlspecialchars($profile_picture) : 'uploads/employees/default.png'; ?>" 
            alt="Profile" class="sidebar-profile-img">
        </a>

        <div class="sidebar-name">
            <p><?php echo "Welcome, $applicantname"; ?></p>
        </div>

        <ul class="nav">
            <li<?php echo $current==='Applicant_Dashboard.php' ? ' class="active"' : ''; ?>><a href="Applicant_Dashboard.php"><i class="fa-solid fa-table-columns"></i>Dashboard</a></li>
            <li<?php echo $current==='Applicant_Application.php' ? ' class="active"' : ''; ?>><a href="Applicant_Application.php"><i class="fa-solid fa-file-lines"></i>Applications</a></li>
            <li<?php echo $current==='Applicant_Jobs.php' ? ' class="active"' : ''; ?>><a href="Applicant_Jobs.php"><i class="fa-solid fa-briefcase"></i>Jobs</a></li>
            <li><a href="Login.php"><i class="fa-solid fa-right-from-bracket"></i>Log Out</a></li>
        </ul>
    </div>

    <!-- Main Content -->
    <main class="main-content">
        <!-- Welcome Section -->
        <div class="welcome-box">
            <div class="welcome-text">
                <h1><?php echo "Welcome back, $applicantname"; ?></h1>
                <p>Here's your application status and recent job opportunities</p>
            </div>
            <div class="welcome-icon">
                <i class="fas fa-hand-wave"></i>
            </div>
        </div>

        <!-- Stats Section -->
        <div class="stats-section">
            <div class="stat-card pending">
                <div class="stat-header">
                    <div class="stat-title">Pending Applications</div>
                    <div class="stat-icon icon-pending">
                        <i class="fas fa-clock"></i>
                    </div>
                </div>
                <div class="stat-value"><?php echo $status_counts['Pending']; ?></div>
                
            </div>
            
            <div class="stat-card initial">
                <div class="stat-header">
                    <div class="stat-title">Initial Interviews</div>
                    <div class="stat-icon icon-initial">
                        <i class="fas fa-user-check"></i>
                    </div>
                </div>
                <div class="stat-value"><?php echo $status_counts['Initial Interview']; ?></div>
                
            </div>
            
            <div class="stat-card final">
                <div class="stat-header">
                    <div class="stat-title">Final Interviews</div>
                    <div class="stat-icon icon-final">
                        <i class="fas fa-user-tie"></i>
                    </div>
                </div>
                <div class="stat-value"><?php echo $status_counts['Final Interview']; ?></div>
                
            </div>
            
            <div class="stat-card rejected" onclick="window.location.href='Applicant_Jobs.php'">
                <div class="stat-header">
                    <div class="stat-title">Rejected</div>
                    <div class="stat-icon icon-rejected">
                        <i class="fas fa-times-circle"></i>
                    </div>
                </div>
                <div class="stat-value"><?php echo $status_counts['Rejected']; ?></div>
                
                
            </div>
        </div>

        <!-- Quick Actions -->
        <div class="quick-actions">
            <div class="action-card" onclick="window.location.href='Applicant_Jobs.php'">
                <div class="action-icon">
                    <i class="fas fa-search"></i>
                </div>
                <div class="action-title">Browse Jobs</div>
                <div class="action-desc">Find new opportunities</div>
            </div>
            
            <div class="action-card" onclick="window.location.href='Applicant_Application.php'">
                <div class="action-icon">
                    <i class="fas fa-file-alt"></i>
                </div>
                <div class="action-title">My Applications</div>
                <div class="action-desc">Track your applications</div>
            </div>
            
            <div class="action-card" onclick="window.location.href='Applicant_Profile.php'">
                <div class="action-icon">
                    <i class="fas fa-user-edit"></i>
                </div>
                <div class="action-title">Update Profile</div>
                <div class="action-desc">Keep your profile current</div>
            </div>
            
            
                
            </div>
        </div>

        <!-- Recent Jobs Section -->
        <div class="recent-jobs">
            <div class="section-header">
                <h2 class="section-title">Recent Job Openings</h2>
                <a href="Applicant_Jobs.php" class="view-all">
                    View All <i class="fas fa-arrow-right"></i>
                </a>
            </div>
            
            <?php if ($recentVacanciesQuery && $recentVacanciesQuery->num_rows > 0): ?>
            <table class="jobs-table">
                <thead>
                    <tr>
                        <th>Position</th>
                        <th>Department</th>
                        <th>Type</th>
                        <th>Vacancies</th>
                        
                    </tr>
                </thead>
                <tbody>
                    <?php while($row = $recentVacanciesQuery->fetch_assoc()): ?>
                    <tr>
                        <td>
                            <div class='job-title'><?php echo htmlspecialchars($row['position_title']); ?></div>
                        </td>
                        <td>
                            <div class='job-department'><?php echo htmlspecialchars($row['deptName']); ?></div>
                        </td>
                        <td>
                            <span class='job-type'><?php echo htmlspecialchars($row['employment_type']); ?></span>
                        </td>
                        <td>
                            <div class='job-vacancy'><?php echo htmlspecialchars($row['vacancy_count']); ?> positions</div>
                        </td>
                        
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
            
            <!-- Pagination -->
            <?php if ($pages > 1): ?>
            <div class="pagination">
                <?php for ($i = 1; $i <= $pages; $i++): ?>
                    <a href="?page=<?php echo $i; ?>" class="page-link <?php echo $i == $page ? 'active' : ''; ?>">
                        <?php echo $i; ?>
                    </a>
                <?php endfor; ?>
            </div>
            <?php endif; ?>
            
            <?php else: ?>
            <div class="empty-state">
                <div class="empty-icon">
                    <i class="fas fa-briefcase"></i>
                </div>
                <h3>No job openings available</h3>
                <p>Check back later for new opportunities</p>
            </div>
            <?php endif; ?>
        </div>
    </main>

    <script>
        // Add some interactive functionality
        document.addEventListener('DOMContentLoaded', function() {
            // Add click handlers for apply buttons
            const applyButtons = document.querySelectorAll('.apply-btn');
            applyButtons.forEach(button => {
                button.addEventListener('click', function() {
                    const jobTitle = this.closest('tr').querySelector('.job-title').textContent;
                    alert(`You're applying for: ${jobTitle}`);
                    // In a real implementation, this would redirect to the application page
                });
            });
            
            // Add animation to stat cards on load
            const statCards = document.querySelectorAll('.stat-card');
            statCards.forEach((card, index) => {
                card.style.animationDelay = `${index * 0.1}s`;
                card.classList.add('fade-in');
            });
        });
    </script>
</body>
</html>