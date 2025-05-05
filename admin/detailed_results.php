<?php
require_once '../config/database.php';
require_once '../includes/auth.php';

if (!is_logged_in() || !is_admin()) {
    header('Location: ../index.php');
    exit();
}

$election_id = filter_input(INPUT_GET, 'id', FILTER_SANITIZE_NUMBER_INT);
$db = new Database();
$conn = $db->connect();

// Get election details with published status
$stmt = $conn->prepare("
    SELECT e.*, 
           COUNT(DISTINCT v.id) as total_votes,
           COUNT(DISTINCT v.user_id) as total_voters,
           (SELECT COUNT(*) FROM users WHERE is_admin = 0) as registered_voters,
           CASE WHEN NOW() > end_date THEN 1 ELSE 0 END as is_ended
    FROM elections e
    LEFT JOIN votes v ON e.id = v.election_id
    WHERE e.id = ?
    GROUP BY e.id
");
$stmt->execute([$election_id]);
$election = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$election) {
    header('Location: results.php');
    exit();
}

// Get candidate results
$stmt = $conn->prepare("
    SELECT 
        c.name,
        c.photo_url,
        COUNT(v.id) as vote_count,
        (COUNT(v.id) * 100.0 / NULLIF((SELECT COUNT(*) FROM votes WHERE election_id = ?), 0)) as vote_percentage
    FROM candidates c
    LEFT JOIN votes v ON c.id = v.candidate_id
    WHERE c.election_id = ?
    GROUP BY c.id
    ORDER BY vote_count DESC
");
$stmt->execute([$election_id, $election_id]);
$candidates = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get voting timeline
$stmt = $conn->prepare("
    SELECT DATE(voted_at) as vote_date, COUNT(*) as daily_votes
    FROM votes
    WHERE election_id = ?
    GROUP BY DATE(voted_at)
    ORDER BY vote_date
");
$stmt->execute([$election_id]);
$timeline = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html>
<head>
    <title>Detailed Results - <?= htmlspecialchars($election['title']) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        .sidebar { min-height: 100vh; background: #343a40; color: white; }
        .chart-container { position: relative; height: 300px; margin-bottom: 20px; }
        .candidate-photo { width: 50px; height: 50px; object-fit: cover; border-radius: 50%; }
    </style>
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <div class="col-md-2 sidebar p-3">
                <h4 class="text-center mb-4">Admin Panel</h4>
                <div class="list-group">
                    <a href="results.php" class="list-group-item list-group-item-action">
                        <i class="fas fa-arrow-left me-2"></i> Back to Results
                    </a>
                </div>
            </div>

            <div class="col-md-10 p-4">
                <h2><?= htmlspecialchars($election['title']) ?> - Detailed Results</h2>
                
                <?php if(isset($_GET['success'])): ?>
                    <div class="alert alert-success">
                        Results have been published successfully!
                    </div>
                <?php endif; ?>

                <?php if(isset($_GET['error'])): ?>
                    <div class="alert alert-danger">
                        <?php 
                            switch($_GET['error']) {
                                case 'not_ended':
                                    echo "Cannot publish results before election ends.";
                                    break;
                                case 'failed':
                                    echo "Failed to publish results. Please try again.";
                                    break;
                                default:
                                    echo "An error occurred.";
                            }
                        ?>
                    </div>
                <?php endif; ?>

                <!-- Add publish button with time check -->
                <div class="mb-3">
                    <?php 
                    $now = new DateTime();
                    $end_date = new DateTime($election['end_date']);
                    $is_ended = $election['is_ended'] == 1;
                    
                    // Debug information
                    if(isset($_GET['debug'])) {
                        echo "<pre class='alert alert-info'>";
                        echo "Current Time: " . $now->format('Y-m-d H:i:s') . "\n";
                        echo "End Date: " . $end_date->format('Y-m-d H:i:s') . "\n";
                        echo "Is Ended: " . ($is_ended ? 'Yes' : 'No') . "\n";
                        echo "</pre>";
                    }
                    ?>

                    <?php if($is_ended): ?>
                        <?php if(!$election['is_published']): ?>
                            <button class="btn btn-success" onclick="publishResults(<?= $election_id ?>)">
                                <i class="fas fa-globe"></i> Publish Results
                            </button>
                        <?php else: ?>
                            <span class="badge bg-success"><i class="fas fa-check"></i> Results Published</span>
                            <a href="../public_results.php?id=<?= $election_id ?>" class="btn btn-info btn-sm ms-2" target="_blank">
                                View Public Page
                            </a>
                        <?php endif; ?>
                    <?php else: ?>
                        <div class="alert alert-warning">
                            <i class="fas fa-clock"></i> Results can be published after election ends on 
                            <?= $election['end_date'] ?>
                        </div>
                    <?php endif; ?>
                </div>
                
                <!-- Summary Cards -->
                <div class="row mt-4">
                    <div class="col-md-3">
                        <div class="card bg-primary text-white">
                            <div class="card-body">
                                <h6>Total Votes Cast</h6>
                                <h3><?= $election['total_votes'] ?></h3>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card bg-success text-white">
                            <div class="card-body">
                                <h6>Voter Turnout</h6>
                                <h3><?= round(($election['total_voters'] / $election['registered_voters']) * 100, 1) ?>%</h3>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-body">
                                <h6>Election Period</h6>
                                <p class="mb-0">
                                    <?= date('Y-m-d H:i', strtotime($election['start_date'])) ?> to 
                                    <?= date('Y-m-d H:i', strtotime($election['end_date'])) ?>
                                </p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Results Table and Charts -->
                <div class="row mt-4">
                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-header">
                                <h5>Candidate Results</h5>
                            </div>
                            <div class="card-body">
                                <table class="table">
                                    <thead>
                                        <tr>
                                            <th>Candidate</th>
                                            <th>Votes</th>
                                            <th>Percentage</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach($candidates as $candidate): ?>
                                        <tr>
                                            <td>
                                                <?php if($candidate['photo_url']): ?>
                                                    <img src="../uploads/<?= htmlspecialchars($candidate['photo_url']) ?>" 
                                                         class="candidate-photo me-2">
                                                <?php endif; ?>
                                                <?= htmlspecialchars($candidate['name']) ?>
                                            </td>
                                            <td><?= $candidate['vote_count'] ?></td>
                                            <td><?= round($candidate['vote_percentage'], 1) ?>%</td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-header">
                                <h5>Voting Timeline</h5>
                            </div>
                            <div class="card-body">
                                <div class="chart-container">
                                    <canvas id="timelineChart"></canvas>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
    // Add this function before the existing script
    function publishResults(electionId) {
        if(confirm('Are you sure you want to publish the results? This will make them publicly visible.')) {
            window.location.href = 'process_results.php?action=publish&id=' + electionId;
        }
    }
    
    // Timeline Chart
    new Chart(document.getElementById('timelineChart'), {
        type: 'line',
        data: {
            labels: <?= json_encode(array_column($timeline, 'vote_date')) ?>,
            datasets: [{
                label: 'Votes per Day',
                data: <?= json_encode(array_column($timeline, 'daily_votes')) ?>,
                borderColor: 'rgb(75, 192, 192)',
                tension: 0.1,
                fill: false
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: { stepSize: 1 }
                }
            }
        }
    });
    </script>
</body>
</html>
