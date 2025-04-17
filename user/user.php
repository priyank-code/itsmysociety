<?php
session_start();
include('../connection.php');

// Check if user is logged in and has status = 1
if(!isset($_SESSION['user_id'])) {
    header("Location: ../index.php");
    exit();
}

// Verify user status
$user_id = $_SESSION['user_id'];
$status_check = "SELECT status FROM user WHERE id = ? AND status = 1";
$stmt = mysqli_prepare($conn, $status_check);
mysqli_stmt_bind_param($stmt, "i", $user_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

if(mysqli_num_rows($result) == 0) {
    session_destroy();
    header("Location: ../index.php");
    exit();
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Dashboard</title>
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
            font-family: 'Nunito', sans-serif;
        }

        .wrapper {
            display: flex;
            width: 100%;
            align-items: stretch;
        }

        /* Sidebar Styles */
        #sidebar {
            min-width: 250px;
            max-width: 250px;
            background: linear-gradient(180deg, var(--primary-color) 0%, #224abe 100%);
            color: white;
            transition: all 0.3s;
            position: fixed;
            height: 100vh;
            z-index: 999;
        }

        #sidebar.active {
            margin-left: -250px;
        }

        #sidebar .sidebar-header {
            padding: 20px;
            background: rgba(0, 0, 0, 0.1);
        }

        #sidebar ul.components {
            padding: 20px 0;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }

        #sidebar ul li a {
            padding: 10px 20px;
            font-size: 1.1em;
            display: block;
            color: white;
            text-decoration: none;
            transition: all 0.3s;
        }

        #sidebar ul li a:hover {
            background: rgba(255, 255, 255, 0.1);
        }

        #sidebar ul li.active > a {
            background: rgba(255, 255, 255, 0.1);
        }

        /* Content Styles */
        #content {
            width: calc(100% - 250px);
            min-height: 100vh;
            transition: all 0.3s;
            position: relative;
            margin-left: 250px;
            padding: 20px;
        }

        #content.active {
            margin-left: 0;
            width: 100%;
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
                width: calc(100% - 250px);
            }
            .dropdown .dropdown-menu {
                width: 100%;
            }
        }

        /* Card Styles */
        .stats-card {
            transition: all 0.3s;
        }
        .stats-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15);
        }
        .activity-card {
            height: 100%;
        }

        /* Add styles for dropdown menu */
        .dropdown-item {
            padding: 0.5rem 1rem;
        }
        
        .dropdown-item i {
            margin-right: 10px;
            width: 16px;
            color: var(--secondary-color);
        }

        .dropdown-divider {
            margin: 0.5rem 0;
        }

        /* User dropdown button */
        #userDropdown {
            padding: 0.5rem 1rem;
            font-weight: 500;
        }

        #userDropdown i {
            font-size: 1.1rem;
        }

        .dropdown-menu {
            box-shadow: 0 0.15rem 1.75rem rgba(0, 0, 0, 0.15);
            margin-top: 0.5rem;
        }
    </style>
