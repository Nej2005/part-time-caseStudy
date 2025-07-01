<?php
function handleFailedLogin($userInput, $conn)
{
    try {
        $sql = "SELECT user_id, email_address FROM Users WHERE employee_id = ? OR email_address = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ss", $userInput, $userInput);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows === 1) {
            $user = $result->fetch_assoc();

            // Record the failed attempt
            $insertSql = "INSERT INTO Failed_Login_Attempts (user_id) VALUES (?)";
            $insertStmt = $conn->prepare($insertSql);
            $insertStmt->bind_param("i", $user['user_id']);
            $insertStmt->execute();

            // Count recent failed attempts
            $countSql = "SELECT COUNT(*) AS attempt_count 
                         FROM Failed_Login_Attempts 
                         WHERE user_id = ? AND attempt_time > DATE_SUB(NOW(), INTERVAL 1 HOUR)";
            $countStmt = $conn->prepare($countSql);
            $countStmt->bind_param("i", $user['user_id']);
            $countStmt->execute();
            $countResult = $countStmt->get_result();
            return $countResult->fetch_assoc()['attempt_count'];
        }
        return 0;
    } catch (Exception $e) {
        return 0;
    }
}

function sendResetEmail($email, $userId, $conn)
{
    try {
        // Generate and store token
        $token = bin2hex(random_bytes(32));
        $expiry = date('Y-m-d H:i:s', strtotime('+1 hour'));

        error_log("Generating token for user $userId: $token (expires: $expiry)");

        $sql = "INSERT INTO Password_Reset_Tokens (user_id, token, expires_at) VALUES (?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("iss", $userId, $token, $expiry);
        
        if (!$stmt->execute()) {
            error_log("Failed to insert token: " . $stmt->error);
            return false;
        }
        
        error_log("Token successfully stored in database");

        // Create reset link - using absolute URL
        $protocol = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') ? "https" : "http";
        $host = $_SERVER['HTTP_HOST'];
        $host = rtrim($host, '/');
        $resetLink = "$protocol://$host/FinalProject/finalspro/resetpass.php?token=" . urlencode($token);

        error_log("Generated reset link: " . $resetLink);

        // PHPMailer setup
        require_once __DIR__ . '/../../phpmailer/src/PHPMailer.php';
        require_once __DIR__ . '/../../phpmailer/src/SMTP.php';
        require_once __DIR__ . '/../../phpmailer/src/Exception.php';

        $mail = new PHPMailer\PHPMailer\PHPMailer(true);

        // Server settings
        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com';
        $mail->SMTPAuth = true;
        $mail->Username = 'parttimesystemplp@gmail.com';
        $mail->Password = 'dwxs yqrb zxzy kemf';
        $mail->SMTPSecure = PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = 587;

        // Recipients
        $mail->setFrom('parttimesystemplp@gmail.com', 'Part-Time Salary Processing');
        $mail->addAddress($email);

        // Get user's last name for personalization
        $stmt = $conn->prepare("
            SELECT pp.last_name 
            FROM Users u
            JOIN PartTime_Professor pp ON u.email_address = pp.email_address
            WHERE u.user_id = ?
        ");
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        $result = $stmt->get_result();
        $last_name = $result->fetch_assoc()['last_name'] ?? 'User';

        // Content
        $mail->isHTML(true);
        $mail->Subject = 'Password Reset Request';
        $mail->Body = generateResetEmailTemplate($resetLink, $last_name);
        $mail->AltBody = "
            Password Reset Request\n
            Click this link to reset your password: $resetLink\n
            This link expires in 1 hour.
        ";

        $sendResult = $mail->send();

        if (!$sendResult) {
            error_log("PHPMailer Error: " . $mail->ErrorInfo);
            return false;
        }

        return true;
    } catch (Exception $e) {
        error_log("Email sending failed: " . $e->getMessage());
        return false;
    }
}

function generateResetEmailTemplate($resetLink, $last_name)
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
            .reset-link { 
                font-size: 16px; 
                font-weight: bold; 
                color: #2f5f21; /* dark green */
                background: #f0f0f0; /* light gray */
                padding: 10px 20px;
                margin: 15px 0;
                display: inline-block;
                border-radius: 5px;
                text-align: center;
                word-break: break-word;
            }
            .footer { 
                margin-top: 20px; 
                font-size: 12px; 
                color: #777; 
                text-align: center; 
            }
            .button {
                background-color: #f0f0f0; /* light gray */
                color: #2f5f21; /* dark green */
                padding: 10px 20px;
                text-decoration: none;
                border-radius: 5px;
                display: inline-block;
                margin: 10px 0;
                font-weight: bold;
            }
        </style>
    </head>
    <body>
        <div class="container">
            <div class="header">
                <h2>Part-Time Salary Processing</h2>
                <h3>Password Reset Request</h3>
            </div>
            <p>Dear ' . htmlspecialchars($last_name) . ',</p>
            <p>It appears someone is trying to gain unauthorized access to your account</p>
            <p>A password reset option is available for you.</p>

            <p><a href="' . $resetLink . '" class="button">Reset My Password</a></p>
        
            <p>This link will expire in 1 hour.</p>
            <p>If you didn\'t request this, please ignore this email.</p>
            <div class="footer">
                <p>© ' . date('Y') . ' Pamantasan ng Lungsod ng Pasig</p>
            </div>
        </div>
    </body>
    </html>
    ';
}



function resetFailedAttempts($userId, $conn)
{
    try {
        $sql = "DELETE FROM Failed_Login_Attempts WHERE user_id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $userId);
        $stmt->execute();
    } catch (Exception $e) {
        // Silently fail
    }
}
?>