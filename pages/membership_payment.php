<?php
// pages/membership_payment.php
// สมัคร / ต่ออายุสมาชิก: ข้อมูลผู้สมัคร (ชื่อ นามสกุล อีเมล เบอร์โทร), รายละเอียดแพ็กเกจและระยะเวลา,
// วิธีชำระเงิน (QR / โอนเข้าบัญชี), อัปโหลดสลิป และปุ่มยืนยัน
require_once '../config.php';
requireLogin();
require_once '../api/thunder_api.php';
require_once '../api/promptpay.php';

$plan = $pdo->query("SELECT * FROM membership_plans WHERE status = 'active' ORDER BY plan_id LIMIT 1")->fetch();
if (!$plan) redirect('/membership');

$stmt = $pdo->prepare("SELECT * FROM users WHERE user_id = ?");
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch();

// สมาชิกที่ยังไม่หมดอายุ สมัครซ้ำไม่ได้ ต้องรอให้หมดอายุก่อน
if (isActiveMember($user)) {
    $_SESSION['flash_error'] = t('member.err_active', ['date' => formatDateLocalized($user['member_expire'])]);
    redirect('/membership');
}
$startDate = date('Y-m-d');
$endDate = date('Y-m-d', strtotime($startDate . ' +' . (int) $plan['duration_months'] . ' months -1 day'));
$amount = (float) $plan['price'];

$settings = [];
foreach ($pdo->query("SELECT setting_key, setting_value FROM settings") as $row) $settings[$row['setting_key']] = $row['setting_value'];
$promptpayNumber = ($settings['promptpay_number'] ?? '') ?: '0951386174';
$bankName = $settings['bank_name'] ?? '';
$bankAccount = $settings['bank_account'] ?? '';
$accountName = $settings['company_name'] ?? '';
$qrPayload = promptpayPayload($promptpayNumber, $amount);

