<?php
require 'includes/db.php';
require 'includes/auth.php';
require 'includes/functions.php';

requireAuth();
$user = currentUser();

$id = (int)($_GET['id'] ?? 0);
if (!$id) {
    header("Location: /search.php");
    exit();
}

// Get proposal
if ($user['role'] === 'admin') {
    $stmt = $pdo->prepare(
        "SELECT p.*, u.username, u.email
         FROM proposals p
         JOIN users u ON p.researcher_id = u.id
         WHERE p.id = ?"
    );
    $stmt->execute([$id]);
} else {
    $stmt = $pdo->prepare(
        "SELECT p.*, u.username, u.email
         FROM proposals p
         JOIN users u ON p.researcher_id = u.id
         WHERE p.id = ?
         AND (p.classification = 'public'
         OR (p.classification = 'confidential'
             AND p.researcher_id = ?))"
    );
    $stmt->execute([$id, $user['id']]);
}
$proposal = $stmt->fetch();

if (!$proposal) {
    header("Location: /search.php");
    exit();
}

// Check if PDF exists
$filePath = '/var/www/researchportal/uploads/' .
            $proposal['file_path'];
$hasFile  = !empty($proposal['file_path']) &&
            file_exists($filePath);
$isPDF    = $hasFile &&
            strtolower(
                pathinfo($filePath, PATHINFO_EXTENSION)
            ) === 'pdf';

