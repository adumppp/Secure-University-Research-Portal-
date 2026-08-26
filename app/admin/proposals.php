<?php
require '../includes/db.php';
require '../includes/auth.php';
require '../includes/functions.php';

requireAdmin();
$user = currentUser();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $pid    = (int)$_POST['proposal_id'];
    $status = $_POST['status'];

    if (in_array($status,
        ['approved','rejected','pending'])) {
        $stmt = $pdo->prepare(
            "UPDATE proposals SET status=? WHERE id=?");
        $stmt->execute([$status, $pid]);
        setFlash('success', 'Proposal status updated.');
    }
    header("Location: /admin/proposals.php");
    exit();
}

$proposals = $pdo->query(
    "SELECT p.*, u.username FROM proposals p
     JOIN users u ON p.researcher_id = u.id
     ORDER BY p.submitted_at DESC"
)->fetchAll();

$_SERVER['PHP_SELF'] = '/admin/proposals.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport"
          content="width=device-width, initial-scale=1.0">
    <title>Manage Proposals — ResearchPortal</title>
    <link rel="stylesheet" href="/assets/style.css">
</head>
<body>
<div class="app-layout">
    <?php require '../includes/nav.php'; ?>

    <div class="main-content">
        <div class="topbar">
            <div class="topbar-title">
                <h1>📋 Proposal Management</h1>
                <p><?= count($proposals) ?> total proposals</p>
            </div>
        </div>

        <div class="page-content">
            <?php showFlash(); ?>

            <div class="card">
                <div class="card-header">
                    <h2>All Proposals</h2>
                </div>
                <div class="table-container">
                    <table>
                        <thead>
                            <tr>
                                <th>Title</th>
                                <th>Researcher</th>
                                <th>Classification</th>
                                <th>Current Status</th>
                                <th>Submitted</th>
                                <th>Update Status</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($proposals as $p): ?>
                            <tr>
                                <td style="font-weight:500;
                                    max-width:260px;">
                                    <?= e($p['title']) ?>
                                </td>
                                <td>
                                    <?= e($p['username']) ?>
                                </td>
                                <td>
                                    <span class="badge
                                        badge-<?=
                                        $p['classification'] ?>">
                                        <?= strtoupper(
                                        $p['classification']) ?>
                                    </span>
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
                                <td>
                                    <form method="POST"
                                          style="display:flex;
                                          gap:6px;">
                                        <input type="hidden"
                                               name="proposal_id"
                                               value="<?= $p['id'] ?>">
                                        <select name="status"
                                            style="font-size:12px;
                                            padding:4px 8px;
                                            border-radius:6px;
                                            border:1px solid
                                            var(--border);">
                                            <option value="pending"
                                                <?= $p['status']==='pending'
                                                ?'selected':'' ?>>
                                                Pending
                                            </option>
                                            <option value="approved"
                                                <?= $p['status']==='approved'
                                                ?'selected':'' ?>>
                                                Approved
                                            </option>
                                            <option value="rejected"
                                                <?= $p['status']==='rejected'
                                                ?'selected':'' ?>>
                                                Rejected
                                            </option>
                                        </select>
                                        <button type="submit"
                                            class="btn btn-primary btn-sm">
                                            Save
                                        </button>
                                    </form>
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
