<?php
require_once '../config/database.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';

if (!is_logged_in() || !is_admin()) {
    header('Location: ../index.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $db = new Database();
    $conn = $db->connect();
    
    try {
        // Sanitize and validate inputs
        $title = filter_input(INPUT_POST, 'title', FILTER_SANITIZE_STRING);
        $description = filter_input(INPUT_POST, 'description', FILTER_SANITIZE_STRING);
        $start_date = date('Y-m-d H:i:s', strtotime($_POST['start_date']));
        $end_date = date('Y-m-d H:i:s', strtotime($_POST['end_date']));
        $is_active = isset($_POST['is_active']) ? 1 : 0;
        
        // Validate dates
        if (strtotime($end_date) <= strtotime($start_date)) {
            header('Location: elections.php?error=invalid_dates');
            exit();
        }

        // Check if this is an edit action
        if (isset($_POST['action']) && $_POST['action'] == 'edit') {
            $id = filter_input(INPUT_POST, 'id', FILTER_SANITIZE_NUMBER_INT);
            $stmt = $conn->prepare("UPDATE elections SET title = ?, description = ?, start_date = ?, end_date = ?, is_active = ? WHERE id = ?");
            $stmt->execute([$title, $description, $start_date, $end_date, $is_active, $id]);
        } else {
            $stmt = $conn->prepare("INSERT INTO elections (title, description, start_date, end_date, is_active) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$title, $description, $start_date, $end_date, $is_active]);
            log_activity($conn, 'election_create', "Created new election: " . $title);
        }
        
        header('Location: elections.php?success=1');
        exit();
    } catch (PDOException $e) {
        error_log("Election operation error: " . $e->getMessage());
        header('Location: elections.php?error=operation_failed');
        exit();
    }
}

// Handle delete action
if (isset($_GET['action']) && $_GET['action'] == 'delete' && isset($_GET['id'])) {
    $db = new Database();
    $conn = $db->connect();
    
    try {
        // Start transaction
        $conn->beginTransaction();
        
        $id = filter_input(INPUT_GET, 'id', FILTER_SANITIZE_NUMBER_INT);
        
        // Delete related votes first
        $stmt = $conn->prepare("DELETE FROM votes WHERE election_id = ?");
        $stmt->execute([$id]);
        
        // Delete related candidates
        $stmt = $conn->prepare("DELETE FROM candidates WHERE election_id = ?");
        $stmt->execute([$id]);
        
        // Finally delete the election
        $stmt = $conn->prepare("DELETE FROM elections WHERE id = ?");
        $stmt->execute([$id]);
        log_activity($conn, 'election_delete', "Deleted election #" . $id);
        
        // Commit transaction
        $conn->commit();
        
        header('Location: elections.php?success=1');
    } catch (PDOException $e) {
        // Rollback on error
        $conn->rollBack();
        error_log("Election deletion error: " . $e->getMessage());
        header('Location: elections.php?error=delete_failed');
    }
    exit();
}
?>
