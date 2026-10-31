<?php
// pages/profile.php
// ข้อมูลบัญชี: ชื่อผู้ใช้, รหัสผ่าน, อีเมล, สถานะสมาชิก + วันหมดอายุ, เบอร์โทร และปุ่มแก้ไข
require_once '../config.php';
requireLogin();

$load = $pdo->prepare("SELECT * FROM users WHERE user_id = ?");
$load->execute([$_SESSION['user_id']]);
$user = $load->fetch();
if (!$user) redirect('/api/auth.php?action=logout');

$errors = [];
$success = null;
$action = $_POST['action'] ?? '';

if ($action === 'details') {
    $firstName = trim($_POST['first_name'] ?? '');
    $lastName = trim($_POST['last_name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = t('err.email_invalid');
    if (!preg_match('/^[0-9+\-\s]{9,20}$/', $phone)) $errors[] = t('err.phone_invalid');
    $dup = $pdo->prepare("SELECT 1 FROM users WHERE LOWER(email) = LOWER(?) AND user_id <> ?");
    $dup->execute([$email, $user['user_id']]);
    if ($dup->fetchColumn()) $errors[] = t('auth.err_email_taken');
    if (!$errors) {
        $pdo->prepare("UPDATE users SET first_name = ?, last_name = ?, email = ?, phone = ? WHERE user_id = ?")
            ->execute([$firstName ?: null, $lastName ?: null, $email, $phone, $user['user_id']]);
        $success = t('profile.saved');
    }
} elseif ($action === 'password') {
    $current = $_POST['current_password'] ?? '';
    $new = $_POST['new_password'] ?? '';
    if (!password_verify($current, $user['password'])) $errors[] = t('profile.err_current');
    if (strlen($new) < 8) $errors[] = t('auth.err_password_short');
    if ($new !== ($_POST['confirm_password'] ?? '')) $errors[] = t('auth.err_password_match');
    if (!$errors) {
        $pdo->prepare("UPDATE users SET password = ? WHERE user_id = ?")->execute([password_hash($new, PASSWORD_DEFAULT), $user['user_id']]);
        $success = t('profile.password_saved');
    }
}
if ($action && !$errors) {
    $load->execute([$_SESSION['user_id']]);
    $user = $load->fetch();
}

$isMember = isActiveMember($user);
$daysLeft = $isMember ? (int) ceil((strtotime($user['member_expire']) - strtotime(date('Y-m-d'))) / 86400) : 0;
$points = (int) $user['points'];

$pageTitle = t('profile.title');
include '../includes/head.php';
?>
<main class="page">
    <div class="wrap">
        <header class="page-head">
            <span class="eyebrow"><?= icon('user', 14) ?> <?= e('nav.profile') ?></span>
            <h1><?= e('profile.title') ?></h1>
        </header>

        <?php if ($success): ?><div class="alert alert-success mb-3" role="status"><?= icon('check-circle') ?><div><?= htmlspecialchars($success) ?></div></div><?php endif; ?>
        <?php if ($errors): ?><div class="alert alert-danger mb-3" role="alert"><?= icon('alert') ?><ul><?php foreach ($errors as $err): ?><li><?= htmlspecialchars($err) ?></li><?php endforeach; ?></ul></div><?php endif; ?>

        <div class="split">
            <div class="stack">
                <form method="POST" class="card" data-once>
                    <input type="hidden" name="action" value="details">
                    <div class="card-head"><h2><?= icon('user', 18) ?> <?= e('profile.account') ?></h2></div>
                    <div class="card-body">
                        <div class="form-grid">
                            <div class="field">
                                <label class="label" for="username"><?= e('auth.username') ?></label>
                                <input class="input" id="username" value="<?= htmlspecialchars($user['username']) ?>" readonly>
                                <span class="hint"><?= e('profile.username_fixed') ?></span>
                            </div>
                            <div class="field">
                                <span class="label"><?= e('auth.password') ?></span>
                                <input class="input" value="••••••••" readonly aria-label="<?= e('auth.password') ?>">
                                <a href="#password" class="hint"><?= e('profile.change_password') ?></a>
                            </div>
                            <div class="field">
                                <label class="label" for="first_name"><?= e('form.first_name') ?></label>
                                <input class="input" id="first_name" name="first_name" maxlength="100" value="<?= htmlspecialchars($user['first_name'] ?? '') ?>">
                            </div>
                            <div class="field">
                                <label class="label" for="last_name"><?= e('form.last_name') ?></label>
                                <input class="input" id="last_name" name="last_name" maxlength="100" value="<?= htmlspecialchars($user['last_name'] ?? '') ?>">
                            </div>
                            <div class="field">
                                <label class="label" for="email"><?= e('form.email') ?></label>
                                <input class="input" id="email" name="email" type="email" required maxlength="100" value="<?= htmlspecialchars($user['email']) ?>">
                            </div>
                            <div class="field">
                                <label class="label" for="phone"><?= e('form.phone') ?></label>
                                <input class="input" id="phone" name="phone" type="tel" required maxlength="20" pattern="[0-9+\-\s]{9,20}" title="<?= e('err.phone_invalid') ?>" value="<?= htmlspecialchars($user['phone'] ?? '') ?>">
                            </div>
                        </div>
                        <button type="submit" class="btn btn-primary mt-3"><?= icon('check', 18) ?> <?= e('profile.change') ?></button>
                    </div>
                </form>

                <form method="POST" class="card" id="password" data-once>
                    <input type="hidden" name="action" value="password">
                    <div class="card-head"><h2><?= icon('lock', 18) ?> <?= e('profile.change_password') ?></h2></div>
                    <div class="card-body">
                        <div class="field">
                            <label class="label" for="current_password"><?= e('profile.current_password') ?></label>
                            <input class="input" id="current_password" name="current_password" type="password" required autocomplete="current-password">
                        </div>
                        <div class="form-grid mt-2">
                            <div class="field">
                                <label class="label" for="new_password"><?= e('profile.new_password') ?></label>
                                <input class="input" id="new_password" name="new_password" type="password" required minlength="8" autocomplete="new-password">
                                <span class="hint"><?= e('auth.password_hint') ?></span>
                            </div>
                            <div class="field">
                                <label class="label" for="confirm_password"><?= e('auth.confirm_password') ?></label>
                                <input class="input" id="confirm_password" name="confirm_password" type="password" required minlength="8" autocomplete="new-password">
                            </div>
                        </div>
                        <button type="submit" class="btn btn-dark mt-3"><?= icon('lock', 18) ?> <?= e('profile.change_password') ?></button>
                    </div>
                </form>
            </div>

            <aside class="stack sticky">
                <div class="card card-pad member-card <?= $isMember ? 'is-member' : '' ?>">
                    <div class="row-between">
                        <span class="badge <?= $isMember ? 'badge-gold' : '' ?>"><?= icon('crown', 12) ?> <?= $isMember ? e('profile.member') : e('profile.not_member') ?></span>
                    </div>
                    <?php if ($isMember): ?>
                        <h2 class="mt-2 mb-1"><?= e('profile.member_until', ['date' => formatDateLocalized($user['member_expire'])]) ?></h2>
                        <p class="muted mb-0"><?= e('profile.days_left', ['n' => $daysLeft]) ?></p>
                    <?php else: ?>
                        <h2 class="mt-2 mb-1"><?= e('profile.join_title') ?></h2>
                        <p class="muted"><?= e('profile.join_body') ?></p>
                        <a href="<?= SITE_URL ?>/membership" class="btn btn-primary btn-block"><?= e('courts.promo_cta') ?></a>
                    <?php endif; ?>
                </div>

                <div class="card card-pad">
                    <h3 class="row"><?= icon('trophy', 18) ?> <?= e('profile.points') ?></h3>
                    <div class="points-big"><?= $points % POINTS_FOR_FREE_ROUND ?><span>/<?= POINTS_FOR_FREE_ROUND ?></span></div>
                    <div class="progress"><span style="width: <?= min(100, ($points % POINTS_FOR_FREE_ROUND) * 100 / POINTS_FOR_FREE_ROUND) ?>%"></span></div>
                    <p class="muted small mt-1 mb-0">
                        <?= e('profile.points_total', ['n' => $points]) ?>
                        <?php if ($points >= POINTS_FOR_FREE_ROUND): ?> · <strong class="text-success"><?= e('profile.points_ready') ?></strong><?php endif; ?>
                    </p>
                    <dl class="kv mt-2">
                        <div class="kv-row"><dt><?= e('profile.total_bookings') ?></dt><dd><?= (int) $user['total_bookings'] ?></dd></div>
                        <div class="kv-row"><dt><?= e('profile.joined') ?></dt><dd><?= htmlspecialchars(formatDateLocalized($user['created_at'])) ?></dd></div>
                    </dl>
                </div>
            </aside>
        </div>
    </div>
</main>
<style>
.member-card.is-member { background: linear-gradient(135deg, #FFFBEB, #fff); border-color: #FDE68A; }
.points-big { font-family: var(--font-display); font-size: 2.6rem; font-weight: 700; color: var(--c-ink); line-height: 1; }
.points-big span { font-size: 1.2rem; color: var(--c-muted); }
.progress { height: 10px; border-radius: 999px; background: #E2E8F0; overflow: hidden; margin-top: 10px; }
.progress span { display: block; height: 100%; background: linear-gradient(90deg, var(--c-gold), #F97316); border-radius: inherit; }
</style>
<?php include '../includes/footer.php'; ?>
