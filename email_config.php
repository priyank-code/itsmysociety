<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'PHPMailer/Exception.php';
require 'PHPMailer/PHPMailer.php';
require 'PHPMailer/SMTP.php';

function sendEmail($to, $subject, $message) {
    $mail = new PHPMailer(true);

    try {
        // Server settings
        $mail->SMTPDebug = 0;
        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com';
        $mail->SMTPAuth = true;
        $mail->Username = 'your_mail@gmail.com'; // Add your email
        $mail->Password = 'your_app_password'; // Add app password
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
        $mail->Port = 465;
        $mail->CharSet = 'UTF-8';                   // Set character encoding
        $mail->Encoding = 'base64';                 // Set email encoding
        
        // Recipients
        $mail->setFrom('your_mail@gmail.com', 'ITS My Society');  // Changed sender name
        $mail->addReplyTo('your_mail@gmail.com', 'ITS My Society Support');  // Added reply-to
        $mail->addAddress($to);

        // Email Settings
        $mail->Priority = 1;                        // Set High priority
        $mail->isHTML(true);
        $mail->Subject = $subject;
        
        // Add email template styling
        $message = '
        <div style="font-family: Arial, sans-serif; color: #333333; max-width: 600px; margin: 0 auto;">
            <div style="background-color: #f8f9fa; padding: 20px; text-align: center;">
                <h2 style="color: #1a73e8;">ITS My Society</h2>
            </div>
            <div style="padding: 20px; background-color: #ffffff; border-radius: 5px; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">
                ' . $message . '
            </div>
            <div style="text-align: center; margin-top: 20px; color: #666666; font-size: 12px;">
                <p>This is an automated email, please do not reply directly to this email.</p>
                <p>© ' . date('Y') . ' ITS My Society. All rights reserved.</p>
            </div>
        </div>';

        $mail->Body = $message;
        $mail->AltBody = strip_tags($message);

        $mail->send();
        return true;
    } catch (Exception $e) {
        error_log("Mailer Error: " . $mail->ErrorInfo);
        return false;
    }
}
?> 