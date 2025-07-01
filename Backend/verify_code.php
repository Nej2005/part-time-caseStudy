<?php
include 'db_connect.php';
session_start();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user_code = $_POST['code'];
    $email = $_SESSION['verification_email'] ?? '';
    
    // Check if verification session exists
    if (empty($email)) {
        echo json_encode(['status' => 'error', 'message' => 'Session expired. Please request a new code.']);
        exit();
    }
    
    // Check if code is expired
    if (time() > ($_SESSION['verification_expires'] ?? 0)) {
        echo json_encode(['status' => 'error', 'message' => 'Verification code expired. Please request a new one.']);
        exit();
    }
    
    // Check if code matches
    if ($user_code !== ($_SESSION['verification_code'] ?? '')) {
        echo json_encode(['status' => 'error', 'message' => 'Code did not match. Try again.']);
        exit();
    }
    
    // Code is valid - redirect to password reset page
    $_SESSION['verified_for_reset'] = true;
    echo json_encode(['status' => 'success', 'redirect' => 'changepass.php']);
}
?>