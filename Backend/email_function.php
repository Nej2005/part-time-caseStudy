<?php
// Include PHPMailer classes manually
require_once 'path/to/PHPMailer/src/PHPMailer.php';
require_once 'path/to/PHPMailer/src/SMTP.php';
require_once 'path/to/PHPMailer/src/Exception.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

function sendSecurityAlertEmail($email, $resetLink)
{
    // First validate the email domain
    $allowedDomains = ['gmail.com', 'plpasig.edu.ph'];
    $emailParts = explode('@', $email);

    if (count($emailParts) !== 2 || !in_array(strtolower($emailParts[1]), $allowedDomains)) {
        error_log("Email sending failed: Invalid domain for email $email");
        return false;
    }

    $mail = new PHPMailer(true); // Passing `true` enables exceptions

    try {
        // Server settings
        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com'; // Your SMTP server
        $mail->SMTPAuth = true;
        $mail->Username = 'parttimesystemplp@gmail.com'; // SMTP username
        $mail->Password = 'dwxs yqrb zxzy kemf'; // SMTP password
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS; // Enable TLS encryption
        $mail->Port = 587; // TCP port to connect to

        // Recipients
        $mail->setFrom('parttimesystemplp@gmail.com', 'Part-Time Salary Processing');
        $mail->addAddress($email); // Add a recipient

        // Content
        $mail->isHTML(true);
        $mail->Subject = 'Security Alert: Unsuccessful Login Attempts';

        $mail->Body = "
        <html>
        <head>
            <title>Security Alert</title>
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; }
                .container { max-width: 600px; margin: 0 auto; padding: 20px; border: 1px solid #e0e0e0; border-radius: 5px; }
                .header { color: #2c5e1a; text-align: center; margin-bottom: 20px; }
                .button { 
                    display: inline-block; 
                    padding: 10px 20px; 
                    background-color: #2c5e1a; 
                    color: white !important; 
                    text-decoration: none; 
                    border-radius: 5px; 
                    margin: 15px 0;
                }
                .footer { margin-top: 20px; font-size: 12px; color: #666; text-align: center; }
                .logo { max-width: 150px; margin-bottom: 20px; }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='header'>
                    <img src='../finalspro/Logo.ico' alt='PLP Logo' class='logo'>
                    <h2>Pamantasan ng Lungsod ng Pasig</h2>
                    <h3>Security Alert</h3>
                </div>
                
                <p>Dear User,</p>
                
                <p>We've detected multiple unsuccessful login attempts to your account at <strong>" . htmlspecialchars($email) . "</strong>.</p>
                <p>If this was you, you can ignore this message. If not, we recommend you secure your account immediately.</p>
                
                <p><strong>For your security, we recommend changing your password:</strong></p>
                <div style='text-align: center; margin: 25px 0;'>
                    <a href='" . htmlspecialchars($resetLink) . "' class='button'>Change Password Now</a>
                </div>
                
                <p>This link will expire in 1 hour for security reasons.</p>
                                
                <div class='footer'>
                    <p>This is an automated message. Please do not reply to this email.</p>
                    <p>&copy; " . date('Y') . " Pamantasan ng Lungsod ng Pasig.</p>
                </div>
            </div>
        </body>
        </html>
        ";

        // Plain text version for non-HTML email clients
        $mail->AltBody = "SECURITY ALERT - Pamantasan ng Lungsod ng Pasig\n\n" .
            "We've detected multiple unsuccessful login attempts to your account ($email).\n\n" .
            "If this was you, you can ignore this message. If not, please secure your account immediately.\n\n" .
            "To change your password, please visit this link (expires in 1 hour):\n" .
            $resetLink . "\n\n" .
            "If you didn't attempt to log in, please contact the PLP IT Department immediately at it-support@plpasig.edu.ph or call (02) 1234-5678.\n\n" .
            "This is an automated message. Please do not reply to this email.";

        $mail->send();
        return true;
    } catch (Exception $e) {
        // Log the error
        error_log("Email sending failed for $email. Error: {$mail->ErrorInfo}");
        return false;
    }
}
?>