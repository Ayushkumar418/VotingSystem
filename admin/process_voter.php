<?php
require_once '../config/database.php';
require_once '../includes/auth.php';

if (!is_logged_in() || !is_admin()) {
    header('Location: ../index.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $db = new Database();
    $conn = $db->connect();
    
    try {
        $full_name = filter_input(INPUT_POST, 'full_name', FILTER_SANITIZE_STRING);
        $email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);
        $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
        $voter_id = 'V' . date('Y') . rand(1000, 9999);
        
        $stmt = $conn->prepare("INSERT INTO users (full_name, email, password, voter_id) VALUES (?, ?, ?, ?)");
        $stmt->execute([$full_name, $email, $password, $voter_id]);
        
        header('Location: voters.php?success=1');
    } catch (PDOException $e) {
        header('Location: voters.php?error=creation_failed');
    }
    exit();
}

if (isset($_GET['action']) && $_GET['action'] == 'delete' && isset($_GET['id'])) {
    $db = new Database();
    $conn = $db->connect();
    
    try {
        $id = filter_input(INPUT_GET, 'id', FILTER_SANITIZE_NUMBER_INT);
        
        // Start transaction
        $conn->beginTransaction();
        
        // Delete votes first
        $stmt = $conn->prepare("DELETE FROM votes WHERE user_id = ?");
        $stmt->execute([$id]);
        
        // Then delete user
        $stmt = $conn->prepare("DELETE FROM users WHERE id = ?");
        $stmt->execute([$id]);
        
        $conn->commit();
        header('Location: voters.php?success=1');
    } catch (PDOException $e) {
        $conn->rollBack();
        header('Location: voters.php?error=delete_failed');
    }
    exit();
}
?>
