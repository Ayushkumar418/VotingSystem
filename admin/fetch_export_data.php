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
        WITH RankedVotes AS (
            SELECT 
                e.id as election_id,
                e.title as election_title,
                c.name as candidate_name,
                COUNT(v.id) as vote_count,
                ROUND(COUNT(v.id) * 100.0 / NULLIF((
                    SELECT COUNT(*) FROM votes WHERE election_id = e.id
                ), 0), 2) as vote_percentage,
                ROW_NUMBER() OVER (PARTITION BY e.id ORDER BY COUNT(v.id) DESC) as rank,
                CASE 
                    WHEN NOW() > e.end_date THEN 'ended'
                    WHEN NOW() BETWEEN e.start_date AND e.end_date AND e.is_active = true THEN 'active'
                    ELSE 'upcoming'
                END as election_status
            FROM elections e
            JOIN candidates c ON c.election_id = e.id
            LEFT JOIN votes v ON v.candidate_id = c.id
            GROUP BY e.id, c.id, e.start_date, e.end_date, e.is_active
        )
        SELECT 
            election_title,
            candidate_name,
            vote_count,
            vote_percentage,
            CASE 
                WHEN election_status = 'ended' AND rank = 1 AND vote_count > 0 THEN 'Winner!'
                WHEN election_status = 'active' THEN 'Election in Progress'
                WHEN election_status = 'upcoming' THEN 'Election Not Started'
                ELSE ''
            END as status
        FROM RankedVotes
        ORDER BY election_id, rank ASC
    ")->fetchAll(PDO::FETCH_ASSOC),
];

// Process results to add empty rows between elections
$processed_results = [];
$current_election = null;

foreach ($data['results'] as $result) {
    if ($current_election !== null && $current_election !== $result['election_title']) {
        // Add empty row between elections
        $processed_results[] = [
            'election_title' => '',
            'candidate_name' => '',
            'vote_count' => '',
            'vote_percentage' => '',
            'status' => ''
        ];
    }
    $processed_results[] = $result;
    $current_election = $result['election_title'];
}

$data['results'] = $processed_results;

header('Content-Type: application/json');
echo json_encode($data);
