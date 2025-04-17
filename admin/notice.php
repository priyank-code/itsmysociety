<?php
session_start();

// Check if admin is logged in
if(!isset($_SESSION['is_admin']) || $_SESSION['is_admin'] !== true) {
    header("Location: ../index.php");
    exit();
}

include '../connection.php';

// Pagination
$records_per_page = 5;
$page = isset($_GET['page']) ? $_GET['page'] : 1;
$offset = ($page - 1) * $records_per_page;

// Get total records for pagination
$total_records_query = "SELECT COUNT(*) as total FROM notices";
$total_records_result = mysqli_query($conn, $total_records_query);
$total_records = mysqli_fetch_assoc($total_records_result)['total'];
$total_pages = ceil($total_records / $records_per_page);

// Handle status update
if(isset($_POST['action']) && isset($_POST['notice_id'])) {
    $notice_id = mysqli_real_escape_string($conn, $_POST['notice_id']);
    
    if($_POST['action'] == 'toggle_status') {
        $sql = "UPDATE notices SET status = IF(status=1, 0, 1) WHERE id = '$notice_id'";
        mysqli_query($conn, $sql);
    } elseif($_POST['action'] == 'delete') {
        $sql = "DELETE FROM notices WHERE id = '$notice_id'";
        mysqli_query($conn, $sql);
    }
    
    // Redirect to prevent form resubmission
    header("Location: notice.php");
    exit();
}

// Handle form submission
if ($_SERVER["REQUEST_METHOD"] == "POST" && !isset($_POST['action'])) {
    $notice_name = mysqli_real_escape_string($conn, $_POST['notice_name']);
    $notice_type = mysqli_real_escape_string($conn, $_POST['notice_type']);
    $notice_date = mysqli_real_escape_string($conn, $_POST['notice_date']);
    $notice_message = mysqli_real_escape_string($conn, $_POST['notice_message']);
    
    $sql = "INSERT INTO notices (notice_name, notice_type, notice_date, notice_message) 
            VALUES ('$notice_name', '$notice_type', '$notice_date', '$notice_message')";
    
    if (mysqli_query($conn, $sql)) {
        header("Location: notice.php");
        exit();
    } else {
        $error_message = "Error: " . mysqli_error($conn);
    }
}

