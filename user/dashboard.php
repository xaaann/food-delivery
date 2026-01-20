<?php
// user/dashboard.php - Enhanced Customer Dashboard
session_start();

// Security checks
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'customer') {
    header("Location: ../auth/login.php");
    exit();
}

require_once '../config/db.php';

$db = new Database();
$conn = $db->getConnection();

// Get available food items
$stmt = $conn->prepare("SELECT f.*, u.name as owner_name 
                        FROM food_items f 
                        JOIN users u ON f.owner_id = u.id 
                        WHERE f.available = 1 AND f.stock > 0 
                        ORDER BY f.created_at DESC");
$stmt->execute();
$food_items = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Handle add to cart
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_to_cart'])) {
    $food_id = $_POST['food_id'];
    $quantity = $_POST['quantity'];
    
    if (!isset($_SESSION['cart'])) {
        $_SESSION['cart'] = [];
    }
    
    if (isset($_SESSION['cart'][$food_id])) {
        $_SESSION['cart'][$food_id] += $quantity;
    } else {
        $_SESSION['cart'][$food_id] = $quantity;
    }
    
    $success_message = "Added to cart successfully!";
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Customer Dashboard</title>
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
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .navbar-right {
            display: flex;
            gap: 15px;
            align-items: center;
        }
        .cart-badge {
            background: #ff4757;
            color: white;
            padding: 3px 8px;
            border-radius: 12px;
            font-size: 11px;
            font-weight: bold;
            margin-left: 5px;
        }
        .btn {
            padding: 10px 20px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            font-weight: 600;
            transition: all 0.3s;
        }
        .btn-white {
            background: white;
            color: #667eea;
        }
        .btn-white:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(255,255,255,0.3);
        }
        .container {
            max-width: 1400px;
            margin: 30px auto;
            padding: 0 20px;
        }
        .welcome-card {
            background: white;
            padding: 25px;
            border-radius: 15px;
            margin-bottom: 30px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .welcome-text h2 {
            color: #333;
            margin-bottom: 5px;
        }
        .welcome-text p {
            color: #666;
        }
        .success-message {
            background: #d4edda;
            color: #155724;
            padding: 12px 20px;
            border-radius: 8px;
            margin-bottom: 20px;
            border-left: 4px solid #28a745;
            animation: slideIn 0.3s ease-out;
        }
        @keyframes slideIn {
            from { opacity: 0; transform: translateY(-10px); }
            to { opacity: 1; transform: translateY(0); }
        }
        .food-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 25px;
        }
        .food-card {
            background: white;
            border-radius: 15px;
            overflow: hidden;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            transition: all 0.3s;
        }
        .food-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 5px 20px rgba(0,0,0,0.15);
        }
        .food-image {
            width: 100%;
            height: 200px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 64px;
        }
        .food-details {
            padding: 20px;
        }
        .food-name {
            font-size: 20px;
            font-weight: 600;
            margin-bottom: 5px;
            color: #333;
        }
        .restaurant-name {
            color: #999;
            font-size: 13px;
            margin-bottom: 10px;
        }
        .food-description {
            color: #666;
            margin-bottom: 15px;
            font-size: 14px;
            line-height: 1.5;
        }
        .food-footer {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
        }
        .price {
            font-size: 28px;
            font-weight: bold;
            color: #667eea;
        }
        .stock {
            color: #28a745;
            font-size: 13px;
            font-weight: 600;
        }
        .add-to-cart {
            display: flex;
            gap: 10px;
        }
        .quantity-input {
            width: 70px;
            padding: 10px;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            font-size: 14px;
            text-align: center;
        }
        .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            flex: 1;
            justify-content: center;
        }
        .btn-primary:hover {
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
        }
        @media (max-width: 768px) {
            .navbar {
                flex-direction: column;
                gap: 15px;
            }
            .navbar-right {
                flex-wrap: wrap;
                justify-content: center;
            }
            .welcome-card {
                flex-direction: column;
                gap: 15px;
                text-align: center;
            }
        }
    </style>
</head>
<body>
    <div class="navbar">
        <h1><span>🍕</span> Food Delivery</h1>
        <div class="navbar-right">
            <span>Welcome, <?php echo htmlspecialchars($_SESSION['name']); ?>!</span>
            <a href="my_orders.php" class="btn btn-white">
                📋 My Orders
            </a>
            <a href="cart.php" class="btn btn-white">
                🛒 Cart 
                <?php if (isset($_SESSION['cart']) && count($_SESSION['cart']) > 0): ?>
                    <span class="cart-badge"><?php echo count($_SESSION['cart']); ?></span>
                <?php endif; ?>
            </a>
            <a href="../auth/logout.php" class="btn btn-white">Logout</a>
        </div>
    </div>

    <div class="container">
        <div class="welcome-card">
            <div class="welcome-text">
                <h2>Browse Our Menu</h2>
                <p>Discover delicious food from the best restaurants</p>
            </div>
        </div>

        <?php if (isset($success_message)): ?>
            <div class="success-message">✅ <?php echo $success_message; ?></div>
        <?php endif; ?>

        <?php if (count($food_items) > 0): ?>
            <div class="food-grid">
                <?php foreach ($food_items as $item): ?>
                    <div class="food-card">
                        <div class="food-image">🍽️</div>
                        <div class="food-details">
                            <div class="food-name"><?php echo htmlspecialchars($item['name']); ?></div>
                            <div class="restaurant-name">by <?php echo htmlspecialchars($item['owner_name']); ?></div>
                            <div class="food-description">
                                <?php echo htmlspecialchars($item['description'] ?? 'Delicious food item'); ?>
                            </div>
                            <div class="food-footer">
                                <div class="price">₱<?php echo number_format($item['price'], 2); ?></div>
                                <div class="stock">Stock: <?php echo $item['stock']; ?></div>
                            </div>
                            <form method="POST" class="add-to-cart">
                                <input type="hidden" name="food_id" value="<?php echo $item['id']; ?>">
                                <input type="number" name="quantity" value="1" min="1" max="<?php echo $item['stock']; ?>" class="quantity-input">
                                <button type="submit" name="add_to_cart" class="btn btn-primary">Add to Cart</button>
                            </form>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="empty-state">
                <div class="empty-state-icon">🍽️</div>
                <h3>No food items available</h3>
                <p>Please check back later for delicious options!</p>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>