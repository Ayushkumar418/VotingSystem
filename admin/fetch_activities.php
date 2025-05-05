<?php
require_once '../config/database.php';
require_once '../includes/auth.php';

if (!is_logged_in() || !is_admin()) {
    http_response_code(403);
    exit();
}

$db = new Database();
$conn = $db->connect();

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

$activities = $stmt->fetchAll(PDO::FETCH_ASSOC);

header('Content-Type: application/json');
echo json_encode($activities);
