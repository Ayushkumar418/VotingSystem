<?php
require_once '../config/database.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';

if (!is_logged_in() || !is_admin()) {
    header('Location: ../index.php');
    exit();
}

$db = new Database();
$conn = $db->connect();

// Handle delete action
if (isset($_GET['action']) && $_GET['action'] == 'delete' && isset($_GET['id'])) {
    try {
        $conn->beginTransaction();
        
        $id = filter_input(INPUT_GET, 'id', FILTER_SANITIZE_NUMBER_INT);

        // First verify the voter exists and is not an admin
        $check = $conn->prepare("SELECT id FROM users WHERE id = ? AND is_admin = 0");
        $check->execute([$id]);
        if (!$check->fetch()) {
            throw new PDOException("Voter not found or is admin");
        }
        
        // Delete from activity_logs first
        $stmt = $conn->prepare("DELETE FROM activity_logs WHERE user_id = ?");
        $stmt->execute([$id]);
        
        // Delete related votes
        $stmt = $conn->prepare("DELETE FROM votes WHERE user_id = ?");
        $stmt->execute([$id]);
        
        // Finally delete the voter
        $stmt = $conn->prepare("DELETE FROM users WHERE id = ? AND is_admin = 0");
        if (!$stmt->execute([$id])) {
            throw new PDOException("Failed to delete voter");
        }
        
        $conn->commit();
        log_activity($conn, 'voter_delete', "Deleted voter #" . $id);
        header('Location: voters.php?success=1');
        
    } catch (PDOException $e) {
        $conn->rollBack();
        error_log("Failed to delete voter: " . $e->getMessage());
        header('Location: voters.php?error=delete_failed');
    }
    exit();
}

// Handle add voter
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
?>
