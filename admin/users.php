<?php
$admin_page_title = "Manage Users";
$admin_breadcrumb = "Users";
require_once __DIR__ . '/sidebar.php';

$db = get_db_connection();

$msg = null;
$error = null;

// Handle user actions (toggle role, toggle suspend, delete)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && verify_csrf_token($_POST['csrf_token'] ?? '')) {
    $action = $_POST['action'] ?? '';
    $target_uid = (int)($_POST['user_id'] ?? 0);

    if ($target_uid === (int)$admin_user['id'] && in_array($action, ['suspend', 'delete'])) {
        $error = "You cannot suspend or delete your own admin account.";
    } elseif ($target_uid > 0) {
        if ($action === 'toggle_role') {
            $new_role = ($_POST['role'] === 'admin') ? 'user' : 'admin';
            $stmt = $db->prepare("UPDATE users SET role = :r WHERE id = :id");
            $stmt->execute([':r' => $new_role, ':id' => $target_uid]);
            $msg = "User role updated to " . ucfirst($new_role) . ".";
        } elseif ($action === 'toggle_status') {
            $new_status = ($_POST['status'] === 'active') ? 'suspended' : 'active';
            $stmt = $db->prepare("UPDATE users SET status = :s WHERE id = :id");
            $stmt->execute([':s' => $new_status, ':id' => $target_uid]);
            $msg = "User status updated to " . ucfirst($new_status) . ".";
        } elseif ($action === 'delete') {
            $stmt = $db->prepare("DELETE FROM users WHERE id = :id");
            $stmt->execute([':id' => $target_uid]);
            $msg = "User account deleted.";
        }
    }
}

// Fetch users
$users = $db->query("SELECT * FROM users ORDER BY created_at DESC")->fetchAll();
?>

<div style="display:flex; align-items:flex-end; justify-content:space-between; margin-bottom: 24px;">
    <div>
        <div style="font-size: 11px; font-weight: 700; color: var(--color-primary); letter-spacing: 0.1em; text-transform: uppercase; margin-bottom: 6px;">
            USER MANAGEMENT
        </div>
        <h1 style="font-size: 32px; font-weight: 800; letter-spacing: -0.02em; margin-bottom: 4px;">
            Campus Users
        </h1>
        <p style="font-size: 14px; color: var(--text-secondary);">
            Manage registered student and staff accounts, permissions and access.
        </p>
    </div>
</div>

<?php if ($msg): ?>
    <div class="status-alert-box success" style="margin-bottom: 20px;">
        <svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><path d="m9 12 2 2 4-4"/></svg>
        <div><?= e($msg) ?></div>
    </div>
<?php endif; ?>

<?php if ($error): ?>
    <div class="status-alert-box danger" style="margin-bottom: 20px;">
        <svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><line x1="12" x2="12" y1="8" y2="12"/><line x1="12" x2="12.01" y1="16" y2="16"/></svg>
        <div><?= e($error) ?></div>
    </div>
<?php endif; ?>

<div class="info-card-box" style="padding:0; overflow:hidden;">
    <table class="data-table">
        <thead>
            <tr>
                <th>USER</th>
                <th>FACULTY / PHONE</th>
                <th>ROLE</th>
                <th>STATUS</th>
                <th>JOINED</th>
                <th style="text-align:right;">ACTIONS</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($users as $u): ?>
                <tr>
                    <td>
                        <div style="display:flex; align-items:center; gap:10px;">
                            <?= render_user_avatar($u, 32) ?>
                            <div>
                                <strong style="font-size:14px;"><?= e($u['name']) ?></strong>
                                <div style="font-size:12px; color:var(--text-muted);"><?= e($u['email']) ?></div>
                            </div>
                        </div>
                    </td>
                    <td>
                        <div style="font-size:13px;"><?= e($u['faculty'] ?: 'Taylor\'s University') ?></div>
                        <div style="font-size:12px; color:var(--text-muted);"><?= e($u['phone'] ?: 'No phone') ?></div>
                    </td>
                    <td>
                        <form method="POST" action="/admin/users.php" style="display:inline;">
                            <?= csrf_field() ?>
                            <input type="hidden" name="action" value="toggle_role">
                            <input type="hidden" name="user_id" value="<?= $u['id'] ?>">
                            <input type="hidden" name="role" value="<?= $u['role'] ?>">
                            <button type="submit" class="badge" style="cursor:pointer; border:none; background: <?= $u['role'] === 'admin' ? '#fee2e2' : '#f3f4f6' ?>; color: <?= $u['role'] === 'admin' ? 'var(--color-primary)' : 'var(--text-main)' ?>;">
                                <?= ucfirst($u['role']) ?> ⇄
                            </button>
                        </form>
                    </td>
                    <td>
                        <form method="POST" action="/admin/users.php" style="display:inline;">
                            <?= csrf_field() ?>
                            <input type="hidden" name="action" value="toggle_status">
                            <input type="hidden" name="user_id" value="<?= $u['id'] ?>">
                            <input type="hidden" name="status" value="<?= $u['status'] ?>">
                            <button type="submit" class="badge <?= $u['status'] === 'active' ? 'badge-open' : 'badge-full' ?>" style="cursor:pointer; border:none;">
                                <?= ucfirst($u['status']) ?> ⇄
                            </button>
                        </form>
                    </td>
                    <td style="font-size:12.5px; color:var(--text-muted);">
                        <?= e(date('j M Y', strtotime($u['created_at']))) ?>
                    </td>
                    <td style="text-align:right;">
                        <?php if ($u['id'] !== $admin_user['id']): ?>
                            <form method="POST" action="/admin/users.php" onsubmit="return confirm('Permanently delete user account?');" style="display:inline;">
                                <?= csrf_field() ?>
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="user_id" value="<?= $u['id'] ?>">
                                <button type="submit" style="background:none; border:none; color:#dc2626; cursor:pointer; font-size:12.5px; font-weight:600;">
                                    Delete
                                </button>
                            </form>
                        <?php else: ?>
                            <span style="font-size:12px; color:var(--text-muted);">Current user</span>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<?php require_once __DIR__ . '/footer.php'; ?>
