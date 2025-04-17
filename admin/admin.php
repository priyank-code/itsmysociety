<?php
session_start();
include('../connection.php');

// Check if admin is logged in
if(!isset($_SESSION['is_admin']) || $_SESSION['is_admin'] !== true) {
    header("Location: ../index.php");
    exit();
}

// Count total users
$total_users_query = "SELECT COUNT(*) as total FROM user";
$total_result = mysqli_query($conn, $total_users_query);
$total_users = mysqli_fetch_assoc($total_result)['total'];

// Count active users
$active_users_query = "SELECT COUNT(*) as active FROM user WHERE status = 1";
$active_result = mysqli_query($conn, $active_users_query);
$active_users = mysqli_fetch_assoc($active_result)['active'];

// Count resolved complaints
$resolved_complaints_query = "SELECT COUNT(*) as resolved FROM complaints WHERE status = 1";
$resolved_result = mysqli_query($conn, $resolved_complaints_query);
$resolved_complaints = mysqli_fetch_assoc($resolved_result)['resolved'];

// Get recent complaints with error handling
$recent_complaints_query = "SELECT c.*, r.name as user_name 
                          FROM complaints c 
                          LEFT JOIN user r ON c.user_email = r.email 
                          ORDER BY c.created_at DESC LIMIT 5";
$recent_complaints = mysqli_query($conn, $recent_complaints_query);

if (!$recent_complaints) {
    // If query fails, check if table exists
    $check_table_query = "SHOW TABLES LIKE 'complaints'";
    $table_exists = mysqli_query($conn, $check_table_query);
    
    if (mysqli_num_rows($table_exists) == 0) {
        // Create complaints table if it doesn't exist
        $create_table_query = "CREATE TABLE IF NOT EXISTS complaints (
            id INT PRIMARY KEY AUTO_INCREMENT,
            user_email VARCHAR(255),
            title VARCHAR(255),
            complaints TEXT,
            status TINYINT DEFAULT 0,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )";
        mysqli_query($conn, $create_table_query);
    }
    
    // Try the query again
    $recent_complaints = mysqli_query($conn, $recent_complaints_query);
}

// Count total notices
$total_notices_query = "SELECT COUNT(*) as total FROM notices";
$notices_result = mysqli_query($conn, $total_notices_query);
$total_notices = mysqli_fetch_assoc($notices_result)['total'];

// Get recent notices
$recent_notices_query = "SELECT * FROM notices ORDER BY notice_date DESC LIMIT 5";
$recent_notices = mysqli_query($conn, $recent_notices_query);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard</title>
    <link rel="icon" href="../img/favicon.png" type="png" />
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        /* Common Styles */
        :root {
            --sidebar-width: 250px;
            --primary-color: #4e73df;
            --secondary-color: #858796;
        }

        body {
            overflow-x: hidden;
            background-color: #f8f9fc;
        }

        /* Sidebar Styles */
        #sidebar {
            min-width: 250px;
            max-width: 250px;
            min-height: 100vh;
            background: linear-gradient(180deg, var(--primary-color) 0%, #224abe 100%);
            color: white;
            transition: all 0.3s;
            position: fixed;
            z-index: 1000;
        }

        #sidebar.active {
            margin-left: -250px;
        }

        #sidebar .sidebar-header {
            padding: 20px;
            background: rgba(0, 0, 0, 0.1);
            text-align: center;
        }

        #sidebar ul.components {
            padding: 20px 0;
        }

        #sidebar ul li a {
            padding: 10px 20px;
            font-size: 1.1em;
            display: block;
            color: rgba(255, 255, 255, 0.8);
            text-decoration: none;
            transition: all 0.3s;
        }

        #sidebar ul li a:hover {
            color: white;
            background: rgba(255, 255, 255, 0.1);
            padding-left: 25px;
        }

        #sidebar ul li.active > a {
            color: white;
            background: rgba(255, 255, 255, 0.2);
        }

        /* Content Styles */
        #content {
            width: calc(100% - 250px);
            margin-left: 250px;
            transition: all 0.3s;
            padding: 20px;
        }

        #content.active {
            width: 100%;
            margin-left: 0;
        }

        /* Card Styles */
        .stats-card {
            border: none;
            border-radius: 10px;
            box-shadow: 0 0 15px rgba(0,0,0,0.1);
            transition: transform 0.3s;
        }

        .stats-card:hover {
            transform: translateY(-5px);
        }

        .stats-card .icon {
            font-size: 2em;
            opacity: 0.9;
        }

        .text-primary {
            color: #4e73df !important;
        }

        .text-success {
            color: #1cc88a !important;
        }

        .text-info {
            color: #36b9cc !important;
        }

        .text-warning {
            color: #f6c23e !important;
        }

        /* Table Styles */
        .table th {
            white-space: nowrap;
            background-color: #f8f9fc;
        }
        
        .table td {
            vertical-align: middle;
        }

        .action-buttons {
            white-space: nowrap;
        }

        .action-buttons .btn {
            margin: 0 2px;
        }

        .badge {
            padding: 8px 12px;
        }

        /* Responsive Styles */
        @media (max-width: 768px) {
            #sidebar {
                margin-left: -250px;
            }
            #sidebar.active {
                margin-left: 0;
            }
            #content {
                width: 100%;
                margin-left: 0;
            }
            #content.active {
                margin-left: 250px;
            }
        }

        /* Overlay */
        .overlay {
            display: none;
            position: fixed;
            width: 100vw;
            height: 100vh;
            background: rgba(0, 0, 0, 0.7);
            z-index: 999;
            opacity: 0;
            transition: all 0.5s ease-in-out;
        }

        .overlay.active {
            display: block;
            opacity: 1;
        }
    </style>
