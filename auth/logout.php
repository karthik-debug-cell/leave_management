<?php
// ============================================================
// auth/logout.php — Logout Handler
// Destroys the session and redirects to login
// ============================================================
require_once dirname(__DIR__) . '/config/session.php';

// Destroy all session data
$_SESSION = [];
session_destroy();

// Also clear the session cookie
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// Redirect to login with a message
session_start();
setFlash('info', 'You have been logged out successfully.');
header("Location: /leave_management/auth/login.php");
exit;
