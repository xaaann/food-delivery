<?php
// owner/edit_food.php
require_once '../config/db.php';

if (!isLoggedIn() || !checkRole('owner')) {
    redirect('../auth/login.php');
}

$db = new Database();
$conn = $db->getConnection();

$error = '';
$success = '';
$food_id = $_GET['id'] ?? 0;

// Get food item
$stmt = $conn->prepare("SELECT * FROM food_items WHERE id = ? AND owner_id = ?");
$stmt->execute([$food_id, $_SESSION['user_id']]);
$food = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$food) {
    redirect('dashboard.php');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name']);
    $description = trim($_POST['description']);
    $price = floatval($_POST['price']);
    $stock = intval($_POST['stock']);
    $available = isset($_POST['available']) ? 1 : 0;

    if (empty($name) || $price <= 0 || $stock < 0) {
        $error = "Please fill all required fields with valid values!";
    } else {
        $stmt = $conn->prepare("UPDATE food_items SET name = ?, description = ?, price = ?, stock = ?, available = ? 
                               WHERE id = ? AND owner_id = ?");
        
        if ($stmt->execute([$name, $description, $price, $stock, $available, $food_id, $_SESSION['user_id']])) {
            $success = "Food item updated successfully!";
            // Refresh data
            $stmt = $conn->prepare("SELECT * FROM food_items WHERE id = ? AND owner_id = ?");
            $stmt->execute([$food_id, $_SESSION['user_id']]);
            $food = $stmt->fetch(PDO::FETCH_ASSOC);
        } else {
            $error = "Failed to update food item. Please try again.";
        }
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
        .btn-back {
            background: white;
            color: #667eea;
            padding: 8px 16px;
            border-radius: 5px;
            text-decoration: none;
            font-weight: 600;
        }
        .container {
            max-width: 700px;
            margin: 30px auto;
            padding: 0 20px;
        }
        .form-card {
            background: white;
            border-radius: 10px;
            padding: 40px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        h2 {
            margin-bottom: 30px;
            color: #333;
        }
        .form-group {
            margin-bottom: 25px;
        }
        label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #555;
        }
        input, textarea {
            width: 100%;
            padding: 12px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 14px;
        }
        textarea {
            resize: vertical;
            min-height: 100px;
        }
        .checkbox-group {
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .checkbox-group input {
            width: auto;
        }
        .btn-submit {
            width: 100%;
            padding: 15px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            border-radius: 5px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
        }
        .error {
            background: #fee;
            color: #c33;
            padding: 12px;
            border-radius: 5px;
            margin-bottom: 20px;
        }
        .success {
            background: #efe;
            color: #3c3;
            padding: 12px;
            border-radius: 5px;
            margin-bottom: 20px;
        }
        .required {
            color: red;
        }
    </style>
</head>
<body>
    <div class="navbar">
        <h1>🏪 Edit Food Item</h1>
        <a href="dashboard.php" class="btn-back">← Back to Dashboard</a>
    </div>

    <div class="container">
        <div class="form-card">
            <h2>Edit Food Item</h2>

            <?php if ($error): ?>
                <div class="error"><?php echo $error; ?></div>
            <?php endif; ?>

            <?php if ($success): ?>
                <div class="success"><?php echo $success; ?></div>
            <?php endif; ?>

            <form method="POST">
                <div class="form-group">
                    <label>Food Name <span class="required">*</span></label>
                    <input type="text" name="name" required value="<?php echo htmlspecialchars($food['name']); ?>">
                </div>

                <div class="form-group">
                    <label>Description</label>
                    <textarea name="description"><?php echo htmlspecialchars($food['description']); ?></textarea>
                </div>

                <div class="form-group">
                    <label>Price ($) <span class="required">*</span></label>
                    <input type="number" name="price" step="0.01" min="0" required value="<?php echo $food['price']; ?>">
                </div>

                <div class="form-group">
                    <label>Stock Quantity <span class="required">*</span></label>
                    <input type="number" name="stock" min="0" required value="<?php echo $food['stock']; ?>">
                </div>

                <div class="form-group">
                    <div class="checkbox-group">
                        <input type="checkbox" name="available" id="available" <?php echo $food['available'] ? 'checked' : ''; ?>>
                        <label for="available" style="margin-bottom: 0;">Available for ordering</label>
                    </div>
                </div>

                <button type="submit" class="btn-submit">Update Food Item</button>
            </form>
        </div>
    </div>
</body>
</html>