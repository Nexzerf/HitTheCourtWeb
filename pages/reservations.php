<?php
// pages/reservations.php
// การจองของฉัน: ข้อมูลลูกค้า + รายละเอียดการจองครบถ้วน พร้อมปุ่มแก้ไข / ชำระเงิน / ขอยกเลิก
require_once '../config.php';
requireLogin();

$stmt = $pdo->prepare("SELECT booking_id FROM bookings WHERE user_id = ? ORDER BY booking_date DESC, booking_id DESC");
$stmt->execute([$_SESSION['user_id']]);
$groups = ['upcoming' => [], 'pending' => [], 'history' => []];
$today = date('Y-m-d');
foreach ($stmt->fetchAll(PDO::FETCH_COLUMN) as $id) {
    $b = loadBookingDetails($pdo, (int) $id);
    if (!$b) continue;
    if ($b['booking_status'] === 'active' && $b['payment_status'] === 'paid' && $b['booking_date'] >= $today) $groups['upcoming'][] = $b;
    elseif ($b['booking_status'] === 'active' && $b['payment_status'] === 'pending' && !$b['is_expired']) $groups['pending'][] = $b;
    else $groups['history'][] = $b;
}
// งานที่ใกล้ถึงก่อนอยู่บนสุด
usort($groups['upcoming'], fn($a, $b) => strcmp($a['booking_date'] . ($a['slots'][0]['start_time'] ?? ''), $b['booking_date'] . ($b['slots'][0]['start_time'] ?? '')));
$activeTab = $groups['pending'] && !$groups['upcoming'] ? 'pending' : 'upcoming';

