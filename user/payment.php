<?php
session_start();
require_once '../config/db.php';

// Security check
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'customer') {
    header("Location: ../auth/login.php");
    exit();
}

$db = new Database();
$conn = $db->getConnection();
$user_id = $_SESSION['user_id'];

if (!isset($_SESSION['cart']) || count($_SESSION['cart']) === 0) {
    $_SESSION['error'] = "Your cart is empty!";
    header("Location: cart.php");
    exit();
}

$success = false;
$error_message = "";
$order_items = [];
$total = 0;

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $delivery_address = trim($_POST['delivery_address']);

    if (empty($delivery_address)) {
        $error_message = "Delivery address is required!";
    } else {
        try {
            $conn->beginTransaction();

            // Calculate total & validate stock
            foreach ($_SESSION['cart'] as $food_id => $quantity) {
                $stmt = $conn->prepare("SELECT name, price, stock FROM food_items WHERE id = ?");
                $stmt->execute([$food_id]);
                $item = $stmt->fetch(PDO::FETCH_ASSOC);

                if (!$item) throw new Exception("Food item not found (ID: $food_id).");
                if ($quantity > $item['stock']) throw new Exception("Not enough stock for '{$item['name']}'. Available: {$item['stock']}.");

                $subtotal = $item['price'] * $quantity;
                $total += $subtotal;

                $order_items[] = [
                    'name' => $item['name'],
                    'quantity' => $quantity,
                    'price' => $item['price'],
                    'subtotal' => $subtotal
                ];
            }

            // Insert order
            $stmt = $conn->prepare("INSERT INTO orders (user_id, total_amount, delivery_address, order_date) VALUES (?, ?, ?, NOW())");
            $stmt->execute([$user_id, $total, $delivery_address]);
            $order_id = $conn->lastInsertId();

            // Insert order items & reduce stock
            foreach ($_SESSION['cart'] as $food_id => $quantity) {
                $stmt = $conn->prepare("SELECT price, stock FROM food_items WHERE id = ?");
                $stmt->execute([$food_id]);
                $item = $stmt->fetch(PDO::FETCH_ASSOC);

                $new_stock = $item['stock'] - $quantity;
                $stmt2 = $conn->prepare("UPDATE food_items SET stock = ? WHERE id = ?");
                $stmt2->execute([$new_stock, $food_id]);

                $stmt3 = $conn->prepare("INSERT INTO order_items (order_id, food_id, quantity, price) VALUES (?, ?, ?, ?)");
                $stmt3->execute([$order_id, $food_id, $quantity, $item['price']]);
            }

            $conn->commit();

            // Clear cart
            unset($_SESSION['cart']);
            $success = true;

        } catch (Exception $e) {
            $conn->rollBack();
            $error_message = "❌ Error placing order: " . $e->getMessage();
        }
    }
} else {
    header("Location: cart.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Payment Status</title>
<style>
body {font-family:sans-serif; background:#f5f5f5; display:flex; justify-content:center; align-items:center; min-height:100vh; margin:0;}
.message-card {background:white; padding:40px; border-radius:10px; box-shadow:0 4px 20px rgba(0,0,0,0.1); text-align:center; max-width:600px;}
h1 {font-size:28px; margin-bottom:20px;}
p {font-size:16px; margin-bottom:20px;}
.btn {padding:12px 25px; border:none; border-radius:5px; cursor:pointer; text-decoration:none; font-weight:600; margin-top:10px; display:inline-block;}
.btn-success {background:linear-gradient(135deg,#667eea,#764ba2); color:white;}
.btn-error {background:#ff4757; color:white;}
.order-summary {margin-top:20px; text-align:left; border-top:2px solid #eee; padding-top:20px;}
.order-item {display:flex; justify-content:space-between; margin-bottom:10px;}
.order-item span {display:inline-block;}
.summary-total {font-weight:bold; font-size:18px; color:#667eea; margin-top:10px; text-align:right;}
</style>
</head>
<body>
<div class="message-card">
<?php if ($success): ?>
    <h1>✅ Order Placed!</h1>
    <p>Your order has been successfully placed. Thank you for ordering with us!</p>

    <div class="order-summary">
        <h3>Order Summary:</h3>
        <?php foreach($order_items as $item): ?>
            <div class="order-item">
                <span><?php echo htmlspecialchars($item['name']); ?> x <?php echo $item['quantity']; ?></span>
                <span>₱<?php echo number_format($item['subtotal'],2); ?></span>
            </div>
        <?php endforeach; ?>
        <div class="summary-total">Total: ₱<?php echo number_format($total,2); ?></div>
    </div>

    <a href="dashboard.php" class="btn btn-success">Back to Menu</a>
<?php else: ?>
    <h1>❌ Payment Failed</h1>
    <p><?php echo htmlspecialchars($error_message); ?></p>
    <a href="cart.php" class="btn btn-error">Back to Cart</a>
<?php endif; ?>
</div>
</body>
</html>
