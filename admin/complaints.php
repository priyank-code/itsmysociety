<?php
session_start();
if(!isset($_SESSION['is_admin']) || $_SESSION['is_admin'] !== true) {
    header("Location: ../index.php");
    exit();
}

include '../connection.php';

// Handle status update
if(isset($_POST['action']) && isset($_POST['complaint_id'])) {
    $complaint_id = mysqli_real_escape_string($conn, $_POST['complaint_id']);
    if($_POST['action'] == 'resolve') {
        $sql = "UPDATE complaints SET status = 1 WHERE id = '$complaint_id'";
        mysqli_query($conn, $sql);
    } elseif($_POST['action'] == 'pending') {
        $sql = "UPDATE complaints SET status = 0 WHERE id = '$complaint_id'";
        mysqli_query($conn, $sql);
    }
    header("Location: complaints.php");
    exit();
}

// Handle search
$where_clause = "1";
if(isset($_GET['search']) && !empty($_GET['search'])) {
    $search = mysqli_real_escape_string($conn, $_GET['search']);
    $search_type = isset($_GET['search_type']) ? $_GET['search_type'] : 'title';
    
    switch($search_type) {
        case 'email':
            $where_clause = "user_email LIKE '%$search%'";
            break;
        case 'title':
            $where_clause = "title LIKE '%$search%'";
            break;
        case 'status':
            $status_value = strtolower($search) == 'resolved' ? 1 : 0;
            $where_clause = "status = $status_value";
            break;
    }
}

// Pagination settings
$records_per_page = 10;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $records_per_page;

