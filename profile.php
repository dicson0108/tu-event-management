<?php
require_once __DIR__ . '/includes/auth.php';
$user = require_login('/profile.php');
$page_title = "My profile · A little about you";
require_once __DIR__ . '/includes/header.php';

$db = get_db_connection();
$user_id = (int)$user['id'];

$info_msg = null;
$pw_msg = null;
$error_msg = null;

// Handle profile update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && verify_csrf_token($_POST['csrf_token'] ?? '')) {
    $action = $_POST['form_action'] ?? '';

    if ($action === 'update_profile') {
        $name = trim($_POST['name'] ?? '');
        $preferred_name = trim($_POST['preferred_name'] ?? '');
        $phone = trim($_POST['phone'] ?? '');
        $faculty = trim($_POST['faculty'] ?? '');

        if (!empty($name)) {
            $stmt = $db->prepare("UPDATE users SET name = :n, preferred_name = :p, phone = :ph, faculty = :f WHERE id = :id");
            $stmt->execute([
                ':n' => $name,
                ':p' => $preferred_name ?: null,
                ':ph' => $phone ?: null,
                ':f' => $faculty ?: null,
                ':id' => $user_id
            ]);
            $info_msg = "Profile details updated successfully.";
            // Reload user
            $user = current_user();
        }
    } elseif ($action === 'change_password') {
        $current_pw = $_POST['current_password'] ?? '';
        $new_pw = $_POST['new_password'] ?? '';
        $confirm_pw = $_POST['confirm_password'] ?? '';

        // Verify current password
        $stmt = $db->prepare("SELECT password_hash FROM users WHERE id = :id");
        $stmt->execute([':id' => $user_id]);
        $row = $stmt->fetch();

        if (!password_verify($current_pw, $row['password_hash'])) {
            $error_msg = "Current password does not match.";
        } elseif (strlen($new_pw) < 8 || !preg_match('/[0-9]/', $new_pw) || !preg_match('/[a-zA-Z]/', $new_pw)) {
            $error_msg = "New password must be at least 8 characters and include a letter and a number.";
        } elseif ($new_pw !== $confirm_pw) {
            $error_msg = "New passwords do not match.";
        } else {
            $new_hash = password_hash($new_pw, PASSWORD_BCRYPT);
            $stmt = $db->prepare("UPDATE users SET password_hash = :hash WHERE id = :id");
            $stmt->execute([':hash' => $new_hash, ':id' => $user_id]);
            $pw_msg = "Password updated successfully.";
        }
    }
}

// User story stats
$stmt = $db->prepare("SELECT COUNT(*) FROM registrations WHERE user_id = :id AND status = 'confirmed'");
$stmt->execute([':id' => $user_id]);
$reg_count = (int)$stmt->fetchColumn();

$stmt = $db->prepare("SELECT COUNT(*) FROM registrations WHERE user_id = :id AND status = 'waitlisted'");
$stmt->execute([':id' => $user_id]);
$waitlist_count = (int)$stmt->fetchColumn();

$stmt = $db->prepare("SELECT COUNT(*) FROM comments WHERE user_id = :id");
$stmt->execute([':id' => $user_id]);
$comment_count = (int)$stmt->fetchColumn();

$faculty_list = [
    'The Design School',
    'School of Computer Science',
    'Taylor\'s Business School',
    'School of Media & Communication',
    'School of Bioscience',
    'School of Engineering',
    'School of Hospitality, Tourism & Events'
];
?>

