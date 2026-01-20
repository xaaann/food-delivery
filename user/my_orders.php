<?php
// user/my_orders.php - Customer Order History & Tracking
session_start();

// Security checks
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'customer') {
    header("Location: ../auth/login.php");
    exit();
}

require_once '../config/db.php';

$db = new Database();
$conn = $db->getConnection();

// Get all orders for this customer
$stmt = $conn->prepare("
    SELECT o.*, r.name as rider_name
    FROM orders o
    LEFT JOIN users r ON o.rider_id = r.id
    WHERE o.user_id = ?
    ORDER BY o.created_at DESC
");
$stmt->execute([$_SESSION['user_id']]);
$orders = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Orders</title>
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
            max-width: 1200px;
            margin: 30px auto;
            padding: 0 20px;
        }
        .page-header {
            background: white;
            padding: 30px;
            border-radius: 15px;
            margin-bottom: 30px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .page-header h2 {
            color: #333;
            margin-bottom: 10px;
        }
        .page-header p {
            color: #666;
        }
        .order-card {
            background: white;
            border-radius: 15px;
            padding: 25px;
            margin-bottom: 20px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            transition: all 0.3s;
        }
        .order-card:hover {
            box-shadow: 0 5px 20px rgba(0,0,0,0.15);
        }
        .order-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 2px solid #f0f0f0;
        }
        .order-id {
            font-size: 20px;
            font-weight: bold;
            color: #333;
        }
        .order-date {
            color: #999;
            font-size: 14px;
        }
        .status-badge {
            display: inline-block;
            padding: 8px 20px;
            border-radius: 20px;
            font-size: 13px;
            font-weight: 600;
            text-transform: capitalize;
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
        .order-details {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 20px;
        }
        .detail-item {
            font-size: 14px;
        }
        .detail-label {
            color: #999;
            font-size: 12px;
            text-transform: uppercase;
            margin-bottom: 5px;
        }
        .detail-value {
            color: #333;
            font-weight: 600;
        }
        .total-amount {
            font-size: 24px;
            font-weight: bold;
            color: #667eea;
        }
        .track-btn {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 10px 25px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 600;
            text-decoration: none;
            display: inline-block;
            transition: all 0.3s;
        }
        .track-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.3);
        }
        .empty-state {
            text-align: center;
            padding: 80px 20px;
            background: white;
            border-radius: 15px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .empty-state-icon {
            font-size: 80px;
            margin-bottom: 20px;
        }
        .empty-state h3 {
            color: #333;
            margin-bottom: 10px;
        }
        .empty-state p {
            color: #999;
            margin-bottom: 20px;
        }
    </style>
</head>
<body>
    <div class="navbar">
        <h1>📋 My Orders</h1>
        <div class="nav-links">
            <a href="dashboard.php">← Back to Menu</a>
            <a href="cart.php">Cart</a>
            <a href="../auth/logout.php">Logout</a>
        </div>
    </div>

    <div class="container">
        <div class="page-header">
            <h2>Order History</h2>
            <p>Track and manage all your orders</p>
        </div>

        <?php if (empty($orders)): ?>
            <div class="empty-state">
                <div class="empty-state-icon">📋</div>
                <h3>No orders yet</h3>
                <p>Start ordering delicious food now!</p>
                <a href="dashboard.php" class="track-btn">Browse Menu</a>
            </div>
        <?php else: ?>
            <?php foreach ($orders as $order): ?>
                <div class="order-card">
                    <div class="order-header">
                        <div>
                            <div class="order-id">Order #<?php echo $order['id']; ?></div>
                            <div class="order-date"><?php echo date('M d, Y h:i A', strtotime($order['created_at'])); ?></div>
                        </div>
                        <span class="status-badge status-<?php echo $order['status']; ?>">
                            <?php echo ucfirst(str_replace('_', ' ', $order['status'])); ?>
                        </span>
                    </div>

                    <div class="order-details">
                        <div class="detail-item">
                            <div class="detail-label">Delivery Address</div>
                            <div class="detail-value"><?php echo htmlspecialchars($order['delivery_address']); ?></div>
                        </div>

                        <div class="detail-item">
                            <div class="detail-label">Rider</div>
                            <div class="detail-value">
                                <?php echo $order['rider_name'] ? htmlspecialchars($order['rider_name']) : 'Not assigned yet'; ?>
                            </div>
                        </div>

                        <div class="detail-item">
                            <div class="detail-label">Payment Status</div>
                            <div class="detail-value" style="color: <?php echo $order['payment_status'] === 'completed' ? '#28a745' : '#ffc107'; ?>">
                                <?php echo ucfirst($order['payment_status']); ?>
                            </div>
                        </div>

                        <div class="detail-item">
                            <div class="detail-label">Total Amount</div>
                            <div class="total-amount">₱<?php echo number_format($order['total_amount'], 2); ?></div>
                        </div>
                    </div>

                    <div style="display: flex; gap: 10px;">
                        <a href="track_order.php?order_id=<?php echo $order['id']; ?>" class="track-btn">
                            📍 Track Order
                        </a>
                        <?php if ($order['rider_id'] && $order['status'] === 'on_the_way'): ?>
                            <a href="../messages/chat.php?order_id=<?php echo $order['id']; ?>" class="track-btn" style="background: #28a745;">
                                💬 Message Rider
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</body>
</html>