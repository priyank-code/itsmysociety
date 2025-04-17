<?php
session_start();

// Add cache control headers
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");

include 'connection.php';

if(isset($_POST['admin_login'])) {
    $username = mysqli_real_escape_string($conn, $_POST['username']);
    $password = mysqli_real_escape_string($conn, $_POST['password']);

    $sql = "SELECT * FROM admin WHERE username = '$username'";
    $result = mysqli_query($conn, $sql);
    
    if(mysqli_num_rows($result) > 0) {
        $admin = mysqli_fetch_assoc($result);
        // Verify password
        if(password_verify($password, $admin['password'])) {
            // Set session variables
            $_SESSION['admin_id'] = $admin['id'];
            $_SESSION['admin_name'] = $admin['name'];
            $_SESSION['is_admin'] = true;
            
            echo "<script>
                alert('Login Successful!');
                window.location.href = 'admin/admin.php';
                </script>";
            exit();
        } else {
            echo "<script>
                alert('Invalid Password!');
                window.location.href = 'index.php';
                </script>";
            exit();
        }
    } else {
        echo "<script>
            alert('Admin not found!');
            window.location.href = 'index.php';
            </script>";
        exit();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="style.css">
</head>
<body class="bg-light">
    <div class="container">
        <div class="row justify-content-center align-items-center min-vh-100">
            <div class="col-md-6 col-lg-4">
                <div class="card shadow-lg border-0">
                    <div class="card-body p-5">
                        <h2 class="text-center mb-4" style="color: #212529;">Admin Login</h2>
                        
                        <form id="adminLoginForm" method="POST" action="admin_login.php">
                            <div class="mb-4">
                                <label for="adminUsername" class="form-label">Username</label>
                                <div class="input-group">
                                    <span class="input-group-text">
                                        <i class="bi bi-person"></i>
                                    </span>
                                    <input type="text" class="form-control" id="adminUsername" name="username" 
                                        placeholder="Enter username" required autocomplete="off">
                                </div>
                            </div>

                            <div class="mb-4">
                                <label for="adminPassword" class="form-label">Password</label>
                                <div class="input-group">
                                    <span class="input-group-text">
                                        <i class="bi bi-lock"></i>
                                    </span>
                                    <input type="password" class="form-control" id="adminPassword" name="password" 
                                        placeholder="Enter password" required autocomplete="new-password">
                                    <button class="btn btn-outline-secondary" type="button" onclick="togglePassword('adminPassword')">
                                        <i class="bi bi-eye"></i>
                                    </button>
                                </div>
                            </div>

                            <button type="submit" name="admin_login" class="btn btn-primary w-100 py-2 mb-3"
                                style="background-color: rgb(26, 160, 144); border-color: rgb(26, 160, 144);">
                                Login
                            </button>
                            
                            <div class="text-center mb-3">
                                <a href="#" data-bs-toggle="modal" data-bs-target="#forgotPasswordModal" 
                                   class="text-primary text-decoration-none">
                                    Forgot Password?
                                </a>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        function togglePassword(inputId) {
            const input = document.getElementById(inputId);
            const icon = input.nextElementSibling.querySelector('i');
            
            if (input.type === 'password') {
                input.type = 'text';
                icon.classList.remove('bi-eye');
                icon.classList.add('bi-eye-slash');
            } else {
                input.type = 'password';
                icon.classList.remove('bi-eye-slash');
                icon.classList.add('bi-eye');
            }
        }
    </script>

    <!-- Forgot Password Modal -->
    <div class="modal fade" id="forgotPasswordModal">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title">Reset Password</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4">
                    <form id="forgotPasswordForm" method="POST" action="handle_forgot_password.php">
                        <div class="mb-4">
                            <label for="resetEmail" class="form-label">Enter Your Email Address</label>
                            <div class="input-group">
                                <span class="input-group-text">
                                    <i class="bi bi-envelope"></i>
                                </span>
                                <input type="email" class="form-control" id="resetEmail" name="email" 
                                       placeholder="Enter your email" required autocomplete="off">
                            </div>
                        </div>
                        <div class="d-grid">
                            <button type="submit" class="btn btn-primary py-2">
                                Reset Password
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</body>
</html> 