<?php
// index.php - Landing Page
session_start();

// If already logged in, redirect to appropriate dashboard
if (isset($_SESSION['user_id']) && isset($_SESSION['role'])) {
    switch ($_SESSION['role']) {
        case 'customer':
            header("Location: user/dashboard.php");
            break;
        case 'rider':
            header("Location: rider/dashboard.php");
            break;
        case 'owner':
            header("Location: owner/dashboard.php");
            break;
    }
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Food Delivery System</title>
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
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        .landing-container {
            background: white;
            border-radius: 20px;
            padding: 60px 40px;
            max-width: 900px;
            width: 100%;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            text-align: center;
        }
        .logo {
            font-size: 80px;
            margin-bottom: 20px;
            animation: bounce 2s infinite;
        }
        @keyframes bounce {
            0%, 100% { transform: translateY(0); }
            50% { transform: translateY(-20px); }
        }
        h1 {
            font-size: 42px;
            color: #333;
            margin-bottom: 15px;
        }
        .subtitle {
            font-size: 18px;
            color: #666;
            margin-bottom: 50px;
        }
        .role-cards {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 30px;
            margin-bottom: 40px;
        }
        .role-card {
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
            padding: 40px 30px;
            border-radius: 15px;
            transition: all 0.3s;
            cursor: pointer;
            text-decoration: none;
            color: inherit;
            display: block;
        }
        .role-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 15px 40px rgba(0,0,0,0.2);
        }
        .role-card.customer {
            background: linear-gradient(135deg, #ffecd2 0%, #fcb69f 100%);
        }
        .role-card.rider {
            background: linear-gradient(135deg, #a8edea 0%, #fed6e3 100%);
        }
        .role-card.owner {
            background: linear-gradient(135deg, #d299c2 0%, #fef9d7 100%);
        }
        .role-icon {
            font-size: 60px;
            margin-bottom: 20px;
        }
        .role-title {
            font-size: 24px;
            font-weight: bold;
            margin-bottom: 10px;
            color: #333;
        }
        .role-description {
            font-size: 14px;
            color: #555;
            margin-bottom: 20px;
        }
        .btn {
            display: inline-block;
            padding: 12px 30px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            text-decoration: none;
            border-radius: 25px;
            font-weight: 600;
            transition: transform 0.2s;
            margin: 5px;
        }
        .btn:hover {
            transform: scale(1.05);
        }
        .btn-secondary {
            background: white;
            color: #667eea;
            border: 2px solid #667eea;
        }
        .features {
            background: #f9f9f9;
            padding: 30px;
            border-radius: 15px;
            margin-top: 50px;
            text-align: left;
        }
        .features h3 {
            text-align: center;
            margin-bottom: 25px;
            color: #333;
        }
        .features ul {
            list-style: none;
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 15px;
        }
        .features li {
            padding: 10px 15px;
            background: white;
            border-radius: 8px;
            border-left: 4px solid #667eea;
        }
        .features li::before {
            content: "✓";
            color: #2ecc71;
            font-weight: bold;
            margin-right: 10px;
        }
        .footer {
            margin-top: 40px;
            color: #888;
            font-size: 14px;
        }
    </style>
</head>
<body>
    <div class="landing-container">
        <div class="logo">🍕</div>
        <h1>Food Delivery System</h1>
        <p class="subtitle">Order food, manage restaurants, or deliver orders - all in one place!</p>

        <div class="role-cards">
            <div class="role-card customer">
                <div class="role-icon">🛒</div>
                <div class="role-title">Customer</div>
                <div class="role-description">
                    Browse restaurants, order delicious food, and track your delivery in real-time
                </div>
                <a href="auth/login.php" class="btn">Login</a>
                <a href="auth/register.php" class="btn btn-secondary">Sign Up</a>
            </div>

            <div class="role-card rider">
                <div class="role-icon">🚴</div>
                <div class="role-title">Delivery Partner</div>
                <div class="role-description">
                    Accept delivery orders, earn money, and provide excellent service to customers
                </div>
                <a href="auth/login.php" class="btn">Login</a>
                <a href="auth/register.php" class="btn btn-secondary">Sign Up</a>
            </div>

            <div class="role-card owner">
                <div class="role-icon">🏪</div>
                <div class="role-title">Restaurant Owner</div>
                <div class="role-description">
                    Manage your menu, track inventory, and grow your restaurant business online
                </div>
                <a href="auth/login.php" class="btn">Login</a>
                <a href="auth/register.php" class="btn btn-secondary">Sign Up</a>
            </div>
        </div>

        <div class="features">
            <h3>🌟 Platform Features</h3>
            <ul>
                <li>Easy food ordering and checkout</li>
                <li>Real-time order tracking</li>
                <li>Direct customer-rider messaging</li>
                <li>Complete restaurant menu management</li>
                <li>Inventory and stock monitoring</li>
                <li>Secure payment processing</li>
                <li>Multi-role user system</li>
                <li>Responsive modern design</li>
            </ul>
        </div>

        <div class="footer">
            <p>&copy; 2025 Food Delivery System. All rights reserved.</p>
        </div>
    </div>
</body>
</html>