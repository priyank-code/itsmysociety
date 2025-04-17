<?php
session_start();
include('../connection.php');

// Check if admin is logged in
if(!isset($_SESSION['is_admin']) || $_SESSION['is_admin'] !== true) {
    header("Location: ../index.php");
    exit();
}

if(!isset($_SESSION['admin_id'])) {
    die("Error: Admin ID not found in session. Please log out and log in again.");
}

// Handle individual payment request
if(isset($_POST['send_individual_request'])) {
    $user_id = $_POST['user_id'];
    $amount = floatval($_POST['amount']);
    
    $insert_query = "INSERT INTO maintenance (user_id, amount) VALUES (?, ?)";
    $stmt = mysqli_prepare($conn, $insert_query);
    mysqli_stmt_bind_param($stmt, "id", $user_id, $amount);
    
    if(mysqli_stmt_execute($stmt)) {
        $_SESSION['success_message'] = "Payment request sent successfully!";
    } else {
        $_SESSION['error_message'] = "Error sending request: " . mysqli_error($conn);
    }
    header("Location: maintenance.php");
    exit();
}

// Handle bulk payment requests
if(isset($_POST['send_bulk_requests'])) {
    $amount = floatval($_POST['amount']);
    $selected_users = isset($_POST['selected_users']) ? $_POST['selected_users'] : [];
    
    if(empty($selected_users)) {
        $_SESSION['error_message'] = "Please select at least one user.";
    } else {
        $success_count = 0;
        foreach($selected_users as $user_id) {
            $insert_query = "INSERT INTO maintenance (user_id, amount) VALUES (?, ?)";
            $stmt = mysqli_prepare($conn, $insert_query);
            mysqli_stmt_bind_param($stmt, "id", $user_id, $amount);
            
            if(mysqli_stmt_execute($stmt)) {
                $success_count++;
            }
        }
        $_SESSION['success_message'] = "Payment requests sent successfully to {$success_count} users!";
    }
    header("Location: maintenance.php");
    exit();
}

// Handle secretary status update
if(isset($_POST['update_secretary_status'])) {
    $request_id = $_POST['request_id'];
    $status = $_POST['secretary_status'];
    
    $update_query = "UPDATE maintenance SET secretary_status = ? WHERE id = ?";
    $stmt = mysqli_prepare($conn, $update_query);
    mysqli_stmt_bind_param($stmt, "si", $status, $request_id);
    
    if(mysqli_stmt_execute($stmt)) {
        $_SESSION['success_message'] = "Status updated successfully!";
    } else {
        $_SESSION['error_message'] = "Error updating status: " . mysqli_error($conn);
    }
    
    mysqli_stmt_close($stmt);
    header("Location: maintenance.php");
    exit();
}

// Handle approve request
if(isset($_POST['approve'])) {
    $request_id = $_POST['request_id'];
    
    $update_query = "UPDATE maintenance SET secretary_status = 'approved' WHERE id = ?";
    $stmt = mysqli_prepare($conn, $update_query);
    mysqli_stmt_bind_param($stmt, "i", $request_id);
    
    if(mysqli_stmt_execute($stmt)) {
        $_SESSION['success_message'] = "Request approved successfully!";
    } else {
        $_SESSION['error_message'] = "Error approving request: " . mysqli_error($conn);
    }
    
    mysqli_stmt_close($stmt);
    header("Location: maintenance.php");
    exit();
}

// Handle pending request
if(isset($_POST['pending'])) {
    $request_id = $_POST['request_id'];
    
    $update_query = "UPDATE maintenance SET secretary_status = 'pending' WHERE id = ?";
    $stmt = mysqli_prepare($conn, $update_query);
    mysqli_stmt_bind_param($stmt, "i", $request_id);
    
    if(mysqli_stmt_execute($stmt)) {
        $_SESSION['success_message'] = "Request marked as pending successfully!";
    } else {
        $_SESSION['error_message'] = "Error marking request as pending: " . mysqli_error($conn);
    }
    
    mysqli_stmt_close($stmt);
    header("Location: maintenance.php");
    exit();
}

