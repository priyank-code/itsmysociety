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

// Pagination
$records_per_page = 5;
$page = isset($_GET['page']) ? $_GET['page'] : 1;
$offset = ($page - 1) * $records_per_page;

// Get total records for pagination
$total_records_query = "SELECT COUNT(*) as total FROM notices WHERE status = 1";
$total_records_result = mysqli_query($conn, $total_records_query);
$total_records = mysqli_fetch_assoc($total_records_result)['total'];
$total_pages = ceil($total_records / $records_per_page);

// Get notices with pagination
$notices_query = "SELECT * FROM notices WHERE status = 1 ORDER BY notice_date DESC LIMIT ?, ?";
$stmt = mysqli_prepare($conn, $notices_query);
mysqli_stmt_bind_param($stmt, "ii", $offset, $records_per_page);
mysqli_stmt_execute($stmt);
$notices_result = mysqli_stmt_get_result($stmt);

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Notices</title>
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

        .card {
            border-radius: 10px;
        }
        .badge {
            padding: 8px 12px;
            font-weight: 500;
        }
        .notice-content {
            background-color: #f8f9fa;
            padding: 15px;
            border-radius: 8px;
            margin: 10px 0;
        }
        .text-primary {
            color: #4e73df !important;
        }
        .bg-primary {
            background-color: #4e73df !important;
        }
        .shadow-sm {
            box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075) !important;
        }
        .notice-list {
            max-width: 100%;
        }
        .notice-item {
            background: #fff;
        }
        .notice-item:last-child {
            border-bottom: none !important;
            margin-bottom: 0 !important;
            padding-bottom: 0 !important;
        }
        .notice-message {
            color: #555;
            line-height: 1.6;
            margin-top: 10px;
        }
        .badge {
            padding: 6px 12px;
        }
        .card-header {
            border-bottom: 0;
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
        .text-danger {
            color: #e74a3b !important;
        }
        .text-warning {
            color: #f6c23e !important;
        }
        .text-primary {
            color: #4e73df !important;
        }
        .table > :not(caption) > * > * {
            padding: 1rem 0.75rem;
        }
        .pagination {
            margin-bottom: 0;
        }
        .btn-sm {
            padding: 0.25rem 0.5rem;
            font-size: 0.875rem;
        }
        .modal-header .btn-close {
            margin: -0.5rem -0.5rem -0.5rem auto;
        }
        .fw-bold {
            font-weight: 600 !important;
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
        
        /* Fix table content alignment */
        .table td {
            vertical-align: middle;
        }

        .table tbody td {
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            max-width: 200px;
            vertical-align: middle;
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
                <li>
                    <a href="complaints.php">
                        <i class="fas fa-exclamation-circle me-2"></i>Complaints
                    </a>
                </li>
                <li class="active">
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
                <div class="card">
                    <div class="card-header bg-primary">
                        <h5 class="card-title text-white mb-0">Notice List</h5>
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
                                        <th>Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    if (mysqli_num_rows($notices_result) > 0) {
                                        while($row = mysqli_fetch_assoc($notices_result)) {
                                            $notice_type_class = strtolower($row['notice_type']) == 'urgent' ? 'text-danger' : 
                                                              (strtolower($row['notice_type']) == 'maintenance' ? 'text-warning' : 'text-primary');
                                            ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($row['notice_name']); ?></td>
                                                <td>
                                                    <span class="notice-type <?php echo $notice_type_class; ?>">
                                                        <?php echo htmlspecialchars($row['notice_type']); ?>
                                                    </span>
                                                </td>
                                                <td><?php echo date('d-m-Y', strtotime($row['notice_date'])); ?></td>
                                                <td style="max-width: 300px;">
                                                    <div class="notice-message" style="max-width: 300px;">
                                                        <?php echo htmlspecialchars($row['notice_message']); ?>
                                                    </div>
                                                </td>
                                                <td>
                                                    <button type="button" class="btn btn-primary btn-sm" 
                                                            data-bs-toggle="modal" 
                                                            data-bs-target="#viewNoticeModal"
                                                            data-notice-name="<?php echo htmlspecialchars($row['notice_name']); ?>"
                                                            data-notice-type="<?php echo htmlspecialchars($row['notice_type']); ?>"
                                                            data-notice-date="<?php echo date('d-m-Y', strtotime($row['notice_date'])); ?>"
                                                            data-notice-message="<?php echo htmlspecialchars($row['notice_message']); ?>">
                                                        <i class="fas fa-eye"></i> View
                                                    </button>
                                                </td>
                                            </tr>
                                            <?php
                                        }
                                    } else {
                                        echo "<tr><td colspan='5' class='text-center'>No notices available</td></tr>";
                                    }
                                    ?>
                                </tbody>
                            </table>

                            <!-- Pagination -->
                            <?php if($total_pages > 1): ?>
                            <nav aria-label="Page navigation" class="mt-4">
                                <ul class="pagination justify-content-center">
                                    <?php if($page > 1): ?>
                                        <li class="page-item">
                                            <a class="page-link" href="?page=<?php echo ($page-1); ?>">Previous</a>
                                        </li>
                                    <?php endif; ?>
                                    
                                    <?php for($i = 1; $i <= $total_pages; $i++): ?>
                                        <li class="page-item <?php echo ($page == $i) ? 'active' : ''; ?>">
                                            <a class="page-link" href="?page=<?php echo $i; ?>"><?php echo $i; ?></a>
                                        </li>
                                    <?php endfor; ?>
                                    
                                    <?php if($page < $total_pages): ?>
                                        <li class="page-item">
                                            <a class="page-link" href="?page=<?php echo ($page+1); ?>">Next</a>
                                        </li>
                                    <?php endif; ?>
                                </ul>
                            </nav>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>

            <!-- View Notice Modal -->
            <div class="modal fade" id="viewNoticeModal" tabindex="-1">
                <div class="modal-dialog modal-lg">
                    <div class="modal-content">
                        <div class="modal-header bg-primary text-white">
                            <h5 class="modal-title">Notice Details</h5>
                            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body">
                            <div class="mb-3">
                                <label class="form-label fw-bold">Notice Title:</label>
                                <p id="modalNoticeName"></p>
                            </div>
                            <div class="mb-3">
                                <label class="form-label fw-bold">Type:</label>
                                <p id="modalNoticeType"></p>
                            </div>
                            <div class="mb-3">
                                <label class="form-label fw-bold">Date:</label>
                                <p id="modalNoticeDate"></p>
                            </div>
                            <div class="mb-3">
                                <label class="form-label fw-bold">Message:</label>
                                <p id="modalNoticeMessage" style="white-space: pre-wrap;"></p>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                        </div>
                    </div>
                </div>
            </div>

            <script>
                // Handle view notice modal
                document.addEventListener('DOMContentLoaded', function() {
                    const viewNoticeModal = document.getElementById('viewNoticeModal');
                    if (viewNoticeModal) {
                        viewNoticeModal.addEventListener('show.bs.modal', function(event) {
                            const button = event.relatedTarget;
                            
                            // Get data from button
                            const noticeName = button.getAttribute('data-notice-name');
                            const noticeType = button.getAttribute('data-notice-type');
                            const noticeDate = button.getAttribute('data-notice-date');
                            const noticeMessage = button.getAttribute('data-notice-message');
                            
                            // Update modal content
                            document.getElementById('modalNoticeName').textContent = noticeName;
                            document.getElementById('modalNoticeType').textContent = noticeType;
                            document.getElementById('modalNoticeDate').textContent = noticeDate;
                            document.getElementById('modalNoticeMessage').textContent = noticeMessage;
                        });
                    }
                });
            </script>

            <!-- Bootstrap JS -->
            <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
            
            <!-- Custom JS -->
            <script>
                document.addEventListener('DOMContentLoaded', function() {
                    // Sidebar toggle
                    document.getElementById('sidebarCollapse').addEventListener('click', function() {
                        document.getElementById('sidebar').classList.toggle('active');
                        document.getElementById('content').classList.toggle('active');
                    });
                });
            </script>
        </div>
    </div>
</body>
</html>