$pageTitle = t('res.title');
$pageStyles = ['reservations'];
include '../includes/head.php';
?>
<main class="page">
    <div class="wrap">
        <header class="page-head row-between">
            <div>
                <span class="eyebrow"><?= icon('list', 14) ?> <?= e('res.eyebrow') ?></span>
                <h1><?= e('res.title') ?></h1>
                <p><?= e('res.intro') ?></p>
            </div>
            <a href="<?= SITE_URL ?>/courts" class="btn btn-primary"><?= icon('plus', 18) ?> <?= e('res.new') ?></a>
        </header>

        <?php if (!empty($_SESSION['flash_success'])): ?>
            <div class="alert alert-success mb-3" role="status"><?= icon('check-circle') ?><div><?= htmlspecialchars($_SESSION['flash_success']) ?></div></div>
        <?php unset($_SESSION['flash_success']); endif; ?>

        <div data-tabs>
            <div class="tabs mb-3" role="tablist">
                <?php foreach (['upcoming', 'pending', 'history'] as $tab): ?>
                    <button type="button" class="tab<?= $tab === $activeTab ? ' is-active' : '' ?>" data-tab="<?= $tab ?>" role="tab">
                        <?= e('res.tab_' . $tab) ?> <span class="badge"><?= count($groups[$tab]) ?></span>
                    </button>
                <?php endforeach; ?>
            </div>

            <?php foreach ($groups as $tab => $list): ?>
            <div class="tab-panel" data-panel="<?= $tab ?>" <?= $tab !== $activeTab ? 'hidden' : '' ?>>
                <?php if (!$list): ?>
                    <div class="card empty">
                        <?= icon('calendar', 48) ?>
                        <h3><?= e('res.empty_' . $tab) ?></h3>
                        <p><?= e('res.empty_hint') ?></p>
                        <a href="<?= SITE_URL ?>/courts" class="btn btn-primary"><?= e('courts.book_now') ?></a>
                    </div>
                <?php else: ?>
                <div class="res-list">
                    <?php foreach ($list as $b): [$statusText, $statusClass] = bookingStatusBadge($b); ?>
                    <article class="card res-card">
                        <div class="res-date">
                            <span class="res-day"><?= date('j', strtotime($b['booking_date'])) ?></span>
                            <span class="res-month"><?= htmlspecialchars(formatDateLocalized($b['booking_date'], true)) ?></span>
                        </div>
                        <div class="res-main">
                            <div class="row-between">
                                <h3 class="mb-0"><?= htmlspecialchars($b['sport_label']) ?> · <?= htmlspecialchars($b['court_label']) ?></h3>
                                <span class="badge <?= $statusClass ?>"><?= htmlspecialchars($statusText) ?></span>
                            </div>
                            <div class="meta mt-1">
                                <span><?= icon('clock', 15) ?> <?= htmlspecialchars($b['time_label']) ?></span>
                                <span><?= icon('tag', 15) ?> <?= htmlspecialchars($b['booking_code']) ?></span>
                            </div>

                            <details class="res-details">
                                <summary><?= e('res.show_details') ?> <?= icon('chevron-down', 14) ?></summary>
                                <div class="res-cols">
                                    <dl class="kv">
                                        <div class="kv-row"><dt><?= e('form.first_name') ?></dt><dd><?= htmlspecialchars($b['first_name'] ?: '-') ?></dd></div>
                                        <div class="kv-row"><dt><?= e('form.last_name') ?></dt><dd><?= htmlspecialchars($b['last_name'] ?: '-') ?></dd></div>
                                        <div class="kv-row"><dt><?= e('form.phone') ?></dt><dd><?= htmlspecialchars($b['customer_phone'] ?: '-') ?></dd></div>
                                        <div class="kv-row"><dt><?= e('success.booked_on') ?></dt><dd><?= htmlspecialchars(formatDateLocalized($b['created_at'])) ?></dd></div>
                                    </dl>
                                    <dl class="kv">
                                        <div class="kv-row"><dt><?= e('success.equipment') ?></dt><dd>
                                            <?php if (!$b['equipment']): ?>-<?php else: foreach ($b['equipment'] as $eq): ?>
                                                <div><?= htmlspecialchars(LANG === 'th' && $eq['name_th'] ? $eq['name_th'] : $eq['eq_name']) ?> × <?= (int) $eq['quantity'] ?></div>
                                            <?php endforeach; endif; ?></dd></div>
                                        <div class="kv-row"><dt><?= e('payment.method') ?></dt><dd><?= e($b['payment_method'] === 'bank_transfer' ? 'payment.bank' : 'payment.promptpay') ?></dd></div>
                                        <?php if ((float) $b['discount_amount'] > 0): ?>
                                            <div class="kv-row discount"><dt><?= e('price.discount') ?></dt><dd>−<?= formatPrice($b['discount_amount']) ?></dd></div>
                                        <?php endif; ?>
                                        <div class="kv-row"><dt><?= e($b['payment_status'] === 'paid' ? 'success.paid' : 'booking.total') ?></dt><dd class="text-primary"><?= formatPrice($b['total_price']) ?></dd></div>
                                    </dl>
                                </div>
                                <?php if (!empty($b['notes'])): ?><p class="note mt-1 mb-0"><?= icon('info', 16) ?> <?= htmlspecialchars($b['notes']) ?></p><?php endif; ?>
                            </details>

                            <div class="res-actions">
                                <?php if ($tab === 'pending'): ?>
                                    <a class="btn btn-primary btn-sm" href="<?= SITE_URL ?>/pay_booking?id=<?= $b['booking_id'] ?>"><?= icon('card', 16) ?> <?= e('res.pay_now') ?></a>
                                <?php endif; ?>
                                <?php if (bookingIsEditable($b)): ?>
                                    <a class="btn btn-outline btn-sm" href="<?= SITE_URL ?>/booking?edit=<?= $b['booking_id'] ?>"><?= icon('edit', 16) ?> <?= e('res.edit') ?></a>
                                <?php endif; ?>
                                <?php if ($tab === 'upcoming'): ?>
                                    <a class="btn btn-ghost btn-sm" href="<?= SITE_URL ?>/payment_success?booking=<?= urlencode($b['booking_code']) ?>"><?= icon('receipt', 16) ?> <?= e('res.receipt') ?></a>
                                    <a class="btn btn-danger-outline btn-sm" href="<?= SITE_URL ?>/reports?category=cancel&booking=<?= urlencode($b['booking_code']) ?>"><?= icon('x-circle', 16) ?> <?= e('res.cancel') ?></a>
                                <?php elseif ($tab === 'history' && $b['payment_status'] !== 'paid'): ?>
                                    <a class="btn btn-outline btn-sm" href="<?= SITE_URL ?>/booking?sport_id=<?= (int) $b['sport_id'] ?>"><?= icon('refresh', 16) ?> <?= e('res.book_again') ?></a>
                                <?php endif; ?>
                            </div>
                        </div>
                    </article>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</main>
<?php include '../includes/footer.php'; ?>
