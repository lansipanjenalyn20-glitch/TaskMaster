<?php
// Start a session only if one isn't already active
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Security check: ensure user is authenticated before handling any task mutations or queries
if (!isset($_SESSION['user_id'])) {
    header('Location: ../../index.php');
    exit;
}

// Unset all session variables
$_SESSION = array();

// If it's desired to kill the session, also delete the session cookie.
// Note: This will destroy the session, and not just the session data!
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// Finally, destroy the session.
session_destroy();

// Redirect to the login page (index.php)
header('Location: ../../index.php');
exit();
?>