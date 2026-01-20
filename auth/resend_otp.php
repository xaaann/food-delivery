<?php
// auth/resend_otp.php - Resend OTP to owner's email
session_start();
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit();
}

// Check if verification session exists
if (!isset($_SESSION['owner_verification'])) {
    echo json_encode(['success' => false, 'message' => 'No active verification session. Please start login again.']);
    exit();
}

$verification = $_SESSION['owner_verification'];

// Rate limiting: prevent too many resend requests
if (isset($_SESSION['last_otp_sent'])) {
    $timeSinceLastSend = time() - $_SESSION['last_otp_sent'];
    if ($timeSinceLastSend < 60) { // 60 seconds cooldown
        $waitTime = 60 - $timeSinceLastSend;
        echo json_encode([
            'success' => false, 
            'message' => "Please wait {$waitTime} second(s) before requesting a new code."
        ]);
        exit();
    }
}

// Generate new OTP
$otp = sprintf("%06d", mt_rand(0, 999999));
$otp_expiry = date('Y-m-d H:i:s', strtotime('+10 minutes'));

// Update session with new OTP
$_SESSION['owner_verification']['otp'] = $otp;
$_SESSION['owner_verification']['otp_expiry'] = $otp_expiry;
$_SESSION['owner_verification']['attempts'] = 0; // Reset attempts
$_SESSION['last_otp_sent'] = time();

// Log OTP (REMOVE IN PRODUCTION)
error_log("=== RESEND OTP ===");
error_log("Email: {$verification['email']}");
error_log("NEW OTP CODE: {$otp}");
error_log("Expires: {$otp_expiry}");
error_log("==================");

// Send OTP email
$to = $verification['email'];
$subject = "Restaurant Owner Login - New Verification Code";
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
                <h2>🔐 New Verification Code</h2>
            </div>
            <div class='content'>
                <p>Hello,</p>
                <p>You've requested a new verification code for your Restaurant Owner account. Here's your new code:</p>
                <div class='otp-code'>{$otp}</div>
                <div class='warning'>
                    <strong>⚠️ Important:</strong> This code will expire in 10 minutes.
                </div>
                <p>If you didn't request this code, please ignore this email and contact support immediately.</p>
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

$emailSent = @mail($to, $subject, $message, $headers);

// DEVELOPMENT MODE: Return OTP in response
echo json_encode([
    'success' => true, 
    'message' => 'A new verification code has been sent to your email.',
    'dev_mode' => true,
    'dev_otp' => $otp, // SHOWS NEW OTP - REMOVE IN PRODUCTION!
    'dev_message' => "Development Mode: New OTP is {$otp}"
]);
?>