<?php
// admin/create_owner.php - Admin Panel for Creating Restaurant Owners
session_start();

// ADMIN AUTHENTICATION CHECK
// Replace this with your actual admin authentication system
$ADMIN_USERNAME = 'admin';
$ADMIN_PASSWORD = 'admin123'; // Change this to a secure password

// Simple admin login check
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    // Show login form
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['admin_login'])) {
        $username = $_POST['admin_username'] ?? '';
        $password = $_POST['admin_password'] ?? '';
        
        if ($username === $ADMIN_USERNAME && $password === $ADMIN_PASSWORD) {
            $_SESSION['admin_logged_in'] = true;
            header("Location: create_owner.php");
            exit();
        } else {
            $login_error = "Invalid admin credentials";
        }
    }
    
    // Display admin login form
    ?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Admin Login</title>
        <style>
            * { margin: 0; padding: 0; box-sizing: border-box; }
            body {
                font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
                background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                min-height: 100vh;
                display: flex;
                justify-content: center;
                align-items: center;
            }
            .login-box {
                background: white;
                padding: 40px;
                border-radius: 15px;
                box-shadow: 0 10px 40px rgba(0,0,0,0.3);
                width: 400px;
            }
            h2 { color: #333; margin-bottom: 20px; text-align: center; }
            input {
                width: 100%;
                padding: 12px;
                margin-bottom: 15px;
                border: 2px solid #e0e0e0;
                border-radius: 8px;
                font-size: 14px;
            }
            button {
                width: 100%;
                padding: 12px;
                background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                color: white;
                border: none;
                border-radius: 8px;
                font-size: 16px;
                font-weight: 600;
                cursor: pointer;
            }
            .error { background: #fee; color: #c33; padding: 10px; border-radius: 5px; margin-bottom: 15px; }
        </style>
    </head>
    <body>
        <div class="login-box">
            <h2>🔐 Admin Login</h2>
            <?php if (isset($login_error)): ?>
                <div class="error"><?php echo $login_error; ?></div>
            <?php endif; ?>
            <form method="POST">
                <input type="text" name="admin_username" placeholder="Admin Username" required>
                <input type="password" name="admin_password" placeholder="Admin Password" required>
                <button type="submit" name="admin_login">Login</button>
            </form>
        </div>
    </body>
    </html>
    <?php
    exit();
}

require_once '../config/db.php';

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_owner'])) {
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $phone = trim($_POST['phone']);
    $address = trim($_POST['address']);
    $restaurant_name = trim($_POST['restaurant_name']);
    $restaurant_address = trim($_POST['restaurant_address']);
    $restaurant_phone = trim($_POST['restaurant_phone']);
    $send_email = isset($_POST['send_email']);

    // Validation
    if (empty($name) || empty($email) || empty($password) || empty($restaurant_name)) {
        $error = "Name, email, password, and restaurant name are required!";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Invalid email format!";
    } elseif (strlen($password) < 6) {
        $error = "Password must be at least 6 characters!";
    } else {
        try {
            $db = new Database();
            $conn = $db->getConnection();

            // Check if email already exists
            $stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
            $stmt->execute([$email]);
            
            if ($stmt->rowCount() > 0) {
                $error = "Email already exists in the system!";
            } else {
                // Hash password
                $hashed_password = password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
                
                // Begin transaction
                $conn->beginTransaction();
                
                try {
                    // Insert owner user
                    $stmt = $conn->prepare("
                        INSERT INTO users (name, email, password, phone, address, role, status, created_at) 
                        VALUES (?, ?, ?, ?, ?, 'owner', 'active', NOW())
                    ");
                    $stmt->execute([$name, $email, $hashed_password, $phone, $address]);
                    $owner_id = $conn->lastInsertId();
                    
                    // Create restaurant
                    $stmt = $conn->prepare("
                        INSERT INTO restaurants (owner_id, restaurant_name, address, phone, email, status, created_at) 
                        VALUES (?, ?, ?, ?, ?, 'active', NOW())
                    ");
                    $stmt->execute([
                        $owner_id,
                        $restaurant_name,
                        $restaurant_address ?: $address,
                        $restaurant_phone ?: $phone,
                        $email
                    ]);
                    
                    // Log security event
                    $stmt = $conn->prepare("
                        INSERT INTO security_events (user_id, event_type, ip_address, user_agent, details, created_at) 
                        VALUES (?, 'owner_created_by_admin', ?, ?, ?, NOW())
                    ");
                    $stmt->execute([
                        $owner_id,
                        $_SERVER['REMOTE_ADDR'] ?? 'unknown',
                        $_SERVER['HTTP_USER_AGENT'] ?? 'unknown',
                        "Admin created owner account: $email"
                    ]);
                    
                    $conn->commit();
                    
                    // Send email notification if requested
                    if ($send_email) {
                        $subject = "Your Restaurant Owner Account - Food Delivery System";
                        $message = "
                            <html>
                            <head>
                                <style>
                                    body { font-family: Arial, sans-serif; line-height: 1.6; }
                                    .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                                    .header { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 30px; text-align: center; border-radius: 10px 10px 0 0; }
                                    .content { background: #f9f9f9; padding: 30px; border-radius: 0 0 10px 10px; }
                                    .credentials { background: white; padding: 20px; border-radius: 8px; margin: 20px 0; }
                                    .credentials strong { color: #667eea; }
                                    .footer { text-align: center; color: #888; font-size: 12px; margin-top: 20px; }
                                </style>
                            </head>
                            <body>
                                <div class='container'>
                                    <div class='header'>
                                        <h2>🏪 Welcome to Food Delivery System</h2>
                                    </div>
                                    <div class='content'>
                                        <p>Hello <strong>$name</strong>,</p>
                                        <p>Your Restaurant Owner account has been created! You can now manage your restaurant and start accepting orders.</p>
                                        
                                        <div class='credentials'>
                                            <h3>Login Credentials:</h3>
                                            <p><strong>Email:</strong> $email</p>
                                            <p><strong>Password:</strong> $password</p>
                                            <p><strong>Restaurant:</strong> $restaurant_name</p>
                                        </div>
                                        
                                        <p><strong>Important:</strong> Please change your password after your first login for security reasons.</p>
                                        <p>Login URL: <a href='".($_SERVER['HTTP_HOST'] ?? 'localhost')."/food_delivery_system/'>Click here to login</a></p>
                                    </div>
                                    <div class='footer'>
                                        <p>&copy; 2025 Food Delivery System. All rights reserved.</p>
                                    </div>
                                </div>
                            </body>
                            </html>
                        ";
                        
                        $headers = "MIME-Version: 1.0" . "\r\n";
                        $headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";
                        $headers .= "From: Food Delivery Admin <admin@fooddelivery.com>" . "\r\n";
                        
                        @mail($email, $subject, $message, $headers);
                    }
                    
                    $success = "Restaurant owner account created successfully!<br>
                                <strong>Email:</strong> $email<br>
                                <strong>Password:</strong> $password<br>
                                " . ($send_email ? "<em>Login credentials have been sent to the owner's email.</em>" : "<em>Please share these credentials with the owner manually.</em>");
                    
                    // Clear form
                    $_POST = array();
                    
                } catch (Exception $e) {
                    $conn->rollBack();
                    throw $e;
                }
            }
        } catch (PDOException $e) {
            error_log("Error creating owner: " . $e->getMessage());
            $error = "An error occurred while creating the owner account.";
        }
    }
}

// Get list of existing owners
try {
    $db = new Database();
    $conn = $db->getConnection();
    
    $stmt = $conn->prepare("
        SELECT u.id, u.name, u.email, u.phone, u.created_at, u.status,
               r.restaurant_name, r.restaurant_id
        FROM users u
        LEFT JOIN restaurants r ON u.id = r.owner_id
        WHERE u.role = 'owner'
        ORDER BY u.created_at DESC
    ");
    $stmt->execute();
    $owners = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Error fetching owners: " . $e->getMessage());
    $owners = [];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Restaurant Owner - Admin Panel</title>
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
        .logout-btn {
            background: white;
            color: #667eea;
            padding: 10px 20px;
            border-radius: 20px;
            text-decoration: none;
            font-weight: 600;
        }
        .container {
            max-width: 1400px;
            margin: 30px auto;
            padding: 0 20px;
        }
        .card {
            background: white;
            padding: 30px;
            border-radius: 15px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            margin-bottom: 30px;
        }
        .card h2 {
            margin-bottom: 20px;
            color: #333;
        }
        .form-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            margin-bottom: 20px;
        }
        .form-group {
            margin-bottom: 15px;
        }
        .form-group.full-width {
            grid-column: 1 / -1;
        }
        label {
            display: block;
            margin-bottom: 5px;
            color: #555;
            font-weight: 600;
        }
        input, textarea {
            width: 100%;
            padding: 10px;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            font-size: 14px;
        }
        input:focus, textarea:focus {
            outline: none;
            border-color: #667eea;
        }
        button {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 12px 30px;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
        }
        button:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.3);
        }
        .error {
            background: #fee;
            color: #c33;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            border-left: 4px solid #c33;
        }
        .success {
            background: #d4edda;
            color: #155724;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            border-left: 4px solid #28a745;
        }
        .checkbox-label {
            display: flex;
            align-items: center;
            gap: 10px;
            font-weight: normal;
        }
        .checkbox-label input {
            width: auto;
        }
        table {
            width: 100%;
            border-collapse: collapse;
        }
        table th, table td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #e0e0e0;
        }
        table th {
            background: #f5f7fa;
            font-weight: 600;
            color: #333;
        }
        .status-badge {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: 600;
        }
        .status-active {
            background: #d4edda;
            color: #155724;
        }
        .status-pending {
            background: #fff3cd;
            color: #856404;
        }
        @media (max-width: 768px) {
            .form-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <div class="navbar">
        <h1>🔧 Admin Panel - Create Restaurant Owner</h1>
        <a href="?logout=1" class="logout-btn">Logout</a>
    </div>

    <div class="container">
        <div class="card">
            <h2>Create New Restaurant Owner Account</h2>
            
            <?php if ($error): ?>
                <div class="error">❌ <?php echo $error; ?></div>
            <?php endif; ?>
            
            <?php if ($success): ?>
                <div class="success">✅ <?php echo $success; ?></div>
            <?php endif; ?>

            <form method="POST">
                <div class="form-grid">
                    <div class="form-group">
                        <label>Owner Full Name *</label>
                        <input type="text" name="name" required>
                    </div>

                    <div class="form-group">
                        <label>Owner Email *</label>
                        <input type="email" name="email" required>
                    </div>

                    <div class="form-group">
                        <label>Password *</label>
                        <input type="text" name="password" required minlength="6" value="<?php echo bin2hex(random_bytes(4)); ?>">
                        <small style="color: #666;">Auto-generated secure password (you can change it)</small>
                    </div>

                    <div class="form-group">
                        <label>Owner Phone</label>
                        <input type="tel" name="phone" placeholder="+63 912 345 6789">
                    </div>

                    <div class="form-group full-width">
                        <label>Owner Address</label>
                        <input type="text" name="address">
                    </div>

                    <div class="form-group">
                        <label>Restaurant Name *</label>
                        <input type="text" name="restaurant_name" required>
                    </div>

                    <div class="form-group">
                        <label>Restaurant Phone</label>
                        <input type="tel" name="restaurant_phone" placeholder="+63 912 345 6789">
                    </div>

                    <div class="form-group full-width">
                        <label>Restaurant Address</label>
                        <textarea name="restaurant_address" rows="2"></textarea>
                    </div>

                    <div class="form-group full-width">
                        <label class="checkbox-label">
                            <input type="checkbox" name="send_email" checked>
                            Send login credentials to owner's email
                        </label>
                    </div>
                </div>

                <button type="submit" name="create_owner">Create Owner Account</button>
            </form>
        </div>

        <div class="card">
            <h2>Existing Restaurant Owners (<?php echo count($owners); ?>)</h2>
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Restaurant</th>
                        <th>Phone</th>
                        <th>Status</th>
                        <th>Created</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($owners)): ?>
                        <tr>
                            <td colspan="7" style="text-align: center; color: #999;">No restaurant owners found</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($owners as $owner): ?>
                            <tr>
                                <td><?php echo $owner['id']; ?></td>
                                <td><?php echo htmlspecialchars($owner['name']); ?></td>
                                <td><?php echo htmlspecialchars($owner['email']); ?></td>
                                <td><?php echo htmlspecialchars($owner['restaurant_name'] ?? 'No restaurant'); ?></td>
                                <td><?php echo htmlspecialchars($owner['phone'] ?? '-'); ?></td>
                                <td>
                                    <span class="status-badge status-<?php echo $owner['status']; ?>">
                                        <?php echo ucfirst($owner['status']); ?>
                                    </span>
                                </td>
                                <td><?php echo date('M d, Y', strtotime($owner['created_at'])); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</body>
</html>

<?php
// Handle logout
if (isset($_GET['logout'])) {
    session_destroy();
    header("Location: create_owner.php");
    exit();
}
?>