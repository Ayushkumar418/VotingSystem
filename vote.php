<?php
require_once 'config/database.php';
require_once 'includes/auth.php';

if (!is_logged_in()) {
    header('Location: index.php');
    exit();
}

$election_id = filter_input(INPUT_GET, 'election_id', FILTER_SANITIZE_NUMBER_INT);
$db = new Database();
$conn = $db->connect();

// Check if user has already voted
$stmt = $conn->prepare("SELECT id FROM votes WHERE user_id = ? AND election_id = ?");
$stmt->execute([$_SESSION['user_id'], $election_id]);
$existing_vote = $stmt->fetch();

if ($existing_vote) {
    // User has already voted, show message and redirect
    $_SESSION['vote_message'] = "You have already cast your vote in this election.";
    header('Location: dashboard.php');
    exit();
}

// Fetch election details and candidates
$stmt = $conn->prepare("SELECT * FROM elections WHERE id = ? AND is_active = true");
$stmt->execute([$election_id]);
$election = $stmt->fetch(PDO::FETCH_ASSOC);

$stmt = $conn->prepare("SELECT * FROM candidates WHERE election_id = ?");
$stmt->execute([$election_id]);
$candidates = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html>
<head>
    <title>Cast Your Vote</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-4">
        <h2><?= htmlspecialchars($election['title']) ?></h2>
        <p><?= htmlspecialchars($election['description']) ?></p>
        
        <form action="process_vote.php" method="POST" class="mt-4">
            <input type="hidden" name="election_id" value="<?= $election_id ?>">
            
            <div class="row">
                <?php foreach ($candidates as $candidate): ?>
                    <div class="col-md-4 mb-3">
                        <div class="card">
                            <?php if ($candidate['photo_url']): ?>
                                <img src="<?= htmlspecialchars($candidate['photo_url']) ?>" class="card-img-top">
                            <?php endif; ?>
                            <div class="card-body">
                                <h5 class="card-title"><?= htmlspecialchars($candidate['name']) ?></h5>
                                <p class="card-text"><?= htmlspecialchars($candidate['description']) ?></p>
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" 
                                           name="candidate_id" value="<?= $candidate['id'] ?>" required>
                                    <label class="form-check-label">
                                        Select this candidate
                                    </label>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
            
            <div class="mt-3">
                <button type="submit" class="btn btn-primary">Cast Vote</button>
                <a href="dashboard.php" class="btn btn-secondary">Cancel</a>
            </div>
        </form>
    </div>
</body>
</html>
