<?php
// rider/ajax_order_action.php
require_once '../config/db.php';

session_start();
header('Content-Type: application/json');

// Check login and role
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'rider') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$db = new Database();
$conn = $db->getConnection();

$order_id = isset($_POST['order_id']) ? (int)$_POST['order_id'] : 0;

if ($order_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid order ID']);
    exit;
}

// Accept order
if (isset($_POST['accept_order'])) {
    $stmt = $conn->prepare("UPDATE orders SET rider_id = ?, status = 'confirmed' WHERE id = ? AND rider_id IS NULL");
    if ($stmt->execute([$_SESSION['user_id'], $order_id])) {
        echo json_encode([
            'success' => true,
            'message' => "✅ Order #$order_id accepted successfully!",
            'new_status' => 'confirmed',
            'new_status_label' => 'Confirmed'
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to accept order']);
    }
    exit;
}

// Update status
if (isset($_POST['update_status'])) {
    $status = $_POST['status'] ?? '';
    $allowed_status = ['confirmed','preparing','on_the_way','delivered'];
    if (!in_array($status, $allowed_status)) {
        echo json_encode(['success' => false, 'message' => 'Invalid status']);
        exit;
    }

    $stmt = $conn->prepare("UPDATE orders SET status = ? WHERE id = ? AND rider_id = ?");
    if ($stmt->execute([$status, $order_id, $_SESSION['user_id']])) {
        $status_label = ucfirst(str_replace('_', ' ', $status));
        echo json_encode([
            'success' => true,
            'message' => "✅ Order #$order_id status updated to $status_label!",
            'new_status' => $status,
            'new_status_label' => $status_label
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to update status']);
    }
    exit;
}

echo json_encode(['success' => false, 'message' => 'No valid action']);
