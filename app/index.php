<?php
require 'includes/db.php';
require 'includes/auth.php';
require 'includes/functions.php';

$stmt = $pdo->prepare(
    "SELECT a.*, u.username as author
     FROM announcements a
     JOIN users u ON a.posted_by = u.id
     ORDER BY a.date DESC LIMIT 10"
);
$stmt->execute();
$announcements = $stmt->fetchAll();

// Search on homepage
$q = trim($_GET['q'] ?? '');
$searchResults = [];
if (!empty($q)) {
    $isLoggedIn = isLoggedIn();
    if ($isLoggedIn) {
        $uid  = $_SESSION['user_id'];
        $role = $_SESSION['user_role'];
        if ($role === 'admin') {
            $s = $pdo->prepare(
                "SELECT p.*, u.username FROM proposals p
                 JOIN users u ON p.researcher_id=u.id
                 WHERE p.title LIKE ?
                 ORDER BY p.submitted_at DESC LIMIT 8"
            );
            $s->execute(['%'.$q.'%']);
        } else {
            $s = $pdo->prepare(
                "SELECT p.*, u.username FROM proposals p
                 JOIN users u ON p.researcher_id=u.id
                 WHERE p.title LIKE ?
                 AND (p.classification='public'
                 OR (p.classification='confidential'
                     AND p.researcher_id=?))
                 ORDER BY p.submitted_at DESC LIMIT 8"
            );
            $s->execute(['%'.$q.'%', $uid]);
        }
        $searchResults = $s->fetchAll();
    }
}

$isLoggedIn = isLoggedIn();
if ($isLoggedIn) { $user = currentUser(); }

// Stats for landing page
$totalPublic   = $pdo->query("SELECT COUNT(*) FROM proposals WHERE classification='public'")->fetchColumn();
$totalApproved = $pdo->query("SELECT COUNT(*) FROM proposals WHERE status='approved'")->fetchColumn();
$totalUsers    = $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ResearchPortal — University Collaboration</title>
    <link rel="stylesheet" href="/assets/style.css">
</head>
<body>

