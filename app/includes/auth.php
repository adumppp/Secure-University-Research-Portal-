<?php
if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params([
        'lifetime' => 0,
        'path'     => '/',
        'secure'   => true,
        'httponly' => true,
        'samesite' => 'Strict'
    ]);
    session_start();
}

// Session timeout — 15 minutes idle
define('SESSION_TIMEOUT', 900);

function checkSessionTimeout() {
    if (isset($_SESSION['last_activity'])) {
        if ((time() - $_SESSION['last_activity']) > SESSION_TIMEOUT) {
            session_unset();
            session_destroy();
            header("Location: /login.php?reason=timeout");
            exit();
        }
    }
    $_SESSION['last_activity'] = time();
}

function requireAuth() {
    checkSessionTimeout();
    if (!isset($_SESSION['user_id'])) {
        header("Location: /login.php");
        exit();
    }
}

function requireAdmin() {
    requireAuth();
    if ($_SESSION['user_role'] !== 'admin') {
        header("Location: /dashboard.php?error=unauthorized");
        exit();
    }
}

function requireResearcher() {
    requireAuth();
    if (!in_array($_SESSION['user_role'], ['admin', 'researcher'])) {
        header("Location: /dashboard.php?error=unauthorized");
        exit();
    }
}

function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

function currentUser() {
    return [
        'id'       => $_SESSION['user_id'] ?? null,
        'username' => $_SESSION['username'] ?? null,
        'role'     => $_SESSION['user_role'] ?? null,
    ];
}
?>
