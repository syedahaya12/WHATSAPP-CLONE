<?php
// Database configuration and connection
class Database {
    private $host = 'localhost';
    private $db_name = 'dblfznb1zgb3x8';
    private $username = 'uannmukxu07nw';
    private $password = 'nhh1divf0d2c';
    private $conn;
    
    // Get database connection
    public function getConnection() {
        if ($this->conn === null) {
            try {
                $this->conn = new PDO(
                    "mysql:host=" . $this->host . ";dbname=" . $this->db_name,
                    $this->username,
                    $this->password,
                    array(
                        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                        PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8"
                    )
                );
            } catch(PDOException $exception) {
                error_log("Database connection error: " . $exception->getMessage());
                throw new Exception("Database connection failed: " . $exception->getMessage());
            }
        }
        
        return $this->conn;
    }
    
    // Close connection
    public function closeConnection() {
        $this->conn = null;
    }
    
    // Execute query with parameters
    public function executeQuery($query, $params = []) {
        try {
            if ($this->conn === null) {
                $this->getConnection();
            }
            
            $stmt = $this->conn->prepare($query);
            $stmt->execute($params);
            return $stmt;
        } catch(PDOException $e) {
            error_log("Query execution failed: " . $e->getMessage());
            throw new Exception("Query execution failed: " . $e->getMessage());
        }
    }
    
    // Get single record
    public function fetchOne($query, $params = []) {
        $stmt = $this->executeQuery($query, $params);
        return $stmt->fetch();
    }
    
    // Get multiple records
    public function fetchAll($query, $params = []) {
        $stmt = $this->executeQuery($query, $params);
        return $stmt->fetchAll();
    }
    
    // Get last inserted ID
    public function getLastInsertId() {
        return $this->conn->lastInsertId();
    }
}

// Global database instance
$database = new Database();
$db = $database->getConnection();

// Helper functions
function sanitizeInput($data) {
    return htmlspecialchars(strip_tags(trim($data)));
}

function validateEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL);
}

function hashPassword($password) {
    return password_hash($password, PASSWORD_DEFAULT);
}

function verifyPassword($password, $hash) {
    return password_verify($password, $hash);
}

// Session management
function startSession() {
    if (session_status() == PHP_SESSION_NONE) {
        session_start();
    }
}

function isLoggedIn() {
    startSession();
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

function requireLogin() {
    if (!isLoggedIn()) {
        header('Location: login.php');
        exit();
    }
}

function getCurrentUserId() {
    startSession();
    return $_SESSION['user_id'] ?? null;
}

function getCurrentUser() {
    global $database;
    $userId = getCurrentUserId();
    if ($userId) {
        return $database->fetchOne("SELECT * FROM users WHERE id = ?", [$userId]);
    }
    return null;
}
?>
