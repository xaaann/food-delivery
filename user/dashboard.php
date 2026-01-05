<?php
// user/dashboard.php
require_once '../config/db.php';

if (!isLoggedIn() || !checkRole('customer')) {
    redirect('../auth/login.php');
}

$db = new Database();
$conn = $db->getConnection();

// Get available food items
$stmt = $conn->prepare("SELECT f.*, u.name as owner_name FROM food_items f 
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
    
    echo "<script>alert('Added to cart!');</script>";
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
            background: #f5f5f5;
        }
        .navbar {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 15px 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }
        .navbar h1 {
            font-size: 24px;
        }
        .navbar-right {
            display: flex;
            gap: 20px;
            align-items: center;
        }
        .cart-badge {
            background: #ff4757;
            color: white;
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: bold;
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
        .food-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 25px;
            margin-top: 30px;
        }
        .food-card {
            background: white;
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            transition: transform 0.3s;
        }
        .food-card:hover {
            transform: translateY(-5px);
        }
        .food-image {
            width: 100%;
            height: 200px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 48px;
        }
        .food-details {
            padding: 20px;
        }
        .food-name {
            font-size: 20px;
            font-weight: 600;
            margin-bottom: 5px;
        }
        .restaurant-name {
            color: #888;
            font-size: 14px;
            margin-bottom: 10px;
        }
        .food-description {
            color: #666;
            margin-bottom: 15px;
            font-size: 14px;
        }
        .food-footer {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-top: 15px;
        }
        .price {
            font-size: 24px;
            font-weight: bold;
            color: #667eea;
        }
        .stock {
            color: #888;
            font-size: 13px;
        }
        .add-to-cart {
            display: flex;
            gap: 10px;
            margin-top: 15px;
        }
        .quantity-input {
            width: 60px;
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 5px;
        }
        .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            flex: 1;
            font-weight: 600;
        }
        .empty-state {
            text-align: center;
            padding: 60px 20px;
            color: #888;
        }
    </style>
</head>
<body>
    <div class="navbar">
        <h1>🍕 Food Delivery</h1>
        <div class="navbar-right">
            <span>Welcome, <?php echo $_SESSION['name']; ?>!</span>
            <a href="order.php" class="btn btn-white">
                View Cart 
                <?php if (isset($_SESSION['cart']) && count($_SESSION['cart']) > 0): ?>
                    <span class="cart-badge"><?php echo count($_SESSION['cart']); ?></span>
                <?php endif; ?>
            </a>
            <a href="../auth/logout.php" class="btn btn-white">Logout</a>
        </div>
    </div>

    <div class="container">
        <h2 style="margin-bottom: 20px;">Available Food Items</h2>

        <?php if (count($food_items) > 0): ?>
            <div class="food-grid">
                <?php foreach ($food_items as $item): ?>
                    <div class="food-card">
                        <div class="food-image">🍽️</div>
                        <div class="food-details">
                            <div class="food-name"><?php echo htmlspecialchars($item['name']); ?></div>
                            <div class="restaurant-name">by <?php echo htmlspecialchars($item['owner_name']); ?></div>
                            <div class="food-description">
                                <?php echo htmlspecialchars($item['description']); ?>
                            </div>
                            <div class="food-footer">
                                <div class="price">$<?php echo number_format($item['price'], 2); ?></div>
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
                <h3>No food items available at the moment</h3>
                <p>Please check back later!</p>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>