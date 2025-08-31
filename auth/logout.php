<?php
require_once '../config/config.php';

// Log the logout action
if (isset($_SESSION['user_id'])) {
    try {
        $pdo = getDBConnection();
        $log_stmt = $pdo->prepare("INSERT INTO system_logs (user_id, action, description, ip_address, user_agent) VALUES (?, 'logout', 'User logged out', ?, ?)");
        $log_stmt->execute([$_SESSION['user_id'], $_SERVER['REMOTE_ADDR'], $_SERVER['HTTP_USER_AGENT']]);
    } catch (PDOException $e) {
        // Continue with logout even if logging fails
    }
}

// Destroy session
session_destroy();

// Clear all session variables
$_SESSION = array();

// Delete session cookie
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// Redirect to login page
redirect('../auth/login.php');
?> 