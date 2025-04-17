<?php
session_start();
if(!isset($_SESSION['is_admin']) || $_SESSION['is_admin'] !== true) {
    die('Unauthorized access');
}

include '../connection.php';
require '../PHPMailer/PHPMailer.php';
require '../PHPMailer/SMTP.php';
require '../PHPMailer/Exception.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

// Handle Update User
if(isset($_POST['action']) && $_POST['action'] == 'update') {
    $id = $_POST['id'];
    $name = $_POST['name'];
    $email = $_POST['email'];
    $phone = $_POST['phone'];
    $flatno = $_POST['flatno'];
    $familymembers = $_POST['familymembers'];

    $sql = "UPDATE user SET name=?, email=?, phone=?, flatno=?, familymembers=? WHERE id=?";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "sssssi", $name, $email, $phone, $flatno, $familymembers, $id);
    
    if(mysqli_stmt_execute($stmt)) {
        echo json_encode(['status' => 'success']);
    } else {
        echo json_encode(['status' => 'error', 'message' => mysqli_error($conn)]);
    }
    exit;
}

// Delete User
if(isset($_POST['action']) && $_POST['action'] == 'delete') {
    $id = $_POST['id'];

    // Get user email before deletion
    $get_email = "SELECT email FROM user WHERE id = '$id'";
    $email_result = mysqli_query($conn, $get_email);
    $user = mysqli_fetch_assoc($email_result);
    $email = $user['email'];

    // Delete from complaints table using email
    $sql_complaints = "DELETE FROM complaints WHERE user_email = '$email'";
    mysqli_query($conn, $sql_complaints);

    // Delete from maintenance table using user_id
    $sql_maintenance = "DELETE FROM maintenance WHERE user_id = '$id'";
    mysqli_query($conn, $sql_maintenance);

    // Delete from user table
    $sql_user = "DELETE FROM user WHERE id = '$id'";
    if(mysqli_query($conn, $sql_user)) {
        echo json_encode(['status' => 'success']);
    } else {
        echo json_encode(['status' => 'error', 'message' => mysqli_error($conn)]);
    }
    exit;
}

// Handle Status Change
else if(isset($_POST['action']) && $_POST['action'] == 'toggle_status') {
    $id = $_POST['id'];
    $new_status = $_POST['status'];
    
    // First get user email and name
    $user_query = "SELECT name, email FROM user WHERE id = ?";
    $stmt = mysqli_prepare($conn, $user_query);
    mysqli_stmt_bind_param($stmt, "i", $id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $user = mysqli_fetch_assoc($result);
    
    // Update status
    $sql = "UPDATE user SET status = ? WHERE id = ?";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "ii", $new_status, $id);
    
    if(mysqli_stmt_execute($stmt)) {
        // If status is changed to approved (1), send email
        if($new_status == 1) {
            $mail = new PHPMailer(true);
            
            try {
                // Server settings
                $mail->isSMTP();
                $mail->Host = 'smtp.gmail.com';
                $mail->SMTPAuth = true;
                $mail->Username = 'itsmysociety.info@gmail.com'; // Your Gmail
                $mail->Password = 'hzli emki hggs rfts'; // Your App Password
                $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
                $mail->Port = 587;

                // Recipients
                $mail->setFrom('itsmysociety.info@gmail.com', 'Society Management');
                $mail->addAddress($user['email'], $user['name']);

                // Content
                $mail->isHTML(true);
                $mail->Subject = 'Account Approved by Secretary';
                
                $mailContent = "
                    <html>
                    <head>
                        <style>
                            body { font-family: Arial, sans-serif; }
                            .container { padding: 20px; }
                            .header { background-color: #1aa090; color: white; padding: 20px; text-align: center; }
                            .content { padding: 20px; }
                            .footer { background-color: #f5f5f5; padding: 10px; text-align: center; }
                        </style>
                    </head>
                    <body>
                        <div class='container'>
                            <div class='header'>
                                <h2>Account Approved!</h2>
                            </div>
                            <div class='content'>
                                <p>Dear {$user['name']},</p>
                                <p>Congratulations! Your account has been approved by the Society Secretary.</p>
                                <p>You can now log in to your account and access all the features of our society management system.</p>
                                <p>Thank you for your patience!</p>
                                <br>
                                <p>Best Regards,<br>Society Management Team</p>
                            </div>
                            <div class='footer'>
                                <p>This is an automated message. Please do not reply.</p>
                            </div>
                        </div>
                    </body>
                    </html>
                ";
                
                $mail->Body = $mailContent;
                $mail->AltBody = "Dear {$user['name']}, Your account has been approved by the Society Secretary. You can now log in to your account.";

                $mail->send();
                echo json_encode(['status' => 'success', 'emailSent' => true]);
            } catch (Exception $e) {
                echo json_encode(['status' => 'success', 'emailSent' => false, 'emailError' => $mail->ErrorInfo]);
            }
        } else {
            echo json_encode(['status' => 'success']);
        }
    } else {
        echo json_encode(['status' => 'error', 'message' => mysqli_error($conn)]);
    }
}

// Add User
if(isset($_POST['action']) && $_POST['action'] == 'add') {
    $name = mysqli_real_escape_string($conn, $_POST['name']);
    $email = mysqli_real_escape_string($conn, $_POST['email']);
    $phone = mysqli_real_escape_string($conn, $_POST['phone']);
    $flatno = mysqli_real_escape_string($conn, $_POST['flatno']);
    $familymembers = mysqli_real_escape_string($conn, $_POST['familymembers']);
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
    $status = 0;

    // Check if email already exists
    $check_email = "SELECT * FROM user WHERE email = ?";
    $stmt = mysqli_prepare($conn, $check_email);
    mysqli_stmt_bind_param($stmt, "s", $email);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);

    if(mysqli_num_rows($result) > 0) {
        echo json_encode(['status' => 'error', 'message' => 'Email already exists!']);
    } else {
        $sql = "INSERT INTO user (name, email, phone, flatno, familymembers, password, status) 
                VALUES (?, ?, ?, ?, ?, ?, ?)";
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, "ssssssi", $name, $email, $phone, $flatno, $familymembers, $password, $status);
        
        if(mysqli_stmt_execute($stmt)) {
            echo json_encode(['status' => 'success']);
        } else {
            echo json_encode(['status' => 'error', 'message' => mysqli_error($conn)]);
        }
    }
    exit;
}
?>
