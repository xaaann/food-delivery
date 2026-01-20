<?php
// owner/menu.php - Manage Restaurant Menu
session_start();

// Security checks
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'owner') {
    header("Location: ../auth/login.php");
    exit();
}

require_once '../config/db.php';

$success = '';
$error = '';

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

    // Get all food items
    $stmt = $conn->prepare("
        SELECT * FROM food_items 
        WHERE owner_id = ? 
        ORDER BY created_at DESC
    ");
    $stmt->execute([$_SESSION['user_id']]);
    $food_items = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    error_log("Database error: " . $e->getMessage());
    $error = "Unable to load menu items.";
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Menu - <?php echo htmlspecialchars($restaurant['restaurant_name'] ?? 'Restaurant'); ?></title>
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
        .menu-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 20px;
        }
        .menu-item {
            border: 2px solid #e0e0e0;
            border-radius: 10px;
            padding: 20px;
            transition: all 0.3s;
        }
        .menu-item:hover {
            border-color: #667eea;
            transform: translateY(-5px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        .menu-item h3 {
            color: #333;
            margin-bottom: 10px;
        }
        .menu-item p {
            color: #666;
            font-size: 14px;
            margin-bottom: 10px;
        }
        .price {
            font-size: 24px;
            font-weight: bold;
            color: #667eea;
            margin: 10px 0;
        }
        .stock-info {
            display: flex;
            justify-content: space-between;
            margin: 10px 0;
            font-size: 14px;
        }
        .status-badge {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: 600;
        }
        .status-available {
            background: #d4edda;
            color: #155724;
        }
        .status-unavailable {
            background: #f8d7da;
            color: #721c24;
        }
        .btn {
            padding: 10px 20px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 600;
            transition: all 0.3s;
            text-decoration: none;
            display: inline-block;
        }
        .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.3);
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
    </style>
</head>
<body>
    <div class="navbar">
        <h1>🍽️ Manage Menu</h1>
        <div class="nav-links">
            <a href="dashboard.php">← Back to Dashboard</a>
            <a href="orders.php">Orders</a>
            <a href="../auth/logout.php">Logout</a>
        </div>
    </div>

    <div class="container">
        <div class="card">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                <h2>Your Menu Items (<?php echo count($food_items); ?>)</h2>
                <a href="add_food.php" class="btn btn-primary">+ Add New Item</a>
            </div>

            <?php if (empty($food_items)): ?>
                <div class="empty-state">
                    <div class="empty-state-icon">🍽️</div>
                    <h3>No menu items yet</h3>
                    <p>Start adding delicious items to your menu!</p>
                    <br>
                    <a href="add_food.php" class="btn btn-primary">Add Your First Item</a>
                </div>
            <?php else: ?>
                <div class="menu-grid">
                    <?php foreach ($food_items as $item): ?>
                        <div class="menu-item">
                            <h3><?php echo htmlspecialchars($item['name']); ?></h3>
                            <p><?php echo htmlspecialchars($item['description'] ?? 'No description'); ?></p>
                            <div class="price">₱<?php echo number_format($item['price'], 2); ?></div>
                            <div class="stock-info">
                                <span>Stock: <strong><?php echo $item['stock']; ?></strong></span>
                                <span class="status-badge <?php echo $item['available'] ? 'status-available' : 'status-unavailable'; ?>">
                                    <?php echo $item['available'] ? 'Available' : 'Unavailable'; ?>
                                </span>
                            </div>
                            <div style="margin-top: 15px; display: flex; gap: 10px;">
                                <a href="edit_food.php?id=<?php echo $item['id']; ?>" class="btn btn-primary" style="flex: 1; text-align: center; font-size: 14px;">Edit</a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>