<?php
require_once '../config/database.php';
require_once '../includes/auth.php';

if (!is_logged_in() || !is_admin()) {
    header('Location: ../index.php');
    exit();
}

$db = new Database();
$conn = $db->connect();

// Fetch all required data
$data = [
    'elections' => $conn->query("
        SELECT 
            e.title, 
            e.start_date, 
            e.end_date,
            COUNT(DISTINCT v.id) as total_votes,
            CASE 
                WHEN NOW() > end_date THEN 'Ended'
                WHEN NOW() BETWEEN start_date AND end_date THEN 'Active'
                ELSE 'Upcoming'
            END as status
        FROM elections e
        LEFT JOIN votes v ON e.id = e.id
        GROUP BY e.id
    ")->fetchAll(PDO::FETCH_ASSOC),
    
    'results' => $conn->query("
        SELECT 
            e.title as election_title,
            c.name as candidate_name,
            COUNT(v.id) as vote_count,
            ROUND(COUNT(v.id) * 100.0 / NULLIF((
                SELECT COUNT(*) FROM votes WHERE election_id = e.id
            ), 0), 2) as vote_percentage
        FROM elections e
        JOIN candidates c ON c.election_id = e.id
        LEFT JOIN votes v ON v.candidate_id = c.id
        GROUP BY e.id, c.id
        ORDER BY e.id, vote_count DESC
    ")->fetchAll(PDO::FETCH_ASSOC)
];

header('Content-Type: application/json');
echo json_encode($data);