// Get all active users
$users_query = "SELECT id, name, email, flatno FROM user WHERE status = 1";
$users_result = mysqli_query($conn, $users_query);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Maintenance Panel - Admin</title>
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
                <li>
                    <a href="notice.php">
                        <i class="fas fa-bullhorn me-2"></i>
                        Notices
                    </a>
                </li>
                <li class="active">
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

            <div class="container-fluid">
                <?php if(isset($_SESSION['success_message'])): ?>
                    <div class="alert alert-success"><?php echo $_SESSION['success_message']; unset($_SESSION['success_message']); ?></div>
                <?php endif; ?>

                <?php if(isset($_SESSION['error_message'])): ?>
                    <div class="alert alert-danger"><?php echo $_SESSION['error_message']; unset($_SESSION['error_message']); ?></div>
                <?php endif; ?>

                <!-- Individual Payment Request Form -->
                <div class="card shadow mb-4">
                    <div class="card-header py-3">
                        <h6 class="m-0 font-weight-bold text-primary">Send Individual Payment Request</h6>
                    </div>
                    <div class="card-body">
                        <form method="POST" action="">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group mb-3">
                                        <label for="user_id">Select User</label>
                                        <select class="form-control" id="user_id" name="user_id" required>
                                            <option value="">Select User</option>
                                            <?php while($user = mysqli_fetch_assoc($users_result)): ?>
                                                <option value="<?php echo $user['id']; ?>">
                                                    <?php echo htmlspecialchars($user['name']); ?> - 
                                                    Flat: <?php echo htmlspecialchars($user['flatno']); ?>
                                                </option>
                                            <?php endwhile; ?>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group mb-3">
                                        <label for="amount">Amount (₹)</label>
                                        <input type="number" class="form-control" id="amount" name="amount" step="0.01" required>
                                    </div>
                                </div>
                                <div class="col-md-2">
                                    <button type="submit" name="send_individual_request" class="btn btn-primary mt-4">
                                        <i class="fas fa-paper-plane"></i> Send Request
                                    </button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Bulk Payment Request Form -->
                <div class="card shadow mb-4">
                    <div class="card-header py-3">
                        <h6 class="m-0 font-weight-bold text-primary">Send Bulk Payment Requests</h6>
                    </div>
                    <div class="card-body">
                        <form method="POST" action="">
                            <div class="table-responsive mb-3">
                                <table class="table table-bordered">
                                    <thead>
                                        <tr>
                                            <th>
                                                <input type="checkbox" id="select_all" onclick="toggleAllUsers()">
                                            </th>
                                            <th>Name</th>
                                            <th>Email</th>
                                            <th>Flat No</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php 
                                        mysqli_data_seek($users_result, 0);
                                        while($user = mysqli_fetch_assoc($users_result)): 
                                        ?>
                                        <tr>
                                            <td>
                                                <input type="checkbox" name="selected_users[]" value="<?php echo $user['id']; ?>" class="user-checkbox">
                                            </td>
                                            <td><?php echo htmlspecialchars($user['name']); ?></td>
                                            <td><?php echo htmlspecialchars($user['email']); ?></td>
                                            <td><?php echo htmlspecialchars($user['flatno']); ?></td>
                                        </tr>
                                        <?php endwhile; ?>
                                    </tbody>
                                </table>
                            </div>
                            <div class="row">
                                <div class="col-md-4">
                                    <div class="form-group mb-3">
                                        <label for="bulk_amount">Amount (₹)</label>
                                        <input type="number" class="form-control" id="bulk_amount" name="amount" step="0.01" required>
                                    </div>
                                </div>
                                <div class="col-md-2">
                                    <button type="submit" name="send_bulk_requests" class="btn btn-primary mt-4">
                                        <i class="fas fa-paper-plane"></i> Send to Selected Users
                                    </button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Payment Requests List -->
                <div class="card shadow mb-4">
                    <div class="card-header py-3">
                        <h6 class="m-0 font-weight-bold text-primary">Pending Payment Requests</h6>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-bordered">
                                <thead>
                                    <tr>
                                        <th>User Name</th>
                                        <th>Amount</th>
                                        <th>Request Date</th>
                                        <th>Reference Code</th>
                                        <th>Secretary Status</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    $maintenance_query = "SELECT m.*, r.name 
                                                        FROM maintenance m 
                                                        JOIN user r ON m.user_id = r.id 
                                                        WHERE m.secretary_status = 'pending'
                                                        ORDER BY m.request_date DESC";
                                    $maintenance_result = mysqli_query($conn, $maintenance_query);
                                    while($row = mysqli_fetch_assoc($maintenance_result)):
                                    ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($row['name']); ?></td>
                                        <td>₹<?php echo number_format($row['amount'], 2); ?></td>
                                        <td><?php echo date('d-m-Y', strtotime($row['request_date'])); ?></td>
                                        <td>
                                            <?php if($row['reference_code']): ?>
                                                <?php echo htmlspecialchars($row['reference_code']); ?>
                                            <?php else: ?>
                                                <span class="text-muted">Not Available</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <span class="badge bg-warning">
                                                <?php echo ucfirst($row['secretary_status']); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <form method="post">
                                                <input type="hidden" name="request_id" value="<?php echo $row['id']; ?>">
                                                <button type="submit" name="approve" class="btn btn-success btn-sm" 
                                                        <?php if(!$row['reference_code']): ?>disabled<?php endif; ?>>
                                                    <i class="fas fa-check me-1"></i>Approve
                                                </button>
                                            </form>
                                        </td>
                                    </tr>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- View Approved Payments Button -->
                <div class="text-end mb-4">
                    <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#approvedPaymentsModal">
                        View Approved Payments
                    </button>
                </div>

                <!-- Approved Payments Modal -->
                <div class="modal fade" id="approvedPaymentsModal" tabindex="-1">
                    <div class="modal-dialog modal-lg">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title">Approved Payments</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                            </div>
                            <div class="modal-body">
                                <div class="table-responsive">
                                    <table class="table table-bordered">
                                        <thead>
                                            <tr>
                                                <th>User Name</th>
                                                <th>Amount</th>
                                                <th>Request Date</th>
                                                <th>Reference Code</th>
                                                <th>Action</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php
                                            $approved_query = "SELECT m.*, r.name 
                                                             FROM maintenance m 
                                                             JOIN user r ON m.user_id = r.id 
                                                             WHERE m.secretary_status = 'approved'
                                                             ORDER BY m.request_date DESC";
                                            $approved_result = mysqli_query($conn, $approved_query);
                                            while($row = mysqli_fetch_assoc($approved_result)):
                                            ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($row['name']); ?></td>
                                                <td>₹<?php echo number_format($row['amount'], 2); ?></td>
                                                <td><?php echo date('d-m-Y', strtotime($row['request_date'])); ?></td>
                                                <td><?php echo htmlspecialchars($row['reference_code']); ?></td>
                                                <td>
                                                    <form method="post">
                                                        <input type="hidden" name="request_id" value="<?php echo $row['id']; ?>">
                                                        <button type="submit" name="pending" class="btn btn-warning btn-sm">
                                                            <i class="fas fa-clock me-1"></i>Mark Pending
                                                        </button>
                                                    </form>
                                                </td>
                                            </tr>
                                            <?php endwhile; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="overlay"></div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Sidebar toggle
            document.getElementById('sidebarCollapse').addEventListener('click', function() {
                document.getElementById('sidebar').classList.toggle('active');
                document.getElementById('content').classList.toggle('active');
                document.querySelector('.overlay').classList.toggle('active');
            });

            // Close sidebar when clicking overlay
            document.querySelector('.overlay').addEventListener('click', function() {
                document.getElementById('sidebar').classList.remove('active');
                document.getElementById('content').classList.remove('active');
                this.classList.remove('active');
            });
        });

        function toggleAllUsers() {
            const mainCheckbox = document.getElementById('select_all');
            const userCheckboxes = document.getElementsByClassName('user-checkbox');
            
            for(let checkbox of userCheckboxes) {
                checkbox.checked = mainCheckbox.checked;
            }
        }
    </script>
</body>
</html>
