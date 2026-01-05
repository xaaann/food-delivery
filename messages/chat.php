<?php
// messages/chat.php
require_once '../config/db.php';

if (!isLoggedIn()) {
    redirect('../auth/login.php');
}

$db = new Database();
$conn = $db->getConnection();

$order_id = $_GET['order_id'] ?? 0;

// Get order details
$stmt = $conn->prepare("SELECT o.*, 
                        u1.name as customer_name, u1.id as customer_id,
                        u2.name as rider_name, u2.id as rider_id
                        FROM orders o 
                        JOIN users u1 ON o.user_id = u1.id 
                        LEFT JOIN users u2 ON o.rider_id = u2.id
                        WHERE o.id = ?");
$stmt->execute([$order_id]);
$order = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$order) {
    redirect('../auth/login.php');
}

// Check if user has access to this order
$has_access = ($_SESSION['user_id'] == $order['customer_id'] || $_SESSION['user_id'] == $order['rider_id']);
if (!$has_access) {
    redirect('../auth/login.php');
}

// Determine receiver
$receiver_id = ($_SESSION['user_id'] == $order['customer_id']) ? $order['rider_id'] : $order['customer_id'];
$receiver_name = ($_SESSION['user_id'] == $order['customer_id']) ? $order['rider_name'] : $order['customer_name'];

// Handle send message
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['send_message'])) {
    $message = trim($_POST['message']);
    
    if (!empty($message)) {
        $stmt = $conn->prepare("INSERT INTO messages (order_id, sender_id, receiver_id, message) VALUES (?, ?, ?, ?)");
        $stmt->execute([$order_id, $_SESSION['user_id'], $receiver_id, $message]);
    }
}

// Get messages
$stmt = $conn->prepare("SELECT m.*, u.name as sender_name 
                        FROM messages m 
                        JOIN users u ON m.sender_id = u.id 
                        WHERE m.order_id = ? 
                        ORDER BY m.created_at ASC");
$stmt->execute([$order_id]);
$messages = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Chat - Order #<?php echo $order_id; ?></title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #f5f5f5;
            height: 100vh;
            display: flex;
            flex-direction: column;
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
        .btn-back {
            background: white;
            color: #667eea;
            padding: 8px 16px;
            border-radius: 5px;
            text-decoration: none;
            font-weight: 600;
        }
        .chat-container {
            flex: 1;
            max-width: 900px;
            width: 100%;
            margin: 20px auto;
            background: white;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            display: flex;
            flex-direction: column;
            overflow: hidden;
        }
        .chat-header {
            padding: 20px;
            background: #f9f9f9;
            border-bottom: 2px solid #eee;
        }
        .chat-info {
            color: #666;
            font-size: 14px;
            margin-top: 5px;
        }
        .messages-container {
            flex: 1;
            overflow-y: auto;
            padding: 20px;
            background: #f9f9f9;
        }
        .message {
            display: flex;
            margin-bottom: 15px;
            animation: slideIn 0.3s;
        }
        @keyframes slideIn {
            from {
                opacity: 0;
                transform: translateY(10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        .message.sent {
            justify-content: flex-end;
        }
        .message.received {
            justify-content: flex-start;
        }
        .message-bubble {
            max-width: 60%;
            padding: 12px 16px;
            border-radius: 18px;
            word-wrap: break-word;
        }
        .message.sent .message-bubble {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }
        .message.received .message-bubble {
            background: white;
            color: #333;
            border: 1px solid #ddd;
        }
        .message-info {
            font-size: 11px;
            margin-top: 5px;
            opacity: 0.7;
        }
        .message-form {
            padding: 20px;
            background: white;
            border-top: 2px solid #eee;
            display: flex;
            gap: 10px;
        }
        .message-input {
            flex: 1;
            padding: 12px 16px;
            border: 1px solid #ddd;
            border-radius: 25px;
            font-size: 14px;
            outline: none;
        }
        .message-input:focus {
            border-color: #667eea;
        }
        .btn-send {
            padding: 12px 30px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            border-radius: 25px;
            font-weight: 600;
            cursor: pointer;
            transition: transform 0.2s;
        }
        .btn-send:hover {
            transform: scale(1.05);
        }
        .no-messages {
            text-align: center;
            padding: 60px 20px;
            color: #888;
        }
        .order-status {
            display: inline-block;
            padding: 5px 12px;
            border-radius: 15px;
            font-size: 12px;
            font-weight: 600;
            background: #d1e7dd;
            color: #0f5132;
        }
    </style>
</head>
<body>
    <div class="navbar">
        <div>
            <h1>💬 Chat - Order #<?php echo $order_id; ?></h1>
        </div>
        <a href="<?php echo $_SESSION['role'] == 'customer' ? '../user/dashboard.php' : '../rider/dashboard.php'; ?>" 
           class="btn-back">← Back</a>
    </div>

    <div class="chat-container">
        <div class="chat-header">
            <h2>Chatting with <?php echo htmlspecialchars($receiver_name ?? 'Delivery Partner'); ?></h2>
            <div class="chat-info">
                Order Status: <span class="order-status"><?php echo ucfirst(str_replace('_', ' ', $order['status'])); ?></span>
                <br>
                Total: $<?php echo number_format($order['total_amount'], 2); ?>
                <br>
                Delivery Address: <?php echo htmlspecialchars($order['delivery_address']); ?>
            </div>
        </div>

        <div class="messages-container" id="messagesContainer">
            <?php if (count($messages) > 0): ?>
                <?php foreach ($messages as $msg): ?>
                    <div class="message <?php echo $msg['sender_id'] == $_SESSION['user_id'] ? 'sent' : 'received'; ?>">
                        <div class="message-bubble">
                            <?php if ($msg['sender_id'] != $_SESSION['user_id']): ?>
                                <strong><?php echo htmlspecialchars($msg['sender_name']); ?></strong><br>
                            <?php endif; ?>
                            <?php echo nl2br(htmlspecialchars($msg['message'])); ?>
                            <div class="message-info">
                                <?php echo date('h:i A', strtotime($msg['created_at'])); ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="no-messages">
                    <p>No messages yet. Start the conversation!</p>
                </div>
            <?php endif; ?>
        </div>

        <form method="POST" class="message-form">
            <input type="text" name="message" class="message-input" 
                   placeholder="Type your message..." required autocomplete="off">
            <button type="submit" name="send_message" class="btn-send">Send</button>
        </form>
    </div>

    <script>
        // Auto-scroll to bottom
        const container = document.getElementById('messagesContainer');
        container.scrollTop = container.scrollHeight;

        // Auto-refresh messages every 3 seconds
        setInterval(() => {
            location.reload();
        }, 3000);
    </script>
</body>
</html>