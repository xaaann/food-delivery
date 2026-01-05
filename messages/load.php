<?php
include "../config/db.php";
$order_id = $_GET['order_id'];
$res = $conn->query("SELECT * FROM messages WHERE order_id=$order_id ORDER BY created_at ASC");
$data = [];
while($m = $res->fetch_assoc()){
    $data[] = ['message'=>$m['message'], 'sender_role'=>$m['sender_role']];
}
echo json_encode($data);
