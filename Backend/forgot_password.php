<?php
include 'db_connect.php';
require '../phpmailer/src/PHPMailer.php';
require '../phpmailer/src/SMTP.php';
require '../phpmailer/src/Exception.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\SMTP;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $email = trim($_POST['email']);

  // Validate email format first
  if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode(['status' => 'error', 'message' => 'Invalid email format']);
    exit();
  }

  // Check allowed domains
  $allowedDomains = ['gmail.com', 'plpasig.edu.ph'];
  $domain = explode('@', $email)[1] ?? '';
  if (!in_array(strtolower($domain), $allowedDomains)) {
    echo json_encode(['status' => 'error', 'message' => 'Only @gmail.com and @plpasig.edu.ph emails are allowed']);
    exit();
  }

  // Then check if email exists in database
  $stmt = $conn->prepare("
  SELECT pp.last_name 
  FROM Users u
  JOIN PartTime_Professor pp ON u.email_address = pp.email_address
  WHERE u.email_address = ?
");
  $stmt->bind_param("s", $email);
  $stmt->execute();
  $result = $stmt->get_result();

  if ($result->num_rows === 0) {
    echo json_encode(['status' => 'error', 'message' => 'Email address not found']);
    exit();
  }

  $row = $result->fetch_assoc();
  $last_name = $row['last_name'];

  // Generate a 6-digit verification code
  $verification_code = str_pad(mt_rand(0, 999999), 6, '0', STR_PAD_LEFT);

  // Store the code in session with expiration time (3 minutes from now)
  session_start();
  if (isset($_SESSION['verification_email']) && $_SESSION['verification_email'] === $email) {
    // Check if the previous code is still valid (less than 3 minutes old)
    if (isset($_SESSION['verification_expires']) && time() < $_SESSION['verification_expires']) {
      // Reuse the same code if it's still valid
      $verification_code = $_SESSION['verification_code'];
      $_SESSION['verification_expires'] = time() + 180; // Reset to 3 minutes
    } else {
      // Generate a new code
      $verification_code = str_pad(mt_rand(0, 999999), 6, '0', STR_PAD_LEFT);
      $_SESSION['verification_code'] = $verification_code;
      $_SESSION['verification_email'] = $email;
      $_SESSION['verification_expires'] = time() + 180; // 3 minutes
    }
  } else {
    // Generate a new code
    $verification_code = str_pad(mt_rand(0, 999999), 6, '0', STR_PAD_LEFT);
    $_SESSION['verification_code'] = $verification_code;
    $_SESSION['verification_email'] = $email;
    $_SESSION['verification_expires'] = time() + 180; // 3 minutes
  }

  // Create PHPMailer instance
  $mail = new PHPMailer(true);

  try {
    // Server settings
    $mail->isSMTP();
    $mail->Host = 'smtp.gmail.com';
    $mail->SMTPAuth = true;
    $mail->Username = 'parttimesystemplp@gmail.com';
    $mail->Password = 'dwxs yqrb zxzy kemf';
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
    $mail->Port = 587;

    // Recipients
    $mail->setFrom('parttimesystemplp@gmail.com', 'Part-Time Salary Processing');
    $mail->addAddress($email);

    // Content
    $mail->isHTML(true);
    $mail->Subject = 'PLP Password Reset Verification Code';
    $mail->Body = generateEmailTemplate($verification_code, $last_name);
    $mail->AltBody = "Your verification code is: $verification_code\nThis code will expire in 3 minutes.";

    $mail->send();

    echo json_encode([
      'status' => 'success',
      'message' => 'Verification code sent to your email'
    ]);

  } catch (Exception $e) {
    error_log("Message could not be sent. Mailer Error: {$mail->ErrorInfo}");
    echo json_encode([
      'status' => 'error',
      'message' => 'Failed to send verification email. Please try again later.'
    ]);
  }
}

function generateEmailTemplate($code, $last_name)
{
  return '
    <!DOCTYPE html>
    <html>
    <head>
        <style>
            body { font-family: Arial, sans-serif; line-height: 1.6; }
            .container { max-width: 600px; margin: 0 auto; padding: 20px; }
            .header { 
                background-color: #2c5e1a; 
                color: white; 
                padding: 15px; 
                text-align: center; 
                border-radius: 5px 5px 0 0;
            }
            p {
                color: black;
            }
            .code { 
                font-size: 24px; 
                font-weight: bold; 
                color: #2c5e1a;
                background: #f4f4f4;
                padding: 10px 20px;
                margin: 15px 0;
                display: inline-block;
                border-radius: 5px;
                text-align: center;
            }
            .footer { 
                margin-top: 20px; 
                font-size: 12px; 
                color: #777; 
                text-align: center; 
            }
        </style>
    </head>
    <body>
        <div class="container">
            <div class="header">
                <h2>Part-Time Salary Processing</h2>
                <h3>Password Reset Verification</h3>
            </div>
            <p>Dear ' . htmlspecialchars($last_name) . ',</p>
            <p>We received a request to reset your password. Please use the following verification code:</p>
            <div class="code">' . $code . '</div>
            <p>This code will expire in 3 minutes.</p>
            <p>If you didn\'t request this, please ignore this email.</p>
            <div class="footer">
                <p>© ' . date('Y') . ' Pamantasan ng Lungsod ng Pasig</p>
            </div>
        </div>
    </body>
    </html>
    ';
}

?>