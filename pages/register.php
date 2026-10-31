<?php
// pages/register.php
// สมัครสมาชิกเว็บไซต์: Username, Password, Email, Phone Number, ปุ่ม Register และลิงก์กลับไปหน้า Log In
require_once '../config.php';

$next = safeNextUrl($_GET['next'] ?? $_POST['next'] ?? null);
if (isLoggedIn()) redirect($next);

$errors = [];
$old = ['username' => '', 'email' => '', 'phone' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $old = [
        'username' => trim($_POST['username'] ?? ''),
        'email' => trim($_POST['email'] ?? ''),
        'phone' => trim($_POST['phone'] ?? ''),
    ];
    $password = $_POST['password'] ?? '';

    if (!preg_match('/^[A-Za-z0-9_.]{3,30}$/', $old['username'])) $errors['username'] = t('auth.err_username');
    if (strlen($password) < 8) $errors['password'] = t('auth.err_password_short');
    if (!filter_var($old['email'], FILTER_VALIDATE_EMAIL)) $errors['email'] = t('err.email_invalid');
    if (!preg_match('/^[0-9+\-\s]{9,20}$/', $old['phone'])) $errors['phone'] = t('err.phone_invalid');

    if (!$errors) {
        $check = $pdo->prepare("SELECT username, email FROM users WHERE username = ? OR email = ?");
        $check->execute([$old['username'], $old['email']]);
        foreach ($check->fetchAll() as $row) {
            if (strcasecmp($row['username'], $old['username']) === 0) $errors['username'] = t('auth.err_username_taken');
            if (strcasecmp($row['email'], $old['email']) === 0) $errors['email'] = t('auth.err_email_taken');
        }
    }

    if (!$errors) {
        $pdo->prepare("INSERT INTO users (username, email, phone, password) VALUES (?, ?, ?, ?)")
            ->execute([$old['username'], $old['email'], $old['phone'], password_hash($password, PASSWORD_DEFAULT)]);
        redirect('/login?registered=1' . ($next !== '/' ? '&next=' . urlencode($next) : ''));
    }
}

$authTitle = t('auth.register_title');
$authSubtitle = t('auth.register_subtitle');
include '../includes/auth_layout_top.php';

$fieldError = fn($k) => isset($errors[$k]) ? '<span class="field-error">' . htmlspecialchars($errors[$k]) . '</span>' : '';
?>
<?php if ($errors): ?>
    <div class="alert alert-danger mb-2" role="alert"><?= icon('alert') ?><div><?= e('auth.err_check_form') ?></div></div>
<?php endif; ?>

<form method="POST" novalidate data-once>
    <input type="hidden" name="next" value="<?= htmlspecialchars($next) ?>">
    <div class="field">
        <label class="label" for="username"><?= e('auth.username') ?></label>
        <input class="input<?= isset($errors['username']) ? ' is-invalid' : '' ?>" id="username" name="username" required maxlength="30" autocomplete="username" value="<?= htmlspecialchars($old['username']) ?>" placeholder="<?= e('auth.username_new_ph') ?>">
        <?= $fieldError('username') ?: '<span class="hint">' . e('auth.username_hint') . '</span>' ?>
    </div>
    <div class="field">
        <label class="label" for="password"><?= e('auth.password') ?></label>
        <div class="password-wrap">
            <input class="input<?= isset($errors['password']) ? ' is-invalid' : '' ?>" id="password" name="password" type="password" required minlength="8" autocomplete="new-password" placeholder="<?= e('auth.password_new_ph') ?>">
            <button type="button" class="pw-toggle" aria-label="<?= e('auth.show_password') ?>" data-pw="#password"><?= icon('eye', 18) ?></button>
        </div>
        <?= $fieldError('password') ?: '<span class="hint">' . e('auth.password_hint') . '</span>' ?>
    </div>
    <div class="field">
        <label class="label" for="email"><?= e('form.email') ?></label>
        <input class="input<?= isset($errors['email']) ? ' is-invalid' : '' ?>" id="email" name="email" type="email" required maxlength="100" autocomplete="email" value="<?= htmlspecialchars($old['email']) ?>" placeholder="name@example.com">
        <?= $fieldError('email') ?: '<span class="hint">' . e('auth.email_hint') . '</span>' ?>
    </div>
    <div class="field">
        <label class="label" for="phone"><?= e('form.phone') ?></label>
        <input class="input<?= isset($errors['phone']) ? ' is-invalid' : '' ?>" id="phone" name="phone" type="tel" required maxlength="20" autocomplete="tel" inputmode="tel" value="<?= htmlspecialchars($old['phone']) ?>" placeholder="08x-xxx-xxxx">
        <?= $fieldError('phone') ?: '<span class="hint">' . e('auth.phone_hint') . '</span>' ?>
    </div>
    <button type="submit" class="btn btn-primary btn-lg btn-block mt-3"><?= e('auth.register_btn') ?></button>
</form>

<p class="center mt-3 mb-0 muted"><?= e('auth.have_account') ?> <a href="<?= SITE_URL ?>/login<?= $next !== '/' ? '?next=' . urlencode($next) : '' ?>" class="strong"><?= e('auth.login_link') ?></a></p>
<?php include '../includes/auth_layout_bottom.php'; ?>
