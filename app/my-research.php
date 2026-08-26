<?php
require 'includes/db.php';
require 'includes/auth.php';
require 'includes/functions.php';

requireResearcher();
$user = currentUser();

// Get ALL researcher's proposals (public + confidential)
$stmt = $pdo->prepare(
    "SELECT p.*, u.username FROM proposals p
     JOIN users u ON p.researcher_id = u.id
     WHERE p.researcher_id = ?
     ORDER BY p.classification DESC, p.submitted_at DESC"
);
$stmt->execute([$user['id']]);
$myProposals = $stmt->fetchAll();

// Get researcher's documents
$stmt = $pdo->prepare(
    "SELECT * FROM documents WHERE uploader_id=?
     ORDER BY upload_date DESC"
);
$stmt->execute([$user['id']]);
$myDocs = $stmt->fetchAll();

// Get researcher's confidential docs (log access per view)
$stmt = $pdo->prepare(
    "SELECT * FROM sensitive_documents
     WHERE uploader_id=?
     ORDER BY upload_date DESC"
);
$stmt->execute([$user['id']]);
$myConfDocs = $stmt->fetchAll();

// Stats
$totalMy    = count($myProposals);
$totalConf  = count(array_filter($myProposals,
    fn($p) => $p['classification']==='confidential'));
$totalPub   = $totalMy - $totalConf;
$totalPending = count(array_filter($myProposals,
    fn($p) => $p['status']==='pending'));
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Research — ResearchPortal</title>
    <link rel="stylesheet" href="/assets/style.css">
