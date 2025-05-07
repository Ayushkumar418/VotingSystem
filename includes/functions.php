<?php
function log_activity($conn, $type, $description, $status = 'success') {
    try {
        $stmt = $conn->prepare("
            INSERT INTO activity_logs (user_id, activity_type, description, status) 
            VALUES (?, ?, ?, ?)
        ");
        return $stmt->execute([
            $_SESSION['user_id'], 
            $type, 
            $description, 
            $status
        ]);
    } catch (PDOException $e) {
        error_log("Activity logging failed: " . $e->getMessage());
        return false;
    }
}
?>