</head>
<body>
    <div class="wrapper">
        <!-- Sidebar -->
        <nav id="sidebar">
            <div class="sidebar-header">
                <h3>Admin Panel</h3>
            </div>

            <ul class="list-unstyled components">
                <li class="active">
                    <a href="admin.php">
                        <i class="fas fa-tachometer-alt me-2"></i>
                        Dashboard
                    </a>
                </li>
                <li>
                    <a href="users.php">
                        <i class="fas fa-users me-2"></i>
                        Users
                    </a>
                </li>
                <li>
                    <a href="profile.php">
                        <i class="fas fa-user me-2"></i>
                        Profile
                    </a>
                </li>
                <li>
                    <a href="complaints.php">
                        <i class="fas fa-exclamation-circle me-2"></i>
                        Complaints
                    </a>
                </li>
                <li>
                    <a href="notice.php">
                        <i class="fas fa-bullhorn me-2"></i>
                        Notices
                    </a>
                </li>
                <li>
                    <a href="maintenance.php">
                        <i class="fas fa-tools me-2"></i>
                        Maintenance
                    </a>
                </li>
                <li>
                    <a href="contact.php">
                        <i class="fas fa-envelope me-2"></i>
                        Contact Messages
                    </a>
                </li>
                <li>
                    <a href="download-users.php">
                        <i class="fas fa-download me-2"></i>
                        Download User List
                    </a>
                </li>
            </ul>
        </nav>

        <!-- Page Content -->
        <div id="content">
            <!-- Top Navigation -->
            <nav class="navbar navbar-expand-lg navbar-light bg-white shadow-sm mb-4">
                <div class="container-fluid">
                    <button type="button" id="sidebarCollapse" class="btn btn-primary">
                        <i class="fas fa-bars"></i>
                    </button>

                    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarSupportedContent">
                        <span class="navbar-toggler-icon"></span>
                    </button>

                    <div class="collapse navbar-collapse" id="navbarSupportedContent">
                        <ul class="navbar-nav ms-auto">
                            <li class="nav-item dropdown">
                                <a class="nav-link dropdown-toggle" href="#" id="navbarDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                                    <i class="fas fa-user-circle me-1"></i>
                                    <?php echo htmlspecialchars($_SESSION['admin_name']); ?>
                                </a>
                                <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="navbarDropdown">
                                    <li><a class="dropdown-item" href="profile.php"><i class="fas fa-user me-2"></i>Profile</a></li>
                                    <li><hr class="dropdown-divider"></li>
                                    <li><a class="dropdown-item" href="../logout.php"><i class="fas fa-sign-out-alt me-2"></i>Logout</a></li>
                                </ul>
                            </li>
                        </ul>
                    </div>
                </div>
            </nav>

            <!-- Dashboard Content -->
            <div class="container-fluid">
                <!-- Stats Cards -->
                <div class="row g-4 mb-4">
                    <div class="col-12 col-sm-6 col-xl-3">
                        <div class="stats-card card">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h6 class="text-muted mb-2">Total Users</h6>
                                        <h4 class="mb-0"><?php echo $total_users; ?></h4>
                                    </div>
                                    <div class="icon text-primary">
                                        <i class="fas fa-users"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-12 col-sm-6 col-xl-3">
                        <div class="stats-card card">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h6 class="text-muted mb-2">Active Users</h6>
                                        <h4 class="mb-0"><?php echo $active_users; ?></h4>
                                    </div>
                                    <div class="icon text-success">
                                        <i class="fas fa-user-check"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-12 col-sm-6 col-xl-3">
                        <div class="stats-card card">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h6 class="text-muted mb-2">Resolved Complaints</h6>
                                        <h4 class="mb-0"><?php echo $resolved_complaints; ?></h4>
                                    </div>
                                    <div class="icon text-info">
                                        <i class="fas fa-check-circle"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-12 col-sm-6 col-xl-3">
                        <div class="stats-card card">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h6 class="text-muted mb-2">Total Notices</h6>
                                        <h4 class="mb-0"><?php echo $total_notices; ?></h4>
                                    </div>
                                    <div class="icon text-warning">
                                        <i class="fas fa-bell"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Recent Activity -->
                <div class="row">
                    <!-- Recent Complaints -->
                    <div class="col-12 col-xl-6 mb-4">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="card-title mb-0">Recent Complaints</h5>
                            </div>
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table">
                                        <thead>
                                            <tr>
                                                <th>User</th>
                                                <th>Title</th>
                                                <th>Status</th>
                                                <th>Date</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php 
                                            if ($recent_complaints && mysqli_num_rows($recent_complaints) > 0) {
                                                while($complaint = mysqli_fetch_assoc($recent_complaints)) { 
                                            ?>
                                                <tr>
                                                    <td><?php echo htmlspecialchars($complaint['user_name']); ?></td>
                                                    <td><?php echo htmlspecialchars($complaint['title']); ?></td>
                                                    <td>
                                                        <?php if($complaint['status'] == 1): ?>
                                                            <span class="badge bg-success">Resolved</span>
                                                        <?php else: ?>
                                                            <span class="badge bg-warning">Pending</span>
                                                        <?php endif; ?>
                                                    </td>
                                                    <td><?php echo date('d M Y', strtotime($complaint['created_at'])); ?></td>
                                                </tr>
                                            <?php 
                                                }
                                            } else {
                                                echo "<tr><td colspan='4' class='text-center'>No complaints found</td></tr>";
                                            }
                                            ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Recent Notices -->
                    <div class="col-12 col-xl-6 mb-4">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="card-title mb-0">Recent Notices</h5>
                            </div>
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table">
                                        <thead>
                                            <tr>
                                                <th>Title</th>
                                                <th>Type</th>
                                                <th>Date</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php 
                                            if ($recent_notices && mysqli_num_rows($recent_notices) > 0) {
                                                while($notice = mysqli_fetch_assoc($recent_notices)) { 
                                            ?>
                                                <tr>
                                                    <td><?php echo htmlspecialchars($notice['notice_name']); ?></td>
                                                    <td>
                                                        <span class="fw-bold" style="color: <?php 
                                                            echo $notice['notice_type'] == 'Important' ? '#dc3545' : 
                                                                ($notice['notice_type'] == 'Event' ? '#198754' : '#0d6efd'); 
                                                        ?>">
                                                            <?php echo htmlspecialchars($notice['notice_type']); ?>
                                                        </span>
                                                    </td>
                                                    <td><?php echo date('d M Y', strtotime($notice['notice_date'])); ?></td>
                                                </tr>
                                            <?php 
                                                }
                                            } else {
                                                echo "<tr><td colspan='3' class='text-center'>No notices found</td></tr>";
                                            }
                                            ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Sidebar toggle
            const sidebarCollapse = document.getElementById('sidebarCollapse');
            const sidebar = document.getElementById('sidebar');
            const content = document.getElementById('content');
            const wrapper = document.querySelector('.wrapper');

            sidebarCollapse.addEventListener('click', function() {
                sidebar.classList.toggle('active');
                content.classList.toggle('active');

                // Create overlay for mobile
                if (window.innerWidth <= 768) {
                    if (!document.querySelector('.overlay')) {
                        const overlay = document.createElement('div');
                        overlay.classList.add('overlay');
                        wrapper.appendChild(overlay);
                        
                        overlay.addEventListener('click', function() {
                            sidebar.classList.remove('active');
                            content.classList.remove('active');
                            this.remove();
                        });
                    } else {
                        document.querySelector('.overlay').remove();
                    }
                }
            });

            // Handle window resize
            window.addEventListener('resize', function() {
                if (window.innerWidth > 768) {
                    const overlay = document.querySelector('.overlay');
                    if (overlay) {
                        overlay.remove();
                    }
                }
            });

            // Active link handling
            const currentPage = window.location.pathname.split('/').pop() || 'admin.php';
            document.querySelectorAll('#sidebar ul li a').forEach(link => {
                if (link.getAttribute('href') === currentPage) {
                    link.parentElement.classList.add('active');
                }
            });
        });
    </script>
</body>
</html>