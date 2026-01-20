<?php
session_start();
require_once '../config/db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'owner') {
    header("Location: ../auth/login.php");
    exit();
}

$db = new Database();
$conn = $db->getConnection();

$id = $_GET['id'] ?? 0;
$stmt = $conn->prepare("SELECT * FROM food_items WHERE id=? AND owner_id=?");
$stmt->execute([$id,$_SESSION['user_id']]);
$item = $stmt->fetch(PDO::FETCH_ASSOC);

if(!$item) die("Food item not found!");

if($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = $_POST['name'] ?? '';
    $description = $_POST['description'] ?? '';
    $price = floatval($_POST['price'] ?? 0);
    $stock = intval($_POST['stock'] ?? 0);
    $available = isset($_POST['available'])?1:0;

    // Handle image upload if exists
    $image_path = $item['image'];
    if(isset($_FILES['image']) && $_FILES['image']['error']===UPLOAD_ERR_OK){
        $upload_dir = '../uploads/food/';
        if(!is_dir($upload_dir)) mkdir($upload_dir,0755,true);
        $ext = pathinfo($_FILES['image']['name'],PATHINFO_EXTENSION);
        $filename = uniqid().'.'.$ext;
        $target = $upload_dir.$filename;
        if(move_uploaded_file($_FILES['image']['tmp_name'],$target)){
            $image_path = $target;
        }
    }

    // Update database
    try{
        $stmt = $conn->prepare("UPDATE food_items SET name=?,description=?,price=?,stock=?,available=?,image=? WHERE id=? AND owner_id=?");
        $stmt->execute([$name,$description,$price,$stock,$available,$image_path,$id,$_SESSION['user_id']]);
        header("Location: dashboard.php?success=Food item updated successfully");
        exit();
    } catch(PDOException $e){
        $error = "Error updating item: ".$e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Edit Food Item</title>
<style>
body{font-family:sans-serif;background:#f5f5f5;padding:20px;}
.container{max-width:600px;margin:50px auto;background:white;padding:30px;border-radius:10px;box-shadow:0 2px 10px rgba(0,0,0,0.1);}
input,textarea{width:100%;padding:10px;margin:10px 0;border:1px solid #ddd;border-radius:5px;}
label{font-weight:bold;}
.btn{padding:10px 20px;background:#667eea;color:white;border:none;border-radius:5px;cursor:pointer;}
.food-preview{width:100px;height:100px;object-fit:cover;border-radius:5px;margin-bottom:10px;}
.success{background:#d4edda;color:#155724;padding:10px;border-radius:5px;margin-bottom:10px;}
.error{background:#f8d7da;color:#721c24;padding:10px;border-radius:5px;margin-bottom:10px;}
</style>
</head>
<body>
<div class="container">
<h2>Edit Food Item</h2>
<?php if(isset($error)): ?><div class="error"><?= $error ?></div><?php endif; ?>
<form method="POST" enctype="multipart/form-data">
<label>Name</label>
<input type="text" name="name" value="<?= htmlspecialchars($item['name']) ?>" required>

<label>Description</label>
<textarea name="description" rows="3"><?= htmlspecialchars($item['description']) ?></textarea>

<label>Price</label>
<input type="number" step="0.01" name="price" value="<?= $item['price'] ?>" required>

<label>Stock</label>
<input type="number" name="stock" value="<?= $item['stock'] ?>" required>

<label>Available</label>
<input type="checkbox" name="available" <?= $item['available']?'checked':'' ?>>

<label>Food Image (optional)</label><br>
<img src="<?= $item['image'] ?>" class="food-preview" alt="Food">
<input type="file" name="image" accept="image/*">

<button type="submit" class="btn">Update Food Item</button>
</form>
</div>
</body>
</html>
