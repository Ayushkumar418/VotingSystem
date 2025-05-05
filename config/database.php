<?php
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'voting_system');

class Database {
    private $connection;
    
    public function connect() {
        try {
            $this->connection = new PDO(
                "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME,
                DB_USER,
                DB_PASS
            );
            $this->connection->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            return $this->connection;
        } catch(PDOException $e) {
            die("Connection failed: " . $e->getMessage());
        }
    }
    
    public function logActivity($user_id, $type, $description, $status = 'success') {
        try {
            $stmt = $this->connection->prepare("
                INSERT INTO activity_logs (user_id, activity_type, description, status) 
                VALUES (?, ?, ?, ?)
            ");
            return $stmt->execute([$user_id, $type, $description, $status]);
        } catch (PDOException $e) {
            error_log("Activity logging failed: " . $e->getMessage());
            return false;
        }
    }
}
?>