// Get current date for date input min attribute
$current_date = date('Y-m-d');
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Notice Management - Admin Panel</title>
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

        /* Notice Specific Styles */
        .notice-form {
            background: #f8f9fc;
            padding: 20px;
            border-radius: 5px;
            margin-bottom: 20px;
            box-shadow: 0 0.15rem 1.75rem rgba(0, 0, 0, 0.15);
        }

        .notice-message {
            max-width: 300px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .notice-type {
            font-weight: 700;
            font-size: 14px;
        }

        .notice-type.urgent {
            color: #e74a3b;
        }

        .notice-type.general {
            color: #4e73df;
        }

        .notice-type.maintenance {
            color: #f6c23e;
        }

        .badge-active {
            background-color: #1cc88a;
        }

        .badge-hidden {
            background-color: #858796;
        }

        /* Action button styles for mobile */
        @media (max-width: 768px) {
            .action-buttons {
                display: flex;
                gap: 4px;
                justify-content: flex-start;
                align-items: center;
                min-width: 80px;
            }

            .action-btn-text {
                display: none;
            }
            
            .action-buttons .btn {
                padding: 4px 8px;
                font-size: 14px;
                flex: 0 0 auto;
            }

            .table td:last-child {
                min-width: 90px;
                padding: 8px 6px;
                white-space: nowrap;
            }
        }

        @media (min-width: 769px) {
            .action-btn-text {
                display: inline;
            }
        }

        /* Modal Styles */
        .notice-modal .modal-header {
            background: var(--primary-color);
            color: white;
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
                <li>
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
                <li class="active">
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

            <!-- Main Content -->
            <div class="container-fluid">
                <!-- Add Notice Form -->
                <div class="notice-form mb-4">
                    <h5 class="mb-4">Add New Notice</h5>
                    <?php if(isset($success_message)): ?>
                        <div class="alert alert-success"><?php echo $success_message; ?></div>
                    <?php endif; ?>
                    <?php if(isset($error_message)): ?>
                        <div class="alert alert-danger"><?php echo $error_message; ?></div>
                    <?php endif; ?>
                    <form method="POST" action="" onsubmit="return confirm('Are you sure you want to add this notice?');">
                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <label for="notice_name" class="form-label">Notice Name</label>
                                <input type="text" class="form-control" id="notice_name" name="notice_name" placeholder="Enter Notice Name" required>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label for="notice_type" class="form-label">Notice Type</label>
                                <select class="form-control" id="notice_type" name="notice_type" required>
                                    <option value="">Select Type</option>
                                    <option value="General">General</option>
                                    <option value="Important">Important</option>
                                    <option value="Urgent">Urgent</option>
                                </select>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label for="notice_date" class="form-label">Notice Date</label>
                                <input type="date" class="form-control" id="notice_date" name="notice_date" min="<?php echo $current_date; ?>" value="<?php echo $current_date; ?>" required>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label for="notice_message" class="form-label">Notice Message</label>
                            <textarea class="form-control" id="notice_message" name="notice_message" rows="4" required></textarea>
                        </div>
                        <button type="submit" class="btn btn-primary">Add Notice</button>
                    </form>
                </div>
                
                <!-- Notice List -->
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">Notice List</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-bordered">
                                <thead>
                                    <tr>
                                        <th>Notice Name</th>
                                        <th>Type</th>
                                        <th>Date</th>
                                        <th>Message</th>
                                        <th>Status</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    $sql = "SELECT * FROM notices ORDER BY notice_date ASC LIMIT $offset, $records_per_page";
                                    $result = mysqli_query($conn, $sql);
                                    
                                    if (mysqli_num_rows($result) > 0) {
                                        while($row = mysqli_fetch_assoc($result)) {
                                            echo "<tr>";
                                            echo "<td>" . htmlspecialchars($row['notice_name']) . "</td>";
                                            echo "<td>" . htmlspecialchars($row['notice_type']) . "</td>";
                                            echo "<td>" . htmlspecialchars($row['notice_date']) . "</td>";
                                            echo "<td>";
                                            echo '<div class="d-flex align-items-center">';
                                            echo '<div class="notice-message">' . htmlspecialchars($row['notice_message']) . '</div>';
                                            echo '<button class="btn btn-link btn-sm ms-2" data-bs-toggle="modal" data-bs-target="#viewNoticeModal" ';
                                            echo 'data-type="' . htmlspecialchars($row['notice_type']) . '" ';
                                            echo 'data-date="' . htmlspecialchars($row['notice_date']) . '" ';
                                            echo 'data-message="' . htmlspecialchars(addslashes($row['notice_message'])) . '" ';
                                            echo 'data-status="' . $row['status'] . '">';
                                            echo '<i class="fas fa-eye"></i> View</button>';
                                            echo '</div>';
                                            echo "</td>";
                                            echo "<td>";
                                            if($row['status'] == 1) {
                                                echo '<span class="badge bg-success">Active</span>';
                                            } else {
                                                echo '<span class="badge bg-secondary">Hidden</span>';
                                            }
                                            echo "</td>";
                                            echo "<td class='action-buttons'>";
                                            if($row['status'] == 1) {
                                                echo '<form method="POST" style="display: inline-block;">';
                                                echo '<input type="hidden" name="notice_id" value="' . $row['id'] . '">';
                                                echo '<input type="hidden" name="action" value="toggle_status">';
                                                echo '<button type="submit" class="btn btn-warning btn-sm me-1">
                                                        <i class="fas fa-eye-slash"></i> 
                                                        <span class="action-btn-text">Hide</span>
                                                    </button>';
                                                echo '</form>';
                                            } else {
                                                echo '<form method="POST" style="display: inline-block;">';
                                                echo '<input type="hidden" name="notice_id" value="' . $row['id'] . '">';
                                                echo '<input type="hidden" name="action" value="toggle_status">';
                                                echo '<button type="submit" class="btn btn-success btn-sm me-1">
                                                        <i class="fas fa-eye"></i> 
                                                        <span class="action-btn-text">Show</span>
                                                    </button>';
                                                echo '</form>';
                                            }
                                            
                                            echo '<form method="POST" style="display: inline-block;" onsubmit="return confirm(\'Are you sure you want to delete this notice?\')">';
                                            echo '<input type="hidden" name="notice_id" value="' . $row['id'] . '">';
                                            echo '<input type="hidden" name="action" value="delete">';
                                            echo '<button type="submit" class="btn btn-danger btn-sm">
                                                    <i class="fas fa-trash"></i> 
                                                    <span class="action-btn-text">Delete</span>
                                                </button>';
                                            echo '</form>';
                                            echo "</td>";
                                            echo "</tr>";
                                        }
                                    } else {
                                        echo "<tr><td colspan='6' class='text-center'>No notices found</td></tr>";
                                    }
                                    ?>
                                </tbody>
                            </table>
                            
                            <!-- Pagination -->
                            <?php if($total_pages > 1): ?>
                            <nav aria-label="Page navigation">
                                <ul class="pagination justify-content-center">
                                    <li class="page-item <?php echo ($page <= 1) ? 'disabled' : ''; ?>">
                                        <a class="page-link" href="?page=<?php echo $page-1; ?>" <?php echo ($page <= 1) ? 'tabindex="-1" aria-disabled="true"' : ''; ?>>Previous</a>
                                    </li>
                                    <?php for($i = 1; $i <= $total_pages; $i++): ?>
                                        <li class="page-item <?php echo ($page == $i) ? 'active' : ''; ?>">
                                            <a class="page-link" href="?page=<?php echo $i; ?>"><?php echo $i; ?></a>
                                        </li>
                                    <?php endfor; ?>
                                    <li class="page-item <?php echo ($page >= $total_pages) ? 'disabled' : ''; ?>">
                                        <a class="page-link" href="?page=<?php echo $page+1; ?>" <?php echo ($page >= $total_pages) ? 'tabindex="-1" aria-disabled="true"' : ''; ?>>Next</a>
                                    </li>
                                </ul>
                            </nav>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- View Notice Modal -->
    <div class="modal fade notice-modal" id="viewNoticeModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Notice Details</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <dl class="row">
                        <dt class="col-sm-3">Type</dt>
                        <dd class="col-sm-9">
                            <span class="notice-type" id="modalNoticeType"></span>
                        </dd>

                        <dt class="col-sm-3">Date</dt>
                        <dd class="col-sm-9" id="modalDate"></dd>

                        <dt class="col-sm-3">Message</dt>
                        <dd class="col-sm-9" id="modalMessage"></dd>

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

            // View Notice Modal
            const viewNoticeModal = document.getElementById('viewNoticeModal');
            viewNoticeModal.addEventListener('show.bs.modal', function(event) {
                const button = event.relatedTarget;
                
                // Get data from button
                const type = button.getAttribute('data-type');
                const date = button.getAttribute('data-date');
                const message = button.getAttribute('data-message');
                const status = button.getAttribute('data-status');
                
                // Update modal content
                const typeSpan = document.getElementById('modalNoticeType');
                typeSpan.textContent = type;
                typeSpan.className = 'notice-type ' + type.toLowerCase();
                
                document.getElementById('modalDate').textContent = date;
                document.getElementById('modalMessage').textContent = message;
                
                const statusSpan = document.getElementById('modalStatus');
                if(status === '1') {
                    statusSpan.className = 'badge badge-active';
                    statusSpan.textContent = 'Active';
                } else {
                    statusSpan.className = 'badge badge-hidden';
                    statusSpan.textContent = 'Hidden';
                }
            });
        });
    </script>
</body>
</html>