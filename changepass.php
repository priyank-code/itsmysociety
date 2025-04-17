<?php
session_start();
include 'connection.php';
require 'PHPMailer/PHPMailer.php';
require 'PHPMailer/SMTP.php';
require 'PHPMailer/Exception.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Password</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="container mt-5">
        <div class="row justify-content-center">
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header bg-primary text-white text-center" style="background-color: rgb(26, 160, 144) !important;">
                        <h4>Reset Password</h4>
                    </div>
                    <div class="card-body">
                        <?php if(!isset($_GET['email'])): ?>
                        <!-- Email Form -->
                        <form method="POST" class="form-group">
                            <div class="form-input mb-4">
                                <label for="email" class="form-label fs-6 text-uppercase fw-bold text-black">Email</label>
                                <input type="email" name="email" id="email" class="form-control ps-3" placeholder="Enter your email" required>
                            </div>
                            <div class="d-grid gap-2">
                                <button type="submit" name="send_reset_link" class="btn btn-primary btn-lg text-uppercase" style="background-color: rgb(26, 160, 144); border-color: rgb(26, 160, 144);">
                                    Send Reset Link
                                </button>
                                <a href="index.php" class="btn btn-secondary btn-lg text-uppercase">Back to Login</a>
                            </div>
                        </form>
                        <?php else: ?>
                        <!-- Reset Password Form -->
                        <form id="resetPasswordForm" method="POST" class="form-group" novalidate>
                            <input type="hidden" name="email" value="<?php echo $_GET['email']; ?>">
                            
                            <div class="form-input mb-4">
                                <label for="newPassword" class="form-label fs-6 text-uppercase fw-bold text-black">New Password</label>
                                <div class="input-group">
                                    <input type="password" 
                                           name="newPassword" 
                                           id="newPassword" 
                                           class="form-control ps-3" 
                                           placeholder="Enter new password"
                                           pattern="^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&])[A-Za-z\d@$!%*?&]{10,}$"
                                           required>
                                    <button class="btn btn-outline-secondary" type="button" onclick="togglePassword('newPassword')">
                                        <i class="bi bi-eye"></i>
                                    </button>
                                </div>
                                <div class="password-requirements small text-muted mt-1">
                                    Password must contain:
                                    <ul>
                                        <li>At least 10 characters</li>
                                        <li>One uppercase letter</li>
                                        <li>One lowercase letter</li>
                                        <li>One number</li>
                                        <li>One special character (@$!%*?&)</li>
                                    </ul>
                                </div>
                            </div>

                            <div class="form-input mb-4">
                                <label for="confirmPassword" class="form-label fs-6 text-uppercase fw-bold text-black">Confirm Password</label>
                                <div class="input-group">
                                    <input type="password" 
                                           name="confirmPassword" 
                                           id="confirmPassword" 
                                           class="form-control ps-3" 
                                           placeholder="Confirm new password"
                                           required>
                                    <button class="btn btn-outline-secondary" type="button" onclick="togglePassword('confirmPassword')">
                                        <i class="bi bi-eye"></i>
                                    </button>
                                </div>
                                <div class="invalid-feedback">Passwords do not match</div>
                            </div>

                            <div class="d-grid gap-2">
                                <button type="submit" name="reset_password" class="btn btn-primary btn-lg text-uppercase" style="background-color: rgb(26, 160, 144); border-color: rgb(26, 160, 144);">
                                    Reset Password
                                </button>
                            </div>
                        </form>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
    function togglePassword(inputId) {
        const input = document.getElementById(inputId);
        const button = input.nextElementSibling;
        const icon = button.querySelector('i');
        
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

    document.addEventListener('DOMContentLoaded', function() {
        const form = document.getElementById('resetPasswordForm');
        if(form) {
            const newPassword = document.getElementById('newPassword');
            const confirmPassword = document.getElementById('confirmPassword');

            form.addEventListener('submit', function(event) {
                let isValid = true;

                // Password pattern validation
                const passwordPattern = /^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&])[A-Za-z\d@$!%*?&]{10,}$/;
                if (!passwordPattern.test(newPassword.value)) {
                    isValid = false;
                    newPassword.classList.add('is-invalid');
                } else {
                    newPassword.classList.remove('is-invalid');
                }

                // Password match validation
                if (newPassword.value !== confirmPassword.value) {
                    isValid = false;
                    confirmPassword.classList.add('is-invalid');
                } else {
                    confirmPassword.classList.remove('is-invalid');
                }

                if (!isValid) {
                    event.preventDefault();
                }
            });
        }
    });
    </script>
