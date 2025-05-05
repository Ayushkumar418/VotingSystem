<?php
require_once '../config/database.php';
require_once '../includes/auth.php';

if (!is_logged_in() || !is_admin()) {
    header('Location: ../index.php');
    exit();
}

$election_id = filter_input(INPUT_GET, 'election_id', FILTER_SANITIZE_NUMBER_INT);
$db = new Database();
$conn = $db->connect();

// Get election details with status
$stmt = $conn->prepare("
    SELECT *,
    CASE 
        WHEN NOW() > end_date THEN 'ended'
        WHEN NOW() BETWEEN start_date AND end_date AND is_active = true THEN 'active'
        ELSE 'upcoming'
    END as status 
    FROM elections WHERE id = ?
");
$stmt->execute([$election_id]);
$election = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$election) {
    header('Location: elections.php');
    exit();
}

// Get candidates for this election
$stmt = $conn->prepare("SELECT * FROM candidates WHERE election_id = ?");
$stmt->execute([$election_id]);
$candidates = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html>
<head>
    <title>Manage Candidates - <?= htmlspecialchars($election['title']) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .sidebar { min-height: 100vh; background: #343a40; color: white; }
        .candidate-photo { width: 50px; height: 50px; object-fit: cover; border-radius: 50%; }
    </style>
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <div class="col-md-2 sidebar p-3">
                <h4 class="text-center mb-4">Admin Panel</h4>
                <div class="list-group">
                    <a href="elections.php" class="list-group-item list-group-item-action">
                        <i class="fas fa-arrow-left me-2"></i> Back to Elections
                    </a>
                </div>
            </div>

            <div class="col-md-10 p-4">
                <h2><?= htmlspecialchars($election['title']) ?> - Manage Candidates</h2>
                <p class="text-muted">Election period: <?= date('Y-m-d H:i', strtotime($election['start_date'])) ?> to <?= date('Y-m-d H:i', strtotime($election['end_date'])) ?></p>

                <div class="mb-4">
                    <?php if($election['status'] === 'upcoming'): ?>
                        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addCandidateModal">
                            <i class="fas fa-plus"></i> Add New Candidate
                        </button>
                    <?php else: ?>
                        <div class="alert alert-warning">
                            <i class="fas fa-info-circle"></i> Cannot modify candidates once election has started
                        </div>
                    <?php endif; ?>
                </div>

                <div class="card">
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>Photo</th>
                                        <th>Name</th>
                                        <th>Description</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach($candidates as $candidate): ?>
                                    <tr>
                                        <td>
                                            <?php if($candidate['photo_url']): ?>
                                                <img src="../uploads/<?= htmlspecialchars($candidate['photo_url']) ?>" 
                                                     class="candidate-photo">
                                            <?php else: ?>
                                                <i class="fas fa-user-circle fa-2x"></i>
                                            <?php endif; ?>
                                        </td>
                                        <td><?= htmlspecialchars($candidate['name']) ?></td>
                                        <td><?= htmlspecialchars($candidate['description']) ?></td>
                                        <td>
                                            <?php if($election['status'] === 'upcoming'): ?>
                                                <button class="btn btn-sm btn-primary" onclick="editCandidate(<?= $candidate['id'] ?>)">
                                                    <i class="fas fa-edit"></i>
                                                </button>
                                                <button class="btn btn-sm btn-danger" onclick="deleteCandidate(<?= $candidate['id'] ?>)">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            <?php else: ?>
                                                <button class="btn btn-sm btn-secondary" disabled title="Cannot modify candidates once election has started">
                                                    <i class="fas fa-edit"></i>
                                                </button>
                                                <button class="btn btn-sm btn-secondary" disabled title="Cannot modify candidates once election has started">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            <?php endif; ?>
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
    </div>

    <!-- Add Candidate Modal -->
    <div class="modal fade" id="addCandidateModal">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Add New Candidate</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form action="process_candidate.php" method="POST" enctype="multipart/form-data">
                    <input type="hidden" name="election_id" value="<?= $election_id ?>">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label>Name</label>
                            <input type="text" name="name" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label>Description</label>
                            <textarea name="description" class="form-control" rows="3"></textarea>
                        </div>
                        <div class="mb-3">
                            <label>Photo</label>
                            <input type="file" name="photo" class="form-control" accept="image/*">
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                        <button type="submit" class="btn btn-primary">Add Candidate</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
    function deleteCandidate(id) {
        if(confirm('Are you sure you want to delete this candidate?')) {
            window.location.href = 'process_candidate.php?action=delete&id=' + id;
        }
    }
    
    function editCandidate(id) {
        // Redirect to edit page
        window.location.href = 'edit_candidate.php?id=' + id;
    }
    </script>
</body>
</html>
