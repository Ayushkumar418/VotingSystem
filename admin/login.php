<?php
require_once '../config/database.php';
require_once '../includes/auth.php';

if(is_logged_in() && is_admin()) {
    header('Location: index.php');
    exit();
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Admin Login - Online Voting System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #1e3c72 0%, #2a5298 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
        }
        .card {
            border: none;
            border-radius: 15px;
            box-shadow: 0 10px 20px rgba(0,0,0,0.2);
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-4">
                <div class="card">
                    <div class="card-body p-4">
                        <div class="text-center mb-4">
                            <i class="fas fa-user-shield fa-3x text-primary"></i>
                            <h3 class="mt-3">Admin Login</h3>
                        </div>
                        
                        <?php if(isset($_GET['error'])): ?>
                        <div class="alert alert-danger">
                            <?php 
                            $error = $_GET['error'];
                            if ($error == 1) {
                                echo "Invalid email or password. Please try again.";
                            } else {
                                echo "An error occurred. Please try again.";
                            }
                            ?>
                        </div>
                        <?php endif; ?>

                        <form action="process_admin_login.php" method="POST">
                            <div class="mb-3">
                                <label>Email</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="fas fa-envelope"></i></span>
                                    <input type="email" name="email" class="form-control" required>
                                </div>
                            </div>
                            <div class="mb-3">
                                <label>Password</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="fas fa-lock"></i></span>
                                    <input type="password" name="password" class="form-control" required>
                                </div>
                            </div>
                            <button type="submit" class="btn btn-primary w-100">Login as Admin</button>
                        </form>
                        
                        <div class="text-center mt-3">
                            <a href="../index.php" class="text-decoration-none">
                                <i class="fas fa-arrow-left"></i> Back to Main Page
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