$errors = [];
$old = [
    'first_name' => $user['first_name'] ?? '', 'last_name' => $user['last_name'] ?? '',
    'email' => $user['email'] ?? '', 'phone' => $user['phone'] ?? '',
    'payment_method' => 'promptpay',
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    foreach (['first_name', 'last_name', 'email', 'phone'] as $f) $old[$f] = trim($_POST[$f] ?? '');
    $old['payment_method'] = ($_POST['payment_method'] ?? '') === 'bank_transfer' ? 'bank_transfer' : 'promptpay';

    if ($old['first_name'] === '' || $old['last_name'] === '') $errors[] = t('err.name_required');
    if (!filter_var($old['email'], FILTER_VALIDATE_EMAIL)) $errors[] = t('err.email_invalid');
    if (!preg_match('/^[0-9+\-\s]{9,20}$/', $old['phone'])) $errors[] = t('err.phone_invalid');

    try {
        $upload = null;
        if (!$errors) {
            // ต้องแนบรูปสลิป (โหมดสาธิต: รูปอะไรก็ได้ ขอแค่เป็นไฟล์รูปภาพ)
            $img = validateImageUpload($_FILES['slip'] ?? null);
            if (trim($_POST['qr_text'] ?? '') === '') throw new Exception(t('pay.err_no_qr'));
            $upload = saveUploadedFile($_FILES['slip'], 'slips', 'mem_' . $user['user_id'] . '_' . time() . '.' . $img['ext'], $img['mime']);
            if (!$upload) throw new Exception(t('pay.err_upload'));
        }
        if (!$errors && PAYMENT_MODE === 'thunder') {
            if (!THUNDER_API_KEY) throw new Exception(t('pay.err_verify'));
            $slipData = (new ThunderClient(THUNDER_API_KEY))->verifyByImage($upload['local']);
            $paid = floatval($slipData['amount']['amount'] ?? 0);
            if ($paid < $amount) throw new Exception(t('pay.err_amount', ['need' => formatPrice($amount), 'paid' => formatPrice($paid)]));
        }

        if (!$errors) {
            $pdo->beginTransaction();
            // ล็อกแถวผู้ใช้ แล้วเช็คซ้ำ กันกดสมัครซ้อนกันสองครั้ง
            $lock = $pdo->prepare("SELECT * FROM users WHERE user_id = ? FOR UPDATE");
            $lock->execute([$user['user_id']]);
            if (isActiveMember($lock->fetch())) {
                $pdo->rollBack();
                redirect('/membership');
            }
            $pdo->prepare("INSERT INTO user_membership (user_id, plan_id, first_name, last_name, email, phone, start_date, end_date, total_price, payment_method, payment_status, slip_image)
                           VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'verified', ?)")
                ->execute([$user['user_id'], $plan['plan_id'], $old['first_name'], $old['last_name'], $old['email'], $old['phone'],
                    $startDate, $endDate, $amount, $old['payment_method'], $upload['path'] ?? null]);
            $pdo->prepare("UPDATE users SET is_member = 1, member_expire = ?, first_name = COALESCE(NULLIF(first_name, ''), ?),
                           last_name = COALESCE(NULLIF(last_name, ''), ?), phone = COALESCE(NULLIF(phone, ''), ?) WHERE user_id = ?")
                ->execute([$endDate, $old['first_name'], $old['last_name'], $old['phone'], $user['user_id']]);
            $pdo->commit();

            $_SESSION['is_member'] = 1;
            $_SESSION['flash_success'] = t('member.success', ['date' => formatDateLocalized($endDate)]);
            redirect('/membership');
        }
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        if (!$e instanceof Exception || $e instanceof PDOException) { error_log('Membership failed: ' . $e->getMessage()); $errors[] = t('err.generic'); }
        else $errors[] = $e->getMessage();
    }
}

$pageTitle = t('member.pay_title');
$pageStyles = ['payment', 'membership'];
include '../includes/head.php';
?>
<main class="page">
    <div class="wrap">
        <a href="<?= SITE_URL ?>/membership" class="back-link"><?= icon('arrow-left', 16) ?> <?= e('nav.membership') ?></a>
        <header class="page-head">
            <span class="eyebrow"><?= icon('crown', 14) ?> <?= e('member.join') ?></span>
            <h1><?= e('member.pay_title') ?></h1>
            <p><?= PAYMENT_MODE === 'demo' ? e('pay.demo_note') : e('pay.intro') ?></p>
        </header>

        <?php if ($errors): ?>
            <div class="alert alert-danger mb-3" role="alert"><?= icon('alert') ?><ul><?php foreach ($errors as $err): ?><li><?= htmlspecialchars($err) ?></li><?php endforeach; ?></ul></div>
        <?php endif; ?>

        <form method="POST" enctype="multipart/form-data" class="split" data-once>
            <div class="stack">
                <section class="card">
                    <div class="card-head"><h2><?= icon('user', 18) ?> <?= e('member.applicant') ?></h2></div>
                    <div class="card-body form-grid">
                        <div class="field"><label class="label" for="first_name"><?= e('form.first_name') ?></label>
                            <input class="input" id="first_name" name="first_name" required maxlength="100" autocomplete="given-name" value="<?= htmlspecialchars($old['first_name']) ?>"></div>
                        <div class="field"><label class="label" for="last_name"><?= e('form.last_name') ?></label>
                            <input class="input" id="last_name" name="last_name" required maxlength="100" autocomplete="family-name" value="<?= htmlspecialchars($old['last_name']) ?>"></div>
                        <div class="field"><label class="label" for="email"><?= e('form.email') ?></label>
                            <input class="input" id="email" name="email" type="email" required maxlength="100" autocomplete="email" value="<?= htmlspecialchars($old['email']) ?>"></div>
                        <div class="field"><label class="label" for="phone"><?= e('form.phone') ?></label>
                            <input class="input" id="phone" name="phone" type="tel" required maxlength="20" pattern="[0-9+\-\s]{9,20}" title="<?= e('err.phone_invalid') ?>" autocomplete="tel" value="<?= htmlspecialchars($old['phone']) ?>"></div>
                    </div>
                </section>

                <section class="card">
                    <div class="card-head"><h2><?= icon('card', 18) ?> <?= e('payment.method') ?></h2></div>
                    <div class="card-body">
                        <div class="choices mb-3">
                            <?php foreach (['promptpay' => ['qr', 'payment.promptpay', 'payment.promptpay_desc'], 'bank_transfer' => ['bank', 'payment.bank', 'payment.bank_desc']] as $val => [$ico, $lbl, $desc]): ?>
                            <label class="choice">
                                <input type="radio" name="payment_method" value="<?= $val ?>" <?= $old['payment_method'] === $val ? 'checked' : '' ?>>
                                <span class="choice-box"><span class="choice-icon"><?= icon($ico) ?></span><span><strong><?= e($lbl) ?></strong><small><?= e($desc) ?></small></span></span>
                            </label>
                            <?php endforeach; ?>
                        </div>
                        <div class="pay-panel" data-method="promptpay" <?= $old['payment_method'] !== 'promptpay' ? 'hidden' : '' ?>>
                            <div class="qr-card">
                                <div class="qr-brand"><?= icon('qr', 16) ?> PromptPay</div>
                                <img id="promptpay-qr" data-payload="<?= htmlspecialchars($qrPayload) ?>" alt="<?= e('pay.qr_alt') ?>" width="240" height="240">
                                <div class="qr-amount"><?= formatPrice($amount) ?></div>
                                <div class="muted small"><?= e('pay.promptpay_to', ['number' => $promptpayNumber]) ?><br><?= htmlspecialchars($accountName) ?></div>
                            </div>
                        </div>
                        <div class="pay-panel" data-method="bank_transfer" <?= $old['payment_method'] !== 'bank_transfer' ? 'hidden' : '' ?>>
                            <dl class="kv bank-box">
                                <div class="kv-row"><dt><?= e('pay.bank_name') ?></dt><dd><?= htmlspecialchars($bankName) ?></dd></div>
                                <div class="kv-row"><dt><?= e('pay.account_no') ?></dt><dd><?= htmlspecialchars($bankAccount) ?></dd></div>
                                <div class="kv-row"><dt><?= e('pay.account_name') ?></dt><dd><?= htmlspecialchars($accountName) ?></dd></div>
                                <div class="kv-row"><dt><?= e('pay.amount') ?></dt><dd class="text-primary"><?= formatPrice($amount) ?></dd></div>
                            </dl>
                        </div>

                        <p class="label mt-3 mb-1"><?= e('pay.upload_title') ?></p>
                        <label class="upload">
                            <input type="file" name="slip" accept="image/*" data-compress data-require-qr required>
                            <?= icon('upload', 28) ?>
                            <strong data-upload-label data-empty="<?= e('pay.upload_cta') ?>"><?= e('pay.upload_cta') ?></strong>
                            <span class="small"><?= e('pay.upload_hint') ?></span>
                        </label>
                        <input type="hidden" name="qr_text" value="">
                        <p class="qr-status" data-qr-status hidden aria-live="polite"
                           data-scanning="<?= e('pay.qr_scanning') ?>" data-ok="<?= e('pay.qr_ok') ?>"
                           data-bad="<?= e('pay.err_no_qr') ?>" data-empty="<?= e('pay.err_slip_required') ?>"></p>
                    </div>
                </section>
            </div>

            <aside class="sticky">
                <div class="card">
                    <div class="card-head"><h2><?= icon('crown', 18) ?> <?= e('member.order') ?></h2></div>
                    <div class="card-body">
                        <dl class="kv">
                            <div class="kv-row"><dt><?= e('member.plan') ?></dt><dd><?= htmlspecialchars($plan['plan_name']) ?></dd></div>
                            <div class="kv-row"><dt><?= e('member.duration') ?></dt><dd><?= e('member.months', ['n' => (int) $plan['duration_months']]) ?></dd></div>
                            <div class="kv-row"><dt><?= e('member.start') ?></dt><dd><?= htmlspecialchars(formatDateLocalized($startDate)) ?></dd></div>
                            <div class="kv-row"><dt><?= e('member.end') ?></dt><dd><?= htmlspecialchars(formatDateLocalized($endDate)) ?></dd></div>
                        </dl>
                        <div class="kv-total"><span><?= e('booking.total') ?></span><span class="amount"><?= formatPrice($amount) ?></span></div>
                        <button type="submit" class="btn btn-primary btn-lg btn-block mt-2"><?= icon('check', 18) ?> <?= e('member.confirm') ?></button>
                    </div>
                </div>
            </aside>
        </form>
    </div>
</main>
<script src="<?= SITE_URL ?>/assets/js/qrcode.js"></script>
<script>
(function () {
    var img = document.getElementById('promptpay-qr');
    if (img) { var qr = qrcode(0, 'M'); qr.addData(img.dataset.payload); qr.make(); img.src = qr.createDataURL(6, 2); }
    document.querySelectorAll('input[name=payment_method]').forEach(function (r) {
        r.addEventListener('change', function () {
            document.querySelectorAll('.pay-panel').forEach(function (p) { p.hidden = p.dataset.method !== r.value; });
        });
    });
})();
</script>
<?php include '../includes/footer.php'; ?>
