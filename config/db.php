<?php
session_start();

class Database {
    private $host;
    private $db_name;
    private $username;
    private $password;
    public $conn;

    public function __construct() {
        if ($_SERVER['SERVER_NAME'] === 'localhost') {
            // Local XAMPP
            $this->host = "localhost";
            $this->db_name = "food_delivery";
            $this->username = "root";
            $this->password = "";
        } else {
            // InfinityFree
            $this->host = "sql111.infinityfree.com";
            $this->db_name = "if0_40933697_xanmysql";
            $this->username = "if0_40933697";
            $this->password = "ufjijIXjx7g2fQ";
        }
    }

    public function getConnection() {
        $this->conn = null;
        try {
            $this->conn = new PDO(
                "mysql:host={$this->host};dbname={$this->db_name}",
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
define('BASE_URL', '/food_delivery/'); // subfolder where your files are
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
