<?php
// pages/pay_booking.php
// หน้าชำระเงินของการจอง: เลือกวิธีจ่าย (QR พร้อมเพย์ / โอนเข้าบัญชี), ดูรายละเอียดราคา, อัปโหลดสลิป แล้วกดยืนยัน
require_once '../config.php';
requireLogin();
require_once '../api/thunder_api.php';
require_once '../api/promptpay.php';

$bookingId = intval($_GET['id'] ?? 0);
$booking = $bookingId ? loadBookingDetails($pdo, $bookingId) : null;
if (!$booking || (int) $booking['user_id'] !== (int) $_SESSION['user_id']) redirect('/reservations');
if ($booking['payment_status'] === 'paid') redirect('/payment_success?booking=' . urlencode($booking['booking_code']));
if ($booking['booking_status'] === 'cancelled') redirect('/reservations');

// ถ้าหมดเวลาล็อกสนามแล้ว แต่ช่วงเวลายังว่างอยู่ ก็ต่อเวลาให้อีกรอบ
if ($booking['is_expired']) {
    $taken = getTakenSlots($pdo, (int) $booking['sport_id'], $booking['booking_date'], $bookingId);
    $stillFree = $booking['booking_date'] >= date('Y-m-d');
    foreach ($booking['slots'] as $s) {
        if (isset($taken[$booking['court_id'] . '_' . $s['slot_id']])) $stillFree = false;
        if ($booking['booking_date'] === date('Y-m-d') && $s['start_time'] <= date('H:i:s')) $stillFree = false;
    }
    if ($stillFree) {
        $pdo->prepare("UPDATE bookings SET expires_at = ? WHERE booking_id = ?")->execute([holdExpiresAt(), $bookingId]);
        $booking = loadBookingDetails($pdo, $bookingId);
    }
}

// ข้อมูลบัญชีรับเงิน
$settings = [];
foreach ($pdo->query("SELECT setting_key, setting_value FROM settings") as $row) $settings[$row['setting_key']] = $row['setting_value'];
$promptpayNumber = ($settings['promptpay_number'] ?? '') ?: '0951386174';
$bankName = $settings['bank_name'] ?? '';
$bankAccount = $settings['bank_account'] ?? '';
$accountName = $settings['company_name'] ?? '';
$amount = (float) $booking['total_price'];
$qrPayload = $amount > 0 ? promptpayPayload($promptpayNumber, $amount) : null;

$errors = [];
$method = $_POST['payment_method'] ?? $booking['payment_method'] ?? 'promptpay';
$method = $method === 'bank_transfer' ? 'bank_transfer' : 'promptpay';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$booking['is_expired']) {
    try {
        $hasSlip = !empty($_FILES['slip']['name']);
        $needSlip = PAYMENT_MODE !== 'demo' && $amount > 0;
        if ($needSlip && !$hasSlip) throw new Exception(t('pay.err_slip_required'));

        $upload = null;
        if ($hasSlip) {
            $file = $_FILES['slip'];
            if ($file['error'] !== UPLOAD_ERR_OK) throw new Exception(t('pay.err_upload'));
            $mime = mime_content_type($file['tmp_name']);
            if (!in_array($mime, ['image/jpeg', 'image/png'], true)) throw new Exception(t('pay.err_type'));
            if ($file['size'] > 4 * 1024 * 1024) throw new Exception(t('pay.err_size'));
            $upload = saveUploadedFile($file, 'slips', 'bk_' . $bookingId . '_' . time() . ($mime === 'image/png' ? '.png' : '.jpg'));
            if (!$upload) throw new Exception(t('pay.err_upload'));
        }

        $paidAmount = $amount;
        if (PAYMENT_MODE === 'thunder' && $amount > 0) {
            // ตรวจสลิปจริงผ่าน Thunder API
            if (!THUNDER_API_KEY) throw new Exception(t('pay.err_verify'));
            $slipData = (new ThunderClient(THUNDER_API_KEY))->verifyByImage($upload['local']);
            $paidAmount = floatval($slipData['amount']['amount'] ?? 0);
            if ($paidAmount < $amount) throw new Exception(t('pay.err_amount', ['need' => formatPrice($amount), 'paid' => formatPrice($paidAmount)]));
            $receiver = preg_replace('/\D/', '', ($slipData['receiver']['account']['value'] ?? '') ?: ($slipData['receiver']['account']['proxy_value'] ?? ''));
            if ($receiver !== '' && $receiver !== preg_replace('/\D/', '', $promptpayNumber) && $receiver !== preg_replace('/\D/', '', $bankAccount)) {
                throw new Exception(t('pay.err_receiver'));
            }
        }

        $pdo->beginTransaction();
        // ล็อกการจองนี้ และตรวจว่ายังไม่มีใครจองช่วงเวลาซ้อน
        $lock = $pdo->prepare("SELECT payment_status FROM bookings WHERE booking_id = ? FOR UPDATE");
        $lock->execute([$bookingId]);
        if ($lock->fetchColumn() === 'paid') { $pdo->rollBack(); redirect('/payment_success?booking=' . urlencode($booking['booking_code'])); }
        $taken = getTakenSlots($pdo, (int) $booking['sport_id'], $booking['booking_date'], $bookingId);
        foreach ($booking['slots'] as $s) {
            if (isset($taken[$booking['court_id'] . '_' . $s['slot_id']])) throw new Exception(t('err.slot_taken', ['time' => formatTime($s['start_time'])]));
        }

        $pdo->prepare("INSERT INTO payments (booking_id, payment_method, amount, slip_image, payment_status, verified_at) VALUES (?, ?, ?, ?, 'verified', ?)")
            ->execute([$bookingId, $method, $paidAmount, $upload['path'] ?? null, date('Y-m-d H:i:s')]);
        $pdo->prepare("UPDATE bookings SET payment_status = 'paid', payment_method = ?, expires_at = NULL WHERE booking_id = ?")
            ->execute([$method, $bookingId]);
        // สะสมแต้ม +1 ต่อการจองที่จ่ายแล้ว และหักแต้มที่แลกไป
        $pdo->prepare("UPDATE users SET points = GREATEST(0, points + 1 - ?), total_bookings = total_bookings + 1 WHERE user_id = ?")
            ->execute([(int) $booking['points_redeemed'], $booking['user_id']]);
        $pdo->commit();

        redirect('/payment_success?booking=' . urlencode($booking['booking_code']));
    } catch (Exception $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        $errors[] = $e->getMessage();
    }
}

