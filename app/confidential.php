<?php
require 'includes/db.php';
require 'includes/auth.php';
require 'includes/functions.php';

requireAuth();
$user = currentUser();

if ($user['role'] === 'guest') {
    header("Location: /dashboard.php?error=unauthorized");
    exit();
}

// Log access
$stmt = $pdo->prepare(
    "INSERT INTO research_access_log
     (user_id, document_type, action, ip_address)
     VALUES (?, 'confidential', 'page_access', ?)"
);
$stmt->execute([$user['id'], $_SERVER['REMOTE_ADDR']]);

// Get confidential proposals
if ($user['role'] === 'admin') {
    $stmt = $pdo->prepare(
        "SELECT p.*, u.username
         FROM proposals p
         JOIN users u ON p.researcher_id = u.id
         WHERE p.classification = 'confidential'
         ORDER BY p.submitted_at DESC"
    );
    $stmt->execute();
} else {
    $stmt = $pdo->prepare(
        "SELECT p.*, u.username
         FROM proposals p
         JOIN users u ON p.researcher_id = u.id
         WHERE p.classification = 'confidential'
         AND p.researcher_id = ?
         ORDER BY p.submitted_at DESC"
    );
    $stmt->execute([$user['id']]);
}
$confProposals = $stmt->fetchAll();

// Get sensitive documents
if ($user['role'] === 'admin') {
    $stmt = $pdo->prepare(
        "SELECT s.*, u.username as uploader
         FROM sensitive_documents s
         JOIN users u ON s.uploader_id = u.id
         ORDER BY s.upload_date DESC"
    );
    $stmt->execute();
} else {
    $stmt = $pdo->prepare(
        "SELECT s.*, u.username as uploader
         FROM sensitive_documents s
         JOIN users u ON s.uploader_id = u.id
         WHERE s.uploader_id = ?
         ORDER BY s.upload_date DESC"
    );
    $stmt->execute([$user['id']]);
}
$sensitiveDocs = $stmt->fetchAll();

// Access log
$logStmt = $pdo->prepare(
    "SELECT * FROM research_access_log
     WHERE user_id = ?
     ORDER BY accessed_at DESC LIMIT 10"
);
$logStmt->execute([$user['id']]);
$accessLog = $logStmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport"
          content="width=device-width, initial-scale=1.0">
    <title>Confidential Research — ResearchPortal</title>
    <link rel="stylesheet" href="/assets/style.css">
