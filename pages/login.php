<?php
// pages/login.php
// เข้าสู่ระบบ: ช่องชื่อผู้ใช้ (หรืออีเมล), รหัสผ่าน, ปุ่ม Log In, ลิงก์ไปหน้าสมัคร และข้อความแจ้งเตือนเมื่อกรอกผิด
require_once '../config.php';

$next = safeNextUrl($_GET['next'] ?? $_POST['next'] ?? null);
if (isLoggedIn()) redirect($next);

$error = '';
$username = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    if ($username === '' || $password === '') {
        $error = t('auth.err_fill_all');
    } else {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ? OR email = ?");
        $stmt->execute([$username, $username]);
        $user = $stmt->fetch();
        if ($user && password_verify($password, $user['password'])) {
            session_regenerate_id(true); // กัน session fixation
            $_SESSION['user_id'] = $user['user_id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['is_member'] = $user['is_member'];
            redirect($next);
        }
        $error = t('auth.err_invalid');
    }
}

$authTitle = t('auth.login_title');
$authSubtitle = t('auth.login_subtitle');
include '../includes/auth_layout_top.php';
?>
<?php if (!empty($_GET['registered']) && $_SERVER['REQUEST_METHOD'] !== 'POST'): ?>
    <div class="alert alert-success mb-2" role="status"><?= icon('check-circle') ?><div><?= e('auth.registered') ?></div></div>
<?php endif; ?>
<?php if ($error): ?>
    <div class="alert alert-danger mb-2" role="alert"><?= icon('alert') ?><div><?= htmlspecialchars($error) ?></div></div>
<?php endif; ?>

<form method="POST" action="<?= SITE_URL ?>/login<?= $next !== '/' ? '?next=' . urlencode($next) : '' ?>" novalidate data-once>
    <input type="hidden" name="next" value="<?= htmlspecialchars($next) ?>">
    <div class="field">
        <label class="label" for="username"><?= e('auth.username_or_email') ?></label>
        <input class="input" id="username" name="username" required autocomplete="username" autofocus value="<?= htmlspecialchars($username) ?>" placeholder="<?= e('auth.username_ph') ?>">
        <span class="hint"><?= e('auth.username_login_hint') ?></span>
    </div>
    <div class="field">
        <label class="label" for="password"><?= e('auth.password') ?></label>
        <div class="password-wrap">
            <input class="input" id="password" name="password" type="password" required autocomplete="current-password" placeholder="<?= e('auth.password_ph') ?>">
            <button type="button" class="pw-toggle" aria-label="<?= e('auth.show_password') ?>" data-pw="#password"><?= icon('eye', 18) ?></button>
        </div>
    </div>
    <button type="submit" class="btn btn-primary btn-lg btn-block mt-3"><?= e('auth.login_btn') ?></button>
</form>

<p class="center mt-3 mb-0 muted"><?= e('auth.no_account') ?> <a href="<?= SITE_URL ?>/register<?= $next !== '/' ? '?next=' . urlencode($next) : '' ?>" class="strong"><?= e('auth.register_link') ?></a></p>
<?php include '../includes/auth_layout_bottom.php'; ?>
