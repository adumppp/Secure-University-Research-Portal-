<?php
require 'includes/auth.php';

// Log the logout
if (isset($_SESSION['username'])) {
    error_log("LOGOUT: " . $_SESSION['username'] .
              " | IP: " . $_SERVER['REMOTE_ADDR']);
}

// Destroy session completely
session_unset();
session_destroy();

// Clear session cookie
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

header("Location: /login.php");
exit();
?>
