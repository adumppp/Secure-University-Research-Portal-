<?php
$currentPage = basename($_SERVER['PHP_SELF']);
$currentDir  = basename(dirname($_SERVER['PHP_SELF']));
?>
<aside class="sidebar">
    <div class="sidebar-brand">
        <a href="/index.php" class="brand-logo">
            <div class="brand-icon">🔬</div>
            <div>
                <span class="brand-name">ResearchPortal</span>
                <span class="brand-sub">University Collaboration</span>
            </div>
        </a>
    </div>

    <div class="sidebar-user">
        <div class="avatar">
            <?= strtoupper(substr($user['username'],0,1)) ?>
        </div>
        <div class="user-info">
            <div class="user-name"><?= e($user['username']) ?></div>
            <div class="user-role role-<?= e($user['role']) ?>">
                <?= ucfirst($user['role']) ?>
            </div>
        </div>
    </div>

    <nav class="sidebar-nav">

        <!-- MAIN -->
        <div class="nav-section">
            <div class="nav-section-label">Main</div>

            <a href="/index.php"
               class="nav-item <?= ($currentPage==='index.php' && $currentDir!=='admin') ?'active':'' ?>">
                <span class="nav-icon">🏠</span>
                Home
            </a>

            <a href="/dashboard.php"
               class="nav-item <?= $currentPage==='dashboard.php'?'active':'' ?>">
                <span class="nav-icon">📊</span>
                Dashboard
            </a>

            <a href="/search.php"
               class="nav-item <?= $currentPage==='search.php'?'active':'' ?>">
                <span class="nav-icon">🔍</span>
                Search Research
            </a>
        </div>

        <!-- RESEARCH — researcher + admin only -->
        <?php if (in_array($user['role'],['admin','researcher'])): ?>
        <div class="nav-section">
            <div class="nav-section-label">Research</div>

            <?php if ($user['role']==='researcher'): ?>
            <a href="/my-research.php"
               class="nav-item <?= $currentPage==='my-research.php'?'active':'' ?>">
                <span class="nav-icon">📚</span>
                My Research
            </a>
            <?php endif; ?>

            <a href="/upload.php"
               class="nav-item <?= $currentPage==='upload.php'?'active':'' ?>">
                <span class="nav-icon">📤</span>
                Upload Research
            </a>

            <a href="/confidential.php"
               class="nav-item confidential <?= $currentPage==='confidential.php'?'active':'' ?>">
                <span class="nav-icon">🔴</span>
                Confidential
            </a>
        </div>
        <?php endif; ?>

        <!-- ADMIN only -->
        <?php if ($user['role']==='admin'): ?>
        <div class="nav-section">
            <div class="nav-section-label">Administration</div>

            <a href="/admin/index.php"
               class="nav-item <?= ($currentPage==='index.php' && $currentDir==='admin')?'active':'' ?>">
                <span class="nav-icon">⚙️</span>
                Admin Panel
            </a>

            <a href="/admin/users.php"
               class="nav-item <?= $currentPage==='users.php'?'active':'' ?>">
                <span class="nav-icon">👥</span>
                Manage Users
            </a>

            <a href="/admin/proposals.php"
               class="nav-item <?= $currentPage==='proposals.php'?'active':'' ?>">
                <span class="nav-icon">📋</span>
                Manage Proposals
            </a>
        </div>
        <?php endif; ?>

    </nav>

    <div class="sidebar-footer">
        <a href="/logout.php" class="logout-btn">
            <span class="nav-icon">🚪</span>
            Logout
        </a>
    </div>
</aside>