</body>
</html>

<?php
// Handle send reset link
if(isset($_POST['send_reset_link'])) {
    $email = mysqli_real_escape_string($conn, $_POST['email']);
    
    // Check if email exists
    $sql = "SELECT * FROM user WHERE email = ?";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "s", $email);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    if(mysqli_num_rows($result) > 0) {
        $user = mysqli_fetch_assoc($result);
        
        // Send email with reset link
        $mail = new PHPMailer(true);
        
        try {
            // Server settings
            $mail->isSMTP();
            $mail->Host = 'smtp.gmail.com';
            $mail->SMTPAuth = true;
            $mail->Username = 'your_mail@gmail.com';  // Add your email
            $mail->Password = 'your_app_password';   // Add app password
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port = 587;

            // Recipients
            $mail->setFrom('your_mail@gmail.com', 'Society Management');
            $mail->addAddress($email, $user['name']);

            // Content
            $reset_link = "http://" . $_SERVER['HTTP_HOST'] . "/IMS/changepass.php?email=" . urlencode($email);
            
            $mail->isHTML(true);
            $mail->Subject = 'Reset Your Password - Society Management';
            
            $mailContent = "
                <html>
                <head>
                    <style>
                        body { font-family: Arial, sans-serif; }
                        .container { padding: 20px; }
                        .header { background-color: #1aa090; color: white; padding: 20px; text-align: center; }
                        .content { padding: 20px; }
                        .button { 
                            background-color: #1aa090; 
                            color: white; 
                            padding: 10px 20px; 
                            text-decoration: none; 
                            border-radius: 5px; 
                            display: inline-block; 
                        }
                        .footer { background-color: #f5f5f5; padding: 10px; text-align: center; }
                    </style>
                </head>
                <body>
                    <div class='container'>
                        <div class='header'>
                            <h2>Reset Your Password</h2>
                        </div>
                        <div class='content'>
                            <p>Dear {$user['name']},</p>
                            <p>Click the button below to reset your password:</p>
                            <p style='text-align: center;'>
                                <a href='{$reset_link}' class='button'>Reset Password</a>
                            </p>
                            <p>If you didn't request this, please ignore this email.</p>
                        </div>
                        <div class='footer'>
                            <p>This is an automated message. Please do not reply.</p>
                        </div>
                    </div>
                </body>
                </html>
            ";
            
            $mail->Body = $mailContent;
            $mail->AltBody = "Reset your password: {$reset_link}";

            $mail->send();
            echo "<script>alert('Password reset link has been sent to your email!');</script>";
        } catch (Exception $e) {
            echo "<script>alert('Error sending email. Please try again later.');</script>";
        }
    } else {
        echo "<script>alert('Email not found!');</script>";
    }
}

// Handle password reset
if(isset($_POST['reset_password'])) {
    $email = $_POST['email'];
    $newPassword = $_POST['newPassword'];
    $confirmPassword = $_POST['confirmPassword'];

    if($newPassword !== $confirmPassword) {
        echo "<script>alert('Passwords do not match!');</script>";
        exit();
    }

    // Update password
    $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
    $update_sql = "UPDATE user SET password = ? WHERE email = ?";
    $stmt = mysqli_prepare($conn, $update_sql);
    mysqli_stmt_bind_param($stmt, "ss", $hashedPassword, $email);

    
    
    if(mysqli_stmt_execute($stmt)) {
        echo "<script>
            alert('Password changed successfully!');
            window.location.href = 'index.php';
            </script>";
    } else {
        echo "<script>alert('Error updating password!');</script>";
    }
}
?>
