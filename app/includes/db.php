<?php
// ═════════════════════════════════════════════════════════════
// Secure Research Portal — Database connection (PDO)
//
// SECURITY NOTE: in production, never hardcode credentials in source.
// Load them from environment variables or a secrets manager:
//     define('DB_PASS', getenv('DB_PASS'));
// or via AWS Secrets Manager / systemd EnvironmentFile at runtime.
//
// For local PoC, set DB_PASS in /etc/environment or a .env file that
// is excluded from version control (see repository .gitignore).
// ═════════════════════════════════════════════════════════════

define('DB_HOST', '127.0.0.1');          // localhost only — never remote
define('DB_NAME', 'researchportal');
define('DB_USER', 'portaluser');         // least-privilege — never root
define('DB_PASS', getenv('DB_PASS') ?: '<strong-password>');

try {
    $pdo = new PDO(
        "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8",
        DB_USER,
        DB_PASS
    );
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    // Real prepared statements — emulation disabled so SQL injection
    // cannot bypass the query structure. User input is bound, never
    // concatenated.
    $pdo->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
} catch (PDOException $e) {
    // Never expose DB errors in production — log server-side, die generic
    error_log("DB Connection failed: " . $e->getMessage());
    die("Service temporarily unavailable. Please try again later.");
}
?>
