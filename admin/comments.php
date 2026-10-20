<?php
$admin_page_title = "Moderate Comments";
$admin_breadcrumb = "Comments";
require_once __DIR__ . '/sidebar.php';

$db = get_db_connection();

$msg = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST' && verify_csrf_token($_POST['csrf_token'] ?? '')) {
    $comment_id = (int)($_POST['comment_id'] ?? 0);
    if ($comment_id > 0) {
        $stmt = $db->prepare("DELETE FROM comments WHERE id = :id");
        $stmt->execute([':id' => $comment_id]);
        $msg = "Comment deleted by administrator.";
    }
}

// Fetch all comments
$comments = $db->query("
    SELECT c.*, u.name as user_name, u.email as user_email, e.title as event_title, e.id as event_id
    FROM comments c
    JOIN users u ON c.user_id = u.id
    JOIN events e ON c.event_id = e.id
    ORDER BY c.created_at DESC
")->fetchAll();
?>

<div style="display:flex; align-items:flex-end; justify-content:space-between; margin-bottom: 24px;">
    <div>
        <div style="font-size: 11px; font-weight: 700; color: var(--color-primary); letter-spacing: 0.1em; text-transform: uppercase; margin-bottom: 6px;">
            COMMUNITY SAFETY & FEEDBACK
        </div>
        <h1 style="font-size: 32px; font-weight: 800; letter-spacing: -0.02em; margin-bottom: 4px;">
            Moderate Comments
        </h1>
        <p style="font-size: 14px; color: var(--text-secondary);">
            Review participant feedback and ratings. Remove spam or inappropriate remarks.
        </p>
    </div>
</div>

<?php if ($msg): ?>
    <div class="status-alert-box success" style="margin-bottom: 20px;">
        <svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><path d="m9 12 2 2 4-4"/></svg>
        <div><?= e($msg) ?></div>
    </div>
<?php endif; ?>

<div class="info-card-box" style="padding:0; overflow:hidden;">
    <table class="data-table">
        <thead>
            <tr>
                <th>AUTHOR</th>
                <th>EVENT</th>
                <th>RATING</th>
                <th>COMMENT</th>
                <th>POSTED</th>
                <th style="text-align:right;">ACTIONS</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($comments)): ?>
                <tr>
                    <td colspan="6" style="text-align:center; padding:32px; color:var(--text-muted);">
                        No feedback posted yet.
                    </td>
                </tr>
            <?php else: ?>
                <?php foreach ($comments as $c): ?>
                    <tr>
                        <td>
                            <strong style="font-size:14px;"><?= e($c['user_name']) ?></strong>
                            <div style="font-size:12px; color:var(--text-muted);"><?= e($c['user_email']) ?></div>
                        </td>
                        <td>
                            <a href="<?= APP_BASE_PATH ?>/event-detail.php?id=<?= $c['event_id'] ?>" style="color:var(--color-primary); font-weight:600;">
                                <?= e($c['event_title']) ?>
                            </a>
                        </td>
                        <td>
                            <span style="color:#c81e27; font-weight:700;">
                                <?= str_repeat('★', (int)$c['rating']) ?>
                            </span>
                        </td>
                        <td style="max-width:340px; font-size:13.5px; line-height:1.4;">
                            <?= e($c['comment']) ?>
                        </td>
                        <td style="font-size:12.5px; color:var(--text-muted);">
                            <?= e(date('j M Y', strtotime($c['created_at']))) ?>
                        </td>
                        <td style="text-align:right;">
                            <form method="POST" action="<?= APP_BASE_PATH ?>/admin/comments.php" onsubmit="return confirm('Remove this comment?');" style="display:inline;">
                                <?= csrf_field() ?>
                                <input type="hidden" name="comment_id" value="<?= $c['id'] ?>">
                                <button type="submit" style="background:none; border:none; color:#dc2626; cursor:pointer; font-size:12.5px; font-weight:600;">
                                    Delete
                                </button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<?php require_once __DIR__ . '/footer.php'; ?>
