<?php
// pages/payment_success.php
// หน้ายืนยันการชำระเงินสำเร็จ: ข้อมูลลูกค้า, รายละเอียดการจองครบถ้วน และลิงก์กลับหน้าแรก
require_once '../config.php';
requireLogin();

$code = $_GET['booking'] ?? '';
$stmt = $pdo->prepare("SELECT booking_id FROM bookings WHERE booking_code = ? AND user_id = ?");
$stmt->execute([$code, $_SESSION['user_id']]);
$bookingId = (int) $stmt->fetchColumn();
$booking = $bookingId ? loadBookingDetails($pdo, $bookingId) : null;
if (!$booking) redirect('/reservations');
if ($booking['payment_status'] !== 'paid') redirect('/pay_booking?id=' . $bookingId);

$pageTitle = t('success.title');
$pageStyles = ['payment'];
include '../includes/head.php';
?>
<main class="page">
    <div class="wrap wrap-narrow">
        <div class="success-hero">
            <div class="success-icon"><?= icon('check', 40) ?></div>
            <p class="success-word">Success</p>
            <h1><?= e('success.title') ?></h1>
            <p class="muted"><?= e('success.body') ?></p>
        </div>

        <article class="card receipt">
            <div class="receipt-top">
                <div>
                    <div class="small muted"><?= e('pay.booking_id') ?></div>
                    <div class="receipt-code"><?= htmlspecialchars($booking['booking_code']) ?></div>
                </div>
                <span class="badge badge-success"><?= icon('check-circle', 12) ?> <?= e('status.confirmed') ?></span>
            </div>

            <div class="card-body">
                <h3 class="receipt-section"><?= icon('user', 16) ?> <?= e('success.customer') ?></h3>
                <dl class="kv">
                    <div class="kv-row"><dt><?= e('form.first_name') ?></dt><dd><?= htmlspecialchars($booking['first_name'] ?: '-') ?></dd></div>
                    <div class="kv-row"><dt><?= e('form.last_name') ?></dt><dd><?= htmlspecialchars($booking['last_name'] ?: '-') ?></dd></div>
                    <div class="kv-row"><dt><?= e('form.phone') ?></dt><dd><?= htmlspecialchars($booking['customer_phone'] ?: '-') ?></dd></div>
                </dl>

                <h3 class="receipt-section"><?= icon('calendar', 16) ?> <?= e('success.details') ?></h3>
                <dl class="kv">
                    <div class="kv-row"><dt><?= e('success.sport') ?></dt><dd><?= htmlspecialchars($booking['sport_label']) ?></dd></div>
                    <div class="kv-row"><dt><?= e('success.court') ?></dt><dd><?= htmlspecialchars($booking['court_label']) ?></dd></div>
                    <div class="kv-row"><dt><?= e('success.date') ?></dt><dd><?= htmlspecialchars(formatDateLocalized($booking['booking_date'], true)) ?></dd></div>
                    <div class="kv-row"><dt><?= e('success.time') ?></dt><dd><?= htmlspecialchars($booking['time_label']) ?></dd></div>
                    <div class="kv-row"><dt><?= e('success.equipment') ?></dt><dd>
                        <?php if (!$booking['equipment']): ?>-<?php else: foreach ($booking['equipment'] as $eq): ?>
                            <div><?= htmlspecialchars(LANG === 'th' && $eq['name_th'] ? $eq['name_th'] : $eq['eq_name']) ?> × <?= (int) $eq['quantity'] ?></div>
                        <?php endforeach; endif; ?>
                    </dd></div>
                    <div class="kv-row"><dt><?= e('payment.method') ?></dt><dd><?= e($booking['payment_method'] === 'bank_transfer' ? 'payment.bank' : 'payment.promptpay') ?></dd></div>
                    <div class="kv-row"><dt><?= e('success.booked_on') ?></dt><dd><?= htmlspecialchars(formatDateLocalized($booking['created_at'])) ?> <?= date('H:i', strtotime($booking['created_at'])) ?></dd></div>
                </dl>

                <h3 class="receipt-section"><?= icon('receipt', 16) ?> <?= e('success.payment') ?></h3>
                <dl class="kv">
                    <?php foreach (bookingPriceRows($booking) as $row): ?>
                        <div class="kv-row <?= $row[2] ?? '' ?>"><dt><?= htmlspecialchars($row[0]) ?></dt><dd><?= htmlspecialchars($row[1]) ?></dd></div>
                    <?php endforeach; ?>
                </dl>
                <div class="kv-total"><span><?= e('success.paid') ?></span><span class="amount"><?= formatPrice($booking['total_price']) ?></span></div>

                <p class="note mt-3 mb-0"><?= icon('info', 16) ?> <?= e('success.show_at_desk') ?></p>
            </div>
        </article>

        <div class="row mt-3" style="justify-content:center">
            <a href="<?= SITE_URL ?>/" class="btn btn-primary"><?= icon('home', 18) ?> <?= e('success.home') ?></a>
            <a href="<?= SITE_URL ?>/reservations" class="btn btn-outline"><?= icon('list', 18) ?> <?= e('nav.my_bookings') ?></a>
            <button type="button" class="btn btn-ghost" onclick="window.print()"><?= icon('file', 18) ?> <?= e('success.print') ?></button>
        </div>
    </div>
</main>
<?php include '../includes/footer.php'; ?>
