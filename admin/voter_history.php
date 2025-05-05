<?php
require_once '../config/database.php';
require_once '../includes/auth.php';

if (!is_logged_in() || !is_admin()) {
    header('Location: ../index.php');
    exit();
}

$voter_id = filter_input(INPUT_GET, 'id', FILTER_SANITIZE_NUMBER_INT);
$db = new Database();
$conn = $db->connect();

// Get voter details
$stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$voter_id]);
$voter = $stmt->fetch(PDO::FETCH_ASSOC);

// Get voting history
$stmt = $conn->prepare("
    SELECT v.*, e.title as election_title, c.name as candidate_name 
    FROM votes v 
    JOIN elections e ON v.election_id = e.id 
    JOIN candidates c ON v.candidate_id = c.id 
    WHERE v.user_id = ?
    ORDER BY v.voted_at DESC
");
$stmt->execute([$voter_id]);
$votes = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html>
<head>
    <title>Voter History - Admin Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .sidebar { min-height: 100vh; background: #343a40; color: white; }
    </style>
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <div class="col-md-2 sidebar p-3">
                <h4 class="text-center mb-4">Admin Panel</h4>
                <div class="list-group">
                    <a href="voters.php" class="list-group-item list-group-item-action">
                        <i class="fas fa-arrow-left me-2"></i> Back to Voters
                    </a>
                </div>
            </div>

            <!-- Main Content -->
            <div class="col-md-10 p-4">
                <h2>Voting History - <?= htmlspecialchars($voter['full_name']) ?></h2>
                <p>Voter ID: <?= htmlspecialchars($voter['voter_id']) ?></p>

                <div class="card mt-4">
                    <div class="card-body">
                        <?php if (empty($votes)): ?>
                            <p class="text-muted">No voting history found.</p>
                        <?php else: ?>
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>Election</th>
                                        <th>Candidate Voted</th>
                                        <th>Vote Date</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach($votes as $vote): ?>
                                        <tr>
                                            <td><?= htmlspecialchars($vote['election_title']) ?></td>
                                            <td><?= htmlspecialchars($vote['candidate_name']) ?></td>
                                            <td><?= date('Y-m-d H:i:s', strtotime($vote['voted_at'])) ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