</head>
<body>
    <div class="wrapper">
        <!-- Sidebar -->
        <nav id="sidebar">
            <div class="sidebar-header">
                <h3>Hi <?php echo htmlspecialchars($_SESSION['user_name']); ?></h3>
            </div>
            <ul class="list-unstyled components">
                <li class="active">
                    <a href="user.php">
                        <i class="fas fa-home me-2"></i>Dashboard
                    </a>
                </li>
                <li>
                    <a href="profile.php">
                        <i class="fas fa-user me-2"></i>Profile
                    </a>
                </li>
                <li>
                    <a href="complaints.php">
                        <i class="fas fa-exclamation-circle me-2"></i>Complaints
                    </a>
                </li>
                <li>
                    <a href="notices.php">
                        <i class="fas fa-bell me-2"></i>Notices
                    </a>
                </li>
                <li>
                    <a href="user_maintenance.php">
                        <i class="fas fa-tools me-2"></i>Maintenance
                    </a>
                </li>
                <li>
                    <a href="../logout.php">
                        <i class="fas fa-sign-out-alt me-2"></i>Logout
                    </a>
                </li>
            </ul>
        </nav>

        <!-- Content -->
        <div id="content">
            <!-- Top Navigation -->
            <nav class="navbar navbar-expand-lg navbar-light bg-white shadow-sm mb-4">
                <div class="container-fluid">
                    <button type="button" id="sidebarCollapse" class="btn btn-primary">
                        <i class="fas fa-bars"></i>
                    </button>
                    <div class="ms-auto">
                        <div class="dropdown">
                            <a class="btn btn-link text-dark dropdown-toggle text-decoration-none" href="#" id="userDropdown" data-bs-toggle="dropdown">
                                <i class="fas fa-user-circle me-2"></i>
                                <?php echo htmlspecialchars($_SESSION['user_name']); ?>
                            </a>
                            <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="userDropdown">
                                <li><a class="dropdown-item" href="profile.php"><i class="fas fa-user"></i>Profile</a></li>
                                <li><hr class="dropdown-divider"></li>
                                <li><a class="dropdown-item" href="../logout.php"><i class="fas fa-sign-out-alt"></i>Logout</a></li>
                            </ul>
                        </div>
                    </div>
                </div>
            </nav>

            <!-- Main Content -->
            <div class="container-fluid">
                <!-- Stats Cards -->
                <div class="row g-4 mb-4">
                    <div class="col-12 col-sm-6 col-xl-3">
                        <div class="stats-card card">
                            <div class="card-body">
                                <div class="d-flex align-items-center justify-content-between">
                                    <div>
                                        <h6 class="text-uppercase mb-2">Total Complaints</h6>
                                        <h3 class="mb-0">
                                            <?php
                                            $total_complaints = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total FROM complaints WHERE user_email = '{$_SESSION['email']}'"))['total'];
                                            echo $total_complaints;
                                            ?>
                                        </h3>
                                    </div>
                                    <div class="icon text-primary">
                                        <i class="fas fa-file-alt"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-12 col-sm-6 col-xl-3">
                        <div class="stats-card card">
                            <div class="card-body">
                                <div class="d-flex align-items-center justify-content-between">
                                    <div>
                                        <h6 class="text-uppercase mb-2">Resolved</h6>
                                        <h3 class="mb-0">
                                            <?php
                                            $resolved_complaints = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as resolved FROM complaints WHERE user_email = '{$_SESSION['email']}' AND status = 1"))['resolved'];
                                            echo $resolved_complaints;
                                            ?>
                                        </h3>
                                    </div>
                                    <div class="icon text-success">
                                        <i class="fas fa-check-circle"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-12 col-sm-6 col-xl-3">
                        <div class="stats-card card">
                            <div class="card-body">
                                <div class="d-flex align-items-center justify-content-between">
                                    <div>
                                        <h6 class="text-uppercase mb-2">Pending</h6>
                                        <h3 class="mb-0">
                                            <?php
                                            $pending_complaints = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as pending FROM complaints WHERE user_email = '{$_SESSION['email']}' AND status = 0"))['pending'];
                                            echo $pending_complaints;
                                            ?>
                                        </h3>
                                    </div>
                                    <div class="icon text-warning">
                                        <i class="fas fa-clock"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-12 col-sm-6 col-xl-3">
                        <div class="stats-card card">
                            <div class="card-body">
                                <div class="d-flex align-items-center justify-content-between">
                                    <div>
                                        <h6 class="text-uppercase mb-2">Active Notices</h6>
                                        <h3 class="mb-0">
                                            <?php
                                            $active_notices = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as active FROM notices WHERE status = 1"))['active'];
                                            echo $active_notices;
                                            ?>
                                        </h3>
                                    </div>
                                    <div class="icon text-info">
                                        <i class="fas fa-bell"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Recent Activities -->
                <div class="row mt-4">
                    <div class="col-12 col-xl-6 mb-4">
                        <div class="card activity-card">
                            <div class="card-header">
                                <h5 class="card-title mb-0">My Recent Complaints</h5>
                            </div>
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table">
                                        <thead>
                                            <tr>
                                                <th>Title</th>
                                                <th>Status</th>
                                                <th>Date</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php
                                            if(isset($_SESSION['email'])) {
                                                $recent_complaints_query = "SELECT title, status, created_at FROM complaints WHERE user_email = ? ORDER BY created_at DESC LIMIT 5";
                                                if($stmt = mysqli_prepare($conn, $recent_complaints_query)) {
                                                    mysqli_stmt_bind_param($stmt, "s", $_SESSION['email']);
                                                    mysqli_stmt_execute($stmt);
                                                    $result = mysqli_stmt_get_result($stmt);
                                                    if(mysqli_num_rows($result) > 0) {
                                                        while($complaint = mysqli_fetch_assoc($result)) {
                                                            $status_badge = $complaint['status'] == 1 ? '<span class="badge bg-success">Resolved</span>' : '<span class="badge bg-warning">Pending</span>';
                                                            $date = date('d M Y', strtotime($complaint['created_at']));
                                                            echo "<tr><td>{$complaint['title']}</td><td>{$status_badge}</td><td>{$date}</td></tr>";
                                                        }
                                                    } else {
                                                        echo "<tr><td colspan='3' class='text-center'>No complaints found</td></tr>";
                                                    }
                                                    mysqli_stmt_close($stmt);
                                                }
                                            }
                                            ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-12 col-xl-6 mb-4">
                        <div class="card activity-card">
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
                                            $recent_notices_query = "SELECT notice_name, notice_type, notice_date FROM notices WHERE status = 1 ORDER BY notice_date DESC LIMIT 5";
                                            if($stmt = mysqli_prepare($conn, $recent_notices_query)) {
                                                mysqli_stmt_execute($stmt);
                                                $result = mysqli_stmt_get_result($stmt);
                                                if(mysqli_num_rows($result) > 0) {
                                                    while($notice = mysqli_fetch_assoc($result)) {
                                                        $date = date('d M Y', strtotime($notice['notice_date']));
                                                        echo "<tr>";
                                                        echo "<td>" . htmlspecialchars($notice['notice_name']) . "</td>";
                                                        echo "<td>" . htmlspecialchars($notice['notice_type']) . "</td>";
                                                        echo "<td>{$date}</td>";
                                                        echo "</tr>";
                                                    }
                                                } else {
                                                    echo "<tr><td colspan='3' class='text-center'>No notices found</td></tr>";
                                                }
                                                mysqli_stmt_close($stmt);
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
    
    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

    <!-- Custom Script -->
    <script>
        $(document).ready(function () {
            $('#sidebarCollapse').on('click', function () {
                $('#sidebar, #content').toggleClass('active');
            });

            // Close sidebar on mobile when clicking outside
            $(document).click(function (e) {
                var container = $("#sidebar, #sidebarCollapse");
                if (!container.is(e.target) && container.has(e.target).length === 0) {
                    if ($(window).width() <= 768 && $('#sidebar').hasClass('active')) {
                        $('#sidebar, #content').toggleClass('active');
                    }
                }
            });

            // Handle window resize
            $(window).resize(function () {
                if ($(window).width() > 768) {
                    $('#sidebar, #content').removeClass('active');
                }
            });
        });
    </script>
</body>
</html>
