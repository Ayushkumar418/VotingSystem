<?php
require_once '../config/database.php';
require_once '../includes/auth.php';

if (!is_logged_in() || !is_admin()) {
    header('Location: ../index.php');
    exit();
}

$db = new Database();
$conn = $db->connect();

// Get all elections with vote counts and additional status information
$stmt = $conn->query("
    SELECT 
        e.*,
        COUNT(DISTINCT v.id) as total_votes,
        (SELECT COUNT(DISTINCT user_id) 
         FROM votes 
         WHERE election_id = e.id) as total_voters,
        CASE 
            WHEN NOW() > end_date THEN 'ended'
            WHEN NOW() BETWEEN start_date AND end_date AND e.is_active = true THEN 'active'
            ELSE 'upcoming'
        END as status,
        CASE 
            WHEN NOW() > end_date THEN 0
            ELSE TIMESTAMPDIFF(SECOND, NOW(), end_date)
        END as seconds_remaining
    FROM elections e
    LEFT JOIN votes v ON e.id = v.election_id
    GROUP BY e.id
    ORDER BY e.end_date DESC
");
$elections = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html>
<head>
    <title>Election Results - Admin Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        .sidebar { min-height: 100vh; background: #343a40; color: white; }
        .chart-container { position: relative; height: 300px; }
    </style>
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <div class="col-md-2 sidebar p-3">
                <h4 class="text-center mb-4">Admin Panel</h4>
                <div class="list-group">
                    <a href="index.php" class="list-group-item list-group-item-action">
                        <i class="fas fa-tachometer-alt me-2"></i> Dashboard
                    </a>
                    <a href="elections.php" class="list-group-item list-group-item-action">
                        <i class="fas fa-vote-yea me-2"></i> Elections
                    </a>
                    <a href="candidates.php" class="list-group-item list-group-item-action">
                        <i class="fas fa-users me-2"></i> Candidates
                    </a>
                    <a href="results.php" class="list-group-item list-group-item-action active">
                        <i class="fas fa-chart-bar me-2"></i> Results
                    </a>
                </div>
            </div>

            <!-- Main Content -->
            <div class="col-md-10 p-4">
                <h2 class="mb-4">Election Results</h2>
                
                <?php foreach($elections as $election): ?>
                    <div class="card mb-4">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <h5 class="mb-0"><?= htmlspecialchars($election['title']) ?></h5>
                            <div>
                                <?php if($election['status'] === 'ended'): ?>
                                    <span class="badge bg-secondary">Ended</span>
                                <?php elseif($election['status'] === 'active'): ?>
                                    <span class="badge bg-success">Active</span>
                                    <span class="ms-2 badge bg-info countdown" data-seconds="<?= $election['seconds_remaining'] ?>">
                                        Ends in: Loading...
                                    </span>
                                <?php else: ?>
                                    <span class="badge bg-warning">Upcoming</span>
                                <?php endif; ?>
                            </div>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-4">
                                    <h6>Statistics</h6>
                                    <p>Total Votes: <?= $election['total_votes'] ?></p>
                                    <p>Total Voters: <?= $election['total_voters'] ?></p>
                                    <p>Start Date: <?= date('Y-m-d H:i', strtotime($election['start_date'])) ?></p>
                                    <p>End Date: <?= date('Y-m-d H:i', strtotime($election['end_date'])) ?></p>
                                </div>
                                <div class="col-md-8">
                                    <div class="chart-container">
                                        <canvas id="chart_<?= $election['id'] ?>"></canvas>
                                    </div>
                                </div>
                            </div>
                            <button class="btn btn-primary mt-3" onclick="viewDetailedResults(<?= $election['id'] ?>)">
                                View Detailed Results
                            </button>
                        </div>
                    </div>
                <?php 
                    // Get vote distribution for this election
                    $stmt = $conn->prepare("
                        SELECT c.name, COUNT(v.id) as vote_count
                        FROM candidates c
                        LEFT JOIN votes v ON c.id = v.candidate_id
                        WHERE c.election_id = ?
                        GROUP BY c.id
                    ");
                    $stmt->execute([$election['id']]);
                    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
                ?>
                <script>
                    new Chart(document.getElementById('chart_<?= $election['id'] ?>'), {
                        type: 'bar',
                        data: {
                            labels: <?= json_encode(array_column($results, 'name')) ?>,
                            datasets: [{
                                label: 'Votes',
                                data: <?= json_encode(array_column($results, 'vote_count')) ?>,
                                backgroundColor: 'rgba(54, 162, 235, 0.5)',
                                borderColor: 'rgba(54, 162, 235, 1)',
                                borderWidth: 1
                            }]
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            scales: {
                                y: {
                                    beginAtZero: true,
                                    ticks: {
                                        stepSize: 1
                                    }
                                }
                            }
                        }
                    });
                </script>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <script>
    function updateCountdown(element) {
        let seconds = parseInt(element.dataset.seconds);
        if (seconds <= 0) {
            element.innerHTML = "Ended";
            return;
        }
        
        const days = Math.floor(seconds / (24 * 60 * 60));
        seconds = seconds % (24 * 60 * 60);
        const hours = Math.floor(seconds / (60 * 60));
        seconds = seconds % (60 * 60);
        const minutes = Math.floor(seconds / 60);
        seconds = seconds % 60;
        
        element.innerHTML = `Ends in: ${days}d ${hours}h ${minutes}m ${seconds}s`;
        element.dataset.seconds = parseInt(element.dataset.seconds) - 1;
        
        setTimeout(() => updateCountdown(element), 1000);
    }

    document.addEventListener('DOMContentLoaded', function() {
        document.querySelectorAll('.countdown').forEach(el => updateCountdown(el));
    });

    function viewDetailedResults(id) {
        window.location.href = 'detailed_results.php?id=' + id;
    }
    </script>
</body>
</html>
