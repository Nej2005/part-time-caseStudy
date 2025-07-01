<?php
session_start();
include '../Backend/db_connect.php';
include '../Backend/Failed attempts/login_attempt_handler.php';

header('Content-Type: application/json');

$response = ["status" => "error", "message" => "Something went wrong."];

try {
    if ($_SERVER["REQUEST_METHOD"] == "POST") {
        $userInput = trim($_POST['userid']);
        $passInput = trim($_POST['password']);

        if (empty($userInput) || empty($passInput)) {
            $response = ["status" => "error", "message" => "Please fill in all fields."];
            echo json_encode($response);
            exit();
        }

        $sql = "SELECT * FROM Users WHERE employee_id = ? OR email_address = ?";
        $stmt = $conn->prepare($sql);

        if ($stmt) {
            $stmt->bind_param("ss", $userInput, $userInput);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($result->num_rows === 1) {
                $row = $result->fetch_assoc();

                if ($passInput === $row['password']) {
                    resetFailedAttempts($row['user_id'], $conn);

                    // Set basic session variables
                    $_SESSION['user_id'] = $row['user_id'];
                    $_SESSION['employee_id'] = $row['employee_id'];
                    $_SESSION['user_type'] = $row['user_type'];
                    $_SESSION['email_address'] = $row['email_address'];

                    // Fetch additional user info based on user type
                    if ($row['user_type'] === 'Admin_Secretary') {
                        $adminSql = "SELECT firstname, lastname FROM Admin_Secretary WHERE admin_id = ?";
                        $adminStmt = $conn->prepare($adminSql);
                        $adminStmt->bind_param("i", $row['user_id']);
                        $adminStmt->execute();
                        $adminResult = $adminStmt->get_result();

                        if ($adminResult->num_rows === 1) {
                            $adminRow = $adminResult->fetch_assoc();
                            $_SESSION['first_name'] = $adminRow['firstname'];
                            $_SESSION['last_name'] = $adminRow['lastname'];
                        }
                        $adminStmt->close();
                    } elseif ($row['user_type'] === 'partTime_Professor') {
                        
                        $profSql = "SELECT first_name, last_name FROM PartTime_Professor WHERE email_address = ?";
                        $profStmt = $conn->prepare($profSql);
                        $profStmt->bind_param("s", $row['email_address']);
                        $profStmt->execute();
                        $profResult = $profStmt->get_result();

                        if ($profResult->num_rows === 1) {
                            $profRow = $profResult->fetch_assoc();
                            $_SESSION['first_name'] = $profRow['first_name'];
                            $_SESSION['last_name'] = $profRow['last_name'];
                        }
                        $profStmt->close();
                    }

                    $response = [
                        "status" => "success",
                        "redirect" => $row['user_type'] === 'Admin_Secretary'
                            ? "/FinalProject/finalspro/management.php"
                            : "/FinalProject/finalspro/pt-dash.php"
                    ];
                } else {
                    $attemptCount = handleFailedLogin($userInput, $conn);

                    $response = [
                        "status" => "error",
                        "message" => "Incorrect Password.",
                        "attempts" => $attemptCount
                    ];

                    if ($attemptCount >= 3) {
                        $emailSent = sendResetEmail($row['email_address'], $row['user_id'], $conn);
                        if ($emailSent) {
                            $response['message'] = "Incorrect Password. We've sent a security alert to your email.";
                        } else {
                            $response['message'] = "Incorrect Password. Failed to send security alert email.";
                        }
                    }
                }
            } else {
                $response = ["status" => "error", "message" => "Employee ID or email not found."];
            }

            $stmt->close();
        } else {
            $response = ["status" => "error", "message" => "Database error. Please try again."];
        }
    }
} catch (Exception $e) {
    $response = ["status" => "error", "message" => "An error occurred: " . $e->getMessage()];
}

$conn->close();
echo json_encode($response);
exit();
