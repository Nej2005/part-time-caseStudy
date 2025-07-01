<?php
include '../db_connect.php';

// Start with output buffering to catch any accidental output
ob_start();

// Set headers first
header('Content-Type: application/json');

// Initialize response
$response = ["status" => "error", "message" => "Invalid request"];

try {
    if ($_SERVER["REQUEST_METHOD"] != "POST") {
        throw new Exception("Invalid request method");
    }

    $token = $_POST['reset_token'] ?? '';
    $newPassword = $_POST['new_password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';

    if (empty($token)) {
        throw new Exception("Token is required");
    }

    // Check token validity
    $sql = "SELECT token_id, user_id FROM Password_Reset_Tokens 
            WHERE token = ? AND expires_at > NOW() AND used = FALSE";
    $stmt = $conn->prepare($sql);

    if (!$stmt) {
        throw new Exception("Database error: " . $conn->error);
    }

    $stmt->bind_param("s", $token);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows !== 1) {
        throw new Exception("Invalid or expired token");
    }

    // Validate passwords
    if (empty($newPassword) || empty($confirmPassword)) {
        throw new Exception("All fields are required");
    }

    if ($newPassword !== $confirmPassword) {
        throw new Exception("Passwords do not match");
    }

    // Get token data
    $tokenData = $result->fetch_assoc();
    $userId = $tokenData['user_id'];
    $tokenId = $tokenData['token_id'];

    // Store password in plaintext (INSECURE - NOT RECOMMENDED)
    $plainPassword = $newPassword;

    // Update password in database
    $updateSql = "UPDATE Users SET password = ? WHERE user_id = ?";
    $updateStmt = $conn->prepare($updateSql);

    if (!$updateStmt) {
        throw new Exception("Database error: " . $conn->error);
    }

    $updateStmt->bind_param("si", $plainPassword, $userId);

    if ($updateStmt->execute()) {
        // Mark token as used
        $markUsedSql = "UPDATE Password_Reset_Tokens SET used = TRUE WHERE token_id = ?";
        $markUsedStmt = $conn->prepare($markUsedSql);
        $markUsedStmt->bind_param("i", $tokenId);
        $markUsedStmt->execute();
        
        // Reset failed login attempts for this user
        $resetAttemptsSql = "DELETE FROM Failed_Login_Attempts WHERE user_id = ?";
        $resetAttemptsStmt = $conn->prepare($resetAttemptsSql);
        $resetAttemptsStmt->bind_param("i", $userId);
        $resetAttemptsStmt->execute();

        $response = [
            "status" => "success", 
            "message" => "Password has been reset successfully",
            "redirect" => "login.php"
        ];
    } else {
        throw new Exception("Failed to update password: " . $updateStmt->error);
    }
} catch (Exception $e) {
    $response["message"] = $e->getMessage();
    error_log("Password reset error: " . $e->getMessage());
} finally {
    // Clean any output buffer
    ob_end_clean();
    
    // Close database connection
    if (isset($conn)) {
        $conn->close();
    }
    
    // Send JSON response
    echo json_encode($response);
    exit();
}