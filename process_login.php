<?php
require_once 'config/database.php';
require_once 'includes/auth.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $identifier = filter_input(INPUT_POST, 'identifier', FILTER_SANITIZE_STRING);
    $password = $_POST['password'];
    
    if (login_user($identifier, $password)) {
        if (is_admin()) {
            header('Location: admin/index.php');
        } else {
            header('Location: dashboard.php');
        }
    } else {
        header('Location: index.php?error=invalid_credentials');
    }
    exit();
}
?>
