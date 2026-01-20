<?php
// auth/logout.php - Secure logout with session cleanup
session_start();

// Log the logout event if user was logged in
if (isset($_SESSION['user_id'])) {
    try {
        require_once '../config/db.php';
        $db = new Database();
        $conn = $db->getConnection();
        
        // Log security event
        $stmt = $conn->prepare("
            INSERT INTO security_events (user_id, event_type, ip_address, user_agent, details, created_at) 
            VALUES (?, 'logout', ?, ?, ?, NOW())
        ");
        $stmt->execute([
            $_SESSION['user_id'],
            $_SERVER['REMOTE_ADDR'] ?? 'unknown',
            $_SERVER['HTTP_USER_AGENT'] ?? 'unknown',
            'User logged out'
        ]);
    } catch (PDOException $e) {
        error_log("Error logging logout event: " . $e->getMessage());
    }
}

// Clear all session variables
$_SESSION = array();

// Delete the session cookie
if (isset($_COOKIE[session_name()])) {
    setcookie(session_name(), '', time() - 3600, '/');
}

// Destroy the session
session_destroy();

// Redirect to home page
header("Location: ../index.php?message=logged_out");
exit();
?>