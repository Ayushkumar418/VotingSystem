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
        :root {
            --primary-color: #4e54c8;
            --secondary-color: #8f94fb;
            --accent-color: #6c63ff;
            --text-light: #ffffff;
            --text-dark: #2c3e50;
        }

        body {
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
        }

        .container {
            padding: 50px 0;
        }

        .card {
            border: none;
            border-radius: 20px;
            box-shadow: 0 15px 30px rgba(0,0,0,0.2);
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            transition: transform 0.3s ease;
        }

        .card:hover {
            transform: translateY(-5px);
        }

        .card-header {
            background: transparent;
            border-bottom: none;
            padding: 30px 25px 0;
        }

        .card-header h3 {
            font-size: 28px;
            font-weight: 600;
            color: var(--primary-color);
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .feature-box {
            padding: 25px;
            text-align: center;
            background: rgba(255,255,255,0.1);
            border: 1px solid rgba(255,255,255,0.2);
            border-radius: 15px;
            margin-bottom: 20px;
            color: var(--text-light);
            backdrop-filter: blur(5px);
            transition: all 0.3s ease;
        }

        .feature-box:hover {
            transform: translateY(-5px);
            background: rgba(255,255,255,0.2);
        }

        .feature-icon {
            font-size: 2.5em;
            margin-bottom: 15px;
            color: var(--text-light);
        }

        .input-group {
            margin-bottom: 20px;
        }

        .input-group-text {
            background: transparent;
            border: 1px solid rgba(78, 84, 200, 0.2);
            border-right: none;
            color: var(--primary-color);
            padding: 12px 15px;
        }

        .form-control {
            border: 1px solid rgba(78, 84, 200, 0.2);
            border-left: none;
            padding: 12px 15px;
        }

        .form-control:focus {
            box-shadow: 0 0 0 0.2rem rgba(78, 84, 200, 0.25);
            border-color: var(--primary-color);
        }

        .btn-primary {
            background: linear-gradient(45deg, var(--primary-color), var(--secondary-color));
            padding: 12px;
            font-weight: 500;
            font-size: 16px;
            border: none;
            border-radius: 8px;
            transition: all 0.3s ease;
        }

        .btn-primary:hover {
            background: linear-gradient(45deg, var(--secondary-color), var(--primary-color));
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.2);
        }

        .btn-dark {
            background: var(--text-dark);
            border-radius: 8px;
            padding: 10px 20px;
            transition: all 0.3s ease;
        }

        .btn-dark:hover {
            background: #1a2634;
            transform: translateY(-2px);
        }

        a.text-decoration-none {
            color: var(--primary-color);
            font-weight: 500;
            transition: color 0.3s ease;
        }

        a.text-decoration-none:hover {
            color: var(--secondary-color);
        }

        .form-label {
            color: var(--text-dark);
            font-weight: 500;
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
                        <h3 class="text-center mb-0">Login to Vote</h3>
                    </div>
                    <div class="card-body p-4">
                        <?php if(isset($_GET['error'])): ?>
                            <div class="alert alert-danger">
                                Invalid credentials. Please try again.
                            </div>
                        <?php endif; ?>
                        <form action="process_login.php" method="POST">
                            <div class="mb-3">
                                <label class="form-label">Email/Voter ID</label>
                                <div class="input-group">
                                    <span class="input-group-text bg-white">
                                        <i class="fas fa-user text-muted"></i>
                                    </span>
                                    <input type="text" name="identifier" class="form-control" required 
                                           placeholder="Enter your email or voter ID">
                                </div>
                            </div>
                            <div class="mb-4">
                                <label class="form-label">Password</label>
                                <div class="input-group">
                                    <span class="input-group-text bg-white">
                                        <i class="fas fa-lock text-muted"></i>
                                    </span>
                                    <input type="password" name="password" class="form-control" required
                                           placeholder="Enter your password">
                                </div>
                            </div>
                            <button type="submit" class="btn btn-primary w-100 mb-3">Login</button>
                            <div class="text-center">
                                <a href="register.php" class="text-decoration-none">Register as New Voter</a>
                            </div>
                        </form>
                        <hr class="my-4">
                        <div class="text-center">
                            <a href="admin/login.php" class="btn btn-dark">
                                <i class="fas fa-user-shield me-2"></i>Admin Login
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <footer class="text-center text-white mt-5"><hr>
            <p>&copy; 2025 Online Voting System. All rights reserved.</p>
        </footer>
    </div>
</body>
</html>
