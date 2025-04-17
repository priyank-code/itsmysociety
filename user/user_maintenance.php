<?php
session_start();
include('../connection.php');

// Check if user is logged in
if(!isset($_SESSION['user_id'])) {
    header("Location: ../index.php");
    exit();
}

// Handle reference code update
if(isset($_POST['save_reference'])) {
    $request_id = $_POST['request_id'];
    $reference_code = $_POST['reference_code'];
    
    // Update both reference_code and user_confirmation
    $update_query = "UPDATE maintenance SET reference_code = ?, user_confirmation = 'success' WHERE id = ? AND user_id = ?";
    $stmt = mysqli_prepare($conn, $update_query);
    mysqli_stmt_bind_param($stmt, "sii", $reference_code, $request_id, $_SESSION['user_id']);
    mysqli_stmt_execute($stmt);
    
    header("Location: user_maintenance.php");
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

// Get user's maintenance requests
$maintenance_query = "SELECT * FROM maintenance WHERE user_id = ? ORDER BY request_date DESC";
$stmt = mysqli_prepare($conn, $maintenance_query);
mysqli_stmt_bind_param($stmt, "i", $user_id);
mysqli_stmt_execute($stmt);
$maintenance_result = mysqli_stmt_get_result($stmt);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Maintenance - User</title>
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

        /* Content Styles */
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

        /* Card Styles */
        .maintenance-card {
            border: none;
            border-radius: 10px;
            box-shadow: 0 0 15px rgba(0,0,0,0.1);
            margin-bottom: 20px;
        }

        .maintenance-card .card-header {
            background-color: #f8f9fc;
            border-bottom: 1px solid #e3e6f0;
            padding: 1rem;
        }

        .maintenance-card .card-header h5 {
            color: #4e73df;
            font-weight: 600;
            margin: 0;
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

        .qr-container {
            padding: 8px;
            background-color: #fff;
            border-radius: 8px;
            display: inline-block;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }

        .qr-container img {
            transition: transform 0.3s ease;
        }

        .qr-container img:hover {
            transform: scale(1.05);
        }

        .copy-alert {
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 1050;
            animation: fadeInOut 2s ease-in-out;
        }

        @keyframes fadeInOut {
            0% { opacity: 0; transform: translateY(-20px); }
            15% { opacity: 1; transform: translateY(0); }
            85% { opacity: 1; transform: translateY(0); }
            100% { opacity: 0; transform: translateY(-20px); }
        }

        .upi-details {
            padding-left: 0;
        }

        .upi-details .input-group {
            max-width: 300px;
        }

        @media (max-width: 575px) {
            .qr-container {
                margin-bottom: 20px;
            }
            
            .qr-container img {
                max-width: 100px;
            }

            .upi-details {
                text-align: center;
            }

            .upi-details .input-group {
                margin: 0 auto;
            }
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
                <li>
                    <a href="notices.php">
                        <i class="fas fa-bell me-2"></i>Notices
                    </a>
                </li>
                <li class="active">
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
                                <li><a class="dropdown-item" href="profile.php"><i class="fas fa-user me-2"></i>Profile</a></li>
                                <li><hr class="dropdown-divider"></li>
                                <li><a class="dropdown-item" href="../logout.php"><i class="fas fa-sign-out-alt me-2"></i>Logout</a></li>
                            </ul>
                        </div>
                    </div>
                </div>
            </nav>

            <!-- Main Content -->
            <div class="container-fluid">
                <div class="row mb-4">
                    <div class="col-md-8">
                        <div class="card maintenance-card">
                            <div class="card-header d-flex justify-content-between align-items-center">
                                <h5 class="card-title mb-0">Payment Details</h5>
                                <span class="badge bg-primary">UPI Payment</span>
                            </div>
                            <div class="card-body">
                                <div class="row align-items-center">
                                    <!-- QR Code Column -->
                                    <div class="col-sm-4 text-center mb-3 mb-sm-0">
                                        <div class="qr-container">
                                            <img src="../img/qr.png" alt="Payment QR Code" class="img-fluid" style="max-width: 120px; border-radius: 8px;">
                                        </div>
                                    </div>
                                    <!-- UPI Details Column -->
                                    <div class="col-sm-8">
                                        <div class="upi-details">
                                            <p class="mb-2"><strong>UPI ID:</strong></p>
                                            <div class="input-group" style="max-width: 300px;">
                                                <input type="text" class="form-control" value="your_society@upi" id="upiId" readonly>
                                                <button class="btn btn-outline-primary" type="button" onclick="copyUPI()">
                                                    <i class="fas fa-copy"></i>
                                                </button>
                                            </div>
                                            <div class="alert alert-success copy-alert mt-3" style="display: none;" role="alert">
                                                <i class="fas fa-check-circle me-2"></i>UPI ID copied successfully!
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <?php if(isset($_SESSION['success_msg'])): ?>
                    <div class="alert alert-success"><?php echo $_SESSION['success_msg']; unset($_SESSION['success_msg']); ?></div>
                <?php endif; ?>

                <?php if(isset($_SESSION['error_msg'])): ?>
                    <div class="alert alert-danger"><?php echo $_SESSION['error_msg']; unset($_SESSION['error_msg']); ?></div>
                <?php endif; ?>

                <!-- Maintenance Requests -->
                <div class="card maintenance-card">
                    <div class="card-header">
                        <h5 class="card-title">My Maintenance Requests</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-bordered">
                                <thead>
                                    <tr>
                                        <th>Amount</th>
                                        <th>Request Date</th>
                                        <th>Secretary Status</th>
                                        <th>Payment Status</th>
                                        <th>Reference Code</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    $requests_query = "SELECT * FROM maintenance WHERE user_id = ? ORDER BY request_date DESC";
                                    $stmt = mysqli_prepare($conn, $requests_query);
                                    mysqli_stmt_bind_param($stmt, "i", $user_id);
                                    mysqli_stmt_execute($stmt);
                                    $requests_result = mysqli_stmt_get_result($stmt);
                                    
                                    while($request = mysqli_fetch_assoc($requests_result)):
                                    ?>
                                    <tr>
                                        <td>₹<?php echo number_format($request['amount'], 2); ?></td>
                                        <td><?php echo date('d-m-Y', strtotime($request['request_date'])); ?></td>
                                        <td>
                                            <span class="badge <?php 
                                                if($request['secretary_status'] == 'approved') echo 'bg-success';
                                                elseif($request['secretary_status'] == 'rejected') echo 'bg-danger';
                                                else echo 'bg-warning';
                                            ?>">
                                                <?php echo ucfirst($request['secretary_status']); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <span class="badge <?php echo $request['user_confirmation'] == 'success' ? 'bg-success' : 'bg-warning'; ?>">
                                                <?php echo ucfirst($request['user_confirmation']); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <input type="text" class="form-control form-control-sm reference-input" 
                                                   id="reference_<?php echo $request['id']; ?>"
                                                   value="<?php echo htmlspecialchars($request['reference_code'] ?? ''); ?>"
                                                   <?php if($request['reference_code']): ?>readonly<?php endif; ?>
                                                   placeholder="Enter reference code" maxlength="14">
                                        </td>
                                        <td>
                                            <div class="btn-group">
                                                <form method="POST" class="d-inline">
                                                    <input type="hidden" name="request_id" value="<?php echo $request['id']; ?>">
                                                    <input type="hidden" name="reference_code" id="hidden_reference_<?php echo $request['id']; ?>" maxlength="16">
                                                    <button type="submit" name="save_reference" class="btn btn-success btn-sm save-btn" 
                                                            onclick="updateHiddenField(<?php echo $request['id']; ?>)" 
                                                            <?php if($request['reference_code']): ?>disabled<?php endif; ?>>
                                                        <i class="fas fa-save me-1"></i>Save
                                                    </button>
                                                </form>
                                                <button type="button" class="btn btn-primary btn-sm ms-1 edit-btn"
                                                        onclick="enableEdit(<?php echo $request['id']; ?>)"
                                                        <?php if(!$request['reference_code']): ?>disabled<?php endif; ?>>
                                                    <i class="fas fa-edit me-1"></i>Edit
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
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
            // Handle sidebar toggle
            document.getElementById('sidebarCollapse').addEventListener('click', function() {
                document.getElementById('sidebar').classList.toggle('active');
                document.getElementById('content').classList.toggle('active');
                this.classList.toggle('active');
            });

            // Handle reference code inputs and save buttons
            const referenceInputs = document.querySelectorAll('.reference-input');
            referenceInputs.forEach(input => {
                const requestId = input.id.split('_')[1];
                const saveBtn = input.closest('tr').querySelector('.save-btn');
                
                // Add input event listener
                input.addEventListener('input', function() {
                    if(!input.readOnly) {
                        saveBtn.disabled = !this.value.trim();
                    }
                });
            });
        });

        function updateHiddenField(requestId) {
            const referenceInput = document.getElementById('reference_' + requestId);
            const hiddenInput = document.getElementById('hidden_reference_' + requestId);
            hiddenInput.value = referenceInput.value;
        }

        function enableEdit(requestId) {
            const referenceInput = document.getElementById('reference_' + requestId);
            const saveBtn = referenceInput.closest('tr').querySelector('.save-btn');
            const editBtn = referenceInput.closest('tr').querySelector('.edit-btn');
            
            referenceInput.readOnly = false;
            referenceInput.focus();
            saveBtn.disabled = !referenceInput.value.trim();
            editBtn.disabled = true;
        }

        function copyUPI() {
            var upiId = document.getElementById("upiId");
            upiId.select();
            upiId.setSelectionRange(0, 99999);
            document.execCommand("copy");
            
            var alert = document.querySelector('.copy-alert');
            alert.style.display = 'block';
            
            setTimeout(function() {
                alert.style.display = 'none';
            }, 2000);
        }
    </script>
</body>
</html>
