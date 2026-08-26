<?php
require '../includes/db.php';
require '../includes/auth.php';
require '../includes/functions.php';

requireAdmin();
$user = currentUser();

$totalUsers     = $pdo->query(
    "SELECT COUNT(*) FROM users")->fetchColumn();
$totalProposals = $pdo->query(
    "SELECT COUNT(*) FROM proposals")->fetchColumn();
$totalDocs      = $pdo->query(
    "SELECT COUNT(*) FROM documents")->fetchColumn();
$pendingCollab  = $pdo->query(
    "SELECT COUNT(*) FROM proposals
     WHERE status='pending'")->fetchColumn();
$totalConf      = $pdo->query(
    "SELECT COUNT(*) FROM sensitive_documents")
    ->fetchColumn();

$stmt = $pdo->query(
    "SELECT p.*, u.username
     FROM proposals p
     JOIN users u ON p.researcher_id = u.id
     ORDER BY p.submitted_at DESC LIMIT 8"
);
$recentProposals = $stmt->fetchAll();

// Fix path for nav
$_SERVER['PHP_SELF'] = '/admin/index.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport"
          content="width=device-width, initial-scale=1.0">
    <title>Admin Panel — ResearchPortal</title>
    <link rel="stylesheet" href="/assets/style.css">
</head>
<body>
<div class="app-layout">
    <?php require '../includes/nav.php'; ?>

    <div class="main-content">
        <div class="topbar">
            <div class="topbar-title">
                <h1>⚙️ Admin Panel</h1>
                <p>Portal management and oversight</p>
            </div>
        </div>

        <div class="page-content">
            <?php showFlash(); ?>

            <!-- Stats -->
            <div class="stats-grid">
                <div class="stat-card stat-primary">
                    <div class="stat-accent"></div>
                    <div class="stat-icon">👥</div>
                    <div class="stat-number">
                        <?= $totalUsers ?>
                    </div>
                    <div class="stat-label">Total Users</div>
                </div>
                <div class="stat-card stat-success">
                    <div class="stat-accent"></div>
                    <div class="stat-icon">📋</div>
                    <div class="stat-number">
                        <?= $totalProposals ?>
                    </div>
                    <div class="stat-label">Proposals</div>
                </div>
                <div class="stat-card stat-warning">
                    <div class="stat-accent"></div>
                    <div class="stat-icon">📁</div>
                    <div class="stat-number">
                        <?= $totalDocs ?>
                    </div>
                    <div class="stat-label">Documents</div>
                </div>
                <div class="stat-card stat-danger">
                    <div class="stat-accent"></div>
                    <div class="stat-icon">🔴</div>
                    <div class="stat-number">
                        <?= $totalConf ?>
                    </div>
                    <div class="stat-label">Confidential</div>
                </div>
                <div class="stat-card stat-primary">
                    <div class="stat-accent"></div>
                    <div class="stat-icon">⏳</div>
                    <div class="stat-number">
                        <?= $pendingCollab ?>
                    </div>
                    <div class="stat-label">Pending Review</div>
                </div>
            </div>

            <!-- Recent Proposals -->
            <div class="card">
                <div class="card-header">
                    <div>
                        <h2>📋 Recent Proposals</h2>
                        <div class="card-subtitle">
                            All proposals — admin view
                        </div>
                    </div>
                    <a href="/admin/proposals.php"
                       class="btn btn-secondary btn-sm">
                        Manage All →
                    </a>
                </div>
                <div class="table-container">
                    <table>
                        <thead>
                            <tr>
                                <th>Title</th>
                                <th>Researcher</th>
                                <th>Status</th>
                                <th>Submitted</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php foreach (
                            $recentProposals as $p): ?>
                            <tr>
                                <td style="font-weight:500;
                                    max-width:300px;">
                                    <?= e($p['title']) ?>
                                </td>
                                <td>
                                    <?= e($p['username']) ?>
                                </td>
                                <td>
                                    <span class="badge
                                        badge-<?= $p['status'] ?>">
                                        <?= ucfirst($p['status']) ?>
                                    </span>
                                </td>
                                <td style="color:var(--gray);
                                    font-size:12px;">
                                    <?= formatDate(
                                    $p['submitted_at']) ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>

        </div>
    </div>
</div>
</body>
</html>
