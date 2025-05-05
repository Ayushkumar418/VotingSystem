<?php
require_once '../config/database.php';
require_once '../includes/auth.php';

if (!is_logged_in() || !is_admin()) {
    header('Location: ../index.php');
    exit();
}

$db = new Database();
$conn = $db->connect();

$stmt = $conn->query("SELECT * FROM elections ORDER BY start_date DESC");
$elections = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html>
<head>
    <title>Manage Elections</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
        <div class="container">
            <a class="navbar-brand" href="index.php">Admin Panel</a>
        </div>
    </nav>

    <div class="container mt-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2>Manage Elections</h2>
            <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addElectionModal">
                Add New Election
            </button>
        </div>

        <table class="table table-striped">
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
                <?php foreach ($elections as $election): ?>
                    <tr>
                        <td><?= htmlspecialchars($election['title']) ?></td>
                        <td><?= $election['start_date'] ?></td>
                        <td><?= $election['end_date'] ?></td>
                        <td>
                            <?= $election['is_active'] ? 
                                '<span class="badge bg-success">Active</span>' : 
                                '<span class="badge bg-secondary">Inactive</span>' ?>
                        </td>
                        <td>
                            <a href="edit_election.php?id=<?= $election['id'] ?>" 
                               class="btn btn-sm btn-primary">Edit</a>
                            <a href="view_results.php?id=<?= $election['id'] ?>" 
                               class="btn btn-sm btn-info">Results</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <!-- Add Election Modal -->
    <div class="modal fade" id="addElectionModal">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Add New Election</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form action="process_election.php" method="POST">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label>Title</label>
                            <input type="text" name="title" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label>Description</label>
                            <textarea name="description" class="form-control" rows="3"></textarea>
                        </div>
                        <div class="mb-3">
                            <label>Start Date</label>
                            <input type="datetime-local" name="start_date" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label>End Date</label>
                            <input type="datetime-local" name="end_date" class="form-control" required>
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
</body>
</html>
