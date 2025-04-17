<?php
session_start();
if(!isset($_SESSION['is_admin']) || $_SESSION['is_admin'] !== true) {
    header("Location: ../index.php");
    exit();
}

include '../connection.php';

// Fetch admin data
$admin_id = $_SESSION['admin_id'];
$sql = "SELECT * FROM admin WHERE id = ?";
$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "i", $admin_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$admin = mysqli_fetch_assoc($result);

// Handle profile update
if(isset($_POST['action']) && $_POST['action'] == 'update_profile') {
    $username = mysqli_real_escape_string($conn, $_POST['username']);
    $name = mysqli_real_escape_string($conn, $_POST['name']);
    $email = mysqli_real_escape_string($conn, $_POST['email']);
    
    $update_sql = "UPDATE admin SET username = ?, name = ?, email = ? WHERE id = ?";
    $update_stmt = mysqli_prepare($conn, $update_sql);
    mysqli_stmt_bind_param($update_stmt, "sssi", $username, $name, $email, $admin_id);
    
    if(mysqli_stmt_execute($update_stmt)) {
        // Update session variables
        $_SESSION['admin_name'] = $name;
        $_SESSION['admin_email'] = $email;
        echo json_encode(['status' => 'success', 'message' => 'Profile updated successfully']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Failed to update profile']);
    }
    exit();
}

// Handle password change
if(isset($_POST['action']) && $_POST['action'] == 'change_password') {
    $current_password = $_POST['current_password'];
    $new_password = $_POST['new_password'];
    
    // Verify current password
    $verify_sql = "SELECT password FROM admin WHERE id = ?";
    $verify_stmt = mysqli_prepare($conn, $verify_sql);
    mysqli_stmt_bind_param($verify_stmt, "i", $admin_id);
    mysqli_stmt_execute($verify_stmt);
    $verify_result = mysqli_stmt_get_result($verify_stmt);
    $current_hash = mysqli_fetch_assoc($verify_result)['password'];
    
    if(password_verify($current_password, $current_hash)) {
        $new_hash = password_hash($new_password, PASSWORD_DEFAULT);
        $update_sql = "UPDATE admin SET password = ? WHERE id = ?";
        $update_stmt = mysqli_prepare($conn, $update_sql);
        mysqli_stmt_bind_param($update_stmt, "si", $new_hash, $admin_id);
        
        if(mysqli_stmt_execute($update_stmt)) {
            echo json_encode(['status' => 'success', 'message' => 'Password changed successfully']);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Failed to change password']);
        }
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Current password is incorrect']);
    }
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Profile - Admin Panel</title>
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

        /* Profile Specific Styles */
        .profile-card {
            max-width: 800px;
            margin: 0 auto;
        }

        .profile-header {
            background: linear-gradient(to right, #4e73df, #224abe);
            color: white;
            padding: 2rem;
            border-radius: 10px 10px 0 0;
        }

        .profile-form {
            padding: 2rem;
        }

        .form-label {
            font-weight: 600;
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
                <li class="active">
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
                                    <?php echo htmlspecialchars($admin['name']); ?>
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

            <!-- Profile Content -->
            <div class="container-fluid">
                <div class="card profile-card shadow">
                    <div class="profile-header">
                        <div class="text-center">
                            <h1 style="font-size: 2.5rem; margin-bottom: 0;">Admin Profile</h1>
                        </div>
                    </div>
                    <div class="card-body profile-form">
                        <!-- Profile Form -->
                        <form id="profileForm">
                            <div class="row mb-4">
                                <div class="col-md-4">
                                    <div class="mb-3">
                                        <label class="form-label">Username</label>
                                        <input type="text" class="form-control" id="username" value="<?php echo htmlspecialchars($admin['username']); ?>" disabled>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="mb-3">
                                        <label class="form-label">Name</label>
                                        <input type="text" class="form-control" id="name" value="<?php echo htmlspecialchars($admin['name']); ?>" disabled>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="mb-3">
                                        <label class="form-label">Email</label>
                                        <input type="email" class="form-control" id="email" value="<?php echo htmlspecialchars($admin['email']); ?>" disabled>
                                    </div>
                                </div>
                            </div>
                            <div class="text-end mb-4">
                                <button type="button" class="btn btn-primary" id="editProfileBtn">Edit Profile</button>
                                <button type="button" class="btn btn-success d-none" id="saveProfileBtn">Save Changes</button>
                                <button type="button" class="btn btn-danger d-none" id="cancelProfileBtn">Cancel</button>
                            </div>
                        </form>

                        <!-- Password Change Form -->
                        <div class="mt-4">
                            <h4>Change Password</h4>
                            <form id="passwordForm">
                                <div class="mb-3">
                                    <label class="form-label">Current Password</label>
                                    <input type="password" class="form-control" id="currentPassword">
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">New Password</label>
                                    <input type="password" class="form-control" id="newPassword">
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Confirm New Password</label>
                                    <input type="password" class="form-control" id="confirmPassword">
                                </div>
                                <div class="text-end">
                                    <button type="submit" class="btn btn-primary">Change Password</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Sidebar toggle
            const sidebarCollapse = document.getElementById('sidebarCollapse');
            const sidebar = document.getElementById('sidebar');
            const content = document.getElementById('content');

            sidebarCollapse.addEventListener('click', function() {
                sidebar.classList.toggle('active');
                content.classList.toggle('active');
            });

            // Profile Edit Functionality
            const editProfileBtn = document.getElementById('editProfileBtn');
            const saveProfileBtn = document.getElementById('saveProfileBtn');
            const cancelProfileBtn = document.getElementById('cancelProfileBtn');
            const usernameInput = document.getElementById('username');
            const nameInput = document.getElementById('name');
            const emailInput = document.getElementById('email');
            let originalUsername, originalName, originalEmail;

            editProfileBtn.addEventListener('click', function() {
                originalUsername = usernameInput.value;
                originalName = nameInput.value;
                originalEmail = emailInput.value;
                
                usernameInput.disabled = false;
                nameInput.disabled = false;
                emailInput.disabled = false;
                editProfileBtn.classList.add('d-none');
                saveProfileBtn.classList.remove('d-none');
                cancelProfileBtn.classList.remove('d-none');
            });

            cancelProfileBtn.addEventListener('click', function() {
                usernameInput.value = originalUsername;
                nameInput.value = originalName;
                emailInput.value = originalEmail;
                
                usernameInput.disabled = true;
                nameInput.disabled = true;
                emailInput.disabled = true;
                editProfileBtn.classList.remove('d-none');
                saveProfileBtn.classList.add('d-none');
                cancelProfileBtn.classList.add('d-none');
            });

            saveProfileBtn.addEventListener('click', async function() {
                const formData = new FormData();
                formData.append('action', 'update_profile');
                formData.append('username', usernameInput.value);
                formData.append('name', nameInput.value);
                formData.append('email', emailInput.value);

                try {
                    const response = await fetch('profile.php', {
                        method: 'POST',
                        body: formData
                    });
                    const data = await response.json();
                    
                    alert(data.message);
                    if(data.status === 'success') {
                        usernameInput.disabled = true;
                        nameInput.disabled = true;
                        emailInput.disabled = true;
                        editProfileBtn.classList.remove('d-none');
                        saveProfileBtn.classList.add('d-none');
                        cancelProfileBtn.classList.add('d-none');
                        window.location.reload();
                    }
                } catch(error) {
                    alert('An error occurred while updating profile');
                }
            });

            // Add this password validation function after DOMContentLoaded
            function validatePassword(password) {
                // Minimum length check
                if(password.length < 10) {
                    return "Password must be at least 10 characters long";
                }
                
                // Check for uppercase letters
                if(!/[A-Z]/.test(password)) {
                    return "Password must contain at least one uppercase letter";
                }
                
                // Check for lowercase letters
                if(!/[a-z]/.test(password)) {
                    return "Password must contain at least one lowercase letter";
                }
                
                // Check for numbers
                if(!/[0-9]/.test(password)) {
                    return "Password must contain at least one number";
                }
                
                // Check for special characters
                if(!/[!@#$%^&*(),.?":{}|<>]/.test(password)) {
                    return "Password must contain at least one special character";
                }
                
                return "valid";
            }

            // Update the password change event listener
            document.getElementById('passwordForm').addEventListener('submit', async function(e) {
                e.preventDefault();
                
                const currentPassword = document.getElementById('currentPassword').value;
                const newPassword = document.getElementById('newPassword').value;
                const confirmPassword = document.getElementById('confirmPassword').value;

                if(!currentPassword || !newPassword || !confirmPassword) {
                    alert('Please fill in all password fields');
                    return;
                }

                if(newPassword !== confirmPassword) {
                    alert('New passwords do not match');
                    return;
                }

                // Add new password validation
                const validationResult = validatePassword(newPassword);
                if(validationResult !== "valid") {
                    alert(validationResult);
                    return;
                }

                const formData = new FormData();
                formData.append('action', 'change_password');
                formData.append('current_password', currentPassword);
                formData.append('new_password', newPassword);

                try {
                    const response = await fetch('profile.php', {
                        method: 'POST',
                        body: formData
                    });
                    const data = await response.json();
                    
                    alert(data.message);
                    if(data.status === 'success') {
                        this.reset();
                    }
                } catch(error) {
                    alert('An error occurred while changing password');
                }
            });
        });
    </script>
</body>
</html>