<?php
// user/track_order.php - Real-time Order Tracking
session_start();

// Security checks
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'customer') {
    header("Location: ../auth/login.php");
    exit();
}

require_once '../config/db.php';

$order_id = $_GET['order_id'] ?? 0;

$db = new Database();
$conn = $db->getConnection();

// Get order details
$stmt = $conn->prepare("
    SELECT o.*, r.name as rider_name, r.phone as rider_phone
    FROM orders o
    LEFT JOIN users r ON o.rider_id = r.id
    WHERE o.id = ? AND o.user_id = ?
");
$stmt->execute([$order_id, $_SESSION['user_id']]);
$order = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$order) {
    header("Location: my_orders.php");
    exit();
}

// Get order items
$stmt = $conn->prepare("
    SELECT oi.*, fi.name as food_name, fi.price
    FROM order_items oi
    JOIN food_items fi ON oi.food_item_id = fi.id
    WHERE oi.order_id = ?
");
$stmt->execute([$order_id]);
$order_items = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Define order progress steps
$steps = [
    'pending' => ['label' => 'Order Placed', 'icon' => '📝'],
    'confirmed' => ['label' => 'Confirmed', 'icon' => '✅'],
    'preparing' => ['label' => 'Preparing', 'icon' => '👨‍🍳'],
    'on_the_way' => ['label' => 'On the Way', 'icon' => '🚴'],
    'delivered' => ['label' => 'Delivered', 'icon' => '🎉']
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Track Order #<?php echo $order_id; ?></title>
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
            max-width: 1000px;
            margin: 30px auto;
            padding: 0 20px;
        }
        .status-card {
            background: white;
            padding: 40px;
            border-radius: 15px;
            margin-bottom: 30px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            text-align: center;
        }
        .current-status {
            font-size: 48px;
            margin-bottom: 20px;
        }
        .status-text {
            font-size: 28px;
            font-weight: bold;
            color: #333;
            margin-bottom: 10px;
        }
        .status-description {
            color: #666;
            font-size: 16px;
        }
        .progress-container {
            background: white;
            padding: 40px;
            border-radius: 15px;
            margin-bottom: 30px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .progress-steps {
            display: flex;
            justify-content: space-between;
            position: relative;
            margin: 40px 0;
        }
        .progress-line {
            position: absolute;
            top: 30px;
            left: 0;
            width: 100%;
            height: 4px;
            background: #e0e0e0;
            z-index: 1;
        }
        .progress-line-fill {
            height: 100%;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            transition: width 0.5s ease;
        }
        .progress-step {
            position: relative;
            z-index: 2;
            flex: 1;
            text-align: center;
        }
        .step-circle {
            width: 60px;
            height: 60px;
            background: #e0e0e0;
            border-radius: 50%;
            margin: 0 auto 15px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 28px;
            transition: all 0.3s;
        }
        .progress-step.active .step-circle,
        .progress-step.completed .step-circle {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            transform: scale(1.1);
        }
        .step-label {
            font-size: 14px;
            color: #666;
            font-weight: 600;
        }
        .progress-step.active .step-label {
            color: #667eea;
            font-weight: bold;
        }
        .details-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 30px;
        }
        .details-card {
            background: white;
            padding: 30px;
            border-radius: 15px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .details-card h3 {
            margin-bottom: 20px;
            color: #333;
        }
        .detail-row {
            display: flex;
            justify-content: space-between;
            padding: 12px 0;
            border-bottom: 1px solid #f0f0f0;
        }
        .detail-row:last-child {
            border-bottom: none;
        }
        .detail-label {
            color: #666;
            font-size: 14px;
        }
        .detail-value {
            color: #333;
            font-weight: 600;
            font-size: 14px;
        }
        .item-row {
            display: flex;
            justify-content: space-between;
            padding: 12px 0;
            font-size: 14px;
        }
        .total-row {
            border-top: 2px solid #667eea;
            margin-top: 10px;
            padding-top: 15px;
            font-size: 18px;
            font-weight: bold;
            color: #667eea;
        }
        .auto-refresh {
            background: #fff3cd;
            color: #856404;
            padding: 12px;
            border-radius: 8px;
            text-align: center;
            margin-bottom: 20px;
            font-size: 14px;
        }
        @media (max-width: 768px) {
            .progress-steps {
                flex-direction: column;
                gap: 20px;
            }
            .progress-line {
                width: 4px;
                height: 100%;
                left: 30px;
                top: 0;
            }
            .step-circle {
                width: 50px;
                height: 50px;
            }
            .details-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <div class="navbar">
        <h1>📍 Track Order #<?php echo $order_id; ?></h1>
        <a href="my_orders.php">← Back to Orders</a>
    </div>

    <div class="container">
        <div class="auto-refresh">
            🔄 This page auto-refreshes every 10 seconds to show latest status
        </div>

        <div class="status-card">
            <div class="current-status">
                <?php 
                $current_icon = '📝';
                foreach ($steps as $key => $step) {
                    if ($key === $order['status']) {
                        $current_icon = $step['icon'];
                        break;
                    }
                }
                echo $current_icon;
                ?>
            </div>
            <div class="status-text"><?php echo ucfirst(str_replace('_', ' ', $order['status'])); ?></div>
            <div class="status-description">
                <?php
                $descriptions = [
                    'pending' => 'Your order has been placed and is waiting for confirmation',
                    'confirmed' => 'Restaurant has confirmed your order',
                    'preparing' => 'Your food is being prepared with care',
                    'on_the_way' => 'Your order is on the way to you!',
                    'delivered' => 'Enjoy your meal! 😊',
                    'cancelled' => 'This order has been cancelled'
                ];
                echo $descriptions[$order['status']] ?? 'Order status updated';
                ?>
            </div>
        </div>

        <div class="progress-container">
            <h3 style="margin-bottom: 30px; color: #333;">Order Progress</h3>
            <div class="progress-steps">
                <div class="progress-line">
                    <div class="progress-line-fill" style="width: <?php
                        $progress = 0;
                        $step_keys = array_keys($steps);
                        $current_index = array_search($order['status'], $step_keys);
                        if ($current_index !== false) {
                            $progress = ($current_index / (count($step_keys) - 1)) * 100;
                        }
                        echo $progress;
                    ?>%"></div>
                </div>
                
                <?php 
                $reached = false;
                foreach ($steps as $key => $step):
                    $is_active = ($order['status'] === $key);
                    $is_completed = false;
                    
                    if ($order['status'] === 'delivered' || $is_active) {
                        $reached = true;
                    }
                    
                    $class = '';
                    if ($is_active) {
                        $class = 'active';
                    } elseif (array_search($order['status'], array_keys($steps)) > array_search($key, array_keys($steps))) {
                        $class = 'completed';
                    }
                ?>
                    <div class="progress-step <?php echo $class; ?>">
                        <div class="step-circle"><?php echo $step['icon']; ?></div>
                        <div class="step-label"><?php echo $step['label']; ?></div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

        <div class="details-grid">
            <div class="details-card">
                <h3>Order Details</h3>
                <div class="detail-row">
                    <span class="detail-label">Order ID</span>
                    <span class="detail-value">#<?php echo $order['id']; ?></span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Order Date</span>
                    <span class="detail-value"><?php echo date('M d, Y h:i A', strtotime($order['created_at'])); ?></span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Delivery Address</span>
                    <span class="detail-value"><?php echo htmlspecialchars($order['delivery_address']); ?></span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Payment Status</span>
                    <span class="detail-value" style="color: <?php echo $order['payment_status'] === 'completed' ? '#28a745' : '#ffc107'; ?>">
                        <?php echo ucfirst($order['payment_status']); ?>
                    </span>
                </div>
                <?php if ($order['rider_name']): ?>
                <div class="detail-row">
                    <span class="detail-label">Rider</span>
                    <span class="detail-value"><?php echo htmlspecialchars($order['rider_name']); ?></span>
                </div>
                <?php endif; ?>
            </div>

            <div class="details-card">
                <h3>Order Items</h3>
                <?php foreach ($order_items as $item): ?>
                    <div class="item-row">
                        <span><?php echo htmlspecialchars($item['food_name']); ?> x <?php echo $item['quantity']; ?></span>
                        <span>₱<?php echo number_format($item['price'] * $item['quantity'], 2); ?></span>
                    </div>
                <?php endforeach; ?>
                <div class="item-row total-row">
                    <span>Total</span>
                    <span>₱<?php echo number_format($order['total_amount'], 2); ?></span>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Auto-refresh every 10 seconds
        setTimeout(() => {
            location.reload();
        }, 10000);
    </script>
</body>
</html>