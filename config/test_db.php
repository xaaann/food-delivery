<?php
require 'db.php';

$db = new Database();
$conn = $db->getConnection();

if ($conn) {
    echo "Database connected successfully!";
} else {
    echo "Database connection failed!";
}
?>
