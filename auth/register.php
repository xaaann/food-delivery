<?php
// auth/register.php - Registration with Owner Restriction
session_start();

// If already logged in, redirect to appropriate dashboard
if (isset($_SESSION['user_id']) && isset($_SESSION['role'])) {
    switch ($_SESSION['role']) {
        case 'customer':
            header("Location: ../user/dashboard.php");
            break;
        case 'rider':
            header("Location: ../rider/dashboard.php");
            break;
        case 'owner':
            header("Location: ../owner/dashboard.php");
            break;
    }
    exit();
}

require_once '../config/db.php';

$error = '';
$success = '';
$selected_role = $_GET['role'] ?? 'customer';

// SECURITY: Block owner from URL parameter
if ($selected_role === 'owner') {
    $selected_role = 'customer';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    $phone = trim($_POST['phone']);
    $address = trim($_POST['address']);
    $role = $_POST['role'];

    // CRITICAL SECURITY CHECK: Block owner registration attempts
    if ($role === 'owner') {
        error_log("SECURITY ALERT: Attempted owner registration from IP: " . ($_SERVER['REMOTE_ADDR'] ?? 'unknown') . " Email: $email");
        $error = "Unauthorized registration attempt. Restaurant Owner accounts can only be created by system administrators.";
        
        // Log security event
        try {
            $db = new Database();
            $conn = $db->getConnection();
            $stmt = $conn->prepare("
                INSERT INTO security_events (user_id, event_type, ip_address, user_agent, details, created_at) 
                VALUES (NULL, 'unauthorized_owner_registration', ?, ?, ?, NOW())
            ");
            $stmt->execute([
                $_SERVER['REMOTE_ADDR'] ?? 'unknown',
                $_SERVER['HTTP_USER_AGENT'] ?? 'unknown',
                "Attempted owner registration with email: $email"
            ]);
        } catch (PDOException $e) {
            error_log("Error logging security event: " . $e->getMessage());
        }
        
        // Don't process the registration
        $role = ''; // Invalidate
    }

    // Validation
    if (empty($role)) {
        // Already handled above
    } elseif (empty($name) || empty($email) || empty($password)) {
        $error = "All required fields must be filled!";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Please enter a valid email address!";
    } elseif ($password !== $confirm_password) {
        $error = "Passwords do not match!";
    } elseif (strlen($password) < 6) {
        $error = "Password must be at least 6 characters!";
    } elseif (!preg_match('/[A-Z]/', $password) || !preg_match('/[0-9]/', $password)) {
        $error = "Password must contain at least one uppercase letter and one number!";
    } elseif (!in_array($role, ['customer', 'rider'])) {
        $error = "Invalid role selected. Only Customer and Delivery Partner registrations are allowed.";
    } else {
        try {
            $db = new Database();
            $conn = $db->getConnection();

            // Check if email exists
            $stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
            $stmt->execute([$email]);
            
            if ($stmt->rowCount() > 0) {
                $error = "Email already exists! Please use a different email or <a href='login.php' style='color: #667eea;'>login here</a>.";
            } else {
                // Insert new user (owner role is BLOCKED)
                $hashed_password = password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
                $status = 'active'; // Customers and riders are active immediately
                
                $stmt = $conn->prepare("
                    INSERT INTO users (name, email, password, phone, address, role, status, created_at) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, NOW())
                ");
                
                if ($stmt->execute([$name, $email, $hashed_password, $phone, $address, $role, $status])) {
                    $user_id = $conn->lastInsertId();
                    
                    $role_display = ($role === 'rider') ? 'Delivery Partner' : 'Customer';
                    $success = "Registration successful! You can now <a href='login.php?role={$role}' style='color: #667eea; font-weight: 600;'>login here</a>.";
                    
                    // Log the registration event
                    $stmt = $conn->prepare("
                        INSERT INTO security_events (user_id, event_type, ip_address, user_agent, details, created_at) 
                        VALUES (?, 'registration', ?, ?, ?, NOW())
                    ");
                    $stmt->execute([
                        $user_id,
                        $_SERVER['REMOTE_ADDR'] ?? 'unknown',
                        $_SERVER['HTTP_USER_AGENT'] ?? 'unknown',
                        "New {$role} registration"
                    ]);
                    
                    // Clear form on success
                    $_POST = array();
                } else {
                    $error = "Registration failed. Please try again.";
                }
            }
        } catch (PDOException $e) {
            error_log("Registration error: " . $e->getMessage());
            $error = "An error occurred during registration. Please try again later.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - Food Delivery System</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 20px;
        }
        .register-container {
            background: white;
            padding: 40px;
            border-radius: 20px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            width: 100%;
            max-width: 550px;
            animation: slideUp 0.5s ease-out;
        }
        @keyframes slideUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        .header {
            text-align: center;
            margin-bottom: 35px;
        }
        .logo {
            font-size: 60px;
            margin-bottom: 15px;
        }
        h2 {
            color: #333;
            margin-bottom: 10px;
            font-size: 28px;
        }
        .subtitle {
            color: #666;
            font-size: 14px;
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
        input, select, textarea {
            width: 100%;
            padding: 12px 15px;
            border: 2px solid #e0e0e0;
            border-radius: 10px;
            font-size: 14px;
            transition: all 0.3s;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        input:focus, select:focus, textarea:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }
        textarea {
            resize: vertical;
            min-height: 80px;
        }
        select {
            cursor: pointer;
            background: white;
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
        button:active {
            transform: translateY(0);
        }
        .error {
            background: #fee;
            color: #c33;
            padding: 15px;
            border-radius: 10px;
            margin-bottom: 20px;
            border-left: 4px solid #c33;
            font-size: 14px;
        }
        .success {
            background: #d4edda;
            color: #155724;
            padding: 15px;
            border-radius: 10px;
            margin-bottom: 20px;
            border-left: 4px solid #28a745;
            font-size: 14px;
        }
        .login-link {
            text-align: center;
            margin-top: 25px;
            color: #666;
            font-size: 14px;
        }
        .login-link a {
            color: #667eea;
            text-decoration: none;
            font-weight: 600;
            transition: color 0.3s;
        }
        .login-link a:hover {
            color: #764ba2;
            text-decoration: underline;
        }
        .required {
            color: #e74c3c;
        }
        .back-home {
            text-align: center;
            margin-bottom: 20px;
        }
        .back-home a {
            color: #667eea;
            text-decoration: none;
            font-size: 14px;
            display: inline-flex;
            align-items: center;
            gap: 5px;
            transition: color 0.3s;
        }
        .back-home a:hover {
            color: #764ba2;
        }
        .password-requirements {
            font-size: 12px;
            color: #666;
            margin-top: 5px;
            padding-left: 5px;
        }
        .password-requirements li {
            margin-bottom: 3px;
        }
        .info-box {
            background: #fff3cd;
            border-left: 4px solid #ffc107;
            padding: 12px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-size: 13px;
            color: #856404;
        }
        .two-column {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
        }
        @media (max-width: 600px) {
            .two-column {
                grid-template-columns: 1fr;
            }
            .register-container {
                padding: 30px 20px;
            }
        }
    </style>
</head>
<body>
    <div class="register-container">
        <div class="back-home">
            <a href="../index.php">← Back to Home</a>
        </div>

        <div class="header">
            <div class="logo">
                <?php
                switch($selected_role) {
                    case 'rider':
                        echo '🚴';
                        break;
                    default:
                        echo '🛒';
                }
                ?>
            </div>
            <h2>Create Account</h2>
            <p class="subtitle">Join our food delivery platform today!</p>
        </div>
        
        <?php if ($error): ?>
            <div class="error">❌ <?php echo $error; ?></div>
        <?php endif; ?>
        
        <?php if ($success): ?>
            <div class="success">✅ <?php echo $success; ?></div>
        <?php endif; ?>

        <div class="info-box">
            <strong>ℹ️ Restaurant Owner Registration</strong><br>
            Restaurant owner accounts are created by system administrators only. If you're a restaurant owner, please contact support.
        </div>

        <form method="POST" action="" id="registerForm">
            <div class="form-group">
                <label>Register As <span class="required">*</span></label>
                <select name="role" id="roleSelect" required>
                    <option value="customer" <?php echo ($selected_role === 'customer') ? 'selected' : ''; ?>>Customer - Order Food</option>
                    <option value="rider" <?php echo ($selected_role === 'rider') ? 'selected' : ''; ?>>Delivery Partner - Deliver Orders</option>
                </select>
            </div>

            <div class="form-group">
                <label>Full Name <span class="required">*</span></label>
                <input type="text" name="name" value="<?php echo htmlspecialchars($_POST['name'] ?? ''); ?>" required placeholder="Enter your full name">
            </div>

            <div class="form-group">
                <label>Email Address <span class="required">*</span></label>
                <input type="email" name="email" value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>" required placeholder="your.email@example.com">
            </div>

            <div class="two-column">
                <div class="form-group">
                    <label>Password <span class="required">*</span></label>
                    <input type="password" name="password" required placeholder="••••••••" minlength="6">
                </div>

                <div class="form-group">
                    <label>Confirm Password <span class="required">*</span></label>
                    <input type="password" name="confirm_password" required placeholder="••••••••" minlength="6">
                </div>
            </div>

            <div class="password-requirements">
                <ul style="list-style: none; padding: 0;">
                    <li>✓ At least 6 characters</li>
                    <li>✓ One uppercase letter</li>
                    <li>✓ One number</li>
                </ul>
            </div>

            <div class="two-column">
                <div class="form-group">
                    <label>Phone Number</label>
                    <input type="tel" name="phone" value="<?php echo htmlspecialchars($_POST['phone'] ?? ''); ?>" placeholder="+63 912 345 6789">
                </div>

                <div class="form-group">
                    <label>Address</label>
                    <input type="text" name="address" value="<?php echo htmlspecialchars($_POST['address'] ?? ''); ?>" placeholder="Your address">
                </div>
            </div>

            <button type="submit">Create Account</button>
        </form>

        <div class="login-link">
            Already have an account? <a href="login.php">Login here</a>
        </div>
    </div>

    <script>
        // Password validation
        document.getElementById('registerForm').addEventListener('submit', function(e) {
            const password = document.querySelector('input[name="password"]').value;
            const confirmPassword = document.querySelector('input[name="confirm_password"]').value;
            const role = document.querySelector('select[name="role"]').value;
            
            // SECURITY: Block owner registration on client side too
            if (role === 'owner') {
                e.preventDefault();
                alert('Restaurant Owner accounts cannot be self-registered. Please contact system administrators.');
                return false;
            }
            
            if (password !== confirmPassword) {
                e.preventDefault();
                alert('Passwords do not match!');
                return false;
            }
            
            if (password.length < 6) {
                e.preventDefault();
                alert('Password must be at least 6 characters!');
                return false;
            }
            
            if (!/[A-Z]/.test(password) || !/[0-9]/.test(password)) {
                e.preventDefault();
                alert('Password must contain at least one uppercase letter and one number!');
                return false;
            }
        });
    </script>
</body>
</html>