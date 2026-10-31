<?php
// admin/account.php
// เปลี่ยนรหัสผ่านของแอดมินที่ล็อกอินอยู่
require_once '../config.php';
requireAdmin();

$errors = [];
$success = false;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $stmt = $pdo->prepare("SELECT * FROM admins WHERE admin_id = ?");
    $stmt->execute([$_SESSION['admin_id']]);
    $admin = $stmt->fetch();
    $new = $_POST['new_password'] ?? '';
    if (!$admin || !password_verify($_POST['current_password'] ?? '', $admin['password'])) $errors[] = t('profile.err_current');
    if (strlen($new) < 12) $errors[] = t('admin.err_password_short');
    if ($new !== ($_POST['confirm_password'] ?? '')) $errors[] = t('auth.err_password_match');
    if (in_array(strtolower($new), ['password', 'password123', 'admin12345678', '123456789012'], true)) $errors[] = t('admin.err_password_weak');
    if (!$errors) {
        $pdo->prepare("UPDATE admins SET password = ? WHERE admin_id = ?")->execute([password_hash($new, PASSWORD_DEFAULT), $admin['admin_id']]);
        session_regenerate_id(true);
        $success = true;
    }
}

$adminPage = 'account';
$pageTitle = t('admin.nav_account');
include '../includes/admin_layout_top.php';
?>
<div class="card" style="max-width:560px">
    <div class="card-head"><h2><?= icon('lock', 18) ?> <?= e('profile.change_password') ?></h2></div>
    <form method="POST" class="card-body" data-once>
        <?php if ($success): ?><div class="alert alert-success mb-3" role="status"><?= icon('check-circle') ?><div><?= e('profile.password_saved') ?></div></div><?php endif; ?>
        <?php if ($errors): ?><div class="alert alert-danger mb-3" role="alert"><?= icon('alert') ?><ul><?php foreach ($errors as $err): ?><li><?= htmlspecialchars($err) ?></li><?php endforeach; ?></ul></div><?php endif; ?>
        <div class="field">
            <label class="label" for="current_password"><?= e('profile.current_password') ?></label>
            <input class="input" id="current_password" name="current_password" type="password" required autocomplete="current-password">
        </div>
        <div class="field">
            <label class="label" for="new_password"><?= e('profile.new_password') ?></label>
            <input class="input" id="new_password" name="new_password" type="password" required minlength="12" autocomplete="new-password">
            <span class="hint"><?= e('admin.password_hint') ?></span>
        </div>
        <div class="field">
            <label class="label" for="confirm_password"><?= e('auth.confirm_password') ?></label>
            <input class="input" id="confirm_password" name="confirm_password" type="password" required minlength="12" autocomplete="new-password">
        </div>
        <button type="submit" class="btn btn-primary mt-3"><?= icon('lock', 18) ?> <?= e('profile.change_password') ?></button>
    </form>
</div>
<?php include '../includes/admin_layout_bottom.php'; ?>
