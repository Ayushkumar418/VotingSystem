<?php
require_once 'config/database.php';
require_once 'includes/auth.php';
require_once 'includes/functions.php';

if (!is_logged_in()) {
    header('Location: index.php');
    exit();
}

$db = new Database();
$conn = $db->connect();

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['action']) && $_POST['action'] == 'edit') {
        $id = filter_input(INPUT_POST, 'id', FILTER_SANITIZE_NUMBER_INT);
        $name = filter_input(INPUT_POST, 'name', FILTER_SANITIZE_STRING);
        $stmt = $conn->prepare("UPDATE candidates SET name = ? WHERE id = ?");
        $stmt->execute([$name, $id]);
        log_activity($conn, 'candidate_update', "Updated candidate: " . $name);
    } elseif (isset($_POST['action']) && $_POST['action'] == 'create') {
        $name = filter_input(INPUT_POST, 'name', FILTER_SANITIZE_STRING);
        $stmt = $conn->prepare("INSERT INTO candidates (name) VALUES (?)");
        $stmt->execute([$name]);
        log_activity($conn, 'candidate_create', "Added new candidate: " . $name);
    } elseif (isset($_POST['action']) && $_POST['action'] == 'delete') {
        $id = filter_input(INPUT_POST, 'id', FILTER_SANITIZE_NUMBER_INT);
        $stmt = $conn->prepare("DELETE FROM candidates WHERE id = ?");
        $stmt->execute([$id]);
        log_activity($conn, 'candidate_delete', "Deleted candidate #" . $id);
    }
}

header('Location: dashboard.php');
exit();