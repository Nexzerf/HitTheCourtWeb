<?php
// admin/login.php
// เข้าสู่ระบบสำหรับผู้ดูแลระบบ
require_once '../config.php';

if (isAdmin()) redirect('/admin/dashboard.php');

$error = '';
$username = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $throttle = loginThrottleKey('admin', $username);
    if (loginIsLocked($throttle)) {
        $error = t('auth.err_locked');
    } else {
    $stmt = $pdo->prepare("SELECT * FROM admins WHERE LOWER(username) = LOWER(?) AND status = 'active'");
    $stmt->execute([$username]);
    $admin = $stmt->fetch();
    if ($admin && password_verify($_POST['password'] ?? '', $admin['password'])) {
        loginClearFailures($throttle);
        session_regenerate_id(true);
        $_SESSION['admin_id'] = $admin['admin_id'];
        $_SESSION['admin_username'] = $admin['username'];
        $_SESSION['admin_role'] = $admin['role'];
        $pdo->prepare("UPDATE admins SET last_login = ? WHERE admin_id = ?")->execute([date('Y-m-d H:i:s'), $admin['admin_id']]);
        redirect('/admin/dashboard.php');
    }
    loginRecordFailure($throttle);
    $error = t('auth.err_invalid');
    }
}

$authTitle = t('admin.login_title');
$authSubtitle = t('admin.login_subtitle');
include '../includes/auth_layout_top.php';
?>
<?php if ($error): ?><div class="alert alert-danger mb-2" role="alert"><?= icon('alert') ?><div><?= htmlspecialchars($error) ?></div></div><?php endif; ?>
<form method="POST" novalidate data-once>
    <div class="field">
        <label class="label" for="username"><?= e('auth.username') ?></label>
        <input class="input" id="username" name="username" required autocomplete="username" autofocus value="<?= htmlspecialchars($username) ?>">
    </div>
    <div class="field">
        <label class="label" for="password"><?= e('auth.password') ?></label>
        <div class="password-wrap">
            <input class="input" id="password" name="password" type="password" required autocomplete="current-password">
            <button type="button" class="pw-toggle" aria-label="<?= e('auth.show_password') ?>" data-pw="#password"><?= icon('eye', 18) ?></button>
        </div>
    </div>
    <button type="submit" class="btn btn-dark btn-lg btn-block mt-3"><?= icon('shield', 18) ?> <?= e('admin.login_btn') ?></button>
</form>
<?php include '../includes/auth_layout_bottom.php'; ?>
