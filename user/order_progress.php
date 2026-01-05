<?php
include "../config/db.php";
$order_id = $_GET['order_id'];
$order = $conn->query("SELECT * FROM orders WHERE id=$order_id")->fetch_assoc();
$steps = ['pending'=>'Pending','confirmed'=>'Confirmed','on_the_way'=>'On the Way','delivered'=>'Delivered'];
?>
<div class="container">
    <h2>Order #<?= $order_id ?> Status</h2>

    <div class="progress-container">
        <?php 
        $reached=false;
        foreach($steps as $key=>$label):
            $active=false;
            if($order['status']==$key) $active=true;
            if($order['status']=='delivered'||$active) $reached=true;
            $class = ($active||$reached)?"progress-step active":"progress-step";
        ?>
        <div class="<?= $class ?>">
            <span class="progress-label"><?= $label ?></span>
        </div>
        <?php endforeach; ?>
    </div>

    <div class="card">
        <h3>Order Details</h3>
        <?php
        $items = $conn->query("SELECT oi.quantity, f.name, f.price FROM order_items oi JOIN foods f ON oi.food_id=f.id WHERE oi.order_id=$order_id");
        while($item=$items->fetch_assoc()):
        ?>
        <p><?= $item['name'] ?> x <?= $item['quantity'] ?> - ₱<?= $item['price']*$item['quantity'] ?></p>
        <?php endwhile; ?>
        <p><b>Total: ₱<?= $order['total_price'] ?></b></p>
        <p>Status: <span class="status <?= str_replace(' ','_',$order['status']) ?>"><?= $order['status'] ?></span></p>
    </div>
</div>

<script>
// Auto-refresh order progress every 5 seconds
setInterval(()=>location.reload(),5000);
</script>
