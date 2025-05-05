<?php
require_once 'config/database.php';
require_once 'includes/auth.php';

if(is_logged_in()) {
    header('Location: dashboard.php');
    exit();
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Online Voting System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
        }
        .container {
            padding-top: 50px;
        }
        .card {
            border: none;
            border-radius: 15px;
            box-shadow: 0 10px 20px rgba(0,0,0,0.2);
        }
        .card-header {
            background: transparent;
            border-bottom: none;
            padding: 25px;
        }
        .feature-box {
            padding: 20px;
            text-align: center;
            background: rgba(255,255,255,0.1);
            border-radius: 10px;
            margin-bottom: 20px;
            color: white;
        }
        .feature-icon {
            font-size: 2em;
            margin-bottom: 15px;
            color: #fff;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="row mb-5">
            <div class="col-md-12 text-center text-white">
                <h1>Online Voting System</h1>
                <p class="lead">A secure and transparent platform for digital democracy</p>
            </div>
        </div>
        
        <div class="row">
            <div class="col-md-6">
                <!-- Features Section -->
                <div class="row">
                    <div class="col-md-6">
                        <div class="feature-box">
                            <i class="fas fa-shield-alt feature-icon"></i>
                            <h4>Secure Voting</h4>
                            <p>End-to-end encryption and secure authentication</p>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="feature-box">
                            <i class="fas fa-clock feature-icon"></i>
                            <h4>Real-time Results</h4>
                            <p>Instant vote counting and live results</p>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="feature-box">
                            <i class="fas fa-mobile-alt feature-icon"></i>
                            <h4>Mobile Friendly</h4>
                            <p>Vote from any device, anywhere</p>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="feature-box">
                            <i class="fas fa-chart-bar feature-icon"></i>
                            <h4>Analytics</h4>
                            <p>Detailed voting statistics and reports</p>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-md-6">
                <!-- Login Form -->
                <div class="card">
                    <div class="card-header">
                        <h3 class="text-center">Login to Vote</h3>
                    </div>
                    <div class="card-body p-4">
                        <?php if(isset($_GET['error'])): ?>
                            <div class="alert alert-danger">
                                Invalid credentials. Please try again.
                            </div>
                        <?php endif; ?>
                        <form action="process_login.php" method="POST">
                            <div class="mb-4">
                                <label class="form-label">Email/Voter ID</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="fas fa-user"></i></span>
                                    <input type="text" name="identifier" class="form-control" required>
                                </div>
                            </div>
                            <div class="mb-4">
                                <label class="form-label">Password</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="fas fa-lock"></i></span>
                                    <input type="password" name="password" class="form-control" required>
                                </div>
                            </div>
                            <button type="submit" class="btn btn-primary w-100 mb-3">Login</button>
                            <p class="text-center mb-0">
                                <a href="register.php" class="text-decoration-none">Register as New Voter</a>
                            </p>
                        </form>
                        <hr class="my-4">
                        <div class="text-center">
                            <a href="admin/login.php" class="btn btn-dark">
                                <i class="fas fa-user-shield"></i> Admin Login
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <footer class="text-center text-white mt-5">
            <p>&copy; 2023 Online Voting System. All rights reserved.</p>
        </footer>
    </div>
</body>
</html>
