<?php
// customer/update_cart.php
session_start();
require_once '../config/db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'customer') {
    echo json_encode(['status' => 'error', 'message' => 'Not logged in']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);

    if (!isset($data['food_id']) || !isset($data['quantity'])) {
        echo json_encode(['status'=>'error','message'=>'Invalid data']);
        exit();
    }

    $food_id = intval($data['food_id']);
    $quantity = intval($data['quantity']);

    $db = new Database();
    $conn = $db->getConnection();

    $stmt = $conn->prepare("SELECT stock FROM food_items WHERE id = ?");
    $stmt->execute([$food_id]);
    $item = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$item) {
        echo json_encode(['status'=>'error','message'=>'Item not found']);
        exit();
    }

    if ($quantity > $item['stock']) $quantity = $item['stock'];
    if ($quantity > 0) {
        $_SESSION['cart'][$food_id] = $quantity;
    } else {
        unset($_SESSION['cart'][$food_id]);
    }

    echo json_encode(['status'=>'success','quantity'=>$quantity]);
    exit();
}
?>
