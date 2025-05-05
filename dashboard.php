<?php
require_once 'config/database.php';
require_once 'includes/auth.php';

if (!is_logged_in()) {
    header('Location: index.php');
    exit();
}

$db = new Database();
$conn = $db->connect();

// Update Fetch active elections query
$stmt = $conn->prepare("
    SELECT *, 
    CASE 
        WHEN NOW() > end_date THEN 'ended'
        WHEN NOW() BETWEEN start_date AND end_date AND is_active = true THEN 'active'
        ELSE 'upcoming'
    END as status
    FROM elections
    WHERE is_active = true 
    ORDER BY start_date DESC");
$stmt->execute();
$active_elections = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get published elections for results
$stmt = $conn->prepare("SELECT * FROM elections WHERE is_published = true");
$stmt->execute();
$published_elections = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html>
<head>
    <title>Voting Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container">
            <a class="navbar-brand" href="#">Online Voting System</a>
            <div class="navbar-nav ms-auto">
                <a href="profile.php" class="nav-link">
                    <i class="fas fa-user"></i> My Profile
                </a>
                <a href="logout.php" class="nav-link">Logout</a>
            </div>
        </div>
    </nav>

    <div class="container mt-4">
        <?php if (isset($_SESSION['vote_message'])): ?>
            <div class="alert alert-info alert-dismissible fade show">
                <?= htmlspecialchars($_SESSION['vote_message']) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
            <?php unset($_SESSION['vote_message']); ?>
        <?php endif; ?>

        <h2>Active Elections</h2>
        <?php if (empty($active_elections)): ?>
            <div class="alert alert-info">No active elections at the moment.</div>
        <?php else: ?>
            <div class="row">
                <?php foreach ($active_elections as $election): ?>
                    <div class="col-md-6 mb-4">
                        <div class="card">
                            <div class="card-body">
                                <h5 class="card-title"><?= htmlspecialchars($election['title']) ?></h5>
                                <p class="card-text"><?= htmlspecialchars($election['description']) ?></p>
                                <?php if($election['status'] === 'ended'): ?>
                                    <span class="badge bg-secondary mb-2">Ended</span>
                                <?php else: ?>
                                    <a href="vote.php?election_id=<?= $election['id'] ?>" 
                                       class="btn btn-primary">Cast Vote</a>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <!-- Add Published Results Section -->
        <h2 class="mt-4">Published Results</h2>
        <?php if (empty($published_elections)): ?>
            <div class="alert alert-info">No published results available.</div>
        <?php else: ?>
            <div class="row">
                <?php foreach ($published_elections as $election): ?>
                    <div class="col-md-6 mb-4">
                        <div class="card">
                            <div class="card-body">
                                <h5 class="card-title"><?= htmlspecialchars($election['title']) ?></h5>
                                <p class="card-text"><?= htmlspecialchars($election['description']) ?></p>
                                <a href="public_results.php?id=<?= $election['id'] ?>" 
                                   class="btn btn-info">View Results</a>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
