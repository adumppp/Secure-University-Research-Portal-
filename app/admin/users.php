<?php
require '../includes/db.php';
require '../includes/auth.php';
require '../includes/functions.php';

requireAdmin();
$user = currentUser();

// Handle role update
if ($_SERVER['REQUEST_METHOD'] === 'POST' &&
    ($_POST['action'] ?? '') === 'update_role') {
    $uid     = (int)$_POST['user_id'];
    $newRole = $_POST['new_role'];

    if (in_array($newRole,
        ['admin','researcher','guest']) &&
        $uid !== $user['id']) {
        $stmt = $pdo->prepare(
            "UPDATE users SET role=? WHERE id=?");
        $stmt->execute([$newRole, $uid]);
        setFlash('success', 'User role updated.');
    }
    header("Location: /admin/users.php");
    exit();
}

// Handle delete
if ($_SERVER['REQUEST_METHOD'] === 'POST' &&
    ($_POST['action'] ?? '') === 'delete') {
    $uid = (int)$_POST['user_id'];
    if ($uid !== $user['id']) {
        $stmt = $pdo->prepare(
            "DELETE FROM users WHERE id=?");
        $stmt->execute([$uid]);
        setFlash('success', 'User deleted.');
    } else {
        setFlash('error', 'Cannot delete your own account.');
    }
    header("Location: /admin/users.php");
    exit();
}

$users = $pdo->query(
    "SELECT id, username, email, role, created_at
     FROM users ORDER BY created_at DESC"
)->fetchAll();

$_SERVER['PHP_SELF'] = '/admin/users.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport"
          content="width=device-width, initial-scale=1.0">
    <title>Manage Users — ResearchPortal</title>
    <link rel="stylesheet" href="/assets/style.css">
</head>
<body>
<div class="app-layout">
    <?php require '../includes/nav.php'; ?>

    <div class="main-content">
        <div class="topbar">
            <div class="topbar-title">
                <h1>👥 User Management</h1>
                <p><?= count($users) ?> registered users</p>
            </div>
        </div>

        <div class="page-content">
            <?php showFlash(); ?>

            <div class="card">
                <div class="card-header">
                    <h2>All Users</h2>
                </div>
                <div class="table-container">
                    <table>
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Username</th>
                                <th>Email</th>
                                <th>Role</th>
                                <th>Registered</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($users as $u): ?>
                            <tr>
                                <td style="color:var(--gray);">
                                    #<?= $u['id'] ?>
                                </td>
                                <td>
                                    <div style="display:flex;
                                        align-items:center;gap:8px;">
                                        <div style="width:32px;
                                            height:32px;
                                            border-radius:50%;
                                            background:var(--primary);
                                            display:flex;
                                            align-items:center;
                                            justify-content:center;
                                            color:white;
                                            font-weight:700;
                                            font-size:12px;
                                            flex-shrink:0;">
                                            <?= strtoupper(
                                            substr($u['username'],0,1)) ?>
                                        </div>
                                        <strong>
                                            <?= e($u['username']) ?>
                                        </strong>
                                    </div>
                                </td>
                                <td style="color:var(--gray);">
                                    <?= e($u['email']) ?>
                                </td>
                                <td>
                                    <?= roleBadge($u['role']) ?>
                                </td>
                                <td style="color:var(--gray);
                                    font-size:12px;">
                                    <?= formatDate(
                                    $u['created_at']) ?>
                                </td>
                                <td>
                                    <?php if ($u['id'] !==
                                        $user['id']): ?>
                                    <div style="display:flex;
                                        gap:6px;flex-wrap:wrap;">
                                        <form method="POST"
                                              style="display:flex;
                                              gap:4px;">
                                            <input type="hidden"
                                                   name="action"
                                                   value="update_role">
                                            <input type="hidden"
                                                   name="user_id"
                                                   value="<?= $u['id'] ?>">
                                            <select name="new_role"
                                                style="font-size:12px;
                                                padding:4px 8px;
                                                border-radius:6px;
                                                border:1px solid
                                                var(--border);">
                                                <option value="admin"
                                                    <?= $u['role']==='admin'
                                                    ?'selected':'' ?>>
                                                    Admin
                                                </option>
                                                <option value="researcher"
                                                    <?= $u['role']==='researcher'
                                                    ?'selected':'' ?>>
                                                    Researcher
                                                </option>
                                                <option value="guest"
                                                    <?= $u['role']==='guest'
                                                    ?'selected':'' ?>>
                                                    Guest
                                                </option>
                                            </select>
                                            <button type="submit"
                                                class="btn btn-success btn-sm">
                                                Update
                                            </button>
                                        </form>
                                        <form method="POST"
                                              onsubmit="return confirm(
                                              'Delete this user?');">
                                            <input type="hidden"
                                                   name="action"
                                                   value="delete">
                                            <input type="hidden"
                                                   name="user_id"
                                                   value="<?= $u['id'] ?>">
                                            <button type="submit"
                                                class="btn btn-danger btn-sm">
                                                Delete
                                            </button>
                                        </form>
                                    </div>
                                    <?php else: ?>
                                        <span style="font-size:12px;
                                            color:var(--gray);">
                                            (You)
                                        </span>
                                    <?php endif; ?>
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
