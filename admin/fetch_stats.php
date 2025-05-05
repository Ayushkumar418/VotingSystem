<?php
require_once '../config/database.php';
require_once '../includes/auth.php';

if (!is_logged_in() || !is_admin()) {
    http_response_code(403);
    exit();
}

$db = new Database();
$conn = $db->connect();

$stats = [
    'total_voters' => $conn->query("SELECT COUNT(*) FROM users WHERE is_admin = 0")->fetchColumn(),
    'total_elections' => $conn->query("SELECT COUNT(*) FROM elections")->fetchColumn(),
    'active_elections' => $conn->query("SELECT COUNT(*) FROM elections WHERE is_active = 1 AND NOW() BETWEEN start_date AND end_date")->fetchColumn(),
    'total_votes' => $conn->query("SELECT COUNT(DISTINCT v.id) FROM votes v JOIN elections e ON v.election_id = e.id WHERE v.candidate_id IS NOT NULL")->fetchColumn(),
    'ended_elections' => $conn->query("SELECT COUNT(*) FROM elections WHERE NOW() > end_date")->fetchColumn()
];

header('Content-Type: application/json');
echo json_encode($stats);
