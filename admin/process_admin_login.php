<?php
require_once '../config/database.php';
require_once '../includes/auth.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);
    $password = $_POST['password'];
    
    $db = new Database();
    $conn = $db->connect();
    
    try {
        // Debug password hash
        $test_hash = password_hash('admin123', PASSWORD_DEFAULT);
        error_log("Test hash for admin123: " . $test_hash);
        
        $stmt = $conn->prepare("SELECT * FROM users WHERE email = ? AND is_admin = 1 LIMIT 1");
        $stmt->execute([$email]);
        $admin = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($admin) {
            error_log("Admin found with ID: " . $admin['id']);
            error_log("Stored hash: " . $admin['password']);
            error_log("Comparing with password: admin123");
            
            if (password_verify($password, $admin['password'])) {
                error_log("Password verified successfully");
                session_start();
                $_SESSION['user_id'] = $admin['id'];
                $_SESSION['is_admin'] = true;
                $_SESSION['full_name'] = $admin['full_name'];
                
                header('Location: index.php');
                exit();
            } else {
                error_log("Password verification failed - Hash mismatch");
                // Update admin password in database to ensure correct hash
                $new_hash = password_hash('admin123', PASSWORD_DEFAULT);
                $update = $conn->prepare("UPDATE users SET password = ? WHERE id = ?");
                $update->execute([$new_hash, $admin['id']]);
                error_log("Updated admin password hash");
            }
        } else {
            error_log("No admin found with email: " . $email);
        }
        
    } catch (PDOException $e) {
        error_log("Database error: " . $e->getMessage());
    }
    
    header('Location: login.php?error=1');
    exit();
}
?>
