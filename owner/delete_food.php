<?php
// owner/delete_food.php
require_once '../config/db.php';

if (!isLoggedIn() || !checkRole('owner')) {
    redirect('../auth/login.php');
}

$food_id = $_GET['id'] ?? 0;

if ($food_id > 0) {
    $db = new Database();
    $conn = $db->getConnection();

    // Delete only if owned by current user
    $stmt = $conn->prepare("DELETE FROM food_items WHERE id = ? AND owner_id = ?");
    $stmt->execute([$food_id, $_SESSION['user_id']]);
}

redirect('dashboard.php');
?>