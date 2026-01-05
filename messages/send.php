<?php
include "../config/db.php";
$stmt = $conn->prepare("INSERT INTO messages (order_id, sender_role, message) VALUES (?, ?, ?)");
$stmt->bind_param("iss", $_POST['order_id'], $_SESSION['user']['role'], $_POST['message']);
$stmt->execute();
