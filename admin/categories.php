<?php
$admin_page_title = "Manage Categories";
$admin_breadcrumb = "Categories";
require_once __DIR__ . '/sidebar.php';

$db = get_db_connection();

$msg = null;
$error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && verify_csrf_token($_POST['csrf_token'] ?? '')) {
    $action = $_POST['action'] ?? '';

    if ($action === 'add') {
        $name = trim($_POST['name'] ?? '');
        $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $name)));
        $icon = trim($_POST['icon'] ?? 'calendar');

        if (!empty($name)) {
            try {
                $stmt = $db->prepare("INSERT INTO categories (name, slug, icon) VALUES (:n, :s, :i)");
                $stmt->execute([':n' => $name, ':s' => $slug, ':i' => $icon]);
                $msg = "Category added successfully.";
            } catch (PDOException $e) {
                $error = "Category already exists.";
            }
        }
    } elseif ($action === 'delete') {
        $id = (int)($_POST['id'] ?? 0);
        if ($id > 0) {
            $stmt = $db->prepare("DELETE FROM categories WHERE id = :id");
            $stmt->execute([':id' => $id]);
            $msg = "Category deleted.";
        }
    }
}

$categories = $db->query("
    SELECT c.*, COUNT(e.id) as event_count 
    FROM categories c 
    LEFT JOIN events e ON c.id = e.category_id 
    GROUP BY c.id 
    ORDER BY c.id ASC
")->fetchAll();
?>

<div style="display:flex; align-items:flex-end; justify-content:space-between; margin-bottom: 24px;">
    <div>
        <div style="font-size: 11px; font-weight: 700; color: var(--color-primary); letter-spacing: 0.1em; text-transform: uppercase; margin-bottom: 6px;">
            EVENT TAXONOMY
        </div>
        <h1 style="font-size: 32px; font-weight: 800; letter-spacing: -0.02em; margin-bottom: 4px;">
            Categories
        </h1>
        <p style="font-size: 14px; color: var(--text-secondary);">
            Organise campus events into relevant discovery categories.
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

<div style="display:grid; grid-template-columns: 1fr 340px; gap: 32px; align-items:start;">
    <!-- Category Table -->
    <div class="info-card-box" style="padding:0; overflow:hidden;">
        <table class="data-table">
            <thead>
                <tr>
                    <th>NAME</th>
                    <th>SLUG</th>
                    <th>EVENTS COUNT</th>
                    <th style="text-align:right;">ACTIONS</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($categories as $c): ?>
                    <tr>
                        <td>
                            <strong style="font-size:14px;"><?= e($c['name']) ?></strong>
                        </td>
                        <td>
                            <code style="font-size:12px; background:#f3f4f6; padding:2px 6px; border-radius:4px;"><?= e($c['slug']) ?></code>
                        </td>
                        <td>
                            <?= e($c['event_count']) ?> events
                        </td>
                        <td style="text-align:right;">
                            <?php if ($c['event_count'] == 0): ?>
                                <form method="POST" action="/admin/categories.php" onsubmit="return confirm('Delete category?');" style="display:inline;">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="id" value="<?= $c['id'] ?>">
                                    <button type="submit" style="background:none; border:none; color:#dc2626; cursor:pointer; font-size:12.5px; font-weight:600;">
                                        Delete
                                    </button>
                                </form>
                            <?php else: ?>
                                <span style="font-size:12px; color:var(--text-muted);">In use</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <!-- Add Category Card -->
    <div class="info-card-box">
        <h3 style="font-size: 18px; font-weight: 800; margin-bottom: 16px;">Add category</h3>
        <form method="POST" action="/admin/categories.php">
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="add">

            <div class="form-group" style="margin-bottom: 16px;">
                <label class="form-label">Category Name</label>
                <input type="text" name="name" class="form-input" required placeholder="e.g. Sports & Fitness">
            </div>

            <div class="form-group" style="margin-bottom: 20px;">
                <label class="form-label">Icon identifier</label>
                <input type="text" name="icon" class="form-input" value="calendar" placeholder="e.g. calendar, heart, code">
            </div>

            <button type="submit" class="btn-primary" style="width:100%; justify-content:center;">
                Create category
            </button>
        </form>
    </div>
</div>

<?php require_once __DIR__ . '/footer.php'; ?>