$pageTitle = t('pay.title');
$pageStyles = ['payment'];
include '../includes/head.php';
?>
<main class="page">
    <div class="wrap">
        <a href="<?= SITE_URL ?>/reservations" class="back-link"><?= icon('arrow-left', 16) ?> <?= e('nav.my_bookings') ?></a>
        <header class="page-head">
            <span class="eyebrow"><?= icon('card', 14) ?> <?= e('pay.eyebrow') ?></span>
            <h1><?= e('pay.title') ?></h1>
            <p><?= PAYMENT_MODE === 'demo' ? e('pay.demo_note') : e('pay.intro') ?></p>
        </header>

        <?php if ($errors): ?>
            <div class="alert alert-danger mb-3" role="alert"><?= icon('alert') ?><ul><?php foreach ($errors as $err): ?><li><?= htmlspecialchars($err) ?></li><?php endforeach; ?></ul></div>
        <?php endif; ?>

        <?php if ($booking['is_expired']): ?>
            <div class="card card-pad center">
                <?= icon('clock', 40, 'muted') ?>
                <h2 class="mt-1"><?= e('pay.expired_title') ?></h2>
                <p class="muted"><?= e('pay.expired_body') ?></p>
                <a class="btn btn-primary" href="<?= SITE_URL ?>/booking?sport_id=<?= (int) $booking['sport_id'] ?>&date=<?= htmlspecialchars($booking['booking_date']) ?>"><?= e('pay.book_again') ?></a>
            </div>
        <?php else: ?>
        <form method="POST" enctype="multipart/form-data" class="split" data-once>
            <div class="stack">
                <section class="card">
                    <div class="card-head"><h2><?= icon('card', 18) ?> <?= e('payment.method') ?></h2>
                        <span class="badge badge-warning" id="holdBadge" data-expires="<?= strtotime($booking['expires_at']) * 1000 ?>"><?= icon('clock', 12) ?> <span><?= e('pay.held') ?></span></span>
                    </div>
                    <div class="card-body">
                        <div class="choices mb-3">
                            <label class="choice">
                                <input type="radio" name="payment_method" value="promptpay" <?= $method === 'promptpay' ? 'checked' : '' ?>>
                                <span class="choice-box"><span class="choice-icon"><?= icon('qr') ?></span><span><strong><?= e('payment.promptpay') ?></strong><small><?= e('payment.promptpay_desc') ?></small></span></span>
                            </label>
                            <label class="choice">
                                <input type="radio" name="payment_method" value="bank_transfer" <?= $method === 'bank_transfer' ? 'checked' : '' ?>>
                                <span class="choice-box"><span class="choice-icon"><?= icon('bank') ?></span><span><strong><?= e('payment.bank') ?></strong><small><?= e('payment.bank_desc') ?></small></span></span>
                            </label>
                        </div>

                        <div class="pay-panel" data-method="promptpay" <?= $method !== 'promptpay' ? 'hidden' : '' ?>>
                            <?php if ($qrPayload): ?>
                            <div class="qr-card">
                                <div class="qr-brand"><?= icon('qr', 16) ?> PromptPay</div>
                                <img id="promptpay-qr" data-payload="<?= htmlspecialchars($qrPayload) ?>" alt="<?= e('pay.qr_alt') ?>" width="240" height="240">
                                <div class="qr-amount"><?= formatPrice($amount) ?></div>
                                <div class="muted small"><?= e('pay.promptpay_to', ['number' => $promptpayNumber]) ?><br><?= htmlspecialchars($accountName) ?></div>
                            </div>
                            <?php else: ?>
                                <div class="alert alert-success"><?= icon('check-circle') ?><div><?= e('pay.nothing_to_pay') ?></div></div>
                            <?php endif; ?>
                        </div>

                        <div class="pay-panel" data-method="bank_transfer" <?= $method !== 'bank_transfer' ? 'hidden' : '' ?>>
                            <dl class="kv bank-box">
                                <div class="kv-row"><dt><?= e('pay.bank_name') ?></dt><dd><?= htmlspecialchars($bankName) ?></dd></div>
                                <div class="kv-row"><dt><?= e('pay.account_no') ?></dt><dd><span id="accNo"><?= htmlspecialchars($bankAccount) ?></span>
                                    <button type="button" class="btn btn-ghost btn-sm" data-copy="#accNo"><?= e('pay.copy') ?></button></dd></div>
                                <div class="kv-row"><dt><?= e('pay.account_name') ?></dt><dd><?= htmlspecialchars($accountName) ?></dd></div>
                                <div class="kv-row"><dt><?= e('pay.amount') ?></dt><dd class="text-primary"><?= formatPrice($amount) ?></dd></div>
                            </dl>
                        </div>
                    </div>
                </section>

                <section class="card">
                    <div class="card-head"><h2><?= icon('upload', 18) ?> <?= e('pay.upload_title') ?>
                        <?php if (PAYMENT_MODE === 'demo'): ?><span class="opt muted small">(<?= e('common.optional') ?>)</span><?php endif; ?></h2></div>
                    <div class="card-body">
                        <label class="upload">
                            <input type="file" name="slip" accept="image/jpeg,image/png" <?= PAYMENT_MODE !== 'demo' && $amount > 0 ? 'required' : '' ?>>
                            <?= icon('upload', 28) ?>
                            <strong data-upload-label data-empty="<?= e('pay.upload_cta') ?>"><?= e('pay.upload_cta') ?></strong>
                            <span class="small"><?= e('pay.upload_hint') ?></span>
                        </label>
                    </div>
                </section>
            </div>

            <aside class="sticky">
                <div class="card">
                    <div class="card-head"><h2><?= icon('receipt', 18) ?> <?= e('booking.summary') ?></h2></div>
                    <div class="card-body">
                        <div class="sum-title"><?= htmlspecialchars($booking['sport_label']) ?> · <?= htmlspecialchars($booking['court_label']) ?></div>
                        <div class="sum-sub"><?= htmlspecialchars(formatDateLocalized($booking['booking_date'], true)) ?><br><?= htmlspecialchars($booking['time_label']) ?></div>
                        <dl class="kv">
                            <div class="kv-row"><dt><?= e('pay.booking_id') ?></dt><dd><?= htmlspecialchars($booking['booking_code']) ?></dd></div>
                            <?php foreach (bookingPriceRows($booking) as $row): ?>
                                <div class="kv-row <?= $row[2] ?? '' ?>"><dt><?= htmlspecialchars($row[0]) ?></dt><dd><?= htmlspecialchars($row[1]) ?></dd></div>
                            <?php endforeach; ?>
                        </dl>
                        <div class="kv-total"><span><?= e('booking.total') ?></span><span class="amount"><?= formatPrice($amount) ?></span></div>
                        <button type="submit" class="btn btn-primary btn-lg btn-block mt-2"><?= icon('check', 18) ?> <?= e('pay.confirm') ?></button>
                    </div>
                </div>
            </aside>
        </form>
        <?php endif; ?>
    </div>
