<?php
// owner/reports.php - View Sales Reports
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

    // Today's stats
    $stmt = $conn->prepare("
        SELECT COUNT(DISTINCT o.id) as orders, COALESCE(SUM(o.total_amount), 0) as revenue
        FROM orders o
        INNER JOIN order_items oi ON o.id = oi.order_id
        INNER JOIN food_items fi ON oi.food_item_id = fi.id
        WHERE fi.owner_id = ? AND DATE(o.created_at) = CURDATE()
        AND o.status = 'delivered'
    ");
    $stmt->execute([$_SESSION['user_id']]);
    $today = $stmt->fetch(PDO::FETCH_ASSOC);

    // This week's stats
    $stmt = $conn->prepare("
        SELECT COUNT(DISTINCT o.id) as orders, COALESCE(SUM(o.total_amount), 0) as revenue
        FROM orders o
        INNER JOIN order_items oi ON o.id = oi.order_id
        INNER JOIN food_items fi ON oi.food_item_id = fi.id
        WHERE fi.owner_id = ? AND YEARWEEK(o.created_at) = YEARWEEK(CURDATE())
        AND o.status = 'delivered'
    ");
    $stmt->execute([$_SESSION['user_id']]);
    $this_week = $stmt->fetch(PDO::FETCH_ASSOC);

    // This month's stats
    $stmt = $conn->prepare("
        SELECT COUNT(DISTINCT o.id) as orders, COALESCE(SUM(o.total_amount), 0) as revenue
        FROM orders o
        INNER JOIN order_items oi ON o.id = oi.order_id
        INNER JOIN food_items fi ON oi.food_item_id = fi.id
        WHERE fi.owner_id = ? AND MONTH(o.created_at) = MONTH(CURDATE()) AND YEAR(o.created_at) = YEAR(CURDATE())
        AND o.status = 'delivered'
    ");
    $stmt->execute([$_SESSION['user_id']]);
    $this_month = $stmt->fetch(PDO::FETCH_ASSOC);

    // All time stats
    $stmt = $conn->prepare("
        SELECT COUNT(DISTINCT o.id) as orders, COALESCE(SUM(o.total_amount), 0) as revenue
        FROM orders o
        INNER JOIN order_items oi ON o.id = oi.order_id
        INNER JOIN food_items fi ON oi.food_item_id = fi.id
        WHERE fi.owner_id = ? AND o.status = 'delivered'
    ");
    $stmt->execute([$_SESSION['user_id']]);
    $all_time = $stmt->fetch(PDO::FETCH_ASSOC);

    // Top selling items
    $stmt = $conn->prepare("
        SELECT fi.name, SUM(oi.quantity) as total_sold, SUM(oi.quantity * oi.price) as total_revenue
        FROM order_items oi
        INNER JOIN food_items fi ON oi.food_item_id = fi.id
        INNER JOIN orders o ON oi.order_id = o.id
        WHERE fi.owner_id = ? AND o.status = 'delivered'
        GROUP BY fi.id, fi.name
        ORDER BY total_sold DESC
        LIMIT 10
    ");
    $stmt->execute([$_SESSION['user_id']]);
    $top_items = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    error_log("Database error: " . $e->getMessage());
    $error = "Unable to load reports.";
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reports - <?php echo htmlspecialchars($restaurant['restaurant_name'] ?? 'Restaurant'); ?></title>
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
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        .stat-card {
            background: white;
            padding: 25px;
            border-radius: 15px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            border-left: 5px solid #667eea;
        }
        .stat-period {
            color: #666;
            font-size: 14px;
            text-transform: uppercase;
            margin-bottom: 10px;
        }
        .stat-value {
            font-size: 32px;
            font-weight: bold;
            color: #333;
            margin-bottom: 5px;
        }
        .stat-label {
            color: #999;
            font-size: 14px;
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
        table {
            width: 100%;
            border-collapse: collapse;
        }
        table th, table td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #e0e0e0;
        }
        table th {
            background: #f5f7fa;
            font-weight: 600;
            color: #333;
        }
        .empty-state {
            text-align: center;
            padding: 40px 20px;
            color: #999;
        }
    </style>
</head>
<body>
    <div class="navbar">
        <h1>📊 Sales Reports</h1>
        <div class="nav-links">
            <a href="dashboard.php">← Back to Dashboard</a>
            <a href="menu.php">Menu</a>
            <a href="../auth/logout.php">Logout</a>
        </div>
    </div>

    <div class="container">
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-period">Today</div>
                <div class="stat-value">₱<?php echo number_format($today['revenue'], 2); ?></div>
                <div class="stat-label"><?php echo $today['orders']; ?> orders</div>
            </div>

            <div class="stat-card">
                <div class="stat-period">This Week</div>
                <div class="stat-value">₱<?php echo number_format($this_week['revenue'], 2); ?></div>
                <div class="stat-label"><?php echo $this_week['orders']; ?> orders</div>
            </div>

            <div class="stat-card">
                <div class="stat-period">This Month</div>
                <div class="stat-value">₱<?php echo number_format($this_month['revenue'], 2); ?></div>
                <div class="stat-label"><?php echo $this_month['orders']; ?> orders</div>
            </div>

            <div class="stat-card">
                <div class="stat-period">All Time</div>
                <div class="stat-value">₱<?php echo number_format($all_time['revenue'], 2); ?></div>
                <div class="stat-label"><?php echo $all_time['orders']; ?> orders</div>
            </div>
        </div>

        <div class="card">
            <h2>Top Selling Items</h2>
            <?php if (empty($top_items)): ?>
                <div class="empty-state">
                    <p>No sales data available yet.</p>
                </div>
            <?php else: ?>
                <table>
                    <thead>
                        <tr>
                            <th>Item Name</th>
                            <th>Quantity Sold</th>
                            <th>Total Revenue</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($top_items as $item): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($item['name']); ?></td>
                                <td><?php echo $item['total_sold']; ?></td>
                                <td>₱<?php echo number_format($item['total_revenue'], 2); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>