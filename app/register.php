<?php
require 'includes/db.php';
require 'includes/auth.php';
require 'includes/functions.php';

if (isLoggedIn()) {
    header("Location: /dashboard.php");
    exit();
}

$error = '';

function validatePassword($pw) {
    if (strlen($pw) < 12)
        return "Minimum 12 characters required.";
    if (!preg_match('/[A-Z]/', $pw))
        return "At least 1 uppercase letter required.";
    if (!preg_match('/[a-z]/', $pw))
        return "At least 1 lowercase letter required.";
    if (!preg_match('/[0-9]/', $pw))
        return "At least 1 number required.";
    if (!preg_match('/[^A-Za-z0-9]/', $pw))
        return "At least 1 special character required.";
    if (preg_match('/(.)\1{2,}/', $pw))
        return "No more than 2 repeating characters.";
    return null;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $email    = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm  = $_POST['confirm_password'] ?? '';
    $role     = $_POST['role'] ?? '';

    if (empty($username) || empty($email) ||
        empty($password) || empty($confirm) ||
        empty($role)) {
        $error = "Please fill in all fields.";
    } elseif (strlen($username) < 3 ||
              strlen($username) > 50) {
        $error = "Username: 3–50 characters.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Invalid email address.";
    } elseif (!in_array($role, ['researcher','guest'])) {
        $error = "Please select a valid role.";
    } elseif ($password !== $confirm) {
        $error = "Passwords do not match.";
    } else {
        $pwErr = validatePassword($password);
        if ($pwErr) {
            $error = $pwErr;
        } else {
            $stmt = $pdo->prepare(
                "SELECT id FROM users
                 WHERE username=? OR email=? LIMIT 1"
            );
            $stmt->execute([$username, $email]);

            if ($stmt->fetch()) {
                $error = "Username or email already taken.";
            } else {
                $hash = password_hash(
                    $password, PASSWORD_BCRYPT, ['cost'=>12]
                );
                $stmt = $pdo->prepare(
                    "INSERT INTO users
                     (username,email,password_hash,role)
                     VALUES (?,?,?,?)"
                );
                $stmt->execute([
                    $username,$email,$hash,$role
                ]);

                error_log("REGISTER: " . $username .
                          " role=" . $role .
                          " IP=" . $_SERVER['REMOTE_ADDR']);

                setFlash('success',
                    'Account created! Please sign in.');
                header("Location: /login.php");
                exit();
            }
        }
    }
}

$selRole = $_POST['role'] ?? '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport"
          content="width=device-width, initial-scale=1.0">
    <title>Register — ResearchPortal</title>
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
            <h1>Join the Research <span>Community</span></h1>
            <p>Create your account and start collaborating
               on research projects with colleagues across
               the university.</p>
            <div class="auth-features">
                <div class="auth-feature">
                    <div class="feat-icon">🔬</div>
                    Submit and manage research proposals
                </div>
                <div class="auth-feature">
                    <div class="feat-icon">🔴</div>
                    Upload confidential research securely
                </div>
                <div class="auth-feature">
                    <div class="feat-icon">🤝</div>
                    Request collaboration with other researchers
                </div>
                <div class="auth-feature">
                    <div class="feat-icon">📋</div>
                    Track proposal status in real-time
                </div>
            </div>
        </div>
    </div>

    <!-- Right panel -->
    <div class="auth-right">
        <div class="auth-box">
            <h2>Create Account</h2>
            <p class="auth-subtitle">
                Join ResearchPortal today
            </p>

            <?php if ($error): ?>
                <div class="flash flash-error">
                    ⚠️ <?= e($error) ?>
                </div>
            <?php endif; ?>

            <form method="POST" action="/register.php">

                <div class="form-group">
                    <label>Username</label>
                    <input type="text"
                           name="username"
                           value="<?= e(
                               $_POST['username'] ?? '') ?>"
                           placeholder="Choose a username"
                           required
                           minlength="3" maxlength="50">
                </div>

                <div class="form-group">
                    <label>Email Address</label>
                    <input type="email"
                           name="email"
                           value="<?= e(
                               $_POST['email'] ?? '') ?>"
                           placeholder="your@university.edu.my"
                           required>
                </div>

                <!-- Role selection -->
                <div class="form-group">
                    <label>Select Your Role</label>
                    <div class="role-select-grid">

                        <label class="role-option">
                            <input type="radio"
                                   name="role"
                                   value="researcher"
                                   <?= $selRole==='researcher'
                                   ?'checked':'' ?>
                                   required>
                            <div class="role-content">
                                <div class="role-icon">🔬</div>
                                <div class="role-name">
                                    Researcher
                                </div>
                                <div class="role-desc">
                                    Submit & manage research
                                </div>
                            </div>
                        </label>

                        <label class="role-option">
                            <input type="radio"
                                   name="role"
                                   value="guest"
                                   <?= $selRole==='guest'
                                   ?'checked':'' ?>>
                            <div class="role-content">
                                <div class="role-icon">👤</div>
                                <div class="role-name">
                                    Guest
                                </div>
                                <div class="role-desc">
                                    View announcements only
                                </div>
                            </div>
                        </label>

                    </div>
                    <div class="form-hint">
                        Admin roles are assigned by portal
                        administrators only.
                    </div>
                </div>

                <div class="form-group">
                    <label>Password</label>
                    <input type="password"
                           name="password"
                           id="pw"
                           placeholder="Min 12 chars"
                           required
                           minlength="12"
                           oninput="checkPw(this.value)">
                    <!-- Password requirements -->
                    <div class="pw-requirements">
                        <div class="req" id="req-len">
                            ○ Minimum 12 characters
                        </div>
                        <div class="req" id="req-upper">
                            ○ At least 1 uppercase (A-Z)
                        </div>
                        <div class="req" id="req-lower">
                            ○ At least 1 lowercase (a-z)
                        </div>
                        <div class="req" id="req-num">
                            ○ At least 1 number (0-9)
                        </div>
                        <div class="req" id="req-special">
                            ○ At least 1 special character
                        </div>
                    </div>
                </div>

                <div class="form-group">
                    <label>Confirm Password</label>
                    <input type="password"
                           name="confirm_password"
                           placeholder="Repeat password"
                           required>
                </div>

                <button type="submit"
                        class="btn btn-primary"
                        style="width:100%;
                               justify-content:center;
                               padding:12px;
                               margin-top:8px;">
                    Create Account →
                </button>
            </form>

            <p style="text-align:center;margin-top:24px;
                      font-size:13px;color:var(--gray);">
                Already have an account?
                <a href="/login.php"
                   style="color:var(--primary);
                          font-weight:600;">
                    Sign in here
                </a>
            </p>
        </div>
    </div>
</div>

<script>
function checkPw(val) {
    const set = (id, ok) => {
        const el = document.getElementById(id);
        el.className = 'req' + (ok ? ' met' : '');
        el.textContent = (ok ? '✓' : '○') + ' ' +
            el.textContent.replace(/^[✓○] /, '');
    };
    set('req-len',     val.length >= 12);
    set('req-upper',   /[A-Z]/.test(val));
    set('req-lower',   /[a-z]/.test(val));
    set('req-num',     /[0-9]/.test(val));
    set('req-special', /[^A-Za-z0-9]/.test(val));
}
</script>
</body>
</html>
