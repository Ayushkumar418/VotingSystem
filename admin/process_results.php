<?php
require_once '../config/database.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';

if (!is_logged_in() || !is_admin()) {
    header('Location: ../index.php');
    exit();
}

if (isset($_GET['action']) && $_GET['action'] == 'publish') {
    $election_id = filter_input(INPUT_GET, 'id', FILTER_SANITIZE_NUMBER_INT);
    $db = new Database();
    $conn = $db->connect();
    
    try {
        // Verify election has ended
        $stmt = $conn->prepare("SELECT * FROM elections WHERE id = ? AND NOW() > end_date");
        $stmt->execute([$election_id]);
        $election = $stmt->fetch();

        if ($election) {
            // Update election to published status
            $stmt = $conn->prepare("UPDATE elections SET is_published = 1 WHERE id = ?");
            $stmt->execute([$election_id]);
            log_activity($conn, 'results_publish', "Published results for election #" . $election_id);
            
            header('Location: detailed_results.php?id=' . $election_id . '&success=published');
        } else {
            header('Location: detailed_results.php?id=' . $election_id . '&error=not_ended');
        }
    } catch (PDOException $e) {
        error_log("Failed to publish results: " . $e->getMessage());
        header('Location: detailed_results.php?id=' . $election_id . '&error=failed');
    }
    exit();
}
?>