// Log access if confidential
if ($proposal['classification'] === 'confidential') {
    $log = $pdo->prepare(
        "INSERT INTO research_access_log
         (user_id, document_id, document_type,
          action, ip_address)
         VALUES (?, ?, 'confidential', 'view', ?)"
    );
    $log->execute([
        $user['id'], $id, $_SERVER['REMOTE_ADDR']
    ]);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport"
          content="width=device-width, initial-scale=1.0">
    <title><?= e($proposal['title']) ?> — ResearchPortal</title>
    <link rel="stylesheet" href="/assets/style.css">
</head>
<body>
<div class="app-layout">
    <?php require 'includes/nav.php'; ?>

    <div class="main-content">
        <div class="topbar">
            <div class="topbar-title">
                <h1 style="font-size:16px;">
                    Research Detail
                </h1>
                <p>
                    <a href="/search.php"
                       style="color:var(--primary);
                              text-decoration:none;">
                        ← Back to Search
                    </a>
                </p>
            </div>
            <div class="topbar-actions">
                <span class="badge
                    badge-<?= $proposal['classification'] ?>"
                    style="padding:6px 14px;font-size:12px;">
                    <?= $proposal['classification']
                    ==='confidential'
                    ?'🔴 CONFIDENTIAL':'🟢 PUBLIC' ?>
                </span>
                <span class="badge
                    badge-<?= $proposal['status'] ?>"
                    style="padding:6px 14px;font-size:12px;">
                    <?= ucfirst($proposal['status']) ?>
                </span>
            </div>
        </div>

        <div class="page-content">
            <div style="display:grid;
                grid-template-columns:<?= $hasFile
                ? '1fr 380px' : '1fr' ?>;
                gap:24px;align-items:start;">

                <!-- Main content -->
                <div>
                    <?php if ($proposal['classification']
                        === 'confidential'): ?>
                        <div class="confidential-banner"
                             style="margin-bottom:20px;">
                            🔴 CONFIDENTIAL — Access logged
                        </div>
                    <?php endif; ?>

                    <!-- Research info -->
                    <div class="card">
                        <h2 style="font-size:22px;
                            font-weight:800;
                            line-height:1.3;
                            margin-bottom:16px;
                            color:var(--dark);">
                            <?= e($proposal['title']) ?>
                        </h2>

                        <div style="display:flex;
                            flex-wrap:wrap;gap:20px;
                            padding:16px 0;
                            border-top:1px solid var(--border);
                            border-bottom:1px solid var(--border);
                            margin-bottom:20px;">
                            <div>
                                <div style="font-size:11px;
                                    color:var(--gray);
                                    text-transform:uppercase;
                                    letter-spacing:0.5px;
                                    margin-bottom:4px;">
                                    Researcher
                                </div>
                                <div style="font-weight:600;
                                    font-size:14px;">
                                    👤 <?= e($proposal['username']) ?>
                                </div>
                            </div>
                            <div>
                                <div style="font-size:11px;
                                    color:var(--gray);
                                    text-transform:uppercase;
                                    letter-spacing:0.5px;
                                    margin-bottom:4px;">
                                    Submitted
                                </div>
                                <div style="font-weight:600;
                                    font-size:14px;">
                                    📅 <?= formatDate(
                                    $proposal['submitted_at']) ?>
                                </div>
                            </div>
                            <div>
                                <div style="font-size:11px;
                                    color:var(--gray);
                                    text-transform:uppercase;
                                    letter-spacing:0.5px;
                                    margin-bottom:4px;">
                                    Status
                                </div>
                                <span class="badge
                                    badge-<?= $proposal['status'] ?>">
                                    <?= ucfirst($proposal['status']) ?>
                                </span>
                            </div>
                            <div>
                                <div style="font-size:11px;
                                    color:var(--gray);
                                    text-transform:uppercase;
                                    letter-spacing:0.5px;
                                    margin-bottom:4px;">
                                    Classification
                                </div>
                                <span class="badge
                                    badge-<?= $proposal['classification'] ?>">
                                    <?= strtoupper(
                                    $proposal['classification']) ?>
                                </span>
                            </div>
                        </div>

                        <!-- PDF Viewer -->
                        <?php if ($isPDF): ?>
                            <div>
                                <div style="font-size:13px;
                                    font-weight:600;
                                    color:var(--dark);
                                    margin-bottom:12px;">
                                    📄 Research Document Viewer
                                </div>
                                <div style="border:1px solid
                                    var(--border);
                                    border-radius:12px;
                                    overflow:hidden;
                                    background:#525659;">

                                    <!-- PDF toolbar -->
                                    <div style="background:#3d4043;
                                        padding:10px 16px;
                                        display:flex;
                                        align-items:center;
                                        justify-content:space-between;">
                                        <div style="color:#e8eaed;
                                            font-size:13px;
                                            font-weight:500;">
                                            📄 <?= e(
                                            $proposal['file_path']) ?>
                                        </div>
                                        <div style="display:flex;
                                            gap:8px;">
                                            <a href="/view-doc.php?id=<?= $id ?>"
                                               target="_blank"
                                               style="background:
                                               rgba(255,255,255,0.1);
                                               color:white;
                                               padding:5px 12px;
                                               border-radius:6px;
                                               font-size:12px;
                                               text-decoration:none;">
                                                ↗ Open Full
                                            </a>
                                        </div>
                                    </div>

                                    <!-- Embedded PDF -->
                                    <iframe
                                        src="/view-doc.php?id=<?= $id ?>"
                                        style="width:100%;
                                               height:600px;
                                               border:none;
                                               display:block;"
                                        title="Research Document">
                                    </iframe>
                                </div>
                            </div>

                        <?php elseif ($hasFile): ?>
                            <!-- Non-PDF file -->
                            <div style="background:var(--bg);
                                border:1px solid var(--border);
                                border-radius:10px;
                                padding:24px;text-align:center;">
                                <div style="font-size:48px;
                                    margin-bottom:12px;">📄</div>
                                <div style="font-weight:600;
                                    margin-bottom:8px;">
                                    Document Available
                                </div>
                                <div style="font-size:13px;
                                    color:var(--gray);
                                    margin-bottom:16px;">
                                    This document is in DOCX format.
                                    PDF viewer is only available
                                    for PDF files.
                                </div>
                            </div>

                        <?php else: ?>
                            <div style="background:var(--bg);
                                border:1px dashed var(--border);
                                border-radius:10px;
                                padding:24px;text-align:center;
                                color:var(--gray);">
                                <div style="font-size:32px;
                                    margin-bottom:8px;">📭</div>
                                No document uploaded yet
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Sidebar info -->
                <div>
                    <div class="card">
                        <h2 style="font-size:14px;
                            margin-bottom:16px;">
                            ℹ️ Research Info
                        </h2>

                        <div style="display:flex;
                            flex-direction:column;gap:14px;
                            font-size:13px;">
                            <div>
                                <div style="color:var(--gray);
                                    font-size:11px;
                                    text-transform:uppercase;
                                    letter-spacing:0.5px;
                                    margin-bottom:4px;">
                                    Proposal ID
                                </div>
                                <div style="font-weight:600;">
                                    #<?= $proposal['id'] ?>
                                </div>
                            </div>
                            <div>
                                <div style="color:var(--gray);
                                    font-size:11px;
                                    text-transform:uppercase;
                                    letter-spacing:0.5px;
                                    margin-bottom:4px;">
                                    Submitted By
                                </div>
                                <div style="font-weight:600;">
                                    <?= e($proposal['username']) ?>
                                </div>
                            </div>
                            <div>
                                <div style="color:var(--gray);
                                    font-size:11px;
                                    text-transform:uppercase;
                                    letter-spacing:0.5px;
                                    margin-bottom:4px;">
                                    Submission Date
                                </div>
                                <div style="font-weight:600;">
                                    <?= formatDate(
                                    $proposal['submitted_at']) ?>
                                </div>
                            </div>
                            <div>
                                <div style="color:var(--gray);
                                    font-size:11px;
                                    text-transform:uppercase;
                                    letter-spacing:0.5px;
                                    margin-bottom:4px;">
                                    Review Status
                                </div>
                                <span class="badge
                                    badge-<?= $proposal['status'] ?>">
                                    <?= ucfirst($proposal['status']) ?>
                                </span>
                            </div>
                            <div>
                                <div style="color:var(--gray);
                                    font-size:11px;
                                    text-transform:uppercase;
                                    letter-spacing:0.5px;
                                    margin-bottom:4px;">
                                    Access Level
                                </div>
                                <span class="badge
                                    badge-<?=
                                    $proposal['classification'] ?>">
                                    <?= strtoupper(
                                    $proposal['classification']) ?>
                                </span>
                            </div>
                            <?php if ($hasFile): ?>
                            <div>
                                <div style="color:var(--gray);
                                    font-size:11px;
                                    text-transform:uppercase;
                                    letter-spacing:0.5px;
                                    margin-bottom:4px;">
                                    File
                                </div>
                                <div style="font-size:12px;
                                    word-break:break-all;
                                    color:var(--primary);">
                                    <?= e($proposal['file_path']) ?>
                                </div>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <?php if ($proposal['classification']
                        === 'confidential'): ?>
                    <div class="card"
                         style="background:#fff5f5;
                         border-color:#fecaca;">
                        <div style="font-size:13px;
                            color:#991b1b;">
                            <strong>🔴 Confidential Notice</strong>
                            <br><br>
                            Your access to this document has been
                            logged with timestamp and IP address.
                            Unauthorised sharing is prohibited.
                        </div>
                    </div>
                    <?php endif; ?>
                </div>

            </div>
        </div>
    </div>
</div>
</body>
</html>
