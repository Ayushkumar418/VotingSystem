<?php
require_once '../config/database.php';
require_once '../includes/auth.php';

if (!is_logged_in() || !is_admin()) {
    header('Location: ../index.php');
    exit();
}

$db = new Database();
$conn = $db->connect();

// Get all candidates with election names
$stmt = $conn->query("
    SELECT c.*, e.title as election_title 
    FROM candidates c 
    JOIN elections e ON c.election_id = e.id 
    ORDER BY e.title, c.name
");
$candidates = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get elections for dropdown
$stmt = $conn->query("SELECT id, title FROM elections ORDER BY title");
$elections = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html>
<head>
    <title>Manage Candidates - Admin Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .sidebar { min-height: 100vh; background: #343a40; color: white; }
        .candidate-photo { width: 50px; height: 50px; object-fit: cover; }
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
                    <a href="candidates.php" class="list-group-item list-group-item-action active">
                        <i class="fas fa-users me-2"></i> Candidates
                    </a>
                </div>
            </div>

            <!-- Main Content -->
            <div class="col-md-10 p-4">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h2>Manage Candidates</h2>
                    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addCandidateModal">
                        <i class="fas fa-plus"></i> Add New Candidate
                    </button>
                </div>

                <?php if(isset($_GET['success'])): ?>
                <div class="alert alert-success">
                    Operation completed successfully.
                </div>
                <?php endif; ?>

                <?php if(isset($_SESSION['candidate_errors'])): ?>
                <div class="alert alert-danger">
                    <ul class="mb-0">
                        <?php foreach($_SESSION['candidate_errors'] as $error): ?>
                            <li><?= htmlspecialchars($error) ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
                <?php 
                    unset($_SESSION['candidate_errors']);
                endif; 
                ?>

                <!-- Candidates Table -->
                <div class="card">
                    <div class="card-body">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Photo</th>
                                    <th>Name</th>
                                    <th>Election</th>
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
                                                 class="candidate-photo rounded">
                                        <?php else: ?>
                                            <i class="fas fa-user fa-2x text-secondary"></i>
                                        <?php endif; ?>
                                    </td>
                                    <td><?= htmlspecialchars($candidate['name']) ?></td>
                                    <td><?= htmlspecialchars($candidate['election_title']) ?></td>
                                    <td><?= htmlspecialchars($candidate['description']) ?></td>
                                    <td>
                                        <button class="btn btn-sm btn-primary" onclick="editCandidate(<?= $candidate['id'] ?>)">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <button class="btn btn-sm btn-danger" onclick="deleteCandidate(<?= $candidate['id'] ?>)">
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

    <!-- Add Candidate Modal -->
    <div class="modal fade" id="addCandidateModal">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Add New Candidate</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form action="process_candidate.php" method="POST" enctype="multipart/form-data" onsubmit="return validateCandidateForm()">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label>Name *</label>
                            <input type="text" name="name" class="form-control" required minlength="2">
                        </div>
                        <div class="mb-3">
                            <label>Election *</label>
                            <select name="election_id" class="form-control" required>
                                <option value="">Select Election</option>
                                <?php foreach($elections as $election): ?>
                                    <option value="<?= $election['id'] ?>">
                                        <?= htmlspecialchars($election['title']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label>Description *</label>
                            <textarea name="description" class="form-control" rows="3" required></textarea>
                        </div>
                        <div class="mb-3">
                            <label>Photo *</label>
                            <input type="file" name="photo" class="form-control" accept="image/*" required>
                            <small class="text-muted">Required for new candidates</small>
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

    <!-- Edit Candidate Modal -->
    <div class="modal fade" id="editCandidateModal">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Edit Candidate</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form action="process_candidate.php" method="POST" enctype="multipart/form-data">
                    <input type="hidden" name="action" value="edit">
                    <input type="hidden" name="id" id="edit_candidate_id">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label>Name</label>
                            <input type="text" name="name" id="edit_name" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label>Election</label>
                            <select name="election_id" id="edit_election_id" class="form-control" required>
                                <?php foreach($elections as $election): ?>
                                    <option value="<?= $election['id'] ?>">
                                        <?= htmlspecialchars($election['title']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label>Description</label>
                            <textarea name="description" id="edit_description" class="form-control" rows="3"></textarea>
                        </div>
                        <div class="mb-3">
                            <label>New Photo (leave empty to keep current)</label>
                            <input type="file" name="photo" class="form-control" accept="image/*">
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                        <button type="submit" class="btn btn-primary">Update Candidate</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
    function editCandidate(id) {
        fetch('edit_candidate.php?id=' + id)
            .then(response => response.json())
            .then(data => {
                document.getElementById('edit_candidate_id').value = data.id;
                document.getElementById('edit_name').value = data.name;
                document.getElementById('edit_description').value = data.description;
                document.getElementById('edit_election_id').value = data.election_id;
                
                var modal = new bootstrap.Modal(document.getElementById('editCandidateModal'));
                modal.show();
            });
    }

    function deleteCandidate(id) {
        if(confirm('Are you sure you want to delete this candidate?')) {
            window.location.href = 'process_candidate.php?action=delete&id=' + id;
        }
    }

    // Add form validation function
    function validateCandidateForm() {
        const form = event.target;
        const requiredFields = ['name', 'election_id', 'description'];
        
        for (let field of requiredFields) {
            const value = form[field].value.trim();
            if (!value) {
                alert(`${field.charAt(0).toUpperCase() + field.slice(1)} is required`);
                return false;
            }
        }
        
        // Check photo for new candidates
        const isEdit = form.querySelector('input[name="action"]')?.value === 'edit';
        if (!isEdit && !form['photo'].files[0]) {
            alert('Photo is required for new candidates');
            return false;
        }
        
        return true;
    }
    </script>
</body>
</html>
