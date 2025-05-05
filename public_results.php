<?php
require_once 'config/database.php';

$election_id = filter_input(INPUT_GET, 'id', FILTER_SANITIZE_NUMBER_INT);
$db = new Database();
$conn = $db->connect();

// Get published election details
$stmt = $conn->prepare("
    SELECT e.*, 
           COUNT(DISTINCT v.id) as total_votes,
           COUNT(DISTINCT v.user_id) as total_voters
    FROM elections e
    LEFT JOIN votes v ON e.id = v.election_id
    WHERE e.id = ? AND e.is_published = true
    GROUP BY e.id
");
$stmt->execute([$election_id]);
$election = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$election) {
    die("Results not available");
}

// Get candidate results
$stmt = $conn->prepare("
    SELECT c.name, c.photo_url, COUNT(v.id) as vote_count,
    (COUNT(v.id) * 100.0 / NULLIF((SELECT COUNT(*) FROM votes WHERE election_id = ?), 0)) as vote_percentage
    FROM candidates c
    LEFT JOIN votes v ON c.id = v.candidate_id
    WHERE c.election_id = ?
    GROUP BY c.id
    ORDER BY vote_count DESC
");
$stmt->execute([$election_id, $election_id]);
$candidates = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html>
<head>
    <title>Election Results - <?= htmlspecialchars($election['title']) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .chart-container { height: 400px; }
        .candidate-card { transition: transform 0.2s; }
        .candidate-card:hover { transform: translateY(-5px); }
        .winner-badge {
            position: absolute;
            top: -15px;
            right: -15px;
            background: #ffd700;
            color: #000;
            padding: 15px;
            border-radius: 50%;
            animation: winner-pulse 2s infinite;
        }
        @keyframes winner-pulse {
            0% { transform: scale(1); }
            50% { transform: scale(1.2); }
            100% { transform: scale(1); }
        }
        .winner-card {
            border: 3px solid #ffd700;
            box-shadow: 0 0 20px rgba(255, 215, 0, 0.5);
        }
    </style>
</head>
<body>
    <div class="container py-5">
        <h1 class="text-center mb-4"><?= htmlspecialchars($election['title']) ?> Results</h1>
        
        <?php 
        // Find the winner
        $winner = $candidates[0]; // First candidate has highest votes due to ORDER BY
        $is_tie = false;
        
        // Check for tie
        if (count($candidates) > 1 && $candidates[0]['vote_count'] == $candidates[1]['vote_count']) {
            $is_tie = true;
        }
        
        if (!$is_tie && $winner['vote_count'] > 0): 
        ?>
        <div class="alert alert-success text-center mb-4">
            <h3 class="mb-0">
                <i class="fas fa-crown text-warning me-2"></i>
                Winner: <?= htmlspecialchars($winner['name']) ?>
                with <?= $winner['vote_count'] ?> votes (<?= round($winner['vote_percentage'], 1) ?>%)
            </h3>
        </div>
        <?php endif; ?>

        <?php if ($is_tie): ?>
        <div class="alert alert-warning text-center mb-4">
            <h3 class="mb-0">
                <i class="fas fa-exclamation-triangle me-2"></i>
                The election resulted in a tie!
            </h3>
        </div>
        <?php endif; ?>
        
        <div class="row justify-content-center mb-5">
            <div class="col-md-8">
                <div class="chart-container">
                    <canvas id="resultsChart"></canvas>
                </div>
            </div>
        </div>

        <div class="row">
            <?php foreach($candidates as $candidate): ?>
            <div class="col-md-4 mb-4">
                <div class="card candidate-card position-relative <?= (!$is_tie && $candidate === $winner) ? 'winner-card' : '' ?>">
                    <?php if (!$is_tie && $candidate === $winner): ?>
                    <div class="winner-badge">
                        <i class="fas fa-trophy"></i>
                    </div>
                    <?php endif; ?>
                    <div class="card-body text-center">
                        <h4><?= htmlspecialchars($candidate['name']) ?></h4>
                        <h3 class="text-primary"><?= round($candidate['vote_percentage'], 1) ?>%</h3>
                        <p class="text-muted"><?= $candidate['vote_count'] ?> votes</p>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>

    <script>
    new Chart(document.getElementById('resultsChart'), {
        type: 'pie',
        data: {
            labels: <?= json_encode(array_column($candidates, 'name')) ?>,
            datasets: [{
                data: <?= json_encode(array_column($candidates, 'vote_count')) ?>,
                backgroundColor: [
                    '#FF6384', '#36A2EB', '#FFCE56', '#4BC0C0', '#9966FF', '#FF9F40'
                ]
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false
        }
    });
    </script>
</body>
</html>
