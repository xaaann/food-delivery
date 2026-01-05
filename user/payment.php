<?php
// user/payment.php
require_once '../config/db.php';

if (!isLoggedIn() || !checkRole('customer')) {
    redirect('../auth/login.php');
}

if (!isset($_SESSION['cart']) || count($_SESSION['cart']) == 0) {
    redirect('dashboard.php');
}

$db = new Database();
$conn = $db->getConnection();

$delivery_address = $_POST['delivery_address'] ?? '';

// Calculate total
$total = 0;
$ids = implode(',', array_keys($_SESSION['cart']));
$stmt = $conn->prepare("SELECT * FROM food_items WHERE id IN ($ids)");
$stmt->execute();
$items = $stmt->fetchAll(PDO::FETCH_ASSOC);

foreach ($items as $item) {
    $quantity = $_SESSION['cart'][$item['id']];
    $total += $item['price'] * $quantity;
}

// Process payment
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['confirm_payment'])) {
    try {
        $conn->beginTransaction();
        
        // Create order
        $stmt = $conn->prepare("INSERT INTO orders (user_id, total_amount, delivery_address, status, payment_status) 
                               VALUES (?, ?, ?, 'pending', 'completed')");
        $stmt->execute([$_SESSION['user_id'], $total, $delivery_address]);
        $order_id = $conn->lastInsertId();
        
        // Add order items and update stock
        foreach ($items as $item) {
            $quantity = $_SESSION['cart'][$item['id']];
            
            // Insert order item
            $stmt = $conn->prepare("INSERT INTO order_items (order_id, food_item_id, quantity, price) 
                                   VALUES (?, ?, ?, ?)");
            $stmt->execute([$order_id, $item['id'], $quantity, $item['price']]);
            
            // Update stock
            $stmt = $conn->prepare("UPDATE food_items SET stock = stock - ? WHERE id = ?");
            $stmt->execute([$quantity, $item['id']]);
        }
        
        $conn->commit();
        
        // Clear cart
        unset($_SESSION['cart']);
        
        // Store order ID for confirmation
        $_SESSION['last_order_id'] = $order_id;
        
        redirect('payment.php?success=1');
        
    } catch (Exception $e) {
        $conn->rollBack();
        $error = "Payment processing failed. Please try again.";
    }
}

$success = isset($_GET['success']);
$order_id = $_SESSION['last_order_id'] ?? null;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment</title>
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
        }
        .container {
            max-width: 600px;
            margin: 30px auto;
            padding: 0 20px;
        }
        .payment-card {
            background: white;
            border-radius: 10px;
            padding: 40px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        h2 {
            margin-bottom: 25px;
            text-align: center;
        }
        .order-summary {
            background: #f9f9f9;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 30px;
        }
        .summary-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 10px;
        }
        .total {
            font-size: 24px;
            font-weight: bold;
            color: #667eea;
            margin-top: 15px;
            padding-top: 15px;
            border-top: 2px solid #ddd;
        }
        .form-group {
            margin-bottom: 20px;
        }
        label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
        }
        input {
            width: 100%;
            padding: 12px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 14px;
        }
        .btn {
            width: 100%;
            padding: 15px;
            border: none;
            border-radius: 5px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
        }
        .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }
        .success-message {
            text-align: center;
            padding: 40px;
        }
        .success-icon {
            font-size: 80px;
            margin-bottom: 20px;
        }
        .success-message h2 {
            color: #2ecc71;
            margin-bottom: 15px;
        }
        .btn-secondary {
            background: white;
            color: #667eea;
            border: 2px solid #667eea;
            margin-top: 15px;
        }
        .payment-note {
            background: #fff3cd;
            color: #856404;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
            font-size: 14px;
        }
    </style>
</head>
<body>
    <div class="navbar">
        <h1>🍕 Food Delivery - Payment</h1>
    </div>

    <div class="container">
        <div class="payment-card">
            <?php if ($success): ?>
                <div class="success-message">
                    <div class="success-icon">✓</div>
                    <h2>Payment Successful!</h2>
                    <p>Your order #<?php echo $order_id; ?> has been placed successfully.</p>
                    <p style="margin-top: 15px; color: #666;">
                        A delivery partner will be assigned shortly. You can track your order and 
                        communicate with the rider through the messages page.
                    </p>
                    <a href="dashboard.php" class="btn btn-primary" style="text-decoration: none; margin-top: 25px; display: inline-block;">
                        Back to Home
                    </a>
                    <a href="../messages/chat.php?order_id=<?php echo $order_id; ?>" class="btn btn-secondary" style="text-decoration: none; display: inline-block;">
                        Go to Messages
                    </a>
                </div>
            <?php else: ?>
                <h2>Complete Your Payment</h2>
                
                <div class="payment-note">
                    <strong>Note:</strong> This is a simulation. No real payment will be processed.
                </div>

                <div class="order-summary">
                    <h3 style="margin-bottom: 15px;">Order Summary</h3>
                    <div class="summary-row">
                        <span>Delivery Address:</span>
                        <span style="text-align: right;"><?php echo htmlspecialchars($delivery_address); ?></span>
                    </div>
                    <div class="summary-row total">
                        <span>Total Amount:</span>
                        <span>$<?php echo number_format($total, 2); ?></span>
                    </div>
                </div>

                <form method="POST">
                    <input type="hidden" name="delivery_address" value="<?php echo htmlspecialchars($delivery_address); ?>">
                    
                    <h3 style="margin-bottom: 20px;">Payment Details (Dummy)</h3>
                    
                    <div class="form-group">
                        <label>Card Number</label>
                        <input type="text" placeholder="1234 5678 9012 3456" value="4111111111111111" readonly>
                    </div>

                    <div style="display: flex; gap: 15px;">
                        <div class="form-group" style="flex: 1;">
                            <label>Expiry Date</label>
                            <input type="text" placeholder="MM/YY" value="12/25" readonly>
                        </div>
                        <div class="form-group" style="flex: 1;">
                            <label>CVV</label>
                            <input type="text" placeholder="123" value="123" readonly>
                        </div>
                    </div>

                    <div class="form-group">
                        <label>Cardholder Name</label>
                        <input type="text" placeholder="John Doe" value="<?php echo $_SESSION['name']; ?>" readonly>
                    </div>

                    <button type="submit" name="confirm_payment" class="btn btn-primary">
                        Confirm Payment - $<?php echo number_format($total, 2); ?>
                    </button>
                </form>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>