// Get total records for pagination
$total_records_sql = "SELECT COUNT(*) as count FROM complaints WHERE $where_clause";
$total_records_result = mysqli_query($conn, $total_records_sql);
$total_records = mysqli_fetch_assoc($total_records_result)['count'];
$total_pages = ceil($total_records / $records_per_page);

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Complaints Management - Admin Panel</title>
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

        /* Table Styles */
        .table-responsive {
            overflow-x: auto;
        }

        .table {
            width: 100%;
            margin-bottom: 1rem;
            white-space: nowrap;
        }

        /* Action Column Styles */
        .action-buttons {
            display: flex;
            gap: 4px;
            flex-wrap: nowrap;
            align-items: center;
            justify-content: flex-start;
            min-width: 200px;
        }

        .action-buttons form {
            margin: 0;
            padding: 0;
        }

        .action-buttons .btn {
            padding: 4px 8px;
            font-size: 12px;
            display: inline-flex;
            align-items: center;
            white-space: nowrap;
            margin: 0;
        }

        .action-buttons .btn i {
            font-size: 10px;
            margin-right: 4px;
        }

        /* Mobile Responsive Styles */
        @media (max-width: 768px) {
            .table td {
                max-width: 150px;
                overflow: hidden;
                text-overflow: ellipsis;
                white-space: nowrap;
                padding: 8px 4px;
                font-size: 12px;
            }

            .table th {
                padding: 8px 4px;
                font-size: 12px;
            }

            .action-buttons {
                min-width: 160px;
                gap: 2px;
            }

            .action-buttons .btn {
                padding: 3px 6px;
                font-size: 11px;
            }

            .action-buttons .btn i {
                margin-right: 2px;
            }

            /* Hide less important columns on mobile */
            .complaint-text {
                max-width: 100px;
            }
        }

        /* Complaint-specific styles */
        .filter-section {
            background: #f8f9fc;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
        }

        .filter-section .form-select,
        .filter-section .form-control,
        .filter-section .btn {
            height: 38px;
            font-size: 14px;
        }

        @media (min-width: 769px) {
            .filter-section .btn-primary {
                width: auto;
                min-width: 120px;
            }
        }

        .table td .btn {
            padding: 4px 8px;
            font-size: 13px;
            margin: 2px;
        }

        @media (min-width: 769px) {
            .table td .btn {
                padding: 5px 10px;
                font-size: 14px;
                margin: 0 3px;
            }
        }

        @media (max-width: 768px) {
            .action-buttons {
                display: flex;
                flex-direction: row;
                gap: 3px;
                justify-content: flex-start;
                align-items: center;
            }
            
            .action-buttons .btn {
                flex: 0 0 auto;
                white-space: nowrap;
                padding: 2px 6px;
                font-size: 12px;
                min-height: 24px;
                line-height: 1;
            }

            .action-btn-text {
                display: none;
            }

            .action-btn-icon {
                display: inline;
            }
            
            .table td {
                min-width: 80px;
                padding: 8px 6px;
            }
            
            .table td:last-child {
                min-width: 90px;
                padding-right: 6px;
                padding-left: 6px;
            }

            .complaint-title {
                max-width: 150px;
            }

            .complaint-text {
                max-width: 200px;
            }
        }

        @media (min-width: 769px) {
            .action-btn-icon {
                display: none;
            }

            .action-btn-text {
                display: inline;
            }
        }

        .complaint-title {
            max-width: 200px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .complaint-text {
            max-width: 300px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        /* Modal Styles */
        .complaint-modal .modal-header {
            background: var(--primary-color);
            color: white;
        }

        .complaint-details {
            margin-bottom: 1rem;
        }

        .complaint-details dt {
            font-weight: 600;
            color: var(--secondary-color);
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

        /* Responsive styles for mobile */
        @media (max-width: 768px) {
            .table-responsive {
                overflow-x: auto;
            }
            
            .action-buttons {
                white-space: nowrap;
            }
            
            .action-buttons .btn {
                padding: 0.2rem 0.4rem;
                font-size: 0.75rem;
                margin: 1px;
            }

            .filter-section .row {
                flex-direction: column;
            }

            .filter-section .col-md-4 {
                width: 100%;
                margin-bottom: 10px;
            }

            .filter-section .d-flex {
                flex-direction: column;
            }

            .filter-section select,
            .filter-section input,
            .filter-section button {
                margin: 5px 0 !important;
                width: 100%;
            }
            
            td {
                max-width: 120px;
                overflow: hidden;
                text-overflow: ellipsis;
                white-space: nowrap;
                font-size: 0.875rem;
            }

            .complaint-text {
                max-width: 150px;
            }

            .btn-link {
                padding: 0.2rem 0.4rem;
                font-size: 0.75rem;
            }
        }

        /* View button style */
        .btn-view {
            color: #4e73df;
            text-decoration: none;
            padding: 0.2rem 0.4rem;
            transition: all 0.3s;
        }

        .btn-view:hover {
            color: #224abe;
            background-color: rgba(78, 115, 223, 0.1);
            border-radius: 0.2rem;
        }

        /* Status badge styles */
        .status-badge {
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 13px;
        }

        .status-badge.resolved {
            background-color: #28a745;
            color: white;
        }

        .status-badge.pending {
            background-color: #ffc107;
            color: #000;
        }

        /* Hide text version on mobile */
        @media (max-width: 768px) {
            .status-text {
                display: none;
            }
            .status-icon {
                display: inline;
            }
            .status-badge {
                padding: 3px 6px;
                font-size: 12px;
            }
        }

        /* Hide icon version on desktop */
        @media (min-width: 769px) {
            .status-icon {
                display: none;
            }
            .status-text {
                display: inline;
            }
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
                <li class="active">
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

            <!-- Main Content -->
            <div class="container-fluid">
                <!-- Filter Section -->
                <div class="filter-section mb-4">
                    <div class="row g-3">
                        <div class="col-md-4">
                            <form method="GET" class="d-flex gap-2">
                                <select name="search_type" class="form-select">
                                    <option value="title" <?php echo (isset($_GET['search_type']) && $_GET['search_type'] == 'title') ? 'selected' : ''; ?>>Title</option>
                                    <option value="email" <?php echo (isset($_GET['search_type']) && $_GET['search_type'] == 'email') ? 'selected' : ''; ?>>Email</option>
                                    <option value="status" <?php echo (isset($_GET['search_type']) && $_GET['search_type'] == 'status') ? 'selected' : ''; ?>>Status</option>
                                </select>
                                <input type="text" name="search" class="form-control" placeholder="Search..." value="<?php echo isset($_GET['search']) ? htmlspecialchars($_GET['search']) : ''; ?>">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-search"></i> Search
                                </button>
                            </form>
                        </div>
                    </div>
                </div>

                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">Complaints List</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-bordered">
                                <thead>
                                    <tr>
                                        <th>Email</th>
                                        <th>Title</th>
                                        <th>Complaint</th>
                                        <th>Date</th>
                                        <th>Status</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    $sql = "SELECT * FROM complaints WHERE $where_clause ORDER BY created_at DESC LIMIT $offset, $records_per_page";
                                    $result = mysqli_query($conn, $sql);
                                    
                                    if(mysqli_num_rows($result) > 0) {
                                        while($row = mysqli_fetch_assoc($result)) {
                                            $status_badge = $row['status'] == 1 ? 
                                                '<span class="badge bg-success">Resolved</span>' : 
                                                '<span class="badge bg-warning">Pending</span>';
                                            
                                            echo "<tr>";
                                            echo "<td>" . htmlspecialchars($row['user_email']) . "</td>";
                                            echo "<td>" . htmlspecialchars($row['title']) . "</td>";
                                            echo "<td class='complaint-text'>" . htmlspecialchars($row['complaints']) . "</td>";
                                            echo "<td>" . date('Y-m-d', strtotime($row['created_at'])) . "</td>";
                                            echo "<td>" . $status_badge . "</td>";
                                            echo "<td class='action-buttons'>";
                                            // Show Resolve button for pending complaints
                                            if($row['status'] == 0) {
                                                echo "<form method='POST' style='display:inline;'>";
                                                echo "<input type='hidden' name='action' value='resolve'>";
                                                echo "<input type='hidden' name='complaint_id' value='" . $row['id'] . "'>";
                                                echo "<button type='submit' class='btn btn-success btn-sm' title='Mark as Resolved'>";
                                                echo "<i class='fas fa-check'></i><span>Resolve</span>";
                                                echo "</button>";
                                                echo "</form>";
                                            }
                                            // Show Mark as Pending button for resolved complaints
                                            if($row['status'] == 1) {
                                                echo "<form method='POST' style='display:inline;'>";
                                                echo "<input type='hidden' name='action' value='pending'>";
                                                echo "<input type='hidden' name='complaint_id' value='" . $row['id'] . "'>";
                                                echo "<button type='submit' class='btn btn-warning btn-sm' title='Mark as Pending'>";
                                                echo "<i class='fas fa-clock'></i><span>Pending</span>";
                                                echo "</button>";
                                                echo "</form>";
                                            }
                                            // View button for all complaints
                                            echo "<button type='button' class='btn btn-primary btn-sm view-complaint' 
                                                    data-bs-toggle='modal' 
                                                    data-bs-target='#viewComplaintModal'
                                                    data-email='" . htmlspecialchars($row['user_email'], ENT_QUOTES) . "'
                                                    data-title='" . htmlspecialchars($row['title'], ENT_QUOTES) . "'
                                                    data-complaint='" . htmlspecialchars($row['complaints'], ENT_QUOTES) . "'
                                                    data-date='" . date('Y-m-d', strtotime($row['created_at'])) . "'
                                                    data-status='" . ($row['status'] == 1 ? 'Resolved' : 'Pending') . "'
                                                    title='View Details'>";
                                            echo "<i class='fas fa-eye'></i><span>View</span>";
                                            echo "</button>";
                                            echo "</td>";
                                            echo "</tr>";
                                        }
                                    } else {
                                        echo "<tr><td colspan='6' class='text-center'>No complaints found</td></tr>";
                                    }
                                    ?>
                                </tbody>
                            </table>

                            <!-- Pagination -->
                            <?php if($total_pages > 1): ?>
                            <nav aria-label="Page navigation" class="mt-4">
                                <ul class="pagination justify-content-center">
                                    <?php for($i = 1; $i <= $total_pages; $i++): ?>
                                        <li class="page-item <?php echo $page == $i ? 'active' : ''; ?>">
                                            <a class="page-link" href="?page=<?php echo $i; ?><?php echo isset($_GET['search']) ? '&search=' . urlencode($_GET['search']) : ''; ?><?php echo isset($_GET['search_type']) ? '&search_type=' . urlencode($_GET['search_type']) : ''; ?>">
                                                <?php echo $i; ?>
                                            </a>
                                        </li>
                                    <?php endfor; ?>
                                </ul>
                            </nav>
                            <?php endif; ?>
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
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title">View Complaint</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <strong>Email:</strong>
                        <p id="modal-email" class="mb-2"></p>
                    </div>
                    <div class="mb-3">
                        <strong>Title:</strong>
                        <p id="modal-title" class="mb-2"></p>
                    </div>
                    <div class="mb-3">
                        <strong>Complaint:</strong>
                        <p id="modal-complaint" class="mb-2"></p>
                    </div>
                    <div class="mb-3">
                        <strong>Date:</strong>
                        <p id="modal-date" class="mb-2"></p>
                    </div>
                    <div class="mb-3">
                        <strong>Status:</strong>
                        <p id="modal-status" class="mb-2"></p>
                    </div>
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
            document.getElementById('sidebarCollapse').addEventListener('click', function() {
                document.getElementById('sidebar').classList.toggle('active');
                document.getElementById('content').classList.toggle('active');
            });

            // Add event listeners to all view complaint buttons
            document.querySelectorAll('.view-complaint').forEach(button => {
                button.addEventListener('click', function() {
                    // Get data from button attributes
                    const email = this.getAttribute('data-email');
                    const title = this.getAttribute('data-title');
                    const complaint = this.getAttribute('data-complaint');
                    const date = this.getAttribute('data-date');
                    const status = this.getAttribute('data-status');

                    // Update modal content
                    document.getElementById('modal-email').textContent = email;
                    document.getElementById('modal-title').textContent = title;
                    document.getElementById('modal-complaint').textContent = complaint;
                    document.getElementById('modal-date').textContent = date;
                    document.getElementById('modal-status').textContent = status;
                });
            });
        });
    </script>
</body>
</html>