<?php
// auth/owner_verify.php - Step 1: Verify owner credentials and send OTP
session_start();
header('Content-Type: application/json');

require_once '../config/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit();
}

$email = filter_var($_POST['email'] ?? '', FILTER_SANITIZE_EMAIL);
$password = $_POST['password'] ?? '';

// Validate input
if (empty($email) || empty($password)) {
    echo json_encode(['success' => false, 'message' => 'Email and password are required']);
    exit();
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode(['success' => false, 'message' => 'Invalid email format']);
    exit();
}

try {
    $db = new Database();
    $conn = $db->getConnection();

    // Check if user exists and is a restaurant owner
    $stmt = $conn->prepare("
        SELECT id, email, password, role, status 
        FROM users 
        WHERE email = ? AND role = 'owner'
    ");
    $stmt->execute([$email]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        echo json_encode(['success' => false, 'message' => 'Invalid credentials or not a restaurant owner']);
        exit();
    }

    // Verify password
    if (!password_verify($password, $user['password'])) {
        echo json_encode(['success' => false, 'message' => 'Invalid credentials']);
        exit();
    }

    // Check if account is active
    if ($user['status'] !== 'active') {
        echo json_encode(['success' => false, 'message' => 'Your account is not active. Please contact support.']);
        exit();
    }

    // Generate 6-digit OTP
    $otp = sprintf("%06d", mt_rand(0, 999999));
    $otp_expiry = date('Y-m-d H:i:s', strtotime('+10 minutes'));

    // Store OTP in session
    $_SESSION['owner_verification'] = [
        'user_id' => $user['id'],
        'email' => $user['email'],
        'otp' => $otp,
        'otp_expiry' => $otp_expiry,
        'attempts' => 0
    ];

    // In production, send OTP via email
    // For development, we'll log it
    error_log("=== OWNER LOGIN OTP ===");
    error_log("Email: {$email}");
    error_log("OTP CODE: {$otp}");
    error_log("Expires: {$otp_expiry}");
    error_log("=======================");

    // Send OTP email
    $to = $user['email'];
    $subject = "Restaurant Owner Login - Verification Code";
    $message = "
        <html>
        <head>
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; }
                .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                .header { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 30px; text-align: center; border-radius: 10px 10px 0 0; }
                .content { background: #f9f9f9; padding: 30px; border-radius: 0 0 10px 10px; }
                .otp-code { font-size: 36px; font-weight: bold; color: #667eea; text-align: center; letter-spacing: 8px; margin: 25px 0; padding: 20px; background: white; border-radius: 10px; }
                .footer { text-align: center; color: #888; font-size: 12px; margin-top: 20px; padding-top: 20px; border-top: 1px solid #ddd; }
                .warning { background: #fff3cd; border-left: 4px solid #ffc107; padding: 15px; margin: 20px 0; border-radius: 5px; color: #856404; }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='header'>
                    <h2>🔐 Owner Verification Required</h2>
                </div>
                <div class='content'>
                    <p>Hello,</p>
                    <p>You've requested to login to your Restaurant Owner account. Please use the following verification code:</p>
                    <div class='otp-code'>{$otp}</div>
                    <div class='warning'>
                        <strong>⚠️ Important:</strong> This code will expire in 10 minutes.
                    </div>
                    <p>If you didn't request this code, please ignore this email and ensure your account is secure.</p>
                    <p>For security reasons, never share this code with anyone.</p>
                </div>
                <div class='footer'>
                    <p>&copy; 2025 Food Delivery System. All rights reserved.</p>
                    <p>This is an automated message, please do not reply.</p>
                </div>
            </div>
        </body>
        </html>
    ";

    $headers = "MIME-Version: 1.0" . "\r\n";
    $headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";
    $headers .= "From: Food Delivery System <noreply@fooddelivery.com>" . "\r\n";

    // Attempt to send email
    $emailSent = @mail($to, $subject, $message, $headers);

    // DEVELOPMENT MODE: Return OTP in response (REMOVE IN PRODUCTION!)
    echo json_encode([
        'success' => true, 
        'message' => 'Verification code sent to your email. Please check your inbox.',
        'dev_mode' => true,
        'dev_otp' => $otp, // SHOWS OTP ON SCREEN - REMOVE IN PRODUCTION!
        'dev_message' => "Development Mode: Your OTP is {$otp}"
    ]);

} catch (PDOException $e) {
    error_log("Database error in owner_verify.php: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'An error occurred. Please try again later.']);
}
?>