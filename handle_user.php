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

// Handle Status Change
if(isset($_POST['action']) && $_POST['action'] == 'toggle_status') {
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
                $mail->Username = 'your_mail@gmail.com'; // Your Gmail
                $mail->Password = 'your_app_password'; // Your App Password
                $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
                $mail->Port = 587;

                // Recipients
                $mail->setFrom('your_mail@gmail.com', 'Society Management');
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
    exit;
}
?> 