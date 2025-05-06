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
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .candidate-img-container {
            height: 300px;
            width: 100%;
            overflow: hidden;
            position: relative;
            background-color: #f8f9fa;
        }
        .candidate-img {
            width: 100%;
            height: 100%;
            object-fit: contain;
            padding: 10px;
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
        }
        .card {
            height: 100%;
            transition: transform 0.2s;
            border-radius: 15px;
            overflow: hidden;
        }
        .card:hover {
            transform: translateY(-5px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        .candidate-placeholder {
            height: 300px;
            display: flex;
            align-items: center;
            justify-content: center;
            background-color: #f8f9fa;
            color: #6c757d;
            font-size: 4rem;
        }
    </style>
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
                        <div class="card h-100">
                            <?php if ($candidate['photo_url'] && file_exists('uploads/' . $candidate['photo_url'])): ?>
                                <div class="candidate-img-container">
                                    <img src="uploads/<?= htmlspecialchars($candidate['photo_url']) ?>" 
                                         class="candidate-img"
                                         alt="<?= htmlspecialchars($candidate['name']) ?>"
                                         onerror="this.onerror=null; this.parentElement.innerHTML='<div class=\'candidate-placeholder\'><i class=\'fas fa-user-circle\'></i></div>';">
                                </div>
                            <?php else: ?>
                                <div class="candidate-placeholder">
                                    <i class="fas fa-user-circle"></i>
                                </div>
                            <?php endif; ?>
                            <div class="card-body d-flex flex-column">
                                <h5 class="card-title"><?= htmlspecialchars($candidate['name']) ?></h5>
                                <p class="card-text"><?= htmlspecialchars($candidate['description']) ?></p>
                                <div class="form-check mt-auto">
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
