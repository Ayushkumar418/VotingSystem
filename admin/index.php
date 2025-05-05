<?php
require_once '../config/database.php';
require_once '../includes/auth.php';

if (!is_logged_in() || !is_admin()) {
    header('Location: ../index.php');
    exit();
}

$db = new Database();
$conn = $db->connect();

// Get recent activities
$stmt = $conn->query("
    SELECT 
        al.*, 
        u.full_name,
        CASE 
            WHEN al.created_at > DATE_SUB(NOW(), INTERVAL 5 MINUTE) THEN 'Just now'
            WHEN al.created_at > DATE_SUB(NOW(), INTERVAL 1 HOUR) THEN CONCAT(
                TIMESTAMPDIFF(MINUTE, al.created_at, NOW()),
                ' minutes ago'
            )
            WHEN al.created_at > DATE_SUB(NOW(), INTERVAL 24 HOUR) THEN CONCAT(
                TIMESTAMPDIFF(HOUR, al.created_at, NOW()),
                ' hours ago'
            )
            WHEN al.created_at > DATE_SUB(NOW(), INTERVAL 7 DAY) THEN CONCAT(
                TIMESTAMPDIFF(DAY, al.created_at, NOW()),
                ' days ago'
            )
            ELSE DATE_FORMAT(al.created_at, '%Y-%m-%d %H:%i')
        END as formatted_date
    FROM activity_logs al
    LEFT JOIN users u ON al.user_id = u.id
    ORDER BY al.created_at DESC 
    LIMIT 10
");
$recent_activities = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get dashboard statistics
$stats = [
    'total_voters' => $conn->query("SELECT COUNT(*) FROM users WHERE is_admin = 0")->fetchColumn(),
    'total_elections' => $conn->query("SELECT COUNT(*) FROM elections")->fetchColumn(),
    'active_elections' => $conn->query("SELECT COUNT(*) FROM elections WHERE is_active = 1 AND NOW() BETWEEN start_date AND end_date")->fetchColumn(),
    'total_votes' => $conn->query("
        SELECT COUNT(DISTINCT v.id) 
        FROM votes v 
        JOIN elections e ON v.election_id = e.id 
        WHERE v.candidate_id IS NOT NULL")->fetchColumn(),
    'ended_elections' => $conn->query("SELECT COUNT(*) FROM elections WHERE NOW() > end_date")->fetchColumn()
];

function get_activity_icon($type) {
    switch($type) {
        case 'candidate_create':
        case 'candidate_update':
        case 'candidate_delete':
            return 'user-edit';
        case 'vote':
            return 'vote-yea';
        case 'election_create':
        case 'election_update':
            return 'calendar-plus';
        default:
            return 'info-circle';
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Admin Dashboard - Online Voting System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <script src="https://unpkg.com/xlsx/dist/xlsx.full.min.js"></script>
    <style>
        .sidebar {
            min-height: 100vh;
            background: #343a40;
            color: white;
        }
        .stat-card {
            border-radius: 15px;
            transition: transform 0.3s;
        }
        .stat-card:hover {
            transform: translateY(-5px);
        }
    </style>
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <div class="col-md-2 sidebar p-3">
                <h4 class="text-center mb-4">Admin Panel</h4>
                <div class="list-group">
                    <a href="index.php" class="list-group-item list-group-item-action active">
                        <i class="fas fa-tachometer-alt me-2"></i> Dashboard
                    </a>
                    <a href="elections.php" class="list-group-item list-group-item-action">
                        <i class="fas fa-vote-yea me-2"></i> Elections
                    </a>
                    <a href="candidates.php" class="list-group-item list-group-item-action">
                        <i class="fas fa-users me-2"></i> Candidates
                    </a>
                    <a href="voters.php" class="list-group-item list-group-item-action">
                        <i class="fas fa-user-check me-2"></i> Voters
                    </a>
                    <a href="results.php" class="list-group-item list-group-item-action">
                        <i class="fas fa-chart-bar me-2"></i> Results
                    </a>
                    <a href="../profile.php" class="list-group-item list-group-item-action">
                        <i class="fas fa-user me-2"></i> My Profile
                    </a>
                    <a href="../logout.php" class="list-group-item list-group-item-action text-danger">
                        <i class="fas fa-sign-out-alt me-2"></i> Logout
                    </a>
                </div>
            </div>

            <!-- Main Content -->
            <div class="col-md-10 p-4">
                <h2 class="mb-4">Dashboard Overview</h2>
                <div class="text-end mb-3">
                    <button onclick="exportData()" class="btn btn-success">
                        <i class="fas fa-file-excel me-2"></i>Export Data
                    </button>
                </div>
                
                <!-- Statistics Cards -->
                <div class="row">
                    <div class="col-md-3 mb-4">
                        <div class="card stat-card bg-primary text-white">
                            <div class="card-body">
                                <h5><i class="fas fa-users mb-2"></i> Total Voters</h5>
                                <h3 id="total-voters"><?= $stats['total_voters'] ?></h3>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-2 mb-4">
                        <div class="card stat-card bg-success text-white">
                            <div class="card-body">
                                <h5><i class="fas fa-vote-yea mb-2"></i> Active</h5>
                                <h3 id="active-elections"><?= $stats['active_elections'] ?></h3>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-2 mb-4">
                        <div class="card stat-card bg-secondary text-white">
                            <div class="card-body">
                                <h5><i class="fas fa-flag-checkered mb-2"></i> Ended</h5>
                                <h3 id="ended-elections"><?= $stats['ended_elections'] ?></h3>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-2 mb-4">
                        <div class="card stat-card bg-info text-white">
                            <div class="card-body">
                                <h5><i class="fas fa-chart-bar mb-2"></i> Total</h5>
                                <h3 id="total-elections"><?= $stats['total_elections'] ?></h3>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3 mb-4">
                        <div class="card stat-card bg-warning text-white">
                            <div class="card-body">
                                <h5><i class="fas fa-check-double mb-2"></i> Total Votes</h5>
                                <h3 id="total-votes"><?= $stats['total_votes'] ?></h3>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Recent Activities -->
                <div class="card mt-4">
                    <div class="card-header">
                        <h5>Recent Activities</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table" id="activities-table">
                                <thead>
                                    <tr>
                                        <th>Date</th>
                                        <th>User</th>
                                        <th>Activity</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach($recent_activities as $activity): ?>
                                        <tr>
                                            <td><?= $activity['formatted_date'] ?></td>
                                            <td><?= htmlspecialchars($activity['full_name']) ?></td>
                                            <td>
                                                <i class="fas fa-<?= get_activity_icon($activity['activity_type']) ?> me-2"></i>
                                                <?= htmlspecialchars($activity['description']) ?>
                                            </td>
                                            <td>
                                                <span class="badge bg-<?= $activity['status'] === 'success' ? 'success' : 'danger' ?>">
                                                    <?= ucfirst($activity['status']) ?>
                                                </span>
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
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Initial update
            updateDashboard();
            
            // Set up auto-refresh
            setInterval(updateDashboard, 5000); // Refresh every 5 seconds
        });

        function updateDashboard() {
            // Update stats
            fetch('fetch_stats.php')
                .then(response => response.json())
                .then(stats => {
                    Object.keys(stats).forEach(key => {
                        const element = document.getElementById(key.replace('_', '-'));
                        if (element) {
                            element.textContent = stats[key];
                        }
                    });
                })
                .catch(error => console.error('Error updating stats:', error));

            // Update activities
            fetch('fetch_activities.php')
                .then(response => response.json())
                .then(activities => {
                    const tbody = document.querySelector('#activities-table tbody');
                    if (tbody) {
                        tbody.innerHTML = activities.map(activity => `
                            <tr>
                                <td>${activity.formatted_date}</td>
                                <td>${escapeHtml(activity.full_name)}</td>
                                <td>
                                    <i class="fas fa-${getActivityIcon(activity.activity_type)} me-2"></i>
                                    ${escapeHtml(activity.description)}
                                </td>
                                <td>
                                    <span class="badge bg-${activity.status === 'success' ? 'success' : 'danger'}">
                                        ${capitalize(activity.status)}
                                    </span>
                                </td>
                            </tr>
                        `).join('');
                    }
                })
                .catch(error => console.error('Error updating activities:', error));
        }

        async function exportData() {
            try {
                const response = await fetch('fetch_export_data.php');
                const data = await response.json();
                
                // Create workbook
                const wb = XLSX.utils.book_new();
                
                // Add Elections worksheet
                const electionsWs = XLSX.utils.json_to_sheet(data.elections);
                XLSX.utils.book_append_sheet(wb, electionsWs, "Elections");
                
                // Add Results worksheet
                const resultsWs = XLSX.utils.json_to_sheet(data.results);
                XLSX.utils.book_append_sheet(wb, resultsWs, "Results");
                
                // Save file
                XLSX.writeFile(wb, "voting_system_export.xlsx");
            } catch (error) {
                console.error('Export failed:', error);
                alert('Failed to export data. Please try again.');
            }
        }

        function escapeHtml(unsafe) {
            if (!unsafe) return '';
            return unsafe
                .replace(/&/g, "&amp;")
                .replace(/</g, "&lt;")
                .replace(/>/g, "&gt;")
                .replace(/"/g, "&quot;")
                .replace(/'/g, "&#039;");
        }

        function capitalize(string) {
            return string.charAt(0).toUpperCase() + string.slice(1);
        }

        function getActivityIcon(type) {
            const icons = {
                'candidate_create': 'user-plus',
                'candidate_update': 'user-edit',
                'candidate_delete': 'user-minus',
                'vote': 'vote-yea',
                'election_create': 'calendar-plus',
                'election_update': 'calendar-alt'
            };
            return icons[type] || 'info-circle';
        }
    </script>
</body>
</html>
