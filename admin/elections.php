<?php
require_once '../config/database.php';
require_once '../includes/auth.php';

if (!is_logged_in() || !is_admin()) {
    header('Location: ../index.php');
    exit();
}

$db = new Database();
$conn = $db->connect();

$stmt = $conn->query("
    SELECT *,
    CASE 
        WHEN NOW() > end_date THEN 'ended'
        WHEN NOW() BETWEEN start_date AND end_date AND is_active = true THEN 'active'
        ELSE 'upcoming'
    END as status
    FROM elections 
    ORDER BY start_date DESC
");
$elections = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html>
<head>
    <title>Manage Elections - Admin Dashboard</title>
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
                    <a href="elections.php" class="list-group-item list-group-item-action active">
                        <i class="fas fa-vote-yea me-2"></i> Elections
                    </a>
                    <a href="candidates.php" class="list-group-item list-group-item-action">
                        <i class="fas fa-users me-2"></i> Candidates
                    </a>
                    <a href="voters.php" class="list-group-item list-group-item-action">
                        <i class="fas fa-user-check me-2"></i> Voters
                    </a>
                    <a href="../logout.php" class="list-group-item list-group-item-action text-danger">
                        <i class="fas fa-sign-out-alt me-2"></i> Logout
                    </a>
                </div>
            </div>

            <!-- Main Content -->
            <div class="col-md-10 p-4">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h2>Manage Elections</h2>
                    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addElectionModal">
                        <i class="fas fa-plus"></i> Create New Election
                    </button>
                </div>

                <?php if(isset($_GET['success'])): ?>
                <div class="alert alert-success">
                    Election operation completed successfully.
                </div>
                <?php endif; ?>

                <?php if(isset($_GET['error'])): ?>
                <div class="alert alert-danger">
                    <?php 
                        switch($_GET['error']) {
                            case 'invalid_dates':
                                echo "End date must be after start date.";
                                break;
                            case 'creation_failed':
                                echo "Failed to create election. Please try again.";
                                break;
                            default:
                                echo "An error occurred. Please try again.";
                        }
                    ?>
                </div>
                <?php endif; ?>

                <!-- Elections Table -->
                <div class="card">
                    <div class="card-body">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Title</th>
                                    <th>Start Date</th>
                                    <th>End Date</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach($elections as $election): ?>
                                <tr>
                                    <td><?= htmlspecialchars($election['title']) ?></td>
                                    <td><?= $election['start_date'] ?></td>
                                    <td><?= $election['end_date'] ?></td>
                                    <td>
                                        <?php if($election['status'] === 'ended'): ?>
                                            <span class="badge bg-secondary">Ended</span>
                                        <?php elseif($election['status'] === 'active'): ?>
                                            <span class="badge bg-success">Active</span>
                                        <?php else: ?>
                                            <span class="badge bg-warning">Upcoming</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <a href="edit_election.php?id=<?= $election['id'] ?>" 
                                           class="btn btn-sm btn-primary">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <a href="manage_candidates.php?election_id=<?= $election['id'] ?>" 
                                           class="btn btn-sm btn-info">
                                            <i class="fas fa-users"></i>
                                        </a>
                                        <button class="btn btn-sm btn-danger" 
                                                onclick="deleteElection(<?= $election['id'] ?>)">
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

    <!-- Add Election Modal -->
    <div class="modal fade" id="addElectionModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Create New Election</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form action="process_election.php" method="POST">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label>Election Title</label>
                            <input type="text" name="title" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label>Description</label>
                            <textarea name="description" class="form-control" rows="3"></textarea>
                        </div>
                        <div class="mb-3">
                            <label>Start Date</label>
                            <input type="datetime-local" name="start_date" 
                                   class="form-control" 
                                   required
                                   step="1">
                        </div>
                        <div class="mb-3">
                            <label>End Date</label>
                            <input type="datetime-local" name="end_date" 
                                   class="form-control" 
                                   required
                                   step="1">
                        </div>
                        <div class="mb-3">
                            <div class="form-check">
                                <input type="checkbox" name="is_active" class="form-check-input" id="isActive">
                                <label class="form-check-label" for="isActive">Make Election Active</label>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                        <button type="submit" class="btn btn-primary">Create Election</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function deleteElection(id) {
            if(confirm('Are you sure you want to delete this election?')) {
                window.location.href = 'process_election.php?action=delete&id=' + id;
            }
        }
    </script>
</body>
</html>
