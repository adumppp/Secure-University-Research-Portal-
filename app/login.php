<?php
require 'includes/db.php';
require 'includes/auth.php';
require 'includes/functions.php';

if (isLoggedIn()) {
    header("Location: /dashboard.php");
    exit();
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($username) || empty($password)) {
        $error = "Please fill in all fields.";
    } else {
        $stmt = $pdo->prepare(
            "SELECT id, username, password_hash, role
             FROM users WHERE username = ? LIMIT 1"
        );
        $stmt->execute([$username]);
        $user = $stmt->fetch();

        if ($user && password_verify(
            $password, $user['password_hash'])) {
            session_regenerate_id(true);
            $_SESSION['user_id']       = $user['id'];
            $_SESSION['username']      = $user['username'];
            $_SESSION['user_role']     = $user['role'];
            $_SESSION['last_activity'] = time();

            error_log("LOGIN: " . $user['username'] .
                      " | role=" . $user['role'] .
                      " | IP=" . $_SERVER['REMOTE_ADDR']);

            header("Location: /dashboard.php");
            exit();
        } else {
            error_log("LOGIN FAIL: " . $username .
                      " | IP=" . $_SERVER['REMOTE_ADDR']);
            $error = "Invalid username or password.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport"
          content="width=device-width, initial-scale=1.0">
    <title>Sign In — ResearchPortal</title>
    <link rel="stylesheet" href="/assets/style.css">
</head>
<body>
<div class="auth-wrapper">

    <!-- Left panel -->
    <div class="auth-left">
        <div class="auth-left-content">
            <div class="logo">
                <div class="logo-icon">🔬</div>
                <span class="logo-text">ResearchPortal</span>
            </div>
            <h1>Secure Research <span>Collaboration</span></h1>
            <p>Access your research workspace with
               enterprise-grade security controls and
               data isolation.</p>
            <div class="auth-features">
                <div class="auth-feature">
                    <div class="feat-icon">🔐</div>
                    SSH key-only server access
                </div>
                <div class="auth-feature">
                    <div class="feat-icon">🛡️</div>
                    TLS 1.3 end-to-end encryption
                </div>
                <div class="auth-feature">
                    <div class="feat-icon">👁️</div>
                    Wazuh SIEM + Fail2ban monitoring
                </div>
                <div class="auth-feature">
                    <div class="feat-icon">💾</div>
                    Automated daily backups to VM2
                </div>
            </div>
        </div>
    </div>

    <!-- Right panel — login form -->
    <div class="auth-right">
        <div class="auth-box">
            <h2>Sign In</h2>
            <p class="auth-subtitle">
                Welcome back to ResearchPortal
            </p>

            <?php showFlash(); ?>

            <?php if ($_GET['reason'] ?? '' === 'timeout'): ?>
                <div class="flash flash-warning">
                    ⏱️ Session expired. Please sign in again.
                </div>
            <?php endif; ?>

            <?php if ($error): ?>
                <div class="flash flash-error">
                    ⚠️ <?= e($error) ?>
                </div>
            <?php endif; ?>

            <form method="POST" action="/login.php">
                <div class="form-group">
                    <label>Username</label>
                    <input type="text"
                           name="username"
                           value="<?= e(
                               $_POST['username'] ?? '') ?>"
                           placeholder="Enter your username"
                           required
                           autocomplete="username">
                </div>

                <div class="form-group">
                    <label>Password</label>
                    <input type="password"
                           name="password"
                           placeholder="Enter your password"
                           required
                           autocomplete="current-password">
                </div>

                <button type="submit"
                        class="btn btn-primary"
                        style="width:100%;
                               justify-content:center;
                               padding:12px;
                               margin-top:8px;">
                    Sign In →
                </button>
            </form>

            <p style="text-align:center;margin-top:24px;
                      font-size:13px;color:var(--gray);">
                Don't have an account?
                <a href="/register.php"
                   style="color:var(--primary);
                          font-weight:600;">
                    Register here
                </a>
            </p>
        </div>
    </div>
</div>
</body>
</html>
