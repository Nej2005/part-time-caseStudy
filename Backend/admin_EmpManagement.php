<?php
require '../Backend/db_connect.php';
require '../phpmailer/src/PHPMailer.php';
require '../phpmailer/src/SMTP.php';
require '../phpmailer/src/Exception.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Get POST values from frontend
    $email = $_POST['email'] ?? '';
    $firstName = $_POST['first_name'] ?? '';
    $lastName = $_POST['last_name'] ?? '';
    $middleInitial = $_POST['middle_initial'] ?? '';

    if (empty($email) || empty($firstName) || empty($lastName)) {
        echo json_encode(['error' => 'All fields are required.']);
        exit;
    }

    // Check if the email format is valid
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        echo json_encode(['error' => 'Invalid email format.']);
        exit;
    }

    $allowedDomains = ['gmail.com', 'plpasig.edu.ph'];
    $emailParts = explode('@', $email);
    $emailDomain = end($emailParts);

    if (!in_array($emailDomain, $allowedDomains)) {
        echo json_encode(['error' => 'Email domain not allowed.']);
        exit;
    }

    // Check if the email already exists in the PartTime_Professor table
    $emailCheckStmt = $conn->prepare("SELECT 1 FROM PartTime_Professor WHERE email_address = ?");
    $emailCheckStmt->bind_param("s", $email);
    $emailCheckStmt->execute();
    $emailCheckStmt->store_result();

    if ($emailCheckStmt->num_rows > 0) {
        echo json_encode(['error' => 'The email address is already registered.']);
        exit;
    }

    // Insert professor details into PartTime_Professor table
    $stmt = $conn->prepare("INSERT INTO PartTime_Professor (email_address, first_name, last_name, middle_initial) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("ssss", $email, $firstName, $lastName, $middleInitial);

    if (!$stmt->execute()) {
        echo json_encode(['error' => 'Error inserting into PartTime_Professor: ' . $stmt->error]);
        exit;
    }

    // Generate password
    $generatedPassword = substr(str_shuffle("abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789"), 0, 5);

    // Insert into Users table
    $insertUser = $conn->prepare("INSERT INTO Users (email_address, password, user_type) VALUES (?, ?, 'partTime_Professor')");
    $insertUser->bind_param("ss", $email, $generatedPassword);

    if (!$insertUser->execute()) {
        echo json_encode(['error' => 'Error inserting into Users: ' . $insertUser->error]);
        exit;
    }

    $user_id = $conn->insert_id;

    // Generate employee ID
    $employeeID = str_pad($user_id, 7, '0', STR_PAD_LEFT);

    // Update employee ID in Users table
    $updateEmployeeId = $conn->prepare("UPDATE Users SET employee_id = ? WHERE user_id = ?");
    $updateEmployeeId->bind_param("si", $employeeID, $user_id);

    if (!$updateEmployeeId->execute()) {
        echo json_encode(['error' => 'Error updating employee_id: ' . $updateEmployeeId->error]);
        exit;
    }

    // PHP Mailer setup
    $mail = new PHPMailer(true);
    try {
        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com';
        $mail->SMTPAuth = true;
        $mail->Username = 'parttimesystemplp@gmail.com';
        $mail->Password = 'dwxs yqrb zxzy kemf';  // pp password 
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = 587;

        $mail->setFrom('no-reply@plpasig.edu.ph', 'Part-Time Salary Processing');
        $mail->addAddress($email, "$firstName $lastName");

        $mail->isHTML(true);
        $mail->Subject = 'Your Account in the Part-Time System';
        $mail->Body = generateWelcomeEmail($firstName, $lastName, $employeeID, $generatedPassword);
        $mail->AltBody = "Hello $firstName $lastName,\n\nWelcome! Here are your login credentials:\n\nEmployee ID: $employeeID\nPassword: $generatedPassword";

        $mail->send();
    } catch (Exception $e) {
        echo json_encode(['error' => 'Email could not be sent. Mailer Error: ' . $mail->ErrorInfo]);
        exit;
    }

    echo json_encode([
        'employee_id' => $employeeID,
        'employee_name' => $firstName . ' ' . $lastName,
        'employee_email' => $email,
        'message' => 'Employee added and email sent successfully'
    ]);
}

function generateWelcomeEmail($firstName, $lastName, $employeeID, $password)
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
            .credentials {
                background: #f4f4f4;
                padding: 15px;
                margin: 15px 0;
                border-radius: 5px;
            }
            .credential-item {
                margin: 10px 0;
                font-size: 16px;
            }
            .credential-label {
                font-weight: bold;
                color: #2c5e1a;
            }
            .footer { 
                margin-top: 20px; 
                font-size: 12px; 
                color: #777; 
                text-align: center; 
            }
            .note {
                font-style: italic;
                color: #555;
                margin-top: 20px;
            }
        </style>
    </head>
    <body>
        <div class="container">
            <div class="header">
                <h2>Part-Time Salary Processing</h2>
                <h3>Welcome to the Part-Time System</h3>
            </div>
            <p>Dear ' . htmlspecialchars($firstName . ' ' . $lastName) . ',</p>
            <p>Welcome to the Pamantasan ng Lungsod ng Pasig Part-Time Salary Processing System!</p>
            
            <div class="credentials">
                <div class="credential-item">
                    <span class="credential-label">Employee ID:</span> ' . htmlspecialchars($employeeID) . '
                </div>
                <div class="credential-item">
                    <span class="credential-label">Password:</span> ' . htmlspecialchars($password) . '
                </div>
            </div>
            
            <p>You can now log in to the system using these credentials.</p>
                        
            <div class="footer">
                <p>© ' . date('Y') . ' Pamantasan ng Lungsod ng Pasig</p>
            </div>
        </div>
    </body>
    </html>
    ';
}
?>