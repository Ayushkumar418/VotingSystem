<?php
require_once 'config/database.php';
require_once 'includes/auth.php';
require_once 'includes/functions.php';

if (!is_logged_in()) {
    header('Location: index.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $election_id = filter_input(INPUT_POST, 'election_id', FILTER_SANITIZE_NUMBER_INT);
    $candidate_id = filter_input(INPUT_POST, 'candidate_id', FILTER_SANITIZE_NUMBER_INT);

    $db = new Database();
    $conn = $db->connect();

    try {
        // Check for existing vote again
        $stmt = $conn->prepare("SELECT id FROM votes WHERE user_id = ? AND election_id = ?");
        $stmt->execute([$_SESSION['user_id'], $election_id]);
        if ($stmt->fetch()) {
            $_SESSION['vote_message'] = "You have already cast your vote in this election.";
            header('Location: dashboard.php');
            exit();
        }

        // Check if user hasn't already voted
        $check = $conn->prepare("SELECT id FROM votes WHERE user_id = ? AND election_id = ?");
        $check->execute([$_SESSION['user_id'], $election_id]);
        
        if ($check->rowCount() > 0) {
            throw new Exception('Already voted');
        }
        
        // Record the vote
        $stmt = $conn->prepare("INSERT INTO votes (user_id, election_id, candidate_id) VALUES (?, ?, ?)");
        $stmt->execute([$_SESSION['user_id'], $election_id, $candidate_id]);
        log_activity($conn, 'vote', "Voted in election #" . $election_id);
        $_SESSION['vote_message'] = "Vote cast successfully!";
        
        header('Location: results.php?election_id=' . $election_id . '&success=1');
    } catch (Exception $e) {
        header('Location: dashboard.php?error=voting_failed');
    }
}
exit();
