<?php
// index.php - Landing Page with Enhanced Security
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
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: space-between;
            min-height: 380px;
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
        .role-content {
            flex: 1;
            display: flex;
            flex-direction: column;
            align-items: center;
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
            line-height: 1.5;
        }
        .role-buttons {
            display: flex;
            flex-direction: column;
            gap: 10px;
            width: 100%;
            max-width: 200px;
        }
        .btn {
            display: block;
            padding: 12px 30px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            text-decoration: none;
            border-radius: 25px;
            font-weight: 600;
            transition: transform 0.2s;
            text-align: center;
            border: none;
            cursor: pointer;
            font-size: 15px;
        }
        .btn:hover {
            transform: scale(1.05);
        }
        .btn-secondary {
            background: white;
            color: #667eea;
            border: 2px solid #667eea;
        }
        .btn-secondary:hover {
            background: #f0f0f0;
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
        
        /* Modal Styles */
        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0,0,0,0.5);
            animation: fadeIn 0.3s;
        }
        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }
        .modal-content {
            background-color: white;
            margin: 5% auto;
            padding: 40px;
            border-radius: 15px;
            width: 90%;
            max-width: 500px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.3);
            animation: slideDown 0.3s;
        }
        @keyframes slideDown {
            from { transform: translateY(-50px); opacity: 0; }
            to { transform: translateY(0); opacity: 1; }
        }
        .close {
            color: #aaa;
            float: right;
            font-size: 28px;
            font-weight: bold;
            cursor: pointer;
            line-height: 20px;
        }
        .close:hover {
            color: #000;
        }
        .modal h2 {
            color: #333;
            margin-bottom: 20px;
            text-align: center;
        }
        .form-group {
            margin-bottom: 20px;
        }
        .form-group label {
            display: block;
            margin-bottom: 8px;
            color: #555;
            font-weight: 600;
        }
        .form-group input {
            width: 100%;
            padding: 12px;
            border: 2px solid #ddd;
            border-radius: 8px;
            font-size: 15px;
            transition: border-color 0.3s;
        }
        .form-group input:focus {
            outline: none;
            border-color: #667eea;
        }
        .verification-info {
            background: #f0f7ff;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            border-left: 4px solid #667eea;
        }
        .verification-info p {
            color: #555;
            font-size: 14px;
            line-height: 1.5;
        }
        .alert {
            padding: 12px;
            border-radius: 8px;
            margin-bottom: 15px;
            font-size: 14px;
        }
        .alert-success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        .alert-error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        .btn-full {
            width: 100%;
            margin-top: 10px;
        }
        .otp-section {
            display: none;
        }
        .resend-otp {
            text-align: center;
            margin-top: 15px;
        }
        .resend-otp a {
            color: #667eea;
            text-decoration: none;
            font-size: 14px;
        }
        .resend-otp a:hover {
            text-decoration: underline;
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
                <div class="role-content">
                    <div class="role-icon">🛒</div>
                    <div class="role-title">Customer</div>
                    <div class="role-description">
                        Browse restaurants, order delicious food, and track your delivery in real-time
                    </div>
                </div>
                <div class="role-buttons">
                    <a href="auth/login.php?role=customer" class="btn">Login</a>
                    <a href="auth/register.php?role=customer" class="btn btn-secondary">Sign Up</a>
                </div>
            </div>

            <div class="role-card rider">
                <div class="role-content">
                    <div class="role-icon">🚴</div>
                    <div class="role-title">Delivery Partner</div>
                    <div class="role-description">
                        Accept delivery orders, earn money, and provide excellent service to customers
                    </div>
                </div>
                <div class="role-buttons">
                    <a href="auth/login.php?role=rider" class="btn">Login</a>
                    <a href="auth/register.php?role=rider" class="btn btn-secondary">Sign Up</a>
                </div>
            </div>

            <div class="role-card owner">
                <div class="role-content">
                    <div class="role-icon">🏪</div>
                    <div class="role-title">Restaurant Owner</div>
                    <div class="role-description">
                        Manage your menu, track inventory, and grow your restaurant business online
                    </div>
                </div>
                <div class="role-buttons">
                    <button onclick="openOwnerVerification()" class="btn">Login</button>
                    <small style="color: #666; font-size: 12px; margin-top: 10px; display: block; text-align: center;">
                        Owner accounts are created by admins
                    </small>
                </div>
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

    <!-- Owner Verification Modal -->
    <div id="ownerModal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="closeOwnerModal()">&times;</span>
            <h2>🔐 Owner Verification</h2>
            
            <div id="alertBox"></div>
            
            <!-- Step 1: Email/Password -->
            <div id="loginSection">
                <div class="verification-info">
                    <p><strong>🛡️ Enhanced Security</strong></p>
                    <p>Restaurant owner accounts require two-factor authentication. After entering your credentials, you'll receive a one-time password (OTP) via email.</p>
                </div>
                
                <form id="ownerLoginForm" onsubmit="submitOwnerLogin(event)">
                    <div class="form-group">
                        <label for="owner_email">Email Address</label>
                        <input type="email" id="owner_email" name="email" required placeholder="owner@restaurant.com">
                    </div>
                    
                    <div class="form-group">
                        <label for="owner_password">Password</label>
                        <input type="password" id="owner_password" name="password" required placeholder="Enter your password">
                    </div>
                    
                    <button type="submit" class="btn btn-full">Continue to Verification</button>
                </form>
            </div>
            
            <!-- Step 2: OTP Verification -->
            <div id="otpSection" class="otp-section">
                <div class="verification-info">
                    <p><strong>📧 OTP Sent!</strong></p>
                    <p>We've sent a 6-digit verification code to your registered email address. Please enter it below.</p>
                </div>
                
                <form id="otpVerifyForm" onsubmit="submitOTP(event)">
                    <div class="form-group">
                        <label for="otp_code">Verification Code</label>
                        <input type="text" id="otp_code" name="otp" required placeholder="Enter 6-digit code" maxlength="6" pattern="[0-9]{6}">
                    </div>
                    
                    <button type="submit" class="btn btn-full">Verify & Login</button>
                </form>
                
                <div class="resend-otp">
                    <a href="#" onclick="resendOTP(); return false;">Didn't receive code? Resend OTP</a>
                </div>
            </div>
        </div>
    </div>

    <script>
        function openOwnerVerification() {
            document.getElementById('ownerModal').style.display = 'block';
        }

        function closeOwnerModal() {
            document.getElementById('ownerModal').style.display = 'none';
            document.getElementById('loginSection').style.display = 'block';
            document.getElementById('otpSection').style.display = 'none';
            document.getElementById('ownerLoginForm').reset();
            document.getElementById('otpVerifyForm').reset();
            document.getElementById('alertBox').innerHTML = '';
        }

        function showAlert(message, type) {
            const alertBox = document.getElementById('alertBox');
            alertBox.innerHTML = `<div class="alert alert-${type}">${message}</div>`;
        }

        function submitOwnerLogin(event) {
            event.preventDefault();
            
            const formData = new FormData(event.target);
            
            fetch('auth/owner_verify.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Show success message
                    let message = data.message;
                    
                    // DEVELOPMENT MODE: Show OTP on screen
                    if (data.dev_mode && data.dev_otp) {
                        message += '<br><br><div style="background: #fff3cd; padding: 15px; border-radius: 8px; margin-top: 10px; border-left: 4px solid #ffc107;">';
                        message += '<strong>🔧 DEVELOPMENT MODE</strong><br>';
                        message += '<span style="font-size: 24px; font-weight: bold; color: #667eea; letter-spacing: 3px;">' + data.dev_otp + '</span>';
                        message += '<br><small style="color: #856404;">This OTP display is for development only. Remove in production!</small>';
                        message += '</div>';
                    }
                    
                    showAlert(message, 'success');
                    document.getElementById('loginSection').style.display = 'none';
                    document.getElementById('otpSection').style.display = 'block';
                } else {
                    showAlert(data.message, 'error');
                }
            })
            .catch(error => {
                showAlert('An error occurred. Please try again.', 'error');
            });
        }

        function submitOTP(event) {
            event.preventDefault();
            
            const formData = new FormData(event.target);
            
            fetch('auth/verify_otp.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showAlert('Verification successful! Redirecting...', 'success');
                    setTimeout(() => {
                        window.location.href = 'index.php';
                    }, 1500);
                } else {
                    showAlert(data.message, 'error');
                }
            })
            .catch(error => {
                showAlert('An error occurred. Please try again.', 'error');
            });
        }

        function resendOTP() {
            fetch('auth/resend_otp.php', {
                method: 'POST'
            })
            .then(response => response.json())
            .then(data => {
                showAlert(data.message, data.success ? 'success' : 'error');
            });
        }

        // Close modal when clicking outside
        window.onclick = function(event) {
            const modal = document.getElementById('ownerModal');
            if (event.target == modal) {
                closeOwnerModal();
            }
        }
    </script>
</body>
</html>