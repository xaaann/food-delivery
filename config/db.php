<?php
session_start();

class Database {
    private $host;
    private $db_name;
    private $username;
    private $password;
    private $port;
    public $conn;

    public function __construct() {
        // Check if Render PostgreSQL environment variables exist
        if (getenv('DATABASE_URL')) {
            // Render deployment with PostgreSQL
            $db_url = parse_url(getenv('DATABASE_URL'));
            $this->host = $db_url['host'];
            $this->port = $db_url['port'];
            $this->db_name = ltrim($db_url['path'], '/');
            $this->username = $db_url['user'];
            $this->password = $db_url['pass'];
        } elseif ($_SERVER['SERVER_NAME'] === 'localhost' || $_SERVER['SERVER_NAME'] === '127.0.0.1') {
            // Local development - still using MySQL
            $this->host = "localhost";
            $this->port = "3306";
            $this->db_name = "food_delivery";
            $this->username = "root";
            $this->password = "";
        } else {
            // Other hosting
            $this->host = "localhost";
            $this->port = "3306";
            $this->db_name = "food_delivery";
            $this->username = "root";
            $this->password = "";
        }
    }

    public function getConnection() {
        $this->conn = null;
        try {
            // Check if using PostgreSQL (Render) or MySQL (local)
            if (getenv('DATABASE_URL')) {
                // PostgreSQL connection
                $this->conn = new PDO(
                    "pgsql:host={$this->host};port={$this->port};dbname={$this->db_name}",
                    $this->username,
                    $this->password
                );
            } else {
                // MySQL connection (local)
                $this->conn = new PDO(
                    "mysql:host={$this->host};port={$this->port};dbname={$this->db_name}",
                    $this->username,
                    $this->password
                );
            }
            $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        } catch(PDOException $e) {
            error_log("DB Connection Error: " . $e->getMessage());
            echo "Connection Error: Could not connect to the database. " . $e->getMessage();
        }
        return $this->conn;
    }
}

// ----------------------
// BASE URL FOR THE PROJECT
// ----------------------
// Detect environment and set BASE_URL accordingly
if (getenv('RENDER')) {
    // Render deployment - no subfolder needed
    define('BASE_URL', '/');
} elseif ($_SERVER['SERVER_NAME'] === 'localhost' || $_SERVER['SERVER_NAME'] === '127.0.0.1') {
    // Local XAMPP - with subfolder
    define('BASE_URL', '/food_delivery/');
} else {
    // Other hosting
    define('BASE_URL', '/');
}

$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') ? 'https://' : 'http://';
define('FULL_URL', $protocol . $_SERVER['HTTP_HOST'] . BASE_URL);

// ----------------------
// HELPER FUNCTIONS
// ----------------------
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

function checkRole($role) {
    return isset($_SESSION['role']) && $_SESSION['role'] === $role;
}

function redirect($page) {
    // Use FULL_URL so redirects work in subfolder
    header("Location: " . FULL_URL . $page);
    exit();
}
?>      