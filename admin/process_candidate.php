<?php
require_once '../config/database.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';

if (!is_logged_in() || !is_admin()) {
    header('Location: ../index.php');
    exit();
}

// Create uploads directory if it doesn't exist
$upload_dir = "../uploads/";
if (!file_exists($upload_dir)) {
    mkdir($upload_dir, 0777, true);
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $db = new Database();
    $conn = $db->connect();
    
    // Validate inputs
    $errors = [];
    
    // Validate required fields
    $required_fields = ['name', 'election_id', 'description'];
    foreach ($required_fields as $field) {
        if (!isset($_POST[$field]) || trim($_POST[$field]) === '') {
            $errors[] = ucfirst($field) . " is required";
        }
    }

    // Validate photo for new candidates
    if (!isset($_POST['action']) && (!isset($_FILES['photo']) || $_FILES['photo']['error'] == 4)) {
        $errors[] = "Photo is required for new candidates";
    }

    // Only proceed with other validations if required fields are present
    if (empty($errors)) {
        // Validate name
        $name = filter_input(INPUT_POST, 'name', FILTER_SANITIZE_STRING);
        if (empty($name) || strlen($name) < 2) {
            $errors[] = "Name must be at least 2 characters long";
        }
        
        // Validate election
        $election_id = filter_input(INPUT_POST, 'election_id', FILTER_SANITIZE_NUMBER_INT);
        if (!$election_id) {
            $errors[] = "Please select an election";
        } else {
            // Check if election exists and is active
            $stmt = $conn->prepare("SELECT id FROM elections WHERE id = ?");
            $stmt->execute([$election_id]);
            if (!$stmt->fetch()) {
                $errors[] = "Invalid election selected";
            }
        }
        
        // Validate name uniqueness in election
        if ($election_id && $name) {
            $stmt = $conn->prepare("
                SELECT COUNT(*) FROM candidates 
                WHERE name = ? AND election_id = ? 
                AND id != COALESCE(?, 0)"); // Exclude current candidate when editing
            $stmt->execute([
                $name, 
                $election_id, 
                isset($_POST['action']) && $_POST['action'] == 'edit' ? $_POST['id'] : null
            ]);
            if ($stmt->fetchColumn() > 0) {
                $errors[] = "A candidate with this name already exists in the selected election";
            }
        }
        
        // Validate photo if uploaded
        if (isset($_FILES['photo']) && $_FILES['photo']['error'] != 4) {
            $allowed = ['jpg', 'jpeg', 'png', 'gif'];
            $file_ext = strtolower(pathinfo($_FILES['photo']['name'], PATHINFO_EXTENSION));
            
            if (!in_array($file_ext, $allowed)) {
                $errors[] = "Invalid file type. Allowed types: " . implode(', ', $allowed);
            }
            if ($_FILES['photo']['size'] > 5000000) { // 5MB limit
                $errors[] = "File size too large. Maximum size: 5MB";
            }
        }
    }
    
    if (!empty($errors)) {
        $_SESSION['candidate_errors'] = $errors;
        $_SESSION['candidate_form_data'] = $_POST; // Save form data for repopulation
        header('Location: candidates.php?error=validation');
        exit();
    }
    
    try {
        $description = filter_input(INPUT_POST, 'description', FILTER_SANITIZE_STRING);
        
        $photo_url = null;
        
        // Handle photo upload
        if (isset($_FILES['photo']) && $_FILES['photo']['error'] == 0) {
            $allowed = ['jpg', 'jpeg', 'png', 'gif'];
            $filename = $_FILES['photo']['name'];
            $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
            
            if (in_array($ext, $allowed)) {
                $new_filename = uniqid() . '.' . $ext;
                if (move_uploaded_file($_FILES['photo']['tmp_name'], $upload_dir . $new_filename)) {
                    $photo_url = $new_filename;
                }
            }
        }
        
        if (isset($_POST['action']) && $_POST['action'] == 'edit') {
            $id = filter_input(INPUT_POST, 'id', FILTER_SANITIZE_NUMBER_INT);
            
            // Update candidate
            $stmt = $conn->prepare("UPDATE candidates SET name = ?, description = ?, election_id = ? WHERE id = ?");
            $params = [$name, $description, $election_id, $id];
            
            // Update photo if new one uploaded
            if ($photo_url) {
                // Delete old photo first
                $old_photo = $conn->query("SELECT photo_url FROM candidates WHERE id = $id")->fetch();
                if ($old_photo && $old_photo['photo_url']) {
                    @unlink($upload_dir . $old_photo['photo_url']);
                }
                
                $stmt = $conn->prepare("UPDATE candidates SET name = ?, description = ?, election_id = ?, photo_url = ? WHERE id = ?");
                $params = [$name, $description, $election_id, $photo_url, $id];
            }
            
        } else {
            // Insert new candidate
            $stmt = $conn->prepare("INSERT INTO candidates (name, description, election_id, photo_url) VALUES (?, ?, ?, ?)");
            $params = [$name, $description, $election_id, $photo_url];
        }
        
        $stmt->execute($params);
        header('Location: candidates.php?success=1');
        
    } catch (PDOException $e) {
        error_log("Candidate operation error: " . $e->getMessage());
        header('Location: candidates.php?error=operation_failed');
    }
    exit();
}

// Handle delete action
if (isset($_GET['action']) && $_GET['action'] == 'delete' && isset($_GET['id'])) {
    $db = new Database();
    $conn = $db->connect();
    
    try {
        // Start transaction
        $conn->beginTransaction();
        
        $id = filter_input(INPUT_GET, 'id', FILTER_SANITIZE_NUMBER_INT);
        
        // First delete related votes
        $stmt = $conn->prepare("DELETE FROM votes WHERE candidate_id = ?");
        $stmt->execute([$id]);
        
        // Get and delete photo if exists
        $photo = $conn->prepare("SELECT photo_url FROM candidates WHERE id = ?");
        $photo->execute([$id]);
        $result = $photo->fetch();
        
        if ($result && $result['photo_url']) {
            $photo_path = "../uploads/" . $result['photo_url'];
            if (file_exists($photo_path)) {
                unlink($photo_path);
            }
        }
        
        // Then delete the candidate
        $stmt = $conn->prepare("DELETE FROM candidates WHERE id = ?");
        $stmt->execute([$id]);
        
        // Commit transaction
        $conn->commit();
        
        log_activity($conn, 'candidate_delete', "Deleted candidate #" . $id);
        header('Location: candidates.php?success=1');
    } catch (PDOException $e) {
        // Rollback on error
        $conn->rollBack();
        error_log("Failed to delete candidate: " . $e->getMessage());
        header('Location: candidates.php?error=delete_failed');
    }
    exit();
}
?>
