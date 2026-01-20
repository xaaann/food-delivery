<?php
session_start();
require_once '../config/db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'customer') {
    header("Location: ../auth/login.php");
    exit();
}

$db = new Database();
$conn = $db->getConnection();

// Remove item
if (isset($_POST['remove_item'])) {
    unset($_SESSION['cart'][$_POST['food_id']]);
}

// Fetch cart items
$cart_items = [];
$total = 0;
$total_qty = 0;
if (isset($_SESSION['cart']) && count($_SESSION['cart']) > 0) {
    $ids = implode(',', array_keys($_SESSION['cart']));
    $stmt = $conn->prepare("SELECT * FROM food_items WHERE id IN ($ids)");
    $stmt->execute();
    $items = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($items as $item) {
        $quantity = $_SESSION['cart'][$item['id']];
        $subtotal = $item['price'] * $quantity;
        $total += $subtotal;
        $total_qty += $quantity;

        $cart_items[] = [
            'id' => $item['id'],
            'name' => $item['name'],
            'price' => $item['price'],
            'quantity' => $quantity,
            'subtotal' => $subtotal,
            'stock' => $item['stock'],
            'image' => $item['image'] ?? '../assets/default-food.jpg'
        ];
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>🛒 Cart</title>
<style>
body{font-family:'Segoe UI',sans-serif;background:#f4f5f7;margin:0;padding:0;color:#333;}
.navbar{background:#667eea;color:white;padding:15px 30px;display:flex;justify-content:space-between;align-items:center;box-shadow:0 2px 8px rgba(0,0,0,0.1);}
.navbar h1{font-size:22px;}
.btn-white{background:white;color:#667eea;font-weight:600;padding:8px 16px;border-radius:6px;text-decoration:none;transition:0.3s;}
.btn-white:hover{background:#e0e0e0;}

/* Container */
.container{max-width:1200px;margin:30px auto;padding:0 20px;display:flex;flex-wrap:wrap;gap:20px;}

/* Cart items */
.cart-items{flex:2;}
.cart-card{background:white;border-radius:12px;padding:20px;box-shadow:0 4px 15px rgba(0,0,0,0.08);margin-bottom:20px;transition:0.3s;}
.cart-card:hover{box-shadow:0 6px 20px rgba(0,0,0,0.12);}
.cart-item{display:flex;align-items:center;padding:15px 0;border-bottom:1px solid #eee;gap:15px;}
.cart-item:last-child{border-bottom:none;}
.item-details{flex:1;}
.item-name{font-size:18px;font-weight:600;margin-bottom:4px;}
.item-price{color:#667eea;font-weight:600;}
.item-stock{font-size:13px;color:#999;margin-top:3px;}
.item-subtotal{font-weight:600;margin-top:5px;}
.food-img{width:80px;height:80px;border-radius:8px;object-fit:cover;}

/* Quantity buttons */
.quantity-wrapper{display:flex;align-items:center;gap:5px;margin-top:8px;}
.quantity-btn{background:#667eea;color:white;border:none;padding:6px 10px;border-radius:6px;font-weight:600;cursor:pointer;transition:0.2s;}
.quantity-btn:hover{background:#5563c1;}
.quantity-input{width:50px;text-align:center;padding:5px;border:1px solid #ccc;border-radius:6px;}

/* Remove button */
.btn-remove{background:#ff4757;color:white;padding:6px 10px;border-radius:6px;cursor:pointer;transition:0.2s;}
.btn-remove:hover{background:#e84142;}

/* Summary */
.cart-summary{flex:1;min-width:280px;background:white;border-radius:12px;padding:25px;box-shadow:0 4px 15px rgba(0,0,0,0.08);height:max-content;position:sticky;top:20px;}
.summary-row{display:flex;justify-content:space-between;margin-bottom:12px;font-size:16px;}
.summary-total{font-size:22px;font-weight:bold;color:#667eea;}
.btn-checkout{width:100%;padding:15px;background:linear-gradient(135deg,#667eea 0%,#764ba2 100%);color:white;border:none;border-radius:8px;font-size:18px;font-weight:600;cursor:pointer;margin-top:15px;transition:0.3s;}
.btn-checkout:hover{opacity:0.9;}

/* Responsive */
@media(max-width:900px){.container{flex-direction:column;}.cart-summary{position:relative;top:0;}}
</style>
</head>
<body>

<div class="navbar">
<h1>🛒 Your Cart</h1>
<a href="dashboard.php" class="btn-white">← Back to Menu</a>
</div>

<div class="container">

<!-- Cart Items -->
<div class="cart-items">
<div class="cart-card">
<h2>Cart Items</h2>
<?php if (count($cart_items) > 0): ?>
<?php foreach ($cart_items as $item): ?>
<div class="cart-item" data-id="<?php echo $item['id']; ?>" data-price="<?php echo $item['price']; ?>">
<img src="<?php echo $item['image']; ?>" class="food-img" alt="Food">
<div class="item-details">
<div class="item-name"><?php echo htmlspecialchars($item['name']); ?></div>
<div class="item-price">₱<?php echo number_format($item['price'],2); ?></div>
<div class="item-stock">Stock: <?php echo $item['stock']; ?></div>
<div class="quantity-wrapper">
<button type="button" class="quantity-btn" onclick="changeQuantity(<?php echo $item['id']; ?>,-1,<?php echo $item['stock']; ?>)">-</button>
<input type="number" id="qty-<?php echo $item['id']; ?>" value="<?php echo $item['quantity']; ?>" min="1" max="<?php echo $item['stock']; ?>" class="quantity-input">
<button type="button" class="quantity-btn" onclick="changeQuantity(<?php echo $item['id']; ?>,1,<?php echo $item['stock']; ?>)">+</button>
</div>
<div class="item-subtotal" id="subtotal-<?php echo $item['id']; ?>">Subtotal: ₱<?php echo number_format($item['subtotal'],2); ?></div>
</div>
<form method="POST">
<input type="hidden" name="food_id" value="<?php echo $item['id']; ?>">
<button type="submit" name="remove_item" class="btn-remove">Remove</button>
</form>
</div>
<?php endforeach; ?>
<?php else: ?>
<p>Your cart is empty. <a href="dashboard.php">Browse Menu</a></p>
<?php endif; ?>
</div>
</div>

<!-- Summary -->
<?php if(count($cart_items) > 0): ?>
<div class="cart-summary">
<h3>Order Summary</h3>
<div class="summary-row"><span>Items:</span><span id="total-items"><?php echo $total_qty; ?></span></div>
<div class="summary-row summary-total"><span>Total:</span><span id="total-price">₱<?php echo number_format($total, 2); ?></span></div>
<form method="POST" action="payment.php" id="checkout-form">
<label for="address">Delivery Address</label>
<textarea name="delivery_address" id="address" rows="3" required placeholder="Enter your delivery address" style="width:100%;padding:10px;margin-top:5px;border-radius:6px;border:1px solid #ccc;"></textarea>
<input type="hidden" name="cart_data" id="cart_data">
<button type="submit" class="btn-checkout">Proceed to Payment</button>
</form>
</div>
<?php endif; ?>

</div>

<script>
function changeQuantity(id, delta, stock){
    let input = document.getElementById('qty-'+id);
    let value = parseInt(input.value) + delta;
    if(value < 1) value = 1;
    if(value > stock) value = stock;
    input.value = value;
    updateSessionCart(id, value);
}

function updateSessionCart(id, quantity){
    fetch('update_cart.php', {
        method:'POST',
        headers:{'Content-Type':'application/json'},
        body: JSON.stringify({food_id:id, quantity:quantity})
    })
    .then(res=>res.json())
    .then(data=>{
        if(data.status==='success'){
            document.getElementById('qty-'+id).value = data.quantity;
            updateCartTotals();
        } else {
            alert(data.message);
        }
    });
}

function updateCartTotals(){
    let totalPrice = 0, totalItems = 0;
    document.querySelectorAll('.cart-item').forEach(item=>{
        let id = item.getAttribute('data-id');
        let price = parseFloat(item.getAttribute('data-price'));
        let qty = parseInt(document.getElementById('qty-'+id).value);
        document.getElementById('subtotal-'+id).innerText = 'Subtotal: ₱' + (price*qty).toFixed(2);
        totalPrice += price*qty;
        totalItems += qty;
    });
    document.getElementById('total-price').innerText = '₱' + totalPrice.toFixed(2);
    document.getElementById('total-items').innerText = totalItems;
    document.getElementById('cart_data').value = JSON.stringify(getCartData());
}

function getCartData(){
    let data = {};
    document.querySelectorAll('.cart-item').forEach(item=>{
        let id = item.getAttribute('data-id');
        data[id] = parseInt(document.getElementById('qty-'+id).value);
    });
    return data;
}

// Attach onchange events
document.querySelectorAll('.quantity-input').forEach(input=>{
    input.addEventListener('change', function(){
        let id = this.id.replace('qty-','');
        let qty = parseInt(this.value);
        if(qty < 1) qty = 1;
        let stock = parseInt(this.getAttribute('max'));
        if(qty > stock) qty = stock;
        this.value = qty;
        updateSessionCart(id, qty);
    });
});

// Initial update
updateCartTotals();
</script>

</body>
</html>
