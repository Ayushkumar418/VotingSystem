<?php
require_once 'config/database.php';
require_once 'includes/auth.php';

if (!is_logged_in()) {
    header('HTTP/1.1 403 Forbidden');
    exit('Unauthorized');
}

$election_id = filter_input(INPUT_GET, 'election_id', FILTER_SANITIZE_NUMBER_INT);
$db = new Database();
$conn = $db->connect();

$stmt = $conn->prepare("
    SELECT c.*, COUNT(v.id) as vote_count 
    FROM candidates c 
    LEFT JOIN votes v ON c.id = v.candidate_id 
    WHERE c.election_id = ? 
    GROUP BY c.id
");
$stmt->execute([$election_id]);
$results = $stmt->fetchAll(PDO::FETCH_ASSOC);

header('Content-Type: application/json');
echo json_encode($results);
?>
