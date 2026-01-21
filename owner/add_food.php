<?php
session_start();
require_once '../config/db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'owner') {
    header("Location: ../auth/login.php");
    exit();
}

$db = new Database();
$conn = $db->getConnection();
$success = $error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = $_POST['name'] ?? '';
    $description = $_POST['description'] ?? '';
    $price = floatval($_POST['price'] ?? 0);
    $stock = intval($_POST['stock'] ?? 0);
    $available = isset($_POST['available']);

    // Handle image upload
    $image_path = '../assets/default-food.jpg'; // general default image
    if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
        $upload_dir = '../uploads/food/';
        if (!is_dir($upload_dir)) mkdir($upload_dir, 0755, true);

        $ext = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
        $filename = uniqid() . '.' . $ext;
        $target = $upload_dir . $filename;

        if (move_uploaded_file($_FILES['image']['tmp_name'], $target)) {
            $image_path = $target;
        }
    }

    // Insert into database
    try {
        $stmt = $conn->prepare("INSERT INTO food_items (owner_id, name, description, price, stock, available, image, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, NOW())");
        $stmt->execute([$_SESSION['user_id'], $name, $description, $price, $stock, $available, $image_path]);
        header("Location: dashboard.php?success=Food item added successfully!");
        exit();
    } catch (PDOException $e) {
        $error = "Error adding item: " . $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Add New Food Item</title>
<style>
body{font-family:sans-serif;background:#f5f5f5;padding:20px;}
.container{max-width:600px;margin:50px auto;background:white;padding:30px;border-radius:10px;box-shadow:0 2px 10px rgba(0,0,0,0.1);}
input,textarea{width:100%;padding:10px;margin:10px 0;border:1px solid #ddd;border-radius:5px;}
label{font-weight:bold;}
.btn{padding:10px 20px;background:#667eea;color:white;border:none;border-radius:5px;cursor:pointer;}
.success{background:#d4edda;color:#155724;padding:10px;border-radius:5px;margin-bottom:10px;}
.error{background:#f8d7da;color:#721c24;padding:10px;border-radius:5px;margin-bottom:10px;}
.food-preview{width:100px;height:100px;object-fit:cover;border-radius:5px;margin-bottom:10px;}
</style>
</head>
<body>
<div class="container">
<h2>Add New Food Item</h2>

<?php if($success): ?><div class="success"><?= $success ?></div><?php endif; ?>
<?php if($error): ?><div class="error"><?= $error ?></div><?php endif; ?>

<form method="POST" enctype="multipart/form-data">
<label>Name</label>
<input type="text" name="name" required>

<label>Description</label>
<textarea name="description" rows="3"></textarea>

<label>Price</label>
<input type="number" step="0.01" name="price" required>

<label>Stock</label>
<input type="number" name="stock" required>

<label>Available</label>
<input type="checkbox" name="available" checked>

<label>Food Image (optional)</label><br>
<img src="../assets/default-food.jpg" class="food-preview" alt="Food">
<input type="file" name="image" accept="image/*">

<button type="submit" class="btn">Add Food Item</button>
</form>
</div>
</body>
</html>
