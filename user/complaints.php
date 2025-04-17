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

// Handle complaint submission
if(isset($_POST['submit_complaint'])) {
    $title = mysqli_real_escape_string($conn, $_POST['title']);
    $complaint = mysqli_real_escape_string($conn, $_POST['complaint']);
    $user_id = $_SESSION['user_id'];
    
    $insert_sql = "INSERT INTO complaints (user_id, user_email, title, complaints, status, created_at) 
                   VALUES (?, ?, ?, ?, 0, CURRENT_TIMESTAMP)";
    $stmt = mysqli_prepare($conn, $insert_sql);
    mysqli_stmt_bind_param($stmt, "isss", $user_id, $_SESSION['email'], $title, $complaint);
    
    if(mysqli_stmt_execute($stmt)) {
        echo "<script>alert('Complaint submitted successfully!'); window.location.href='complaints.php';</script>";
    } else {
        echo "<script>alert('Error submitting complaint!');</script>";
    }
}

// Handle complaint deletion
if(isset($_POST['action']) && $_POST['action'] == 'delete' && isset($_POST['complaint_id'])) {
    $complaint_id = mysqli_real_escape_string($conn, $_POST['complaint_id']);
    $user_email = $_SESSION['email'];
    
    // Only allow deletion if the complaint belongs to the user
    $delete_sql = "DELETE FROM complaints WHERE id = ? AND user_email = ?";
    $stmt = mysqli_prepare($conn, $delete_sql);
    mysqli_stmt_bind_param($stmt, "is", $complaint_id, $user_email);
    
    if(mysqli_stmt_execute($stmt)) {
        echo "<script>alert('Complaint deleted successfully!'); window.location.href='complaints.php';</script>";
    } else {
        echo "<script>alert('Error deleting complaint!');</script>";
    }
}

