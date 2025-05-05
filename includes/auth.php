<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function register_user($email, $password, $full_name, $voter_id) {
    $db = new Database();
    $conn = $db->connect();
    
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
    
    $sql = "INSERT INTO users (email, password, full_name, voter_id) VALUES (?, ?, ?, ?)";
    try {
        $stmt = $conn->prepare($sql);
        return $stmt->execute([$email, $hashed_password, $full_name, $voter_id]);
    } catch(PDOException $e) {
        return false;
    }
}

function login_user($email, $password) {
    try {
        $db = new Database();
        $conn = $db->connect();
        
        $stmt = $conn->prepare("SELECT * FROM users WHERE (email = ? OR voter_id = ?) LIMIT 1");
        $stmt->execute([$email, $email]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        error_log("Login attempt - User found: " . ($user ? "Yes" : "No"));
        
        if ($user && password_verify($password, $user['password'])) {
            error_log("Password verified for user ID: " . $user['id']);
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['is_admin'] = (bool)$user['is_admin'];
            $_SESSION['full_name'] = $user['full_name'];
            return true;
        }
        
        error_log("Login failed - Invalid credentials for: " . $email);
        return false;
        
    } catch (PDOException $e) {
        error_log("Database error in login: " . $e->getMessage());
        return false;
    }
}

function is_logged_in() {
    return isset($_SESSION['user_id']);
}

function is_admin() {
    if (!isset($_SESSION['user_id']) || !isset($_SESSION['is_admin'])) {
        error_log("Admin check failed - No session");
        return false;
    }
    
    try {
        $db = new Database();
        $conn = $db->connect();
        
        $stmt = $conn->prepare("SELECT is_admin FROM users WHERE id = ? LIMIT 1");
        $stmt->execute([$_SESSION['user_id']]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        error_log("Admin check - User ID: " . $_SESSION['user_id'] . " Is Admin: " . ($result['is_admin'] ? "Yes" : "No"));
        
        return $result && $result['is_admin'] == 1;
        
    } catch (PDOException $e) {
        error_log("Database error in admin check: " . $e->getMessage());
        return false;
    }
}

function logout() {
    session_destroy();
}
?>
