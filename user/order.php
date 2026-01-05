<?php
// user/order.php
require_once '../config/db.php';

if (!isLoggedIn() || !checkRole('customer')) {
    redirect('../auth/login.php');
}

$db = new Database();
$conn = $db->getConnection();

// Handle remove from cart
if (isset($_POST['remove_item'])) {
    $food_id = $_POST['food_id'];
    unset($_SESSION['cart'][$food_id]);
}

// Handle update quantity
if (isset($_POST['update_quantity'])) {
    $food_id = $_POST['food_id'];
    $quantity = $_POST['quantity'];
    if ($quantity > 0) {
        $_SESSION['cart'][$food_id] = $quantity;
    } else {
        unset($_SESSION['cart'][$food_id]);
    }
}

// Get cart items details
$cart_items = [];
$total = 0;

if (isset($_SESSION['cart']) && count($_SESSION['cart']) > 0) {
    $ids = implode(',', array_keys($_SESSION['cart']));
    $stmt = $conn->prepare("SELECT * FROM food_items WHERE id IN ($ids)");
    $stmt->execute();
    $items = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($items as $item) {
        $quantity = $_SESSION['cart'][$item['id']];
        $subtotal = $item['price'] * $quantity;
        $total += $subtotal;
        
        $cart_items[] = [
            'id' => $item['id'],
            'name' => $item['name'],
            'price' => $item['price'],
            'quantity' => $quantity,
            'subtotal' => $subtotal,
            'stock' => $item['stock']
        ];
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Your Cart</title>
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
            max-width: 900px;
            margin: 30px auto;
            padding: 0 20px;
        }
        .cart-card {
            background: white;
            border-radius: 10px;
            padding: 30px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            margin-bottom: 20px;
        }
        h2 {
            margin-bottom: 25px;
        }
        .cart-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 20px 0;
            border-bottom: 1px solid #eee;
        }
        .cart-item:last-child {
            border-bottom: none;
        }
        .item-details {
            flex: 1;
        }
        .item-name {
            font-size: 18px;
            font-weight: 600;
            margin-bottom: 5px;
        }
        .item-price {
            color: #667eea;
            font-weight: 600;
        }
        .item-actions {
            display: flex;
            gap: 15px;
            align-items: center;
        }
        .quantity-input {
            width: 70px;
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 5px;
        }
        .btn-remove {
            background: #ff4757;
            color: white;
            padding: 8px 12px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
        }
        .btn-update {
            background: #667eea;
            color: white;
            padding: 8px 12px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
        }
        .cart-summary {
            margin-top: 30px;
            padding-top: 20px;
            border-top: 2px solid #eee;
        }
        .summary-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 10px;
            font-size: 16px;
        }
        .summary-total {
            font-size: 24px;
            font-weight: bold;
            color: #667eea;
        }
        .checkout-section {
            margin-top: 30px;
        }
        .form-group {
            margin-bottom: 20px;
        }
        label {
            display: block;
            margin-bottom: 5px;
            font-weight: 600;
        }
        textarea {
            width: 100%;
            padding: 12px;
            border: 1px solid #ddd;
            border-radius: 5px;
            resize: vertical;
        }
        .btn-checkout {
            width: 100%;
            padding: 15px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            border-radius: 5px;
            font-size: 18px;
            font-weight: 600;
            cursor: pointer;
        }
        .empty-cart {
            text-align: center;
            padding: 60px 20px;
            color: #888;
        }
    </style>
</head>
<body>
    <div class="navbar">
        <h1>🍕 Food Delivery - Cart</h1>
        <a href="dashboard.php" class="btn btn-white">← Back to Menu</a>
    </div>

    <div class="container">
        <div class="cart-card">
            <h2>Your Cart</h2>

            <?php if (count($cart_items) > 0): ?>
                <?php foreach ($cart_items as $item): ?>
                    <div class="cart-item">
                        <div class="item-details">
                            <div class="item-name"><?php echo htmlspecialchars($item['name']); ?></div>
                            <div class="item-price">$<?php echo number_format($item['price'], 2); ?> each</div>
                            <div style="color: #888; font-size: 14px; margin-top: 5px;">
                                Subtotal: $<?php echo number_format($item['subtotal'], 2); ?>
                            </div>
                        </div>
                        <div class="item-actions">
                            <form method="POST" style="display: inline;">
                                <input type="hidden" name="food_id" value="<?php echo $item['id']; ?>">
                                <input type="number" name="quantity" value="<?php echo $item['quantity']; ?>" 
                                       min="1" max="<?php echo $item['stock']; ?>" class="quantity-input">
                                <button type="submit" name="update_quantity" class="btn-update">Update</button>
                            </form>
                            <form method="POST" style="display: inline;">
                                <input type="hidden" name="food_id" value="<?php echo $item['id']; ?>">
                                <button type="submit" name="remove_item" class="btn-remove">Remove</button>
                            </form>
                        </div>
                    </div>
                <?php endforeach; ?>

                <div class="cart-summary">
                    <div class="summary-row summary-total">
                        <span>Total:</span>
                        <span>$<?php echo number_format($total, 2); ?></span>
                    </div>
                </div>

                <div class="checkout-section">
                    <form method="POST" action="payment.php">
                        <div class="form-group">
                            <label>Delivery Address</label>
                            <textarea name="delivery_address" rows="4" required 
                                      placeholder="Enter your delivery address"></textarea>
                        </div>
                        <button type="submit" class="btn-checkout">Proceed to Payment</button>
                    </form>
                </div>
            <?php else: ?>
                <div class="empty-cart">
                    <h3>Your cart is empty</h3>
                    <p>Add some delicious food items to get started!</p>
                    <a href="dashboard.php" class="btn btn-checkout" style="width: auto; margin-top: 20px;">
                        Browse Menu
                    </a>
                </div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>