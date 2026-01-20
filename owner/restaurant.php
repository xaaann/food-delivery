<?php
// owner/restaurant.php - Restaurant Settings
session_start();

// Security checks
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'owner') {
    header("Location: ../auth/login.php");
    exit();
}

require_once '../config/db.php';

$success = '';
$error = '';

try {
    $db = new Database();
    $conn = $db->getConnection();

    // Get owner details
    $stmt = $conn->prepare("SELECT * FROM users WHERE id = ? AND role = 'owner'");
    $stmt->execute([$_SESSION['user_id']]);
    $owner = $stmt->fetch(PDO::FETCH_ASSOC);

    // Get restaurant
    $stmt = $conn->prepare("SELECT * FROM restaurants WHERE owner_id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $restaurant = $stmt->fetch(PDO::FETCH_ASSOC);

    // Handle form submission
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $restaurant_name = trim($_POST['restaurant_name']);
        $address = trim($_POST['address']);
        $phone = trim($_POST['phone']);
        $email = trim($_POST['email']);
        $description = trim($_POST['description']);

        if (empty($restaurant_name)) {
            $error = "Restaurant name is required!";
        } else {
            if ($restaurant) {
                // Update existing restaurant
                $stmt = $conn->prepare("
                    UPDATE restaurants 
                    SET restaurant_name = ?, address = ?, phone = ?, email = ?, description = ?, updated_at = NOW()
                    WHERE owner_id = ?
                ");
                $stmt->execute([$restaurant_name, $address, $phone, $email, $description, $_SESSION['user_id']]);
            } else {
                // Create new restaurant
                $stmt = $conn->prepare("
                    INSERT INTO restaurants (owner_id, restaurant_name, address, phone, email, description, status, created_at)
                    VALUES (?, ?, ?, ?, ?, ?, 'active', NOW())
                ");
                $stmt->execute([$_SESSION['user_id'], $restaurant_name, $address, $phone, $email, $description]);
            }
            
            $success = "Restaurant settings updated successfully!";
            
            // Refresh restaurant data
            $stmt = $conn->prepare("SELECT * FROM restaurants WHERE owner_id = ?");
            $stmt->execute([$_SESSION['user_id']]);
            $restaurant = $stmt->fetch(PDO::FETCH_ASSOC);
        }
    }

} catch (PDOException $e) {
    error_log("Database error: " . $e->getMessage());
    $error = "Unable to update restaurant settings.";
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Restaurant Settings</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #f5f7fa;
        }
        .navbar {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 15px 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .navbar h1 {
            font-size: 24px;
        }
        .navbar .nav-links {
            display: flex;
            gap: 20px;
            align-items: center;
        }
        .navbar a {
            color: white;
            text-decoration: none;
            padding: 8px 16px;
            border-radius: 5px;
            transition: background 0.3s;
        }
        .navbar a:hover {
            background: rgba(255,255,255,0.2);
        }
        .container {
            max-width: 800px;
            margin: 30px auto;
            padding: 0 20px;
        }
        .card {
            background: white;
            padding: 40px;
            border-radius: 15px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .card h2 {
            margin-bottom: 30px;
            color: #333;
        }
        .form-group {
            margin-bottom: 20px;
        }
        label {
            display: block;
            margin-bottom: 8px;
            color: #555;
            font-weight: 600;
            font-size: 14px;
        }
        input, textarea {
            width: 100%;
            padding: 12px 15px;
            border: 2px solid #e0e0e0;
            border-radius: 10px;
            font-size: 14px;
            transition: all 0.3s;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        input:focus, textarea:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }
        textarea {
            resize: vertical;
            min-height: 100px;
        }
        button {
            width: 100%;
            padding: 14px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            border-radius: 10px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            margin-top: 10px;
        }
        button:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 20px rgba(102, 126, 234, 0.4);
        }
        .error {
            background: #fee;
            color: #c33;
            padding: 15px;
            border-radius: 10px;
            margin-bottom: 20px;
            border-left: 4px solid #c33;
        }
        .success {
            background: #d4edda;
            color: #155724;
            padding: 15px;
            border-radius: 10px;
            margin-bottom: 20px;
            border-left: 4px solid #28a745;
        }
        .required {
            color: #e74c3c;
        }
    </style>
</head>
<body>
    <div class="navbar">
        <h1>🏪 Restaurant Settings</h1>
        <div class="nav-links">
            <a href="dashboard.php">← Back to Dashboard</a>
            <a href="menu.php">Menu</a>
            <a href="../auth/logout.php">Logout</a>
        </div>
    </div>

    <div class="container">
        <div class="card">
            <h2>Update Restaurant Information</h2>
            
            <?php if ($error): ?>
                <div class="error">❌ <?php echo $error; ?></div>
            <?php endif; ?>
            
            <?php if ($success): ?>
                <div class="success">✅ <?php echo $success; ?></div>
            <?php endif; ?>

            <form method="POST">
                <div class="form-group">
                    <label>Restaurant Name <span class="required">*</span></label>
                    <input type="text" name="restaurant_name" required 
                           value="<?php echo htmlspecialchars($restaurant['restaurant_name'] ?? ''); ?>">
                </div>

                <div class="form-group">
                    <label>Address</label>
                    <textarea name="address" rows="3"><?php echo htmlspecialchars($restaurant['address'] ?? ''); ?></textarea>
                </div>

                <div class="form-group">
                    <label>Phone Number</label>
                    <input type="tel" name="phone" 
                           value="<?php echo htmlspecialchars($restaurant['phone'] ?? ''); ?>"
                           placeholder="+63 912 345 6789">
                </div>

                <div class="form-group">
                    <label>Email</label>
                    <input type="email" name="email" 
                           value="<?php echo htmlspecialchars($restaurant['email'] ?? ''); ?>"
                           placeholder="restaurant@email.com">
                </div>

                <div class="form-group">
                    <label>Description</label>
                    <textarea name="description" rows="4" 
                              placeholder="Tell customers about your restaurant..."><?php echo htmlspecialchars($restaurant['description'] ?? ''); ?></textarea>
                </div>

                <button type="submit">Save Settings</button>
            </form>
        </div>
    </div>
</body>
</html>