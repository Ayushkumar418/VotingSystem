<?php
require_once '../config/database.php';
require_once '../includes/auth.php';

if (!is_logged_in() || !is_admin()) {
    header('Location: ../index.php');
    exit();
}

$id = filter_input(INPUT_GET, 'id', FILTER_SANITIZE_NUMBER_INT);
$db = new Database();
$conn = $db->connect();

$stmt = $conn->prepare("SELECT * FROM elections WHERE id = ?");
$stmt->execute([$id]);
$election = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$election) {
    header('Location: elections.php');
    exit();
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Edit Election - Admin Dashboard</title>
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
                </div>
            </div>

            <!-- Main Content -->
            <div class="col-md-10 p-4">
                <div class="card">
                    <div class="card-header">
                        <h3>Edit Election</h3>
                    </div>
                    <div class="card-body">
                        <form action="process_election.php" method="POST">
                            <input type="hidden" name="action" value="edit">
                            <input type="hidden" name="id" value="<?= $election['id'] ?>">
                            
                            <div class="mb-3">
                                <label>Election Title</label>
                                <input type="text" name="title" class="form-control" 
                                       value="<?= htmlspecialchars($election['title']) ?>" required>
                            </div>
                            
                            <div class="mb-3">
                                <label>Description</label>
                                <textarea name="description" class="form-control" rows="3"><?= htmlspecialchars($election['description']) ?></textarea>
                            </div>
                            
                            <div class="mb-3">
                                <label>Start Date</label>
                                <input type="datetime-local" name="start_date" class="form-control" 
                                       value="<?= date('Y-m-d\TH:i', strtotime($election['start_date'])) ?>" required>
                            </div>
                            
                            <div class="mb-3">
                                <label>End Date</label>
                                <input type="datetime-local" name="end_date" class="form-control" 
                                       value="<?= date('Y-m-d\TH:i', strtotime($election['end_date'])) ?>" required>
                            </div>
                            
                            <div class="mb-3">
                                <div class="form-check">
                                    <input type="checkbox" name="is_active" class="form-check-input" 
                                           <?= $election['is_active'] ? 'checked' : '' ?>>
                                    <label class="form-check-label">Active Election</label>
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <button type="submit" class="btn btn-primary">Update Election</button>
                                <a href="elections.php" class="btn btn-secondary">Cancel</a>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
