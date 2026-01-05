<?php
// rider/dashboard.php
require_once '../config/db.php';

if (!isLoggedIn() || !checkRole('rider')) {
    redirect('../auth/login.php');
}

$db = new Database();
$conn = $db->getConnection();

// Handle accept order
if (isset($_POST['accept_order'])) {
    $order_id = $_POST['order_id'];
    
    $stmt = $conn->prepare("UPDATE orders SET rider_id = ?, status = 'confirmed' WHERE id = ? AND rider_id IS NULL");
    if ($stmt->execute([$_SESSION['user_id'], $order_id])) {
        echo "<script>alert('Order accepted successfully!');</script>";
    }
}

// Handle update status
if (isset($_POST['update_status'])) {
    $order_id = $_POST['order_id'];
    $status = $_POST['status'];
    
    $stmt = $conn->prepare("UPDATE orders SET status = ? WHERE id = ? AND rider_id = ?");
    $stmt->execute([$status, $order_id, $_SESSION['user_id']]);
}

// Get available orders (not yet assigned)
$stmt = $conn->prepare("SELECT o.*, u.name as customer_name, u.phone as customer_phone 
                        FROM orders o 
                        JOIN users u ON o.user_id = u.id 
                        WHERE o.rider_id IS NULL AND o.status = 'pending'
                        ORDER BY o.created_at DESC");
$stmt->execute();
$available_orders = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get my active orders
$stmt = $conn->prepare("SELECT o.*, u.name as customer_name, u.phone as customer_phone 
                        FROM orders o 
                        JOIN users u ON o.user_id = u.id 
                        WHERE o.rider_id = ? AND o.status != 'delivered' AND o.status != 'cancelled'
                        ORDER BY o.created_at DESC");
$stmt->execute([$_SESSION['user_id']]);
$my_orders = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get completed orders
$stmt = $conn->prepare("SELECT o.*, u.name as customer_name 
                        FROM orders o 
                        JOIN users u ON o.user_id = u.id 
                        WHERE o.rider_id = ? AND (o.status = 'delivered' OR o.status = 'cancelled')
                        ORDER BY o.created_at DESC LIMIT 10");
$stmt->execute([$_SESSION['user_id']]);
$completed_orders = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Rider Dashboard</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #f5f5f5;
        }
        .navbar {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 15px 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .btn {
            padding: 8px 16px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
        }
        .btn-white {
            background: white;
            color: #667eea;
            font-weight: 600;
        }
        .container {
            max-width: 1200px;
            margin: 30px auto;
            padding: 0 20px;
        }
        .section {
            background: white;
            border-radius: 10px;
            padding: 30px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            margin-bottom: 30px;
        }
        h2 {
            margin-bottom: 25px;
            color: #333;
        }
        .order-card {
            border: 2px solid #eee;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 20px;
        }
        .order-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
        }
        .order-id {
            font-size: 18px;
            font-weight: bold;
            color: #667eea;
        }
        .status-badge {
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 13px;
            font-weight: 600;
        }
        .status-pending {
            background: #fff3cd;
            color: #856404;
        }
        .status-confirmed {
            background: #cfe2ff;
            color: #084298;
        }
        .status-preparing {
            background: #e7f3ff;
            color: #055160;
        }
        .status-on_the_way {
            background: #d1e7dd;
            color: #0f5132;
        }
        .status-delivered {
            background: #d4edda;
            color: #155724;
        }
        .order-details {
            color: #666;
            line-height: 1.8;
        }
        .order-actions {
            display: flex;
            gap: 10px;
            margin-top: 15px;
        }
        .btn-accept {
            background: #2ecc71;
            color: white;
            font-weight: 600;
        }
        .btn-primary {
            background: #667eea;
            color: white;
            font-weight: 600;
        }
        .btn-success {
            background: #2ecc71;
            color: white;
            font-weight: 600;
        }
        .empty-state {
            text-align: center;
            padding: 40px;
            color: #888;
        }
    </style>
</head>
<body>
    <div class="navbar">
        <h1>🚴 Delivery Partner Dashboard</h1>
        <div>
            <span style="margin-right: 20px;">Welcome, <?php echo $_SESSION['name']; ?>!</span>
            <a href="../auth/logout.php" class="btn btn-white">Logout</a>
        </div>
    </div>

    <div class="container">
        <!-- Available Orders -->
        <div class="section">
            <h2>🔔 Available Orders (<?php echo count($available_orders); ?>)</h2>
            
            <?php if (count($available_orders) > 0): ?>
                <?php foreach ($available_orders as $order): ?>
                    <div class="order-card">
                        <div class="order-header">
                            <div class="order-id">Order #<?php echo $order['id']; ?></div>
                            <span class="status-badge status-pending">New Order</span>
                        </div>
                        <div class="order-details">
                            <p><strong>Customer:</strong> <?php echo htmlspecialchars($order['customer_name']); ?></p>
                            <p><strong>Phone:</strong> <?php echo htmlspecialchars($order['customer_phone']); ?></p>
                            <p><strong>Delivery Address:</strong> <?php echo htmlspecialchars($order['delivery_address']); ?></p>
                            <p><strong>Total Amount:</strong> $<?php echo number_format($order['total_amount'], 2); ?></p>
                            <p><strong>Ordered:</strong> <?php echo date('M d, Y h:i A', strtotime($order['created_at'])); ?></p>
                        </div>
                        <div class="order-actions">
                            <form method="POST" style="display: inline;">
                                <input type="hidden" name="order_id" value="<?php echo $order['id']; ?>">
                                <button type="submit" name="accept_order" class="btn btn-accept">Accept Order</button>
                            </form>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="empty-state">
                    <p>No available orders at the moment. Check back soon!</p>
                </div>
            <?php endif; ?>
        </div>

        <!-- My Active Orders -->
        <div class="section">
            <h2>📦 My Active Deliveries (<?php echo count($my_orders); ?>)</h2>
            
            <?php if (count($my_orders) > 0): ?>
                <?php foreach ($my_orders as $order): ?>
                    <div class="order-card">
                        <div class="order-header">
                            <div class="order-id">Order #<?php echo $order['id']; ?></div>
                            <span class="status-badge status-<?php echo $order['status']; ?>">
                                <?php echo ucfirst(str_replace('_', ' ', $order['status'])); ?>
                            </span>
                        </div>
                        <div class="order-details">
                            <p><strong>Customer:</strong> <?php echo htmlspecialchars($order['customer_name']); ?></p>
                            <p><strong>Phone:</strong> <?php echo htmlspecialchars($order['customer_phone']); ?></p>
                            <p><strong>Delivery Address:</strong> <?php echo htmlspecialchars($order['delivery_address']); ?></p>
                            <p><strong>Total Amount:</strong> $<?php echo number_format($order['total_amount'], 2); ?></p>
                        </div>
                        <div class="order-actions">
                            <form method="POST" style="display: inline;">
                                <input type="hidden" name="order_id" value="<?php echo $order['id']; ?>">
                                <?php if ($order['status'] == 'confirmed'): ?>
                                    <button type="submit" name="update_status" value="preparing" class="btn btn-primary">Mark as Preparing</button>
                                <?php elseif ($order['status'] == 'preparing'): ?>
                                    <button type="submit" name="update_status" value="on_the_way" class="btn btn-primary">On the Way</button>
                                <?php elseif ($order['status'] == 'on_the_way'): ?>
                                    <button type="submit" name="update_status" value="delivered" class="btn btn-success">Mark as Delivered</button>
                                <?php endif; ?>
                                <input type="hidden" name="status" value="">
                                <script>
                                    document.currentScript.previousElementSibling.previousElementSibling.onclick = function(e) {
                                        document.currentScript.previousElementSibling.value = e.target.value;
                                    };
                                </script>
                            </form>
                            <a href="../messages/chat.php?order_id=<?php echo $order['id']; ?>" class="btn btn-primary">
                                💬 Message Customer
                            </a>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="empty-state">
                    <p>No active deliveries. Accept orders from the available orders section!</p>
                </div>
            <?php endif; ?>
        </div>

        <!-- Completed Orders -->
        <div class="section">
            <h2>✅ Recent Completed Orders</h2>
            
            <?php if (count($completed_orders) > 0): ?>
                <?php foreach ($completed_orders as $order): ?>
                    <div class="order-card">
                        <div class="order-header">
                            <div class="order-id">Order #<?php echo $order['id']; ?></div>
                            <span class="status-badge status-<?php echo $order['status']; ?>">
                                <?php echo ucfirst($order['status']); ?>
                            </span>
                        </div>
                        <div class="order-details">
                            <p><strong>Customer:</strong> <?php echo htmlspecialchars($order['customer_name']); ?></p>
                            <p><strong>Amount:</strong> $<?php echo number_format($order['total_amount'], 2); ?></p>
                            <p><strong>Completed:</strong> <?php echo date('M d, Y h:i A', strtotime($order['created_at'])); ?></p>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="empty-state">
                    <p>No completed orders yet.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>