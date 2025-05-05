<?php
require_once 'config/database.php';
require_once 'includes/auth.php';

if (!is_logged_in() || $_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: index.php');
    exit();
}

$db = new Database();
$conn = $db->connect();

try {
    // Verify current password
    $stmt = $conn->prepare("SELECT password FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $user = $stmt->fetch();
    
    if (!password_verify($_POST['current_password'], $user['password'])) {
        header('Location: profile.php?error=invalid_password');
        exit();
    }
    
    $updates = [
        'full_name' => filter_input(INPUT_POST, 'full_name', FILTER_SANITIZE_STRING),
        'email' => filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL)
    ];
    
    // Add new password to updates if provided
    if (!empty($_POST['new_password'])) {
        $updates['password'] = password_hash($_POST['new_password'], PASSWORD_DEFAULT);
    }
    
    // Build update query
    $sql = "UPDATE users SET " . 
           implode(', ', array_map(fn($k) => "$k = ?", array_keys($updates))) .
           " WHERE id = ?";
    
    $stmt = $conn->prepare($sql);
    $stmt->execute([...array_values($updates), $_SESSION['user_id']]);
    
    header('Location: profile.php?success=1');
} catch (PDOException $e) {
    header('Location: profile.php?error=update_failed');
}
exit();
?>