<?php if ($isLoggedIn): ?>
<!-- ── LOGGED IN — sidebar layout ── -->
<div class="app-layout">
    <?php require 'includes/nav.php'; ?>

    <div class="main-content">
        <div class="topbar">
            <div class="topbar-title">
                <h1>📢 Home</h1>
                <p>Announcements and research search</p>
            </div>
        </div>

        <div class="page-content">
            <?php showFlash(); ?>

            <!-- SEARCH BAR — prominent on homepage -->
            <div class="card" style="background:linear-gradient(
                135deg,var(--dark),var(--dark-2));
                border:none;padding:32px;">
                <div style="text-align:center;margin-bottom:24px;">
                    <h2 style="color:white;font-size:22px;
                        font-weight:800;margin-bottom:8px;">
                        🔍 Search Research
                    </h2>
                    <p style="color:var(--gray-light);font-size:14px;">
                        Find proposals, research papers and documents
                    </p>
                </div>
                <form method="GET" action="/index.php">
                    <div style="display:flex;gap:10px;
                        max-width:600px;margin:0 auto;">
                        <div style="flex:1;position:relative;">
                            <span style="position:absolute;left:14px;
                                top:50%;transform:translateY(-50%);
                                font-size:16px;">🔍</span>
                            <input type="text" name="q"
                                   value="<?= e($q) ?>"
                                   placeholder="Search research titles, topics..."
                                   style="padding-left:42px;width:100%;
                                   padding-top:13px;padding-bottom:13px;
                                   font-size:15px;border-radius:10px;
                                   border:none;">
                        </div>
                        <button type="submit"
                                class="btn btn-primary"
                                style="padding:13px 28px;
                                white-space:nowrap;">
                            Search
                        </button>
                    </div>

                    <!-- Quick filter pills -->
                    <div style="display:flex;gap:8px;
                        justify-content:center;margin-top:16px;
                        flex-wrap:wrap;">
                        <?php
                        $pills = [
                            'Machine Learning','Blockchain',
                            'IoT','NLP','Cybersecurity',
                            'Renewable Energy'
                        ];
                        foreach ($pills as $pill): ?>
                            <a href="?q=<?= urlencode($pill) ?>"
                               style="background:rgba(255,255,255,0.1);
                               color:white;padding:5px 14px;
                               border-radius:20px;font-size:12px;
                               text-decoration:none;
                               border:1px solid rgba(255,255,255,0.2);
                               transition:all 0.2s;"
                               onmouseover="this.style.background='rgba(255,255,255,0.2)'"
                               onmouseout="this.style.background='rgba(255,255,255,0.1)'">
                                <?= $pill ?>
                            </a>
                        <?php endforeach; ?>
                    </div>
                </form>
            </div>

            <!-- Search Results -->
            <?php if (!empty($q)): ?>
                <div style="margin-bottom:16px;
                    font-size:14px;color:var(--gray);">
                    <?= count($searchResults) ?> results for
                    "<strong><?= e($q) ?></strong>"
                </div>

                <?php if (empty($searchResults)): ?>
                    <div class="card">
                        <div class="empty-state">
                            <div class="empty-icon">🔍</div>
                            <p>No research found for "<?= e($q) ?>".</p>
                        </div>
                    </div>
                <?php else: ?>
                    <?php foreach ($searchResults as $r): ?>
                        <a href="/research.php?id=<?= $r['id'] ?>"
                           style="text-decoration:none;
                           display:block;margin-bottom:10px;">
                            <div class="card" style="margin-bottom:0;
                                cursor:pointer;transition:all 0.2s;"
                                onmouseover="this.style.borderColor='var(--primary)';this.style.transform='translateY(-1px)'"
                                onmouseout="this.style.borderColor='var(--border)';this.style.transform=''">
                                <div style="display:flex;
                                    justify-content:space-between;
                                    align-items:center;gap:16px;">
                                    <div>
                                        <div style="display:flex;
                                            gap:8px;margin-bottom:8px;">
                                            <span class="badge badge-<?= $r['classification'] ?>">
                                                <?= $r['classification']==='confidential'?'🔴 CONFIDENTIAL':'🟢 PUBLIC' ?>
                                            </span>
                                            <span class="badge badge-<?= $r['status'] ?>">
                                                <?= ucfirst($r['status']) ?>
                                            </span>
                                        </div>
                                        <div style="font-weight:700;
                                            font-size:15px;
                                            color:var(--dark);
                                            margin-bottom:6px;">
                                            <?= e($r['title']) ?>
                                        </div>
                                        <div style="font-size:12px;
                                            color:var(--gray);">
                                            👤 <?= e($r['username']) ?>
                                            · 📅 <?= formatDate($r['submitted_at']) ?>
                                        </div>
                                    </div>
                                    <div style="color:var(--gray);
                                        font-size:20px;flex-shrink:0;">→</div>
                                </div>
                            </div>
                        </a>
                    <?php endforeach; ?>
                <?php endif; ?>

            <?php else: ?>
                <!-- Announcements when no search -->
                <div class="card">
                    <div class="card-header">
                        <h2>📢 Announcements</h2>
                    </div>
                    <?php if (empty($announcements)): ?>
                        <div class="empty-state">
                            <div class="empty-icon">📢</div>
                            <p>No announcements yet.</p>
                        </div>
                    <?php else: ?>
                        <?php foreach ($announcements as $ann): ?>
                            <div style="padding:16px 0;
                                border-bottom:1px solid var(--border);">
                                <div style="display:flex;
                                    justify-content:space-between;
                                    align-items:flex-start;
                                    margin-bottom:8px;">
                                    <h3 style="font-size:15px;
                                        font-weight:700;
                                        color:var(--dark);">
                                        <?= e($ann['title']) ?>
                                    </h3>
                                    <span style="font-size:11px;
                                        color:var(--gray);
                                        white-space:nowrap;
                                        margin-left:16px;">
                                        <?= formatDate($ann['date']) ?>
                                    </span>
                                </div>
                                <p style="color:var(--gray);
                                    font-size:13px;line-height:1.6;
                                    margin-bottom:8px;">
                                    <?= e($ann['content']) ?>
                                </p>
                                <div style="font-size:11px;
                                    color:var(--gray-light);">
                                    Posted by
                                    <strong><?= e($ann['author']) ?></strong>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            <?php endif; ?>

        </div>
    </div>
</div>

