<?php
require_once 'config/database.php';
require_once 'includes/auth.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $full_name = filter_input(INPUT_POST, 'full_name', FILTER_SANITIZE_STRING);
    $email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    
    if ($password !== $confirm_password) {
        header('Location: register.php?error=passwords_dont_match');
        exit();
    }
    
    // Generate unique voter ID
    $voter_id = 'V' . date('Y') . rand(1000, 9999);
    
    if (register_user($email, $password, $full_name, $voter_id)) {
        header('Location: index.php?success=registration_complete');
    } else {
        header('Location: register.php?error=registration_failed');
    }
    exit();
}
?>
