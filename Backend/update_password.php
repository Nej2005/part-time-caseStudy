<?php
include '../Backend/db_connect.php';
session_start();

// Verify user is authenticated to change password
if (!isset($_SESSION['verified_for_reset']) || !$_SESSION['verified_for_reset']) {
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized access']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];
    $email = $_SESSION['verification_email'] ?? '';
    
    // Validate passwords match
    if ($new_password !== $confirm_password) {
        echo json_encode(['status' => 'error', 'message' => 'Passwords do not match']);
        exit();
    }
    
    // Validate password meets requirements
    if (strlen($new_password) < 8 || 
        !preg_match('/[A-Z]/', $new_password) || 
        !preg_match('/[a-z]/', $new_password) || 
        !preg_match('/[0-9]/', $new_password) || 
        !preg_match('/[!@#$%^&*()_+\-=\[\]{};\':"\\|,.<>\/?]/', $new_password)) {
        echo json_encode(['status' => 'error', 'message' => 'Password does not meet requirements']);
        exit();
    }
    
    // Update password in database (storing plain text)
    $stmt = $conn->prepare("UPDATE Users SET password = ? WHERE email_address = ?");
    $stmt->bind_param("ss", $new_password, $email);
    
    if ($stmt->execute()) {
        // Clear the verification session
        unset($_SESSION['verification_code']);
        unset($_SESSION['verification_email']);
        unset($_SESSION['verification_expires']);
        unset($_SESSION['verified_for_reset']);
        
        echo json_encode(['status' => 'success', 'message' => 'Password updated successfully']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Failed to update password']);
    }
} else {
    echo json_encode(['status' => 'error', 'message' => 'Invalid request method']);
}
?>