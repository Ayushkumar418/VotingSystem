<?php
require_once '../config/database.php';
require_once '../includes/auth.php';

if (!is_logged_in() || !is_admin()) {
    header('Location: ../index.php');
    exit();
}

$db = new Database();
$conn = $db->connect();

// Get all voters (non-admin users)
$stmt = $conn->query("SELECT * FROM users WHERE is_admin = 0 ORDER BY created_at DESC");
$voters = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html>
<head>
    <title>Manage Voters - Admin Dashboard</title>
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
                    <a href="index.php" class="list-group-item list-group-item-action">
                        <i class="fas fa-tachometer-alt me-2"></i> Dashboard
                    </a>
                    <a href="elections.php" class="list-group-item list-group-item-action">
                        <i class="fas fa-vote-yea me-2"></i> Elections
                    </a>
                    <a href="candidates.php" class="list-group-item list-group-item-action">
                        <i class="fas fa-users me-2"></i> Candidates
                    </a>
                    <a href="voters.php" class="list-group-item list-group-item-action active">
                        <i class="fas fa-user-check me-2"></i> Voters
                    </a>
                </div>
            </div>

            <!-- Main Content -->
            <div class="col-md-10 p-4">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h2>Manage Voters</h2>
                    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addVoterModal">
                        <i class="fas fa-plus"></i> Add New Voter
                    </button>
                </div>

                <?php if(isset($_GET['success'])): ?>
                <div class="alert alert-success">
                    Operation completed successfully.
                </div>
                <?php endif; ?>

                <!-- Voters Table -->
                <div class="card">
                    <div class="card-body">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Voter ID</th>
                                    <th>Full Name</th>
                                    <th>Email</th>
                                    <th>Registration Date</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach($voters as $voter): ?>
                                <tr>
                                    <td><?= htmlspecialchars($voter['voter_id']) ?></td>
                                    <td><?= htmlspecialchars($voter['full_name']) ?></td>
                                    <td><?= htmlspecialchars($voter['email']) ?></td>
                                    <td><?= date('Y-m-d', strtotime($voter['created_at'])) ?></td>
                                    <td>
                                        <button class="btn btn-sm btn-info" onclick="viewVoterHistory(<?= $voter['id'] ?>)">
                                            <i class="fas fa-history"></i>
                                        </button>
                                        <button class="btn btn-sm btn-danger" onclick="deleteVoter(<?= $voter['id'] ?>)">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Add Voter Modal -->
    <div class="modal fade" id="addVoterModal">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Add New Voter</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form action="process_voter.php" method="POST">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label>Full Name</label>
                            <input type="text" name="full_name" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label>Email</label>
                            <input type="email" name="email" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label>Password</label>
                            <input type="password" name="password" class="form-control" required>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                        <button type="submit" class="btn btn-primary">Add Voter</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
    function viewVoterHistory(id) {
        window.location.href = 'voter_history.php?id=' + id;
    }

    function deleteVoter(id) {
        if(confirm('Are you sure you want to delete this voter? This will also delete their voting history.')) {
            window.location.href = 'process_voter.php?action=delete&id=' + id;
        }
    }
    </script>
</body>
</html>