</head>
<body>
<div class="app-layout">
    <?php require 'includes/nav.php'; ?>

    <div class="main-content">
        <div class="topbar">
            <div class="topbar-title">
                <h1>📚 My Research</h1>
                <p>All your submitted proposals and documents</p>
            </div>
            <div class="topbar-actions">
                <a href="/upload.php" class="btn btn-primary btn-sm">
                    + Upload New
                </a>
            </div>
        </div>

        <div class="page-content">
            <?php showFlash(); ?>

            <!-- Stats -->
            <div class="stats-grid">
                <div class="stat-card stat-primary">
                    <div class="stat-accent"></div>
                    <div class="stat-icon">📋</div>
                    <div class="stat-number"><?= $totalMy ?></div>
                    <div class="stat-label">Total Submissions</div>
                </div>
                <div class="stat-card stat-success">
                    <div class="stat-accent"></div>
                    <div class="stat-icon">🟢</div>
                    <div class="stat-number"><?= $totalPub ?></div>
                    <div class="stat-label">Public</div>
                </div>
                <div class="stat-card stat-danger">
                    <div class="stat-accent"></div>
                    <div class="stat-icon">🔴</div>
                    <div class="stat-number"><?= $totalConf ?></div>
                    <div class="stat-label">Confidential</div>
                </div>
                <div class="stat-card stat-warning">
                    <div class="stat-accent"></div>
                    <div class="stat-icon">⏳</div>
                    <div class="stat-number"><?= $totalPending ?></div>
                    <div class="stat-label">Pending Review</div>
                </div>
            </div>

            <!-- All My Proposals -->
            <div class="card">
                <div class="card-header">
                    <div>
                        <h2>📋 My Proposals</h2>
                        <div class="card-subtitle">
                            All your research submissions
                            — 🔴 marks are access-logged
                        </div>
                    </div>
                    <a href="/upload.php"
                       class="btn btn-secondary btn-sm">
                        + New Proposal
                    </a>
                </div>

                <?php if (empty($myProposals)): ?>
                    <div class="empty-state">
                        <div class="empty-icon">📋</div>
                        <p>No proposals yet.</p>
                        <a href="/upload.php"
                           class="btn btn-primary btn-sm">
                            Submit First Proposal
                        </a>
                    </div>
                <?php else: ?>
                    <div class="table-container">
                        <table>
                            <thead>
                                <tr>
                                    <th>Title</th>
                                    <th>Classification</th>
                                    <th>Status</th>
                                    <th>Submitted</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                            <?php foreach ($myProposals as $p): ?>
                                <tr>
                                    <td style="font-weight:500;
                                        max-width:280px;">
                                        <?php if ($p['classification']==='confidential'): ?>
                                            <span style="display:inline-block;
                                                width:8px;height:8px;
                                                background:var(--danger);
                                                border-radius:50%;
                                                margin-right:6px;
                                                vertical-align:middle;">
                                            </span>
                                        <?php endif; ?>
                                        <?= e($p['title']) ?>
                                    </td>
                                    <td>
                                        <?php if ($p['classification']==='confidential'): ?>
                                            <span class="badge badge-confidential"
                                                  style="display:inline-flex;
                                                  align-items:center;gap:4px;">
                                                🔴 CONFIDENTIAL
                                            </span>
                                        <?php else: ?>
                                            <span class="badge badge-public">
                                                🟢 PUBLIC
                                            </span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <span class="badge badge-<?= $p['status'] ?>">
                                            <?= ucfirst($p['status']) ?>
                                        </span>
                                    </td>
                                    <td style="color:var(--gray);
                                        font-size:12px;white-space:nowrap;">
                                        <?= formatDate($p['submitted_at']) ?>
                                    </td>
                                    <td>
                                        <a href="/research.php?id=<?= $p['id'] ?>"
                                           class="btn btn-secondary btn-sm">
                                            <?= $p['classification']==='confidential'
                                            ? '🔴 View (logged)' : 'View →' ?>
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Confidential Documents Section -->
            <?php if (!empty($myConfDocs)): ?>
            <div class="card" style="border-color:#fecaca;">
                <div class="card-header">
                    <div>
                        <h2 style="color:var(--danger);">
                            🔴 My Confidential Documents
                        </h2>
                        <div class="card-subtitle">
                            Stored in isolated table —
                            every view is logged
                        </div>
                    </div>
                </div>

                <div style="background:#fff5f5;border-radius:8px;
                    padding:12px 16px;margin-bottom:16px;
                    font-size:12px;color:#991b1b;
                    border:1px solid #fecaca;">
                    ⚠️ Viewing any confidential document logs
                    your IP address and timestamp.
                    Unauthorised sharing is prohibited.
                </div>

                <?php foreach ($myConfDocs as $doc): ?>
                    <div class="doc-confidential"
                         style="cursor:pointer;">
                        <div style="display:flex;
                            justify-content:space-between;
                            align-items:flex-start;
                            margin-bottom:8px;">
                            <div style="display:flex;
                                align-items:center;gap:8px;">
                                <span style="width:10px;height:10px;
                                    background:var(--danger);
                                    border-radius:50%;
                                    flex-shrink:0;
                                    display:inline-block;">
                                </span>
                                <strong style="font-size:14px;
                                    color:var(--dark);">
                                    <?= e($doc['doc_name']) ?>
                                </strong>
                            </div>
                            <span class="conf-badge">CONFIDENTIAL</span>
                        </div>
                        <p style="font-size:13px;color:var(--gray);
                            margin-bottom:10px;">
                            <?= e($doc['description']) ?>
                        </p>
                        <div style="display:flex;
                            justify-content:space-between;
                            align-items:center;">
                            <div style="font-size:11px;
                                color:var(--gray-light);">
                                📅 <?= formatDate($doc['upload_date']) ?>
                                · 🗄️ sensitive_documents table
                            </div>
                            <button onclick="logAndView(<?= $doc['id'] ?>)"
                                    class="btn btn-sm"
                                    style="background:#fee2e2;
                                    color:#991b1b;font-size:11px;">
                                🔴 View (access logged)
                            </button>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>

            <!-- Public Documents -->
            <?php if (!empty($myDocs)): ?>
            <div class="card">
                <div class="card-header">
                    <div>
                        <h2>📁 My Public Documents</h2>
                        <div class="card-subtitle">
                            <?= count($myDocs) ?> documents uploaded
                        </div>
                    </div>
                </div>
                <div class="table-container">
                    <table>
                        <thead>
                            <tr>
                                <th>Document Name</th>
                                <th>Upload Date</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($myDocs as $d): ?>
                            <tr>
                                <td style="font-weight:500;">
                                    <?= e($d['doc_name']) ?>
                                </td>
                                <td style="color:var(--gray);
                                    font-size:12px;">
                                    <?= formatDate($d['upload_date']) ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            <?php endif; ?>

        </div>
    </div>
</div>

<script>
function logAndView(docId) {
    // Log access then open
    fetch('/log-access.php?doc_id=' + docId + '&type=sensitive_doc')
        .then(() => {
            alert('Access logged. Opening document details.');
        });
}
</script>
</body>
</html>
