<?php
// owner/orders.php - View Restaurant Orders
session_start();

// Security checks
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'owner') {
    header("Location: ../auth/login.php");
    exit();
}

require_once '../config/db.php';

try {
    $db = new Database();
    $conn = $db->getConnection();

    // Get owner details
    $stmt = $conn->prepare("SELECT * FROM users WHERE id = ? AND role = 'owner'");
    $stmt->execute([$_SESSION['user_id']]);
    $owner = $stmt->fetch(PDO::FETCH_ASSOC);

    // Get restaurant
    $stmt = $conn->prepare("SELECT * FROM restaurants WHERE owner_id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $restaurant = $stmt->fetch(PDO::FETCH_ASSOC);

    // Get all orders for this owner's food items
    $stmt = $conn->prepare("
        SELECT DISTINCT o.*, u.name as customer_name, u.email as customer_email,
               r.name as rider_name
        FROM orders o
        INNER JOIN order_items oi ON o.id = oi.order_id
        INNER JOIN food_items fi ON oi.food_item_id = fi.id
        LEFT JOIN users u ON o.user_id = u.id
        LEFT JOIN users r ON o.rider_id = r.id
        WHERE fi.owner_id = ?
        ORDER BY o.created_at DESC
    ");
    $stmt->execute([$_SESSION['user_id']]);
    $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    error_log("Database error: " . $e->getMessage());
    $error = "Unable to load orders.";
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Orders - <?php echo htmlspecialchars($restaurant['restaurant_name'] ?? 'Restaurant'); ?></title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #f5f7fa;
        }
        .navbar {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 15px 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .navbar h1 {
            font-size: 24px;
        }
        .navbar .nav-links {
            display: flex;
            gap: 20px;
            align-items: center;
        }
        .navbar a {
            color: white;
            text-decoration: none;
            padding: 8px 16px;
            border-radius: 5px;
            transition: background 0.3s;
        }
        .navbar a:hover {
            background: rgba(255,255,255,0.2);
        }
        .container {
            max-width: 1400px;
            margin: 30px auto;
            padding: 0 20px;
        }
        .card {
            background: white;
            padding: 30px;
            border-radius: 15px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            margin-bottom: 30px;
        }
        .card h2 {
            margin-bottom: 20px;
            color: #333;
        }
        .order-card {
            border: 2px solid #e0e0e0;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 15px;
            transition: all 0.3s;
        }
        .order-card:hover {
            border-color: #667eea;
            box-shadow: 0 3px 10px rgba(0,0,0,0.1);
        }
        .order-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
            padding-bottom: 15px;
            border-bottom: 1px solid #e0e0e0;
        }
        .order-id {
            font-size: 18px;
            font-weight: bold;
            color: #333;
        }
        .order-info {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin-bottom: 15px;
        }
        .info-item {
            font-size: 14px;
        }
        .info-label {
            color: #666;
            font-size: 12px;
            text-transform: uppercase;
            margin-bottom: 5px;
        }
        .info-value {
            color: #333;
            font-weight: 600;
        }
        .status-badge {
            display: inline-block;
            padding: 6px 16px;
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
            background: #fff3cd;
            color: #856404;
        }
        .status-on_the_way {
            background: #cfe2ff;
            color: #084298;
        }
        .status-delivered {
            background: #d4edda;
            color: #155724;
        }
        .status-cancelled {
            background: #f8d7da;
            color: #721c24;
        }
        .empty-state {
            text-align: center;
            padding: 60px 20px;
            color: #999;
        }
        .empty-state-icon {
            font-size: 80px;
            margin-bottom: 20px;
        }
        .total-amount {
            font-size: 24px;
            font-weight: bold;
            color: #667eea;
        }
    </style>
</head>
<body>
    <div class="navbar">
        <h1>📋 View Orders</h1>
        <div class="nav-links">
            <a href="dashboard.php">← Back to Dashboard</a>
            <a href="menu.php">Menu</a>
            <a href="../auth/logout.php">Logout</a>
        </div>
    </div>

    <div class="container">
        <div class="card">
            <h2>Restaurant Orders (<?php echo count($orders); ?>)</h2>

            <?php if (empty($orders)): ?>
                <div class="empty-state">
                    <div class="empty-state-icon">📋</div>
                    <h3>No orders yet</h3>
                    <p>Orders from customers will appear here.</p>
                </div>
            <?php else: ?>
                <?php foreach ($orders as $order): ?>
                    <div class="order-card">
                        <div class="order-header">
                            <div class="order-id">Order #<?php echo $order['id']; ?></div>
                            <span class="status-badge status-<?php echo $order['status']; ?>">
                                <?php echo ucfirst(str_replace('_', ' ', $order['status'])); ?>
                            </span>
                        </div>

                        <div class="order-info">
                            <div class="info-item">
                                <div class="info-label">Customer</div>
                                <div class="info-value"><?php echo htmlspecialchars($order['customer_name']); ?></div>
                                <div style="font-size: 12px; color: #666;"><?php echo htmlspecialchars($order['customer_email']); ?></div>
                            </div>

                            <div class="info-item">
                                <div class="info-label">Delivery Address</div>
                                <div class="info-value"><?php echo htmlspecialchars($order['delivery_address']); ?></div>
                            </div>

                            <div class="info-item">
                                <div class="info-label">Rider</div>
                                <div class="info-value">
                                    <?php echo $order['rider_name'] ? htmlspecialchars($order['rider_name']) : 'Not assigned'; ?>
                                </div>
                            </div>

                            <div class="info-item">
                                <div class="info-label">Total Amount</div>
                                <div class="total-amount">₱<?php echo number_format($order['total_amount'], 2); ?></div>
                            </div>

                            <div class="info-item">
                                <div class="info-label">Payment Status</div>
                                <div class="info-value" style="color: <?php echo $order['payment_status'] === 'completed' ? '#28a745' : '#ffc107'; ?>">
                                    <?php echo ucfirst($order['payment_status']); ?>
                                </div>
                            </div>

                            <div class="info-item">
                                <div class="info-label">Order Date</div>
                                <div class="info-value"><?php echo date('M d, Y h:i A', strtotime($order['created_at'])); ?></div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>