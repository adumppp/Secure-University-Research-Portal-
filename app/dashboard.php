<?php
require 'includes/db.php';
require 'includes/auth.php';
require 'includes/functions.php';

requireAuth();
$user = currentUser();

// ── GUEST VIEW ──────────────────────────────────────
if ($user['role'] === 'guest') {
    // Get announcements only
    $anns = $pdo->query(
        "SELECT a.*, u.username as author
         FROM announcements a
         JOIN users u ON a.posted_by = u.id
         ORDER BY a.date DESC LIMIT 5"
    )->fetchAll();

    // Get public proposals (read-only)
    $pubProposals = $pdo->query(
        "SELECT p.*, u.username FROM proposals p
         JOIN users u ON p.researcher_id = u.id
         WHERE p.classification = 'public'
         AND p.status = 'approved'
         ORDER BY p.submitted_at DESC LIMIT 6"
    )->fetchAll();

    $totalPublic = $pdo->query(
        "SELECT COUNT(*) FROM proposals
         WHERE classification='public'")->fetchColumn();
    $totalApproved = $pdo->query(
        "SELECT COUNT(*) FROM proposals
         WHERE status='approved'")->fetchColumn();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard — ResearchPortal</title>
    <link rel="stylesheet" href="/assets/style.css">
</head>
<body>
<div class="app-layout">
    <?php require 'includes/nav.php'; ?>
    <div class="main-content">
        <div class="topbar">
            <div class="topbar-title">
                <h1>Welcome, <?= e($user['username']) ?>!</h1>
                <p>Guest access — view public research and announcements</p>
            </div>
        </div>
        <div class="page-content">
            <?php showFlash(); ?>

            <!-- Guest notice -->
            <div style="background:linear-gradient(135deg,#f0f4ff,#e8f0fe);
                border:1px solid #c7d7fd;border-radius:12px;
                padding:20px 24px;margin-bottom:24px;
                display:flex;align-items:center;gap:16px;">
                <div style="font-size:36px;">👤</div>
                <div>
                    <div style="font-weight:700;color:var(--dark);
                        font-size:15px;margin-bottom:4px;">
                        Guest Access
                    </div>
                    <div style="font-size:13px;color:var(--gray);">
                        You can browse public research and announcements.
                        To submit research or access confidential documents,
                        contact an administrator to upgrade your account.
                    </div>
                </div>
            </div>

            <!-- Simple stats -->
            <div class="stats-grid" style="grid-template-columns:repeat(2,1fr);max-width:500px;">
                <div class="stat-card stat-success">
                    <div class="stat-accent"></div>
                    <div class="stat-icon">📋</div>
                    <div class="stat-number"><?= $totalPublic ?></div>
                    <div class="stat-label">Public Research</div>
                </div>
                <div class="stat-card stat-primary">
                    <div class="stat-accent"></div>
                    <div class="stat-icon">✅</div>
                    <div class="stat-number"><?= $totalApproved ?></div>
                    <div class="stat-label">Approved</div>
                </div>
            </div>

            <!-- Search bar for guest -->
            <div class="card">
                <div class="card-header">
                    <h2>🔍 Search Public Research</h2>
                </div>
                <form method="GET" action="/search.php">
                    <div style="display:flex;gap:10px;">
                        <input type="text" name="q"
                               placeholder="Search research titles..."
                               style="flex:1;padding:10px 14px;">
                        <button type="submit" class="btn btn-primary">
                            Search
                        </button>
                    </div>
                </form>
            </div>

            <!-- Announcements -->
            <div class="card">
                <div class="card-header">
                    <h2>📢 Announcements</h2>
                </div>
                <?php if (empty($anns)): ?>
                    <div class="empty-state">
                        <div class="empty-icon">📢</div>
                        <p>No announcements yet.</p>
                    </div>
                <?php else: ?>
                    <?php foreach ($anns as $ann): ?>
                        <div style="padding:16px 0;
                            border-bottom:1px solid var(--border);">
                            <div style="font-weight:700;
                                font-size:14px;color:var(--dark);
                                margin-bottom:6px;">
                                <?= e($ann['title']) ?>
                            </div>
                            <div style="font-size:13px;
                                color:var(--gray);margin-bottom:8px;
                                line-height:1.6;">
                                <?= e($ann['content']) ?>
                            </div>
                            <div style="font-size:11px;
                                color:var(--gray-light);">
                                Posted by <strong>
                                <?= e($ann['author']) ?></strong>
                                · <?= formatDate($ann['date']) ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>

            <!-- Public research browse -->
            <div class="card">
                <div class="card-header">
                    <h2>📋 Browse Public Research</h2>
                    <a href="/search.php"
                       class="btn btn-secondary btn-sm">
                        View All →
                    </a>
                </div>
                <?php if (empty($pubProposals)): ?>
                    <div class="empty-state">
                        <p>No public research available.</p>
                    </div>
                <?php else: ?>
                    <div class="table-container">
                        <table>
                            <thead>
                                <tr>
                                    <th>Title</th>
                                    <th>Researcher</th>
                                    <th>Status</th>
                                    <th>Date</th>
                                </tr>
                            </thead>
                            <tbody>
                            <?php foreach ($pubProposals as $p): ?>
                                <tr>
                                    <td>
                                        <a href="/research.php?id=<?= $p['id'] ?>"
                                           style="color:var(--primary);
                                           font-weight:600;
                                           text-decoration:none;">
                                            <?= e($p['title']) ?>
                                        </a>
                                    </td>
                                    <td><?= e($p['username']) ?></td>
                                    <td>
                                        <span class="badge badge-<?= $p['status'] ?>">
                                            <?= ucfirst($p['status']) ?>
                                        </span>
                                    </td>
                                    <td style="color:var(--gray);font-size:12px;">
                                        <?= formatDate($p['submitted_at']) ?>
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
<?php
    exit();
}
// ── END GUEST VIEW ───────────────────────────────────

// ── RESEARCHER / ADMIN VIEW ─────────────────────────
if ($user['role'] === 'admin') {
    $totalUsers  = $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
    $totalPub    = $pdo->query("SELECT COUNT(*) FROM proposals WHERE classification='public'")->fetchColumn();
    $totalConf   = $pdo->query("SELECT COUNT(*) FROM sensitive_documents")->fetchColumn();
    $totalDocs   = $pdo->query("SELECT COUNT(*) FROM documents")->fetchColumn();
    $pendingReqs = $pdo->query("SELECT COUNT(*) FROM proposals WHERE status='pending'")->fetchColumn();

    $stmt = $pdo->prepare(
        "SELECT p.*, u.username,
         CASE WHEN p.classification='confidential'
         THEN '🔴 CONFIDENTIAL' ELSE '🟢 PUBLIC'
         END as class_label
         FROM proposals p
         JOIN users u ON p.researcher_id = u.id
         ORDER BY p.classification DESC,
         p.submitted_at DESC LIMIT 10"
    );
    $stmt->execute();
} else {
    // Researcher
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM proposals WHERE researcher_id=?");
    $stmt->execute([$user['id']]);
    $totalPub = $stmt->fetchColumn();

    $stmt = $pdo->prepare("SELECT COUNT(*) FROM documents WHERE uploader_id=?");
    $stmt->execute([$user['id']]);
    $totalDocs = $stmt->fetchColumn();

    $stmt = $pdo->prepare("SELECT COUNT(*) FROM sensitive_documents WHERE uploader_id=?");
    $stmt->execute([$user['id']]);
    $totalConf = $stmt->fetchColumn();

    $stmt = $pdo->prepare("SELECT COUNT(*) FROM proposals WHERE researcher_id=? AND status='pending'");
    $stmt->execute([$user['id']]);
    $pendingReqs = $stmt->fetchColumn();

    $stmt = $pdo->prepare(
        "SELECT p.*, u.username,
         CASE WHEN p.classification='confidential'
         THEN '🔴 CONFIDENTIAL' ELSE '🟢 PUBLIC'
         END as class_label
         FROM proposals p
         JOIN users u ON p.researcher_id = u.id
         WHERE p.classification='public'
         OR (p.classification='confidential' AND p.researcher_id=?)
         ORDER BY p.classification DESC, p.submitted_at DESC LIMIT 10"
    );
    $stmt->execute([$user['id']]);
}
$proposals = $stmt->fetchAll();

// Recent docs
if ($user['role'] === 'admin') {
    $stmt = $pdo->prepare("SELECT d.*, u.username FROM documents d JOIN users u ON d.uploader_id=u.id ORDER BY d.upload_date DESC LIMIT 5");
    $stmt->execute();
} else {
    $stmt = $pdo->prepare("SELECT d.*, u.username FROM documents d JOIN users u ON d.uploader_id=u.id WHERE d.uploader_id=? ORDER BY d.upload_date DESC LIMIT 5");
    $stmt->execute([$user['id']]);
}
$docs = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard — ResearchPortal</title>
    <link rel="stylesheet" href="/assets/style.css">
</head>
<body>
<div class="app-layout">
    <?php require 'includes/nav.php'; ?>
    <div class="main-content">
        <div class="topbar">
            <div class="topbar-title">
                <h1>Dashboard</h1>
                <p>Welcome back, <?= e($user['username']) ?>! Here's your overview.</p>
            </div>
            <div class="topbar-actions">
                <?php if (in_array($user['role'],['admin','researcher'])): ?>
                    <a href="/upload.php" class="btn btn-primary btn-sm">
                        + Upload Research
                    </a>
                <?php endif; ?>
            </div>
        </div>

        <div class="page-content">
            <?php showFlash(); ?>

            <?php if (isset($_GET['error']) && $_GET['error']==='unauthorized'): ?>
                <div class="flash flash-error">
                    ⚠️ You don't have permission to access that page.
                </div>
            <?php endif; ?>

            <!-- Stats -->
            <div class="stats-grid">
                <?php if ($user['role']==='admin'): ?>
                <div class="stat-card stat-primary">
                    <div class="stat-accent"></div>
                    <div class="stat-icon">👥</div>
                    <div class="stat-number"><?= $totalUsers ?></div>
                    <div class="stat-label">Total Users</div>
                </div>
                <?php endif; ?>
                <div class="stat-card stat-success">
                    <div class="stat-accent"></div>
                    <div class="stat-icon">📋</div>
                    <div class="stat-number"><?= $totalPub ?></div>
                    <div class="stat-label"><?= $user['role']==='admin' ? 'Public Proposals' : 'My Proposals' ?></div>
                </div>
                <div class="stat-card stat-warning">
                    <div class="stat-accent"></div>
                    <div class="stat-icon">📁</div>
                    <div class="stat-number"><?= $totalDocs ?></div>
                    <div class="stat-label">Documents</div>
                </div>
                <div class="stat-card stat-danger">
                    <div class="stat-accent"></div>
                    <div class="stat-icon">🔴</div>
                    <div class="stat-number"><?= $totalConf ?></div>
                    <div class="stat-label">Confidential</div>
                </div>
                <div class="stat-card stat-primary">
                    <div class="stat-accent"></div>
                    <div class="stat-icon">⏳</div>
                    <div class="stat-number"><?= $pendingReqs ?></div>
                    <div class="stat-label">Pending Review</div>
                </div>
            </div>

            <!-- Quick search bar -->
            <div class="card" style="padding:20px 24px;">
                <form method="GET" action="/search.php"
                      style="display:flex;gap:10px;align-items:center;">
                    <span style="font-size:18px;">🔍</span>
                    <input type="text" name="q"
                           placeholder="Quick search research..."
                           style="flex:1;border:none;outline:none;
                           font-size:14px;background:transparent;">
                    <button type="submit"
                            class="btn btn-secondary btn-sm">
                        Search
                    </button>
                </form>
            </div>

            <!-- Proposals table -->
            <div class="card">
                <div class="card-header">
                    <div>
                        <h2>📋 Recent Proposals</h2>
                        <div class="card-subtitle">
                            <?= $user['role']==='admin'
                            ? 'All proposals — admin view'
                            : 'Your proposals + public research' ?>
                        </div>
                    </div>
                    <?php if ($user['role']==='researcher'): ?>
                        <a href="/my-research.php"
                           class="btn btn-secondary btn-sm">
                            My Research →
                        </a>
                    <?php endif; ?>
                </div>

                <?php if (empty($proposals)): ?>
                    <div class="empty-state">
                        <div class="empty-icon">📋</div>
                        <p>No proposals yet.</p>
                        <a href="/upload.php" class="btn btn-primary btn-sm">
                            Submit First Proposal
                        </a>
                    </div>
                <?php else: ?>
                    <div class="table-container">
                        <table>
                            <thead>
                                <tr>
                                    <th>Title</th>
                                    <th>Researcher</th>
                                    <th>Classification</th>
                                    <th>Status</th>
                                    <th>Submitted</th>
                                </tr>
                            </thead>
                            <tbody>
                            <?php foreach ($proposals as $p): ?>
                                <tr style="cursor:pointer;"
                                    onclick="location.href='/research.php?id=<?= $p['id'] ?>'">
                                    <td style="font-weight:500;max-width:280px;">
                                        <?= e($p['title']) ?>
                                    </td>
                                    <td><?= e($p['username']) ?></td>
                                    <td>
                                        <span class="badge badge-<?= $p['classification'] ?>">
                                            <?= e($p['class_label']) ?>
                                        </span>
                                    </td>
                                    <td>
                                        <span class="badge badge-<?= $p['status'] ?>">
                                            <?= ucfirst($p['status']) ?>
                                        </span>
                                    </td>
                                    <td style="color:var(--gray);white-space:nowrap;font-size:12px;">
                                        <?= formatDate($p['submitted_at']) ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Recent Documents -->
            <div class="card">
                <div class="card-header">
                    <div>
                        <h2>📁 Recent Documents</h2>
                        <div class="card-subtitle">Public research documents</div>
                    </div>
                    <?php if (in_array($user['role'],['admin','researcher'])): ?>
                        <a href="/confidential.php"
                           class="btn btn-sm"
                           style="background:#fee2e2;color:#991b1b;">
                            🔴 Confidential →
                        </a>
                    <?php endif; ?>
                </div>
                <?php if (empty($docs)): ?>
                    <div class="empty-state">
                        <div class="empty-icon">📁</div>
                        <p>No documents yet.</p>
                    </div>
                <?php else: ?>
                    <div class="table-container">
                        <table>
                            <thead>
                                <tr>
                                    <th>Document Name</th>
                                    <th>Uploaded By</th>
                                    <th>Date</th>
                                </tr>
                            </thead>
                            <tbody>
                            <?php foreach ($docs as $d): ?>
                                <tr>
                                    <td style="font-weight:500;"><?= e($d['doc_name']) ?></td>
                                    <td><?= e($d['username']) ?></td>
                                    <td style="color:var(--gray);font-size:12px;"><?= formatDate($d['upload_date']) ?></td>
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
