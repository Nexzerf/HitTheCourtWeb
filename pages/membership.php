<?php
// pages/membership.php
// หน้าสิทธิประโยชน์สมาชิก: ราคา 499 บาท / 3 เดือน, สิทธิพิเศษทั้งหมด และปุ่มสมัคร
require_once '../config.php';

$plan = $pdo->query("SELECT * FROM membership_plans WHERE status = 'active' ORDER BY plan_id LIMIT 1")->fetch();
$freeItems = $pdo->query("SELECT e.*, s.sport_name, s.name_th AS sport_th FROM equipment e JOIN sports s ON s.sport_id = e.sport_id
                          WHERE e.status = 'available' AND e.member_free_units > 0 ORDER BY e.sport_id, e.eq_id")->fetchAll();

$user = null;
if (isLoggedIn()) {
    $stmt = $pdo->prepare("SELECT * FROM users WHERE user_id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $user = $stmt->fetch();
}
$isMember = $user && isActiveMember($user);

$pageTitle = t('member.title');
$pageStyles = ['membership'];
include '../includes/head.php';
?>
<main class="page">
    <div class="wrap">
        <?php if (!empty($_SESSION['flash_success'])): ?>
            <div class="alert alert-success mb-3" role="status"><?= icon('check-circle') ?><div><?= htmlspecialchars($_SESSION['flash_success']) ?></div></div>
        <?php unset($_SESSION['flash_success']); endif; ?>

        <section class="member-hero">
            <div>
                <span class="badge badge-gold"><?= icon('crown', 12) ?> <?= e('member.eyebrow') ?></span>
                <h1><?= e('member.title') ?></h1>
                <p><?= e('member.intro') ?></p>
            </div>
            <div class="plan-card">
                <div class="plan-name"><?= icon('crown', 18) ?> <?= htmlspecialchars($plan['plan_name'] ?? 'Premium') ?></div>
                <div class="plan-price"><?= number_format((float) ($plan['price'] ?? 499)) ?><span> <?= e('currency') ?> / <?= e('member.months', ['n' => (int) ($plan['duration_months'] ?? 3)]) ?></span></div>
                <?php if ($isMember): ?>
                    <p class="plan-status"><?= icon('check-circle', 16) ?> <?= e('profile.member_until', ['date' => formatDateLocalized($user['member_expire'])]) ?></p>
                    <a href="<?= SITE_URL ?>/membership_payment" class="btn btn-outline btn-block plan-btn"><?= e('member.renew') ?></a>
                <?php else: ?>
                    <a href="<?= isLoggedIn() ? SITE_URL . '/membership_payment' : SITE_URL . '/login?next=' . urlencode('/membership_payment') ?>" class="btn btn-primary btn-lg btn-block plan-btn"><?= e('member.join') ?> <?= icon('arrow-right', 18) ?></a>
                <?php endif; ?>
                <p class="plan-note"><?= e('member.no_auto_renew') ?></p>
            </div>
        </section>

        <h2 class="mt-4 mb-2"><?= e('member.privileges') ?></h2>
        <div class="perk-grid">
            <article class="card card-pad perk">
                <span class="perk-ico"><?= icon('calendar') ?></span>
                <h3>1. <?= e('member.perk_advance') ?></h3>
                <p class="muted mb-0"><?= e('member.perk_advance_body') ?></p>
            </article>
            <article class="card card-pad perk perk-wide">
                <span class="perk-ico"><?= icon('gift') ?></span>
                <h3>2. <?= e('member.perk_free_equipment') ?></h3>
                <ul class="free-list">
                    <?php foreach ($freeItems as $eq): ?>
                        <li><span><?= htmlspecialchars(localized($eq, 'eq_name', 'name_th')) ?> <small class="muted">· <?= htmlspecialchars(LANG === 'th' && $eq['sport_th'] ? $eq['sport_th'] : $eq['sport_name']) ?></small></span>
                            <strong><?= e('member.first_n_free', ['n' => (int) $eq['member_free_units']]) ?></strong></li>
                    <?php endforeach; ?>
                </ul>
            </article>
            <article class="card card-pad perk">
                <span class="perk-ico"><?= icon('percent') ?></span>
                <h3>3. <?= e('member.perk_promos') ?></h3>
                <ul class="deal-list">
                    <li><span class="deal">30%</span> <?= e('member.deal_30') ?></li>
                    <li><span class="deal">20%</span> <?= e('member.deal_20') ?></li>
                    <li><span class="deal">10%</span> <?= e('member.deal_10') ?></li>
                </ul>
                <p class="hint mb-0"><?= e('member.deal_note') ?></p>
            </article>
            <article class="card card-pad perk">
                <span class="perk-ico"><?= icon('clock') ?></span>
                <h3>4. <?= e('member.perk_unlimited') ?></h3>
                <p class="muted mb-0"><?= e('member.perk_unlimited_body') ?></p>
            </article>
            <article class="card card-pad perk">
                <span class="perk-ico"><?= icon('trophy') ?></span>
                <h3>5. <?= e('member.perk_points') ?></h3>
                <p class="muted mb-0"><?= e('member.perk_points_body') ?></p>
            </article>
        </div>

        <section class="card mt-4">
            <div class="card-head"><h2><?= icon('list', 18) ?> <?= e('member.compare') ?></h2></div>
            <div class="table-wrap">
                <table class="table compare">
                    <thead><tr><th><?= e('member.feature') ?></th><th><?= e('member.regular') ?></th><th class="hl"><?= e('profile.member') ?></th></tr></thead>
                    <tbody>
                        <tr><td><?= e('member.row_advance') ?></td><td><?= e('member.days', ['n' => ADVANCE_DAYS_REGULAR]) ?></td><td class="hl"><?= e('member.days', ['n' => ADVANCE_DAYS_MEMBER]) ?></td></tr>
                        <tr><td><?= e('member.row_slots') ?></td><td>1</td><td class="hl"><?= e('member.unlimited') ?></td></tr>
                        <tr><td><?= e('member.row_equipment') ?></td><td><?= e('member.paid') ?></td><td class="hl"><?= e('member.free_first') ?></td></tr>
                        <tr><td><?= e('member.row_discounts') ?></td><td>–</td><td class="hl"><?= e('member.up_to', ['n' => 30]) ?></td></tr>
                        <tr><td><?= e('member.row_points') ?></td><td>–</td><td class="hl"><?= icon('check', 16) ?></td></tr>
                    </tbody>
                </table>
            </div>
        </section>
    </div>
</main>
<?php include '../includes/footer.php'; ?>
