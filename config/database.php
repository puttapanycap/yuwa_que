<?php
require_once __DIR__ . '/env.php';

// Database Configuration
class Database {
    private $host;
    private $db_name;
    private $username;
    private $password;
    private $charset;
    public $conn;

    public function __construct() {
        $this->host = env('DB_HOST', 'localhost');
        $this->db_name = env('DB_NAME', 'yuwaprasart_queue');
        $this->username = env('DB_USERNAME', 'root');
        $this->password = env('DB_PASSWORD', '');
        $this->charset = env('DB_CHARSET', 'utf8mb4');
    }

    public function getConnection() {
        $this->conn = null;
        try {
            $dsn = "mysql:host=" . $this->host . ";dbname=" . $this->db_name . ";charset=" . $this->charset;
            $this->conn = new PDO($dsn, $this->username, $this->password);
            $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $this->conn->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
            
            // Enable query logging if configured
            if (env('QUERY_LOG_ENABLED', false)) {
                $this->conn->setAttribute(PDO::ATTR_STATEMENT_CLASS, ['LoggedPDOStatement']);
            }
            
        } catch(PDOException $exception) {
            $message = "Connection error: " . $exception->getMessage();
            
            if (env('APP_DEBUG', false)) {
                echo $message;
            } else {
                error_log($message);
                echo "Database connection failed. Please check configuration.";
            }
        }
        return $this->conn;
    }
    
    public function testConnection() {
        try {
            $conn = $this->getConnection();
            if ($conn) {
                $stmt = $conn->query("SELECT 1");
                return $stmt !== false;
            }
            return false;
        } catch (Exception $e) {
            return false;
        }
    }
}

// Global database connection
function getDB() {
    static $db = null;
    if ($db === null) {
        $database = new Database();
        $db = $database->getConnection();
    }
    return $db;
}

// Test database connection on load
if (env('APP_DEBUG', false)) {
    $database = new Database();
    if (!$database->testConnection()) {
        error_log("Database connection test failed");
    }
}
?>
