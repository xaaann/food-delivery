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
        // Check if Railway environment variables exist
        if (getenv('MYSQLHOST')) {
            // Railway deployment
            $this->host = getenv('MYSQLHOST');
            $this->port = getenv('MYSQLPORT') ?: '3306';
            $this->db_name = getenv('MYSQLDATABASE');
            $this->username = getenv('MYSQLUSER');
            $this->password = getenv('MYSQLPASSWORD');
        } elseif ($_SERVER['SERVER_NAME'] === 'localhost') {
            // Local XAMPP
            $this->host = "localhost";
            $this->port = "3306";
            $this->db_name = "food_delivery";
            $this->username = "root";
            $this->password = "";
        } else {
            // InfinityFree (fallback)
            $this->host = "sql111.infinityfree.com";
            $this->port = "3306";
            $this->db_name = "if0_40933697_xanmysql";
            $this->username = "if0_40933697";
            $this->password = "ufjijIXjx7g2fQ";
        }
    }

    public function getConnection() {
        $this->conn = null;
        try {
            $this->conn = new PDO(
                "mysql:host={$this->host};port={$this->port};dbname={$this->db_name}",
                $this->username,
                $this->password
            );
            $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        } catch(PDOException $e) {
            error_log("DB Connection Error: " . $e->getMessage());
            echo "Connection Error: Could not connect to the database.";
        }
        return $this->conn;
    }
}

// ----------------------
// BASE URL FOR THE PROJECT
// ----------------------
// Detect environment and set BASE_URL accordingly
if (getenv('RAILWAY_ENVIRONMENT')) {
    // Railway deployment - no subfolder needed
    define('BASE_URL', '/');
} elseif ($_SERVER['SERVER_NAME'] === 'localhost') {
    // Local XAMPP - with subfolder
    define('BASE_URL', '/food_delivery/');
} else {
    // Other hosting (InfinityFree, etc.)
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