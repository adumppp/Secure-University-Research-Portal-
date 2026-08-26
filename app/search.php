<?php
require 'includes/db.php';
require 'includes/auth.php';
require 'includes/functions.php';

requireAuth();
$user = currentUser();

$q      = trim($_GET['q'] ?? '');
$filter = $_GET['filter'] ?? 'all';

if (!empty($q)) {
    if ($user['role'] === 'admin') {
        $stmt = $pdo->prepare(
            "SELECT p.*, u.username FROM proposals p
             JOIN users u ON p.researcher_id = u.id
             WHERE p.title LIKE ?
             ORDER BY p.submitted_at DESC"
        );
        $stmt->execute(['%'.$q.'%']);
    } else {
        $stmt = $pdo->prepare(
            "SELECT p.*, u.username FROM proposals p
             JOIN users u ON p.researcher_id = u.id
             WHERE p.title LIKE ?
             AND (p.classification = 'public'
             OR (p.classification = 'confidential'
                 AND p.researcher_id = ?))
             ORDER BY p.submitted_at DESC"
        );
        $stmt->execute(['%'.$q.'%', $user['id']]);
    }
    $results = $stmt->fetchAll();

    // Apply filter
    if ($filter !== 'all') {
        $results = array_filter($results,
            function($r) use ($filter) {
                if ($filter === 'public')
                    return $r['classification']==='public';
                if ($filter === 'confidential')
                    return $r['classification']==='confidential';
                return $r['status'] === $filter;
            }
        );
    }
} else {
    // Recent research
    if ($user['role'] === 'admin') {
        $stmt = $pdo->query(
            "SELECT p.*, u.username FROM proposals p
             JOIN users u ON p.researcher_id=u.id
             ORDER BY p.submitted_at DESC LIMIT 12"
        );
    } else {
        $stmt = $pdo->prepare(
            "SELECT p.*, u.username FROM proposals p
             JOIN users u ON p.researcher_id=u.id
             WHERE p.classification='public'
             OR (p.classification='confidential'
                 AND p.researcher_id=?)
             ORDER BY p.submitted_at DESC LIMIT 12"
        );
        $stmt->execute([$user['id']]);
    }
    $results = $stmt->fetchAll();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Search Research — ResearchPortal</title>
    <link rel="stylesheet" href="/assets/style.css">
</head>
<body>
<div class="app-layout">
    <?php require 'includes/nav.php'; ?>

    <div class="main-content">
        <div class="topbar">
            <div class="topbar-title">
                <h1>🔍 Search Research</h1>
                <p>Find proposals and research documents</p>
            </div>
        </div>

        <div class="page-content">

            <!-- Search + filters card -->
            <div class="card">
                <form method="GET" action="/search.php">
                    <div style="display:flex;gap:10px;margin-bottom:16px;">
                        <div style="flex:1;position:relative;">
                            <span style="position:absolute;left:14px;
                                top:50%;transform:translateY(-50%);
                                font-size:16px;">🔍</span>
                            <input type="text" name="q"
                                   value="<?= e($q) ?>"
                                   placeholder="Search research titles, topics, researchers..."
                                   style="padding-left:42px;width:100%;
                                   padding-top:12px;padding-bottom:12px;
                                   font-size:15px;"
                                   autofocus>
                        </div>
                        <button type="submit"
                                class="btn btn-primary"
                                style="padding:12px 28px;">
                            Search
                        </button>
                    </div>

                    <!-- Filter pills -->
                    <div style="display:flex;gap:8px;flex-wrap:wrap;">
                        <?php
                        $filters = [
                            'all'      => ['All', '🔭'],
                            'public'   => ['Public', '🟢'],
                            'approved' => ['Approved', '✅'],
                            'pending'  => ['Pending', '⏳'],
                        ];
                        if (in_array($user['role'],['admin','researcher'])) {
                            $filters['confidential'] = ['Confidential','🔴'];
                        }
                        foreach ($filters as $val => [$lbl, $icon]):
                            $active = $filter === $val;
                        ?>
                            <a href="?q=<?= urlencode($q) ?>&filter=<?= $val ?>"
                               style="padding:7px 16px;border-radius:20px;
                               font-size:12px;font-weight:600;
                               text-decoration:none;transition:all 0.2s;
                               background:<?= $active?'var(--primary)':'var(--bg)' ?>;
                               color:<?= $active?'white':'var(--gray)' ?>;
                               border:1px solid <?= $active?'var(--primary)':'var(--border)' ?>;">
                                <?= $icon ?> <?= $lbl ?>
                            </a>
                        <?php endforeach; ?>
                    </div>
                </form>
            </div>

            <!-- Results count -->
            <div style="margin-bottom:16px;font-size:14px;
                color:var(--gray);display:flex;
                align-items:center;justify-content:space-between;">
                <span>
                    <?php if (!empty($q)): ?>
                        <strong><?= count($results) ?></strong>
                        results for "<strong><?= e($q) ?></strong>"
                    <?php else: ?>
                        <strong><?= count($results) ?></strong>
                        recent research papers
                    <?php endif; ?>
                </span>
            </div>

            <!-- Results grid -->
            <?php if (empty($results)): ?>
                <div class="card">
                    <div class="empty-state">
                        <div class="empty-icon">🔍</div>
                        <p>No research found<?= !empty($q)
                            ? ' for "'.e($q).'"' : '' ?>.</p>
                        <?php if (!empty($q)): ?>
                            <a href="/search.php"
                               class="btn btn-secondary btn-sm">
                                Clear Search
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
            <?php else: ?>
                <div style="display:grid;
                    grid-template-columns:repeat(auto-fill,minmax(340px,1fr));
                    gap:16px;">
                    <?php foreach ($results as $r): ?>
                        <a href="/research.php?id=<?= $r['id'] ?>"
                           style="text-decoration:none;">
                            <div style="background:white;
                                border:1px solid var(--border);
                                border-radius:14px;padding:20px;
                                height:100%;transition:all 0.2s;
                                cursor:pointer;
                                <?= $r['classification']==='confidential'
                                ?'border-left:3px solid var(--danger);':'' ?>"
                                onmouseover="this.style.transform='translateY(-2px)';this.style.boxShadow='0 8px 24px rgba(0,0,0,0.08)';this.style.borderColor='var(--primary)'"
                                onmouseout="this.style.transform='';this.style.boxShadow='';this.style.borderColor='<?= $r['classification']==='confidential'?'#ef4444':'var(--border)' ?>'">

                                <!-- Badges -->
                                <div style="display:flex;gap:6px;
                                    margin-bottom:12px;flex-wrap:wrap;">
                                    <?php if ($r['classification']==='confidential'): ?>
                                        <span class="badge badge-confidential"
                                              style="display:inline-flex;
                                              align-items:center;gap:3px;">
                                            🔴 CONFIDENTIAL
                                        </span>
                                    <?php else: ?>
                                        <span class="badge badge-public">
                                            🟢 PUBLIC
                                        </span>
                                    <?php endif; ?>
                                    <span class="badge badge-<?= $r['status'] ?>">
                                        <?= ucfirst($r['status']) ?>
                                    </span>
                                </div>

                                <!-- Title -->
                                <h3 style="font-size:14px;
                                    font-weight:700;
                                    color:var(--dark);
                                    line-height:1.4;
                                    margin-bottom:10px;
                                    display:-webkit-box;
                                    -webkit-line-clamp:2;
                                    -webkit-box-orient:vertical;
                                    overflow:hidden;">
                                    <?= e($r['title']) ?>
                                </h3>

                                <!-- Meta -->
                                <div style="font-size:12px;
                                    color:var(--gray);
                                    display:flex;gap:12px;
                                    flex-wrap:wrap;margin-top:auto;
                                    padding-top:12px;
                                    border-top:1px solid var(--border);">
                                    <span>👤 <?= e($r['username']) ?></span>
                                    <span>📅 <?= formatDate($r['submitted_at']) ?></span>
                                </div>
                            </div>
                        </a>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

        </div>
    </div>
</div>
</body>
</html>