// Get user's complaints
$complaints_query = "SELECT * FROM complaints WHERE user_email = ? ORDER BY created_at DESC";
$stmt = mysqli_prepare($conn, $complaints_query);
mysqli_stmt_bind_param($stmt, "s", $_SESSION['email']);
mysqli_stmt_execute($stmt);
$complaints_result = mysqli_stmt_get_result($stmt);

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Complaints</title>
    <link rel="icon" href="../img/favicon.png" type="png" />
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        /* Same styles as user.php */
        :root {
            --sidebar-width: 250px;
            --primary-color: #4e73df;
            --secondary-color: #858796;
        }

        body {
            overflow-x: hidden;
            background-color: #f8f9fc;
        }

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
        }

        #sidebar ul.components {
            padding: 20px 0;
        }

        #sidebar ul li a {
            padding: 10px 20px;
            font-size: 1.1em;
            display: block;
            color: white;
            text-decoration: none;
        }

        #sidebar ul li a:hover {
            background: rgba(255, 255, 255, 0.1);
        }

        #sidebar ul li.active > a {
            background: rgba(255, 255, 255, 0.1);
        }

        #content {
            width: calc(100% - 250px);
            min-height: 100vh;
            position: absolute;
            right: 0;
            transition: all 0.3s;
        }

        #content.active {
            width: 100%;
        }

        @media (max-width: 768px) {
            #sidebar {
                margin-left: -250px;
            }
            #sidebar.active {
                margin-left: 0;
            }
            #content {
                width: 100%;
            }
            #content.active {
                width: calc(100% - 250px);
            }
        }

        /* Responsive table styles */
        @media (max-width: 768px) {
            .table-responsive {
                overflow-x: auto;
            }
            
            .table {
                white-space: nowrap;
                font-size: 14px;
            }

            .complaint-text {
                max-width: 150px;
                overflow: hidden;
                text-overflow: ellipsis;
                white-space: nowrap;
            }

            .btn-link {
                padding: 0.25rem 0.5rem;
                white-space: nowrap;
            }

            .table td, .table th {
                padding: 0.5rem;
                vertical-align: middle;
            }

            .badge {
                white-space: nowrap;
            }
        }

        /* Navbar Styles */
        .dropdown-menu {
            box-shadow: 0 0.15rem 1rem rgba(0,0,0,0.1);
        }
        .dropdown-item {
            padding: 8px 20px;
        }
        .dropdown-item i {
            margin-right: 8px;
            color: #888;
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
                <li>
                    <a href="user.php">
                        <i class="fas fa-home me-2"></i>Dashboard
                    </a>
                </li>
                <li>
                    <a href="profile.php">
                        <i class="fas fa-user me-2"></i>Profile
                    </a>
                </li>
                <li class="active">
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
                <div class="row mb-4">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="card-title mb-0">Submit New Complaint</h5>
                            </div>
                            <div class="card-body">
                                <form method="POST" action="">
                                    <div class="mb-3">
                                        <label class="form-label">Email Address</label>
                                        <input type="email" class="form-control" value="<?php echo htmlspecialchars($_SESSION['email']); ?>" readonly>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Title</label>
                                        <input type="text" name="title" class="form-control" required>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Complaint</label>
                                        <textarea name="complaint" class="form-control" rows="4" required></textarea>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Date</label>
                                        <input type="text" class="form-control" value="<?php echo date('Y-m-d'); ?>" readonly>
                                    </div>
                                    <button type="submit" name="submit_complaint" class="btn btn-primary">Submit Complaint</button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="card-title mb-0">My Complaints</h5>
                            </div>
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table table-hover">
                                        <thead>
                                            <tr>
                                                <th style="min-width: 100px;">Title</th>
                                                <th style="min-width: 200px;">Complaint</th>
                                                <th style="min-width: 100px;">Date</th>
                                                <th style="min-width: 100px;">Status</th>
                                                <th style="min-width: 80px;">Delete</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php 
                                            if(mysqli_num_rows($complaints_result) > 0) {
                                                while($row = mysqli_fetch_assoc($complaints_result)) {
                                                    echo "<tr>";
                                                    echo "<td style='vertical-align: middle;'>" . htmlspecialchars($row['title']) . "</td>";
                                                    echo "<td style='vertical-align: middle;'>";
                                                    echo '<div class="d-flex align-items-center">';
                                                    echo '<div class="complaint-text">' . substr(htmlspecialchars($row['complaints']), 0, 30) . '...</div>';
                                                    echo '<button class="btn btn-link btn-sm ms-2 p-0" data-bs-toggle="modal" data-bs-target="#viewComplaintModal" ';
                                                    echo 'data-title="' . htmlspecialchars($row['title']) . '" ';
                                                    echo 'data-complaint="' . htmlspecialchars(addslashes($row['complaints'])) . '" ';
                                                    echo 'data-date="' . date('Y-m-d', strtotime($row['created_at'])) . '" ';
                                                    echo 'data-status="' . ($row['status'] == 1 ? 'Resolved' : 'Pending') . '">';
                                                    echo '<i class="fas fa-eye"></i>';
                                                    echo '</button>';
                                                    echo '</div>';
                                                    echo "</td>";
                                                    echo "<td style='vertical-align: middle;'>" . date('Y-m-d', strtotime($row['created_at'])) . "</td>";
                                                    echo "<td style='vertical-align: middle;'>";
                                                    echo $row['status'] == 1 ? 
                                                        '<span class="badge bg-success">Resolved</span>' : 
                                                        '<span class="badge bg-warning">Pending</span>';
                                                    echo "</td>";
                                                    echo "<td style='vertical-align: middle;'>";
                                                    echo "<form method='post' class='d-inline' onsubmit='return confirm(\"Are you sure you want to delete this complaint?\")'>";
                                                    echo "<input type='hidden' name='complaint_id' value='" . $row['id'] . "'>";
                                                    echo "<input type='hidden' name='action' value='delete'>";
                                                    echo "<button type='submit' class='btn btn-danger btn-sm'>";
                                                    echo "<i class='fas fa-trash'></i>";
                                                    echo "</button>";
                                                    echo "</form>";
                                                    echo "</td>";
                                                    echo "</tr>";
                                                }
                                            } else {
                                                echo "<tr><td colspan='5' class='text-center'>No complaints found</td></tr>";
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

    <!-- View Complaint Modal -->
    <div class="modal fade" id="viewComplaintModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Complaint Details</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <dl class="row">
                        <dt class="col-sm-3">Title</dt>
                        <dd class="col-sm-9" id="modalTitle"></dd>

                        <dt class="col-sm-3">Date</dt>
                        <dd class="col-sm-9" id="modalDate"></dd>

                        <dt class="col-sm-3">Complaint</dt>
                        <dd class="col-sm-9" id="modalComplaint"></dd>

                        <dt class="col-sm-3">Status</dt>
                        <dd class="col-sm-9">
                            <span class="badge" id="modalStatus"></span>
                        </dd>
                    </dl>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
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

            // View Complaint Modal
            const viewComplaintModal = document.getElementById('viewComplaintModal');
            viewComplaintModal.addEventListener('show.bs.modal', function(event) {
                const button = event.relatedTarget;
                
                // Get data from button
                const title = button.getAttribute('data-title');
                const date = button.getAttribute('data-date');
                const complaint = button.getAttribute('data-complaint');
                const status = button.getAttribute('data-status');
                
                // Update modal content
                document.getElementById('modalTitle').textContent = title;
                document.getElementById('modalDate').textContent = date;
                document.getElementById('modalComplaint').textContent = complaint;
                
                const statusSpan = document.getElementById('modalStatus');
                statusSpan.textContent = status;
                if(status === 'Resolved') {
                    statusSpan.className = 'badge bg-success';
                } else {
                    statusSpan.className = 'badge bg-warning';
                }
            });
        });
    </script>
</body>
</html>