</head>
<body>
<div class="app-layout">
    <?php require 'includes/nav.php'; ?>

    <div class="main-content">
        <div class="topbar">
            <div class="topbar-title">
                <h1>🔴 Confidential Research</h1>
                <p>Restricted access — all activity is logged</p>
            </div>
        </div>

        <div class="page-content">

            <!-- Banner -->
            <div class="confidential-banner">
                🔴 CONFIDENTIAL RESEARCH SECTION —
                AUTHORISED ACCESS ONLY
            </div>

            <!-- Access notice -->
            <?php if ($user['role'] === 'admin'): ?>
                <div class="flash flash-warning">
                    ⚠️ <strong>Admin View</strong> — You are
                    viewing ALL confidential documents.
                    This access is logged.
                </div>
            <?php else: ?>
                <div class="flash flash-success">
                    🔒 <strong>Researcher View</strong> — You
                    can only see your own confidential research.
                    This access is logged.
                </div>
            <?php endif; ?>

            <!-- Confidential Proposals -->
            <div class="card">
                <div class="card-header">
                    <div>
                        <h2>🔴 Confidential Proposals</h2>
                        <div class="card-subtitle">
                            <?= count($confProposals) ?> records
                            — restricted access
                        </div>
                    </div>
                </div>

                <?php if (empty($confProposals)): ?>
                    <div class="empty-state">
                        <div class="empty-icon">🔒</div>
                        <p>No confidential proposals
                           accessible to your account.</p>
                    </div>
                <?php else: ?>
                    <div class="table-container">
                        <table>
                            <thead>
                                <tr>
                                    <th>Title</th>
                                    <th>Researcher</th>
                                    <th>Status</th>
                                    <th>Classification</th>
                                    <th>Submitted</th>
                                </tr>
                            </thead>
                            <tbody>
                            <?php foreach (
                                $confProposals as $p): ?>
                                <tr>
                                    <td>
                                        <div style="display:flex;
                                            align-items:center;
                                            gap:8px;">
                                            <span style="color:
                                                var(--danger);">
                                                🔴
                                            </span>
                                            <strong>
                                                <?= e($p['title']) ?>
                                            </strong>
                                        </div>
                                    </td>
                                    <td><?= e($p['username']) ?></td>
                                    <td>
                                        <span class="badge
                                            badge-<?= $p['status'] ?>">
                                            <?= ucfirst($p['status']) ?>
                                        </span>
                                    </td>
                                    <td>
                                        <span class="conf-badge">
                                            CONFIDENTIAL
                                        </span>
                                    </td>
                                    <td style="color:var(--gray);">
                                        <?= formatDate(
                                        $p['submitted_at']) ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Sensitive Documents -->
            <div class="card">
                <div class="card-header">
                    <div>
                        <h2>🔴 Sensitive Research Documents</h2>
                        <div class="card-subtitle">
                            <?= count($sensitiveDocs) ?> documents
                            — stored in isolated table
                            (sensitive_documents)
                        </div>
                    </div>
                </div>

                <?php if (empty($sensitiveDocs)): ?>
                    <div class="empty-state">
                        <div class="empty-icon">📁</div>
                        <p>No confidential documents
                           accessible to your account.</p>
                    </div>
                <?php else: ?>
                    <?php foreach ($sensitiveDocs as $doc): ?>
                        <div class="doc-confidential">
                            <div style="display:flex;
                                justify-content:space-between;
                                align-items:flex-start;
                                margin-bottom:8px;">
                                <div style="display:flex;
                                    align-items:center;
                                    gap:8px;">
                                    <span>🔴</span>
                                    <strong style="font-size:15px;
                                        color:var(--dark);">
                                        <?= e($doc['doc_name']) ?>
                                    </strong>
                                </div>
                                <span class="conf-badge">
                                    CONFIDENTIAL
                                </span>
                            </div>
                            <p style="font-size:13px;
                                color:var(--gray);
                                margin-bottom:10px;">
                                <?= e($doc['description']) ?>
                            </p>
                            <div style="font-size:12px;
                                color:var(--gray-light);
                                display:flex;gap:16px;
                                flex-wrap:wrap;">
                                <span>👤 <strong>
                                    <?= e($doc['uploader']) ?>
                                </strong></span>
                                <span>📅 <?= formatDate(
                                    $doc['upload_date']) ?></span>
                                <span>🗄️ Storage:
                                    <code>sensitive_documents</code>
                                    (isolated table)
                                </span>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>

                <div style="margin-top:16px;padding:12px 16px;
                    background:var(--bg);border-radius:8px;
                    font-size:12px;color:var(--gray);
                    border:1px solid var(--border);">
                    ℹ️ Confidential documents are stored in a
                    <strong>separate isolated database table</strong>
                    (sensitive_documents). Only the uploader and
                    admin can view these records. All access is
                    recorded in the audit log.
                </div>
            </div>

            <!-- Access Log -->
            <div class="card">
                <div class="card-header">
                    <div>
                        <h2>📋 Your Access Log</h2>
                        <div class="card-subtitle">
                            Last 10 access events
                        </div>
                    </div>
                </div>

                <?php if (empty($accessLog)): ?>
                    <div class="empty-state">
                        <p>No access log entries yet.</p>
                    </div>
                <?php else: ?>
                    <div class="table-container">
                        <table>
                            <thead>
                                <tr>
                                    <th>Action</th>
                                    <th>Type</th>
                                    <th>IP Address</th>
                                    <th>Timestamp</th>
                                </tr>
                            </thead>
                            <tbody>
                            <?php foreach (
                                $accessLog as $log): ?>
                                <tr>
                                    <td>
                                        <code style="background:
                                            var(--bg);padding:
                                            2px 6px;border-radius:4px;
                                            font-size:12px;">
                                            <?= e($log['action']) ?>
                                        </code>
                                    </td>
                                    <td>
                                        <span class="badge
                                        badge-<?= $log['document_type']
                                        ==='confidential'
                                        ?'rejected':'approved' ?>">
                                            <?= ucfirst(
                                            $log['document_type']) ?>
                                        </span>
                                    </td>
                                    <td style="font-family:monospace;
                                        font-size:12px;">
                                        <?= e($log['ip_address']) ?>
                                    </td>
                                    <td style="color:var(--gray);
                                        font-size:12px;">
                                        <?= formatDate(
                                        $log['accessed_at']) ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>

        </div>
    </div>
</div>
</body>
</html>
