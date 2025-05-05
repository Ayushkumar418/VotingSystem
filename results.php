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

// Get election details
$stmt = $conn->prepare("SELECT * FROM elections WHERE id = ?");
$stmt->execute([$election_id]);
$election = $stmt->fetch(PDO::FETCH_ASSOC);

// Get vote results
$stmt = $conn->prepare("
    SELECT c.*, COUNT(v.id) as vote_count 
    FROM candidates c 
    LEFT JOIN votes v ON c.id = v.candidate_id 
    WHERE c.election_id = ? 
    GROUP BY c.id
");
$stmt->execute([$election_id]);
$results = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html>
<head>
    <title>Election Results</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>
    <div class="container mt-4">
        <h2>Results: <?= htmlspecialchars($election['title']) ?></h2>
        
        <div class="row">
            <div class="col-md-8">
                <canvas id="resultsChart"></canvas>
            </div>
            <div class="col-md-4">
                <div class="list-group">
                    <?php foreach ($results as $result): ?>
                        <div class="list-group-item">
                            <h5><?= htmlspecialchars($result['name']) ?></h5>
                            <p>Votes: <?= $result['vote_count'] ?></p>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
        
        <div class="mt-3">
            <a href="dashboard.php" class="btn btn-primary">Back to Dashboard</a>
        </div>
    </div>

    <script>
        const ctx = document.getElementById('resultsChart').getContext('2d');
        new Chart(ctx, {
            type: 'bar',
            data: {
                labels: <?= json_encode(array_column($results, 'name')) ?>,
                datasets: [{
                    label: 'Votes',
                    data: <?= json_encode(array_column($results, 'vote_count')) ?>,
                    backgroundColor: 'rgba(54, 162, 235, 0.5)'
                }]
            },
            options: {
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

        // Auto-refresh results every 30 seconds
        setInterval(() => {
            location.reload();
        }, 30000);
    </script>
</body>
</html>
