<?php
require_once 'config/database.php';
require_once 'includes/auth.php';

if (!is_logged_in()) {
    header('Location: index.php');
    exit();
}

$db = new Database();
$conn = $db->connect();

$stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html>
<head>
    <title>My Profile - Online Voting System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container">
            <a class="navbar-brand" href="<?= is_admin() ? 'admin/index.php' : 'dashboard.php' ?>">Online Voting System</a>
            <div class="navbar-nav ms-auto">
                <a href="logout.php" class="nav-link">Logout</a>
            </div>
        </div>
    </nav>

    <div class="container mt-4">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header">
                        <h3>My Profile</h3>
                    </div>
                    <div class="card-body">
                        <?php if(isset($_GET['success'])): ?>
                            <div class="alert alert-success">Profile updated successfully!</div>
                        <?php endif; ?>

                        <form action="update_profile.php" method="POST">
                            <div class="mb-3">
                                <label>Full Name</label>
                                <input type="text" name="full_name" class="form-control" 
                                       value="<?= htmlspecialchars($user['full_name']) ?>" required>
                            </div>
                            <div class="mb-3">
                                <label>Email</label>
                                <input type="email" name="email" class="form-control" 
                                       value="<?= htmlspecialchars($user['email']) ?>" required>
                            </div>
                            <div class="mb-3">
                                <label>Voter ID</label>
                                <input type="text" class="form-control" 
                                       value="<?= htmlspecialchars($user['voter_id']) ?>" readonly>
                            </div>
                            <div class="mb-3">
                                <label>New Password (leave blank to keep current)</label>
                                <input type="password" name="new_password" class="form-control">
                            </div>
                            <div class="mb-3">
                                <label>Current Password (required to save changes)</label>
                                <input type="password" name="current_password" class="form-control" required>
                            </div>
                            <button type="submit" class="btn btn-primary">Update Profile</button>
                            <a href="<?= is_admin() ? 'admin/index.php' : 'dashboard.php' ?>" 
                               class="btn btn-secondary">Back</a>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