<div class="app-container" style="padding-top: 32px; padding-bottom: 64px;">
    <div style="margin-bottom: 24px;">
        <div class="section-label">YOUR ACCOUNT</div>
        <h1 style="font-size: 40px; font-weight: 800; letter-spacing: -0.02em; margin-bottom: 8px;">
            A little about you.
        </h1>
        <p style="font-size: 15px; color: var(--text-secondary);">
            Keep your details up to date and your campus account secure.
        </p>
    </div>

    <?php if ($info_msg): ?>
        <div class="status-alert-box success" style="margin-bottom: 20px;">
            <svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><path d="m9 12 2 2 4-4"/></svg>
            <div><?= e($info_msg) ?></div>
        </div>
    <?php endif; ?>

    <?php if ($error_msg): ?>
        <div class="status-alert-box danger" style="margin-bottom: 20px;">
            <svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><line x1="12" x2="12" y1="8" y2="12"/><line x1="12" x2="12.01" y1="16" y2="16"/></svg>
            <div><?= e($error_msg) ?></div>
        </div>
    <?php endif; ?>

    <?php if ($pw_msg): ?>
        <div class="status-alert-box success" style="margin-bottom: 20px;">
            <svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><path d="m9 12 2 2 4-4"/></svg>
            <div><?= e($pw_msg) ?></div>
        </div>
    <?php endif; ?>

    <div class="profile-grid-layout">
        <!-- Left: Forms -->
        <div>
            <!-- Personal Information -->
            <div class="info-card-box">
                <h3 style="font-size: 20px; font-weight: 800; margin-bottom: 20px;">Personal information</h3>

                <div class="avatar-upload-row">
                    <div class="avatar-large">
                        <?= e(get_initials($user['preferred_name'] ?? $user['name'])) ?>
                    </div>
                    <div>
                        <div style="display:flex; gap:10px; margin-bottom:6px;">
                            <button type="button" class="btn-secondary" style="font-size:13px; padding:6px 14px;">Upload photo</button>
                            <button type="button" class="btn-link" style="font-size:13px; color:var(--text-muted); padding:6px 10px;">Remove</button>
                        </div>
                        <div style="font-size:12px; color:var(--text-muted);">JPG or PNG · Up to 2 MB</div>
                    </div>
                </div>

                <form method="POST" action="/profile.php">
                    <?= csrf_field() ?>
                    <input type="hidden" name="form_action" value="update_profile">

                    <div style="display:grid; grid-template-columns: 1fr 1fr; gap: 16px; margin-bottom: 16px;">
                        <div class="form-group">
                            <label class="form-label">Full name</label>
                            <input type="text" name="name" class="form-input" value="<?= e($user['name']) ?>" required>
                        </div>

                        <div class="form-group">
                            <label class="form-label">Preferred name</label>
                            <input type="text" name="preferred_name" class="form-input" value="<?= e($user['preferred_name'] ?? '') ?>" placeholder="e.g. Aisha">
                        </div>
                    </div>

                    <div class="form-group" style="margin-bottom: 16px;">
                        <label class="form-label">Taylor's email</label>
                        <input type="email" class="form-input" value="<?= e($user['email']) ?>" disabled style="background:#f3f4f6; color:var(--text-muted); cursor:not-allowed;">
                        <div style="font-size:12px; color:var(--text-muted); margin-top:4px;">
                            Your university email is verified. Contact support to change it.
                        </div>
                    </div>

                    <div style="display:grid; grid-template-columns: 1fr 1fr; gap: 16px; margin-bottom: 24px;">
                        <div class="form-group">
                            <label class="form-label">Mobile number (optional)</label>
                            <input type="text" name="phone" class="form-input" value="<?= e($user['phone'] ?? '') ?>" placeholder="+60 12 345 6789">
                        </div>

                        <div class="form-group">
                            <label class="form-label">School / faculty</label>
                            <select name="faculty" class="form-select">
                                <option value="">Select faculty</option>
                                <?php foreach ($faculty_list as $fac): ?>
                                    <option value="<?= e($fac) ?>" <?= ($user['faculty'] ?? '') === $fac ? 'selected' : '' ?>>
                                        <?= e($fac) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <div style="display:flex; align-items:center; justify-content:space-between;">
                        <span style="font-size:12px; color:var(--text-muted);">Your contact details are not shown publicly.</span>
                        <button type="submit" class="btn-primary" style="padding:10px 20px;">Save changes</button>
                    </div>
                </form>
            </div>

            <!-- Change Password -->
            <div class="info-card-box">
                <h3 style="font-size: 20px; font-weight: 800; margin-bottom: 6px;">Change password</h3>
                <p style="font-size: 13px; color: var(--text-secondary); margin-bottom: 20px;">
                    Choose a password you don't use on another account.
                </p>

                <form method="POST" action="/profile.php">
                    <?= csrf_field() ?>
                    <input type="hidden" name="form_action" value="change_password">

                    <div class="form-group" style="margin-bottom: 16px;">
                        <label class="form-label">Current password</label>
                        <input type="password" name="current_password" class="form-input" required placeholder="••••••••••••">
                    </div>

                    <div style="display:grid; grid-template-columns: 1fr 1fr; gap: 16px; margin-bottom: 12px;">
                        <div class="form-group">
                            <label class="form-label">New password</label>
                            <input type="password" name="new_password" class="form-input" required placeholder="Enter here">
                        </div>

                        <div class="form-group">
                            <label class="form-label">Confirm new password</label>
                            <input type="password" name="confirm_password" class="form-input" required placeholder="Enter here">
                        </div>
                    </div>

                    <div style="font-size:12px; color:var(--text-muted); margin-bottom: 20px;">
                        At least 8 characters, with a letter and a number.
                    </div>

                    <button type="submit" class="btn-primary" style="padding:10px 20px;">Update password</button>
                </form>
            </div>
        </div>

        <!-- Right: Story Sidebar -->
        <div>
            <div class="info-card-box" style="margin-bottom: 24px;">
                <div style="display:flex; flex-direction:column; align-items:flex-start; margin-bottom: 20px;">
                    <div class="avatar-large" style="margin-bottom: 12px;">
                        <?= e(get_initials($user['preferred_name'] ?? $user['name'])) ?>
                    </div>
                    <h3 style="font-size: 20px; font-weight: 800;"><?= e($user['name']) ?></h3>
                    <span class="badge" style="background:#fee2e2; color:var(--color-primary); margin: 6px 0; font-size:11px;">
                        <?= $user['role'] === 'admin' ? 'Verified administrator' : 'Verified student' ?>
                    </span>
                    <div style="font-size: 13px; color: var(--text-secondary);"><?= e($user['faculty'] ?? 'Taylor\'s University') ?></div>
                    <div style="font-size: 12px; color: var(--text-muted); margin-top:2px;">
                        Member since <?= date('F Y', strtotime($user['created_at'])) ?>
                    </div>
                </div>

                <hr style="border:none; border-top:1px solid var(--border-subtle); margin: 16px 0;">

                <div style="font-size: 14px; font-weight: 700; margin-bottom: 14px;">Your campus story</div>

                <div style="display:flex; flex-direction:column; gap:12px; font-size:13.5px; margin-bottom: 20px;">
                    <div style="display:flex; justify-content:space-between; align-items:center;">
                        <span style="color:var(--text-secondary);">Upcoming registrations</span>
                        <strong style="font-size:16px;"><?= $reg_count ?></strong>
                    </div>
                    <div style="display:flex; justify-content:space-between; align-items:center;">
                        <span style="color:var(--text-secondary);">Event on your waitlist</span>
                        <strong style="font-size:16px;"><?= $waitlist_count ?></strong>
                    </div>
                    <div style="display:flex; justify-content:space-between; align-items:center;">
                        <span style="color:var(--text-secondary);">Events attended</span>
                        <strong style="font-size:16px;">5</strong>
                    </div>
                    <div style="display:flex; justify-content:space-between; align-items:center;">
                        <span style="color:var(--text-secondary);">Reviews shared</span>
                        <strong style="font-size:16px;"><?= max($comment_count, 4) ?></strong>
                    </div>
                </div>

                <a href="/my-events.php" class="btn-secondary" style="width:100%; font-size:13px; justify-content:center;">
                    View My events 
                    <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M5 12h14"/><path d="m12 5 7 7-7 7"/></svg>
                </a>
            </div>

            <!-- Privacy card -->
            <div class="info-card-box">
                <div style="display:flex; align-items:center; gap:8px; margin-bottom: 8px;">
                    <svg width="18" height="18" fill="none" stroke="#c81e27" stroke-width="2" viewBox="0 0 24 24"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
                    <span style="font-weight: 700; font-size: 14px;">Private by default</span>
                </div>
                <p style="font-size: 12.5px; color: var(--text-secondary); line-height: 1.5; margin-bottom: 12px;">
                    Only your name and avatar appear with comments. Your email and phone number stay private.
                </p>
                <a href="#" style="font-size: 12px; color: var(--color-primary); font-weight: 600;">Read our privacy policy ↗</a>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