</main>
<script src="<?= SITE_URL ?>/assets/js/qrcode.js"></script>
<script>
(function () {
    // วาด QR พร้อมเพย์
    var img = document.getElementById('promptpay-qr');
    if (img) { var qr = qrcode(0, 'M'); qr.addData(img.dataset.payload); qr.make(); img.src = qr.createDataURL(6, 2); }

    // สลับแผง QR / บัญชีธนาคาร
    document.querySelectorAll('input[name=payment_method]').forEach(function (r) {
        r.addEventListener('change', function () {
            document.querySelectorAll('.pay-panel').forEach(function (p) { p.hidden = p.dataset.method !== r.value; });
        });
    });

    // คัดลอกเลขบัญชี
    document.querySelectorAll('[data-copy]').forEach(function (b) {
        b.addEventListener('click', function () {
            var text = document.querySelector(b.dataset.copy).textContent.trim();
            if (navigator.clipboard) navigator.clipboard.writeText(text).then(function () { window.toast && window.toast(<?= json_encode(t('pay.copied')) ?>); });
        });
    });

    // นับถอยหลังเวลาที่ล็อกสนามไว้
    var badge = document.getElementById('holdBadge');
    if (badge) {
        var until = parseInt(badge.dataset.expires, 10), label = badge.querySelector('span');
        var tick = function () {
            var s = Math.max(0, Math.round((until - Date.now()) / 1000));
            label.textContent = <?= json_encode(t('pay.held')) ?> + ' ' + Math.floor(s / 60) + ':' + String(s % 60).padStart(2, '0');
            if (s === 0) { location.reload(); return; }
            setTimeout(tick, 1000);
        };
        tick();
    }
})();
</script>
<?php include '../includes/footer.php'; ?>
