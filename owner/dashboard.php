<?php
// owner/dashboard.php
require_once '../config/db.php';

if (!isLoggedIn() || !checkRole('owner')) {
    redirect('../auth/login.php');
}

$db = new Database();
$conn = $db->getConnection();

// Get owner's food items
$stmt = $conn->prepare("SELECT * FROM food_items WHERE owner_id = ? ORDER BY created_at DESC");
$stmt->execute([$_SESSION['user_id']]);
$food_items = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get statistics
$stmt = $conn->prepare("SELECT COUNT(*) as total_items, SUM(stock) as total_stock FROM food_items WHERE owner_id = ?");
$stmt->execute([$_SESSION['user_id']]);
$stats = $stmt->fetch(PDO::FETCH_ASSOC);

// Get low stock items
$stmt = $conn->prepare("SELECT * FROM food_items WHERE owner_id = ? AND stock < 10 ORDER BY stock ASC");
$stmt->execute([$_SESSION['user_id']]);
$low_stock = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Owner Dashboard</title>
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
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        .stat-card {
            background: white;
            padding: 25px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .stat-value {
            font-size: 36px;
            font-weight: bold;
            color: #667eea;
            margin-bottom: 5px;
        }
        .stat-label {
            color: #888;
            font-size: 14px;
        }
        .section {
            background: white;
            border-radius: 10px;
            padding: 30px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            margin-bottom: 30px;
        }
        .section-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 25px;
        }
        .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            font-weight: 600;
        }
        table {
            width: 100%;
            border-collapse: collapse;
        }
        th {
            background: #f9f9f9;
            padding: 15px;
            text-align: left;
            font-weight: 600;
            border-bottom: 2px solid #ddd;
        }
        td {
            padding: 15px;
            border-bottom: 1px solid #eee;
        }
        tr:hover {
            background: #f9f9f9;
        }
        .status-badge {
            padding: 5px 10px;
            border-radius: 20px;
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
        .stock-low {
            color: #ff4757;
            font-weight: bold;
        }
        .stock-ok {
            color: #2ecc71;
        }
        .action-buttons {
            display: flex;
            gap: 10px;
        }
        .btn-small {
            padding: 5px 10px;
            font-size: 13px;
        }
        .btn-edit {
            background: #667eea;
            color: white;
        }
        .btn-delete {
            background: #ff4757;
            color: white;
        }
        .alert {
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
        }
        .alert-warning {
            background: #fff3cd;
            color: #856404;
            border-left: 4px solid #ffc107;
        }
    </style>
</head>
<body>
    <div class="navbar">
        <h1>🏪 Restaurant Owner Dashboard</h1>
        <div>
            <span style="margin-right: 20px;">Welcome, <?php echo $_SESSION['name']; ?>!</span>
            <a href="../auth/logout.php" class="btn btn-white">Logout</a>
        </div>
    </div>

    <div class="container">
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-value"><?php echo $stats['total_items'] ?? 0; ?></div>
                <div class="stat-label">Total Food Items</div>
            </div>
            <div class="stat-card">
                <div class="stat-value"><?php echo $stats['total_stock'] ?? 0; ?></div>
                <div class="stat-label">Total Stock Units</div>
            </div>
            <div class="stat-card">
                <div class="stat-value"><?php echo count($low_stock); ?></div>
                <div class="stat-label">Low Stock Items</div>
            </div>
        </div>

        <?php if (count($low_stock) > 0): ?>
            <div class="alert alert-warning">
                <strong>⚠️ Low Stock Alert:</strong> 
                <?php echo count($low_stock); ?> item(s) have less than 10 units in stock. 
                Please restock soon to avoid running out.
            </div>
        <?php endif; ?>

        <div class="section">
            <div class="section-header">
                <h2>Food Items Management</h2>
                <a href="add_food.php" class="btn btn-primary">+ Add New Item</a>
            </div>

            <?php if (count($food_items) > 0): ?>
                <table>
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Price</th>
                            <th>Stock</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($food_items as $item): ?>
                            <tr>
                                <td>
                                    <strong><?php echo htmlspecialchars($item['name']); ?></strong>
                                    <br>
                                    <small style="color: #888;"><?php echo htmlspecialchars($item['description']); ?></small>
                                </td>
                                <td>$<?php echo number_format($item['price'], 2); ?></td>
                                <td>
                                    <span class="<?php echo $item['stock'] < 10 ? 'stock-low' : 'stock-ok'; ?>">
                                        <?php echo $item['stock']; ?> units
                                    </span>
                                </td>
                                <td>
                                    <span class="status-badge <?php echo $item['available'] ? 'status-available' : 'status-unavailable'; ?>">
                                        <?php echo $item['available'] ? 'Available' : 'Unavailable'; ?>
                                    </span>
                                </td>
                                <td>
                                    <div class="action-buttons">
                                        <a href="edit_food.php?id=<?php echo $item['id']; ?>" class="btn btn-small btn-edit">Edit</a>
                                        <a href="delete_food.php?id=<?php echo $item['id']; ?>" 
                                           class="btn btn-small btn-delete"
                                           onclick="return confirm('Are you sure you want to delete this item?')">Delete</a>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <p style="text-align: center; color: #888; padding: 40px 0;">
                    No food items yet. Click "Add New Item" to get started!
                </p>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>