<?php else: ?>
<!-- ── NOT LOGGED IN — landing page ── -->
<div class="auth-wrapper">
    <div class="auth-left">
        <div class="auth-left-content">
            <div class="logo">
                <div class="logo-icon">🔬</div>
                <span class="logo-text">ResearchPortal</span>
            </div>
            <h1>Secure Research <span>Collaboration</span></h1>
            <p>A centralised, secure platform for university researchers
               to submit proposals, share documents, and collaborate —
               with strict data isolation for sensitive research.</p>

            <!-- Platform stats -->
            <div style="display:grid;grid-template-columns:1fr 1fr;
                gap:12px;margin-top:32px;">
                <div style="background:rgba(255,255,255,0.08);
                    border-radius:12px;padding:16px;
                    border:1px solid rgba(255,255,255,0.1);">
                    <div style="font-size:28px;font-weight:800;
                        color:white;margin-bottom:4px;">
                        <?= $totalPublic ?>
                    </div>
                    <div style="font-size:12px;
                        color:var(--gray-light);">
                        Public Research
                    </div>
                </div>
                <div style="background:rgba(255,255,255,0.08);
                    border-radius:12px;padding:16px;
                    border:1px solid rgba(255,255,255,0.1);">
                    <div style="font-size:28px;font-weight:800;
                        color:white;margin-bottom:4px;">
                        <?= $totalUsers ?>
                    </div>
                    <div style="font-size:12px;
                        color:var(--gray-light);">
                        Researchers
                    </div>
                </div>
                <div style="background:rgba(255,255,255,0.08);
                    border-radius:12px;padding:16px;
                    border:1px solid rgba(255,255,255,0.1);">
                    <div style="font-size:28px;font-weight:800;
                        color:white;margin-bottom:4px;">
                        <?= $totalApproved ?>
                    </div>
                    <div style="font-size:12px;
                        color:var(--gray-light);">
                        Approved Papers
                    </div>
                </div>
                <div style="background:rgba(255,255,255,0.08);
                    border-radius:12px;padding:16px;
                    border:1px solid rgba(255,255,255,0.1);">
                    <div style="font-size:28px;font-weight:800;
                        color:white;margin-bottom:4px;">
                        100%
                    </div>
                    <div style="font-size:12px;
                        color:var(--gray-light);">
                        Encrypted (TLS 1.3)
                    </div>
                </div>
            </div>

            <div class="auth-features" style="margin-top:32px;">
                <div class="auth-feature">
                    <div class="feat-icon">🔐</div>
                    SSH key-only + TLS 1.3 encryption
                </div>
                <div class="auth-feature">
                    <div class="feat-icon">🔴</div>
                    Isolated confidential research storage
                </div>
                <div class="auth-feature">
                    <div class="feat-icon">👁️</div>
                    Wazuh SIEM + Fail2ban monitoring
                </div>
            </div>
        </div>
    </div>

    <div class="auth-right">
        <div class="auth-box">
            <h2>Welcome Back</h2>
            <p class="auth-subtitle">
                Sign in to access ResearchPortal
            </p>

            <?php if (!empty($announcements)): ?>
                <div style="background:var(--bg);border-radius:10px;
                    padding:14px 16px;margin-bottom:24px;
                    border:1px solid var(--border);">
                    <div style="font-size:10px;font-weight:700;
                        color:var(--gray);text-transform:uppercase;
                        letter-spacing:0.5px;margin-bottom:6px;">
                        Latest Announcement
                    </div>
                    <div style="font-size:13px;font-weight:600;
                        color:var(--dark);margin-bottom:4px;">
                        <?= e($announcements[0]['title']) ?>
                    </div>
                    <div style="font-size:12px;color:var(--gray);">
                        <?= e(substr($announcements[0]['content'],0,80)) ?>...
                    </div>
                </div>
            <?php endif; ?>

            <a href="/login.php" class="btn btn-primary"
               style="width:100%;justify-content:center;
               padding:13px;margin-bottom:12px;">
                Sign In →
            </a>
            <a href="/register.php" class="btn btn-secondary"
               style="width:100%;justify-content:center;padding:13px;">
                Create Account
            </a>

            <div style="text-align:center;margin-top:20px;
                font-size:11px;color:var(--gray);">
                🔒 Secured by TLS 1.3 · Fail2ban · Wazuh SIEM
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

</body>
</html>
