<?php
// auth/verify_otp.php - Step 2: Verify OTP and complete login
session_start();
header('Content-Type: application/json');

require_once '../config/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit();
}

$otp = trim($_POST['otp'] ?? '');

// Validate input
if (empty($otp) || !preg_match('/^[0-9]{6}$/', $otp)) {
    echo json_encode(['success' => false, 'message' => 'Please enter a valid 6-digit code']);
    exit();
}

// Check if verification session exists
if (!isset($_SESSION['owner_verification'])) {
    echo json_encode(['success' => false, 'message' => 'Verification session expired. Please start again.']);
    exit();
}

$verification = $_SESSION['owner_verification'];

// Check if OTP has expired
if (strtotime($verification['otp_expiry']) < time()) {
    unset($_SESSION['owner_verification']);
    echo json_encode(['success' => false, 'message' => 'Verification code has expired. Please request a new one.']);
    exit();
}

// Check max attempts (prevent brute force)
if ($verification['attempts'] >= 5) {
    unset($_SESSION['owner_verification']);
    echo json_encode(['success' => false, 'message' => 'Too many failed attempts. Please start the login process again.']);
    exit();
}

// Verify OTP
if ($otp !== $verification['otp']) {
    $_SESSION['owner_verification']['attempts']++;
    $remaining = 5 - $_SESSION['owner_verification']['attempts'];
    echo json_encode([
        'success' => false, 
        'message' => "Invalid verification code. {$remaining} attempt(s) remaining."
    ]);
    exit();
}

try {
    $db = new Database();
    $conn = $db->getConnection();

    // Get full user details with restaurant info
    $stmt = $conn->prepare("
        SELECT u.id, u.name, u.email, u.role, u.phone, u.address,
               r.restaurant_name, r.restaurant_id, r.status as restaurant_status
        FROM users u
        LEFT JOIN restaurants r ON u.id = r.owner_id
        WHERE u.id = ? AND u.role = 'owner'
    ");
    $stmt->execute([$verification['user_id']]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        echo json_encode(['success' => false, 'message' => 'User not found']);
        exit();
    }

    // Set session variables
    $_SESSION['user_id'] = $user['id'];
    $_SESSION['role'] = 'owner';
    $_SESSION['email'] = $user['email'];
    $_SESSION['name'] = $user['name'];
    $_SESSION['restaurant_id'] = $user['restaurant_id'] ?? null;
    $_SESSION['restaurant_name'] = $user['restaurant_name'] ?? null;
    $_SESSION['verified_at'] = time();

    // Clear verification session
    unset($_SESSION['owner_verification']);

    echo json_encode([
        'success' => true, 
        'message' => 'Login successful! Redirecting to dashboard...',
        'redirect' => '../owner/dashboard.php'
    ]);

} catch (PDOException $e) {
    error_log("Database error in verify_otp.php: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'An error occurred. Please try again.']);
}
?>