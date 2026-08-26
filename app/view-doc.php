<?php
require 'includes/db.php';
require 'includes/auth.php';

// Must be logged in
requireAuth();
$user = currentUser();

$id = (int)($_GET['id'] ?? 0);
if (!$id) { http_response_code(404); exit(); }

// Get proposal with access check
if ($user['role'] === 'admin') {
    $stmt = $pdo->prepare(
        "SELECT * FROM proposals WHERE id = ?"
    );
    $stmt->execute([$id]);
} else {
    $stmt = $pdo->prepare(
        "SELECT * FROM proposals WHERE id = ?
         AND (classification = 'public'
         OR (classification = 'confidential'
             AND researcher_id = ?))"
    );
    $stmt->execute([$id, $user['id']]);
}
$proposal = $stmt->fetch();

if (!$proposal || empty($proposal['file_path'])) {
    http_response_code(403);
    die("Access denied or file not found.");
}

$filePath = '/var/www/researchportal/uploads/' .
            $proposal['file_path'];

if (!file_exists($filePath)) {
    http_response_code(404);
    die("File not found.");
}

$ext = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));

if ($ext === 'pdf') {
    header('Content-Type: application/pdf');
    header('Content-Disposition: inline; filename="' .
           basename($filePath) . '"');
    header('Content-Length: ' . filesize($filePath));
    readfile($filePath);
} else {
    header('Content-Type: application/octet-stream');
    header('Content-Disposition: attachment; filename="' .
           basename($filePath) . '"');
    readfile($filePath);
}
exit();
