<?php
// rider/dashboard.php
require_once '../config/db.php';

if (!isLoggedIn() || !checkRole('rider')) {
    redirect('../auth/login.php');
}

$db = new Database();
$conn = $db->getConnection();

// Fetch orders
$available_orders = $conn->query("SELECT o.*, u.name as customer_name, u.phone as customer_phone 
                                  FROM orders o 
                                  JOIN users u ON o.user_id = u.id 
                                  WHERE o.rider_id IS NULL AND o.status='pending' 
                                  ORDER BY o.created_at DESC")->fetchAll(PDO::FETCH_ASSOC);

$my_orders_stmt = $conn->prepare("SELECT o.*, u.name as customer_name, u.phone as customer_phone 
                                  FROM orders o 
                                  JOIN users u ON o.user_id = u.id 
                                  WHERE o.rider_id = ? AND o.status NOT IN ('delivered','cancelled') 
                                  ORDER BY o.created_at DESC");
$my_orders_stmt->execute([$_SESSION['user_id']]);
$my_orders = $my_orders_stmt->fetchAll(PDO::FETCH_ASSOC);

$completed_orders_stmt = $conn->prepare("SELECT o.*, u.name as customer_name 
                                         FROM orders o 
                                         JOIN users u ON o.user_id = u.id 
                                         WHERE o.rider_id = ? AND o.status IN ('delivered','cancelled') 
                                         ORDER BY o.created_at DESC LIMIT 10");
$completed_orders_stmt->execute([$_SESSION['user_id']]);
$completed_orders = $completed_orders_stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Rider Dashboard</title>
<style>
* {margin:0;padding:0;box-sizing:border-box;}
body {font-family:'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background:#f5f5f5;}
.navbar {background:linear-gradient(135deg,#667eea,#764ba2);color:white;padding:15px 30px;display:flex;justify-content:space-between;align-items:center;}
.btn {padding:8px 16px;border:none;border-radius:5px;cursor:pointer;text-decoration:none;display:inline-block;font-weight:600;}
.btn-white {background:white;color:#667eea;}
.btn-accept {background:#2ecc71;color:white;}
.btn-primary {background:#667eea;color:white;}
.btn-success {background:#2ecc71;color:white;}
.container {max-width:1200px;margin:30px auto;padding:0 20px;}
.section {background:white;border-radius:10px;padding:30px;box-shadow:0 2px 10px rgba(0,0,0,0.1);margin-bottom:30px;}
h2 {margin-bottom:25px;color:#333;}
.order-card {border:2px solid #eee;border-radius:8px;padding:20px;margin-bottom:20px;transition: all 0.3s ease;}
.order-card:hover {box-shadow:0 4px 15px rgba(0,0,0,0.15);}
.order-header {display:flex;justify-content:space-between;align-items:center;margin-bottom:15px;}
.order-id {font-size:18px;font-weight:bold;color:#667eea;}
.status-badge {padding:6px 12px;border-radius:20px;font-size:13px;font-weight:600;}
.status-pending {background:#fff3cd;color:#856404;}
.status-confirmed {background:#cfe2ff;color:#084298;}
.status-preparing {background:#e7f3ff;color:#055160;}
.status-on_the_way {background:#d1e7dd;color:#0f5132;}
.status-delivered {background:#d4edda;color:#155724;}
.order-details {color:#666;line-height:1.8;}
.order-actions {display:flex;gap:10px;margin-top:15px;}
.empty-state {text-align:center;padding:40px;color:#888;}
.toast {position:fixed;top:20px;right:20px;background:#333;color:white;padding:15px 25px;border-radius:8px;box-shadow:0 4px 15px rgba(0,0,0,0.2);opacity:0;pointer-events:none;transition: all 0.5s ease;}
.toast.show {opacity:1;pointer-events:auto;}
</style>
</head>
<body>
<div class="navbar">
    <h1>🚴 Delivery Partner Dashboard</h1>
    <div>
        <span style="margin-right:20px;">Welcome, <?php echo $_SESSION['name']; ?>!</span>
        <a href="../auth/logout.php" class="btn btn-white">Logout</a>
    </div>
</div>
<div class="container">

<!-- Available Orders -->
<div class="section">
    <h2>🔔 Available Orders (<?php echo count($available_orders); ?>)</h2>
    <?php if($available_orders): ?>
        <?php foreach($available_orders as $order): ?>
            <div class="order-card" id="order-<?php echo $order['id']; ?>">
                <div class="order-header">
                    <div class="order-id">Order #<?php echo $order['id']; ?></div>
                    <span class="status-badge status-pending">New Order</span>
                </div>
                <div class="order-details">
                    <p><strong>Customer:</strong> <?php echo htmlspecialchars($order['customer_name']); ?></p>
                    <p><strong>Phone:</strong> <?php echo htmlspecialchars($order['customer_phone']); ?></p>
                    <p><strong>Delivery Address:</strong> <?php echo htmlspecialchars($order['delivery_address']); ?></p>
                    <p><strong>Total Amount:</strong> $<?php echo number_format($order['total_amount'],2); ?></p>
                    <p><strong>Ordered:</strong> <?php echo date('M d, Y h:i A',strtotime($order['created_at'])); ?></p>
                </div>
                <div class="order-actions">
                    <button class="btn btn-accept action-btn" data-action="accept" data-id="<?php echo $order['id']; ?>">Accept Order</button>
                </div>
            </div>
        <?php endforeach; ?>
    <?php else: ?>
        <div class="empty-state"><p>No available orders at the moment.</p></div>
    <?php endif; ?>
</div>

<!-- Active Orders -->
<div class="section">
    <h2>📦 My Active Deliveries (<?php echo count($my_orders); ?>)</h2>
    <?php if($my_orders): ?>
        <?php foreach($my_orders as $order): ?>
            <div class="order-card" id="order-<?php echo $order['id']; ?>">
                <div class="order-header">
                    <div class="order-id">Order #<?php echo $order['id']; ?></div>
                    <span class="status-badge status-<?php echo $order['status']; ?>">
                        <?php echo ucfirst(str_replace('_',' ',$order['status'])); ?>
                    </span>
                </div>
                <div class="order-details">
                    <p><strong>Customer:</strong> <?php echo htmlspecialchars($order['customer_name']); ?></p>
                    <p><strong>Phone:</strong> <?php echo htmlspecialchars($order['customer_phone']); ?></p>
                    <p><strong>Delivery Address:</strong> <?php echo htmlspecialchars($order['delivery_address']); ?></p>
                    <p><strong>Total Amount:</strong> $<?php echo number_format($order['total_amount'],2); ?></p>
                </div>
                <div class="order-actions">
                    <?php if($order['status']!='delivered'): ?>
                        <?php
                        $nextAction = '';
                        $btnText = '';
                        $btnClass = 'btn-primary';
                        if($order['status']=='confirmed') { $nextAction='preparing'; $btnText='Mark as Preparing'; }
                        elseif($order['status']=='preparing') { $nextAction='on_the_way'; $btnText='On the Way'; }
                        elseif($order['status']=='on_the_way') { $nextAction='delivered'; $btnText='Mark as Delivered'; $btnClass='btn-success'; }
                        ?>
                        <button class="btn <?php echo $btnClass; ?> action-btn" data-action="update_status" data-id="<?php echo $order['id']; ?>" data-status="<?php echo $nextAction; ?>">
                            <?php echo $btnText; ?>
                        </button>
                    <?php endif; ?>
                    <a href="../messages/chat.php?order_id=<?php echo $order['id']; ?>" class="btn btn-primary">💬 Message Customer</a>
                </div>
            </div>
        <?php endforeach; ?>
    <?php else: ?>
        <div class="empty-state"><p>No active deliveries. Accept orders to start delivering!</p></div>
    <?php endif; ?>
</div>

<!-- Completed Orders -->
<div class="section">
    <h2>✅ Recent Completed Orders</h2>
    <?php if($completed_orders): ?>
        <?php foreach($completed_orders as $order): ?>
            <div class="order-card" id="order-<?php echo $order['id']; ?>">
                <div class="order-header">
                    <div class="order-id">Order #<?php echo $order['id']; ?></div>
                    <span class="status-badge status-<?php echo $order['status']; ?>">
                        <?php echo ucfirst($order['status']); ?>
                    </span>
                </div>
                <div class="order-details">
                    <p><strong>Customer:</strong> <?php echo htmlspecialchars($order['customer_name']); ?></p>
                    <p><strong>Amount:</strong> $<?php echo number_format($order['total_amount'],2); ?></p>
                    <p><strong>Completed:</strong> <?php echo date('M d, Y h:i A',strtotime($order['created_at'])); ?></p>
                </div>
            </div>
        <?php endforeach; ?>
    <?php else: ?>
        <div class="empty-state"><p>No completed orders yet.</p></div>
    <?php endif; ?>
</div>

</div>

<!-- Toast container -->
<div id="toast" class="toast"></div>

<script>
// AJAX function
async function sendAction(orderId, action, status='') {
    const formData = new FormData();
    formData.append('order_id', orderId);
    if(action==='accept') formData.append('accept_order','1');
    else if(action==='update_status'){ formData.append('update_status','1'); formData.append('status', status); }

    const res = await fetch('ajax_order_action.php', {
        method: 'POST',
        body: formData
    });
    const data = await res.json();

    if(data.success){
        // Update status badge
        if(data.new_status){
            const badge = document.querySelector('#order-'+orderId+' .status-badge');
            badge.textContent = data.new_status_label;
            badge.className = 'status-badge status-'+data.new_status;
        }
        // If order accepted, move it to active section
        if(action==='accept') {
            location.reload(); // Simple solution for now
        }

        showToast(data.message);
    } else {
        showToast(data.message);
    }
}

// Show toast
function showToast(msg){
    const toast = document.getElementById('toast');
    toast.textContent = msg;
    toast.classList.add('show');
    setTimeout(()=>{ toast.classList.remove('show'); },3000);
}

// Attach event listeners
document.querySelectorAll('.action-btn').forEach(btn=>{
    btn.addEventListener('click',()=>{
        const orderId = btn.getAttribute('data-id');
        const action = btn.getAttribute('data-action');
        const status = btn.getAttribute('data-status') || '';
        sendAction(orderId, action, status);
    });
});
</script>
</body>
</html>
