<?php
include 'connection.php';
include 'email_config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = mysqli_real_escape_string($conn, $_POST['email']);
    
    // Check if email exists in admin table
    $check_query = "SELECT * FROM admin WHERE email = ?";
    $stmt = mysqli_prepare($conn, $check_query);
    mysqli_stmt_bind_param($stmt, "s", $email);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    if(mysqli_num_rows($result) > 0) {
        $admin = mysqli_fetch_assoc($result);
        
        // Generate new password
        $new_password = substr(str_shuffle("0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ"), 0, 10);
        $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
        
        // Update password in database
        $update_query = "UPDATE admin SET password = ? WHERE email = ?";
        $stmt = mysqli_prepare($conn, $update_query);
        mysqli_stmt_bind_param($stmt, "ss", $hashed_password, $email);
        
        if(mysqli_stmt_execute($stmt)) {
            // Send password reset email
            $subject = "Password Reset";
            $message = "Hello " . $admin['name'] . ",<br><br>";
            $message .= "Your password has been reset. Your new password is: <b>" . $new_password . "</b><br><br>";
            $message .= "Please login with this password and change it immediately.<br><br>";
            $message .= "Best regards,<br>Admin Team";
            
            if(sendEmail($email, $subject, $message)) {
                echo "<script>
                    alert('New password has been sent to your email.');
                    window.location.href = 'admin_login.php';
                    </script>";
            } else {
                echo "<script>
                    alert('Error sending email. Please try again.');
                    window.location.href = 'admin_login.php';
                    </script>";
            }
        } else {
            echo "<script>
                alert('Error resetting password. Please try again.');
                window.location.href = 'admin_login.php';
                </script>";
        }
    } else {
        echo "<script>
            alert('Email not found!');
            window.location.href = 'admin_login.php';
            </script>";
    }
}
?> 