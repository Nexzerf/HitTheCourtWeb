<?php
// pages/reports.php
// แจ้งปัญหา / ขอยกเลิกการจอง: เลือกหัวข้อ, พิมพ์ข้อความ, แนบรูป แล้วส่ง
// ด้านล่างเป็นประวัติการรายงาน (Report details) พร้อมสถานะ
require_once '../config.php';
requireLogin();

const REPORT_CATEGORIES = ['cancel', 'damage', 'service', 'other'];

$errors = [];
$fieldErrors = [];
$old = ['category' => $_GET['category'] ?? 'damage', 'topic' => '', 'description' => '',
        'booking_code' => substr($_GET['booking'] ?? '', 0, 20), 'bank_name' => '', 'bank_account' => '', 'account_name' => ''];
if (!in_array($old['category'], REPORT_CATEGORIES, true)) $old['category'] = 'other';

// การจองที่ขอยกเลิกได้: จ่ายเงินแล้ว ยังไม่ยกเลิก และยังไม่ถึงเวลาเล่น
$stmt = $pdo->prepare("SELECT booking_id FROM bookings WHERE user_id = ? AND payment_status = 'paid' AND booking_status = 'active' AND booking_date >= ? ORDER BY booking_date, booking_id");
$stmt->execute([$_SESSION['user_id'], date('Y-m-d')]);
$cancellable = [];
foreach ($stmt->fetchAll(PDO::FETCH_COLUMN) as $id) {
    $b = loadBookingDetails($pdo, (int) $id);
    if ($b && !bookingHasStarted($b)) $cancellable[$b['booking_code']] = $b;
}
// การจองที่ส่งคำขอยกเลิกไปแล้ว (ยังไม่ปิดเรื่อง) ไม่ต้องส่งซ้ำ
$stmt = $pdo->prepare("SELECT topic, description FROM reports WHERE user_id = ? AND category = 'cancel' AND status <> 'resolved'");
$stmt->execute([$_SESSION['user_id']]);
$openCancelText = implode("\n", array_map(fn($r) => $r['topic'] . "\n" . $r['description'], $stmt->fetchAll()));
$alreadyRequested = fn($code) => $code !== '' && str_contains($openCancelText, $code);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    foreach (['topic', 'description', 'booking_code', 'bank_name', 'bank_account', 'account_name'] as $f) $old[$f] = trim($_POST[$f] ?? '');
    $old['category'] = in_array($_POST['category'] ?? '', REPORT_CATEGORIES, true) ? $_POST['category'] : 'other';

    if ($old['category'] === 'cancel') {
        // ขอยกเลิก: ต้องเลือกการจอง และกรอกบัญชีรับเงินคืนให้ครบ
        if (!isset($cancellable[$old['booking_code']])) $fieldErrors['booking_code'] = t('report.err_booking');
        elseif ($alreadyRequested($old['booking_code'])) $fieldErrors['booking_code'] = t('report.err_already');
        if ($old['bank_name'] === '') $fieldErrors['bank_name'] = t('report.err_bank_name');
        $digits = preg_replace('/[\s-]/', '', $old['bank_account']);
        if (!preg_match('/^\d{10,15}$/', $digits)) $fieldErrors['bank_account'] = t('report.err_bank_account');
        if ($old['account_name'] === '') $fieldErrors['account_name'] = t('report.err_account_name');
        if (!$fieldErrors) {
            $b = $cancellable[$old['booking_code']];
            $old['topic'] = t('report.cancel_topic', ['code' => $b['booking_code']]);
            $refundBlock = t('report.cancel_summary', [
                'code' => $b['booking_code'], 'booking' => $b['sport_label'] . ' · ' . $b['court_label'] . ' · ' . formatDateLocalized($b['booking_date']) . ' ' . $b['time_label'],
                'amount' => formatPrice($b['total_price']), 'bank' => $old['bank_name'], 'account' => $digits, 'name' => $old['account_name'],
            ]);
            $descriptionToSave = $refundBlock . ($old['description'] !== '' ? "\n\n" . $old['description'] : '');
        }
    } else {
        if ($old['topic'] === '' || mb_strlen($old['topic']) > 100) $fieldErrors['topic'] = t('report.err_topic');
        if ($old['description'] === '') $fieldErrors['description'] = t('report.err_content');
        $descriptionToSave = $old['description'];
    }
    $errors = array_values($fieldErrors);

    $imagePath = null;
    if (!$errors && !empty($_FILES['image']['name'])) {
        try {
            $img = validateImageUpload($_FILES['image']);
            $upload = saveUploadedFile($_FILES['image'], 'reports', uniqid() . '.' . $img['ext'], $img['mime']);
            if (!$upload) throw new Exception(t('pay.err_upload'));
            $imagePath = $upload['path'];
        } catch (Exception $e) {
            $errors[] = $e->getMessage();
        }
    }

    if (!$errors) {
        $code = 'RP' . date('Ymd') . strtoupper(substr(uniqid(), -6));
        $pdo->prepare("INSERT INTO reports (report_code, user_id, category, topic, description, image_path, status) VALUES (?, ?, ?, ?, ?, ?, 'new')")
            ->execute([$code, $_SESSION['user_id'], $old['category'], mb_substr($old['topic'], 0, 100), $descriptionToSave, $imagePath]);
        $_SESSION['flash_success'] = t('report.sent', ['code' => $code]);
        redirect('/reports#history');
    }
}

$stmt = $pdo->prepare("SELECT * FROM reports WHERE user_id = ? ORDER BY created_at DESC");
$stmt->execute([$_SESSION['user_id']]);
$reports = $stmt->fetchAll();

$catIcons = ['cancel' => 'x-circle', 'damage' => 'alert', 'service' => 'message', 'other' => 'help'];
$statusClass = ['new' => 'badge-primary', 'in_progress' => 'badge-warning', 'resolved' => 'badge-success'];

$pageTitle = t('report.title');
$pageStyles = ['reports'];
include '../includes/head.php';
?>
<main class="page">
    <div class="wrap">
        <header class="page-head">
            <span class="eyebrow"><?= icon('flag', 14) ?> <?= e('nav.contact') ?></span>
            <h1><?= e('report.title') ?></h1>
            <p><?= e('report.intro') ?></p>
        </header>

        <?php if (!empty($_SESSION['flash_success'])): ?>
            <div class="alert alert-success mb-3" role="status"><?= icon('check-circle') ?><div><?= htmlspecialchars($_SESSION['flash_success']) ?></div></div>
        <?php unset($_SESSION['flash_success']); endif; ?>
        <?php if ($errors): ?>
            <div class="alert alert-danger mb-3" role="alert"><?= icon('alert') ?><ul><?php foreach ($errors as $err): ?><li><?= htmlspecialchars($err) ?></li><?php endforeach; ?></ul></div>
        <?php endif; ?>

        <div class="split">
            <form method="POST" enctype="multipart/form-data" class="card" data-once>
                <div class="card-head"><h2><?= icon('edit', 18) ?> <?= e('report.new') ?></h2></div>
                <div class="card-body">
                    <p class="label mb-1"><?= e('report.topic_type') ?></p>
                    <div class="choices report-cats mb-3">
                        <?php foreach (REPORT_CATEGORIES as $cat): ?>
                        <label class="choice">
                            <input type="radio" name="category" value="<?= $cat ?>" <?= $old['category'] === $cat ? 'checked' : '' ?>>
                            <span class="choice-box"><span class="choice-icon"><?= icon($catIcons[$cat]) ?></span><strong><?= e('report.cat_' . $cat) ?></strong></span>
                        </label>
                        <?php endforeach; ?>
                    </div>

                    <?php $fe = fn($k) => isset($fieldErrors[$k]) ? '<span class="field-error">' . htmlspecialchars($fieldErrors[$k]) . '</span>' : ''; ?>
                    <div data-for="cancel" <?= $old['category'] !== 'cancel' ? 'hidden' : '' ?>>
                        <div class="alert alert-info mb-3"><?= icon('info') ?><div><?= e('report.cancel_help') ?></div></div>
                        <?php if (!$cancellable): ?>
                            <div class="alert alert-warning mb-3"><?= icon('alert') ?><div><?= e('report.no_cancellable') ?></div></div>
                        <?php endif; ?>
                        <div class="field">
                            <label class="label" for="booking_code"><?= e('report.booking') ?></label>
                            <select class="select<?= isset($fieldErrors['booking_code']) ? ' is-invalid' : '' ?>" id="booking_code" name="booking_code" data-req>
                                <option value=""><?= e('report.booking_ph') ?></option>
                                <?php foreach ($cancellable as $code => $b): $done = $alreadyRequested($code); ?>
                                    <option value="<?= htmlspecialchars($code) ?>" <?= $old['booking_code'] === $code ? 'selected' : '' ?> <?= $done ? 'disabled' : '' ?>>
                                        <?= htmlspecialchars($code . ' · ' . $b['sport_label'] . ' · ' . formatDateLocalized($b['booking_date'], true) . ' ' . $b['time_label'] . ' · ' . formatPrice($b['total_price'])) ?><?= $done ? ' (' . e('report.requested') . ')' : '' ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <?= $fe('booking_code') ?>
                        </div>
                        <div class="form-grid">
                            <div class="field">
                                <label class="label" for="bank_name"><?= e('pay.bank_name') ?></label>
                                <input class="input<?= isset($fieldErrors['bank_name']) ? ' is-invalid' : '' ?>" id="bank_name" name="bank_name" maxlength="60" list="banks" data-req value="<?= htmlspecialchars($old['bank_name']) ?>" placeholder="<?= e('report.bank_ph') ?>">
                                <datalist id="banks"><?php foreach (['กสิกรไทย', 'ไทยพาณิชย์', 'กรุงเทพ', 'กรุงไทย', 'กรุงศรีอยุธยา', 'ทหารไทยธนชาต (ttb)', 'ออมสิน', 'ธ.ก.ส.', 'พร้อมเพย์'] as $bk): ?><option value="<?= $bk ?>"><?php endforeach; ?></datalist>
                                <?= $fe('bank_name') ?>
                            </div>
                            <div class="field">
                                <label class="label" for="bank_account"><?= e('pay.account_no') ?></label>
                                <input class="input<?= isset($fieldErrors['bank_account']) ? ' is-invalid' : '' ?>" id="bank_account" name="bank_account" maxlength="20" inputmode="numeric" pattern="[0-9\s\-]{10,20}" data-req value="<?= htmlspecialchars($old['bank_account']) ?>" placeholder="xxx-x-xxxxx-x">
                                <?= $fe('bank_account') ?>
                            </div>
                        </div>
                        <div class="field">
                            <label class="label" for="account_name"><?= e('pay.account_name') ?></label>
                            <input class="input<?= isset($fieldErrors['account_name']) ? ' is-invalid' : '' ?>" id="account_name" name="account_name" maxlength="100" data-req value="<?= htmlspecialchars($old['account_name']) ?>" placeholder="<?= e('report.account_name_ph') ?>">
                            <?= $fe('account_name') ?>
                        </div>
                    </div>

                    <div class="field" data-for="general" <?= $old['category'] === 'cancel' ? 'hidden' : '' ?>>
                        <label class="label" for="topic"><?= e('report.topic') ?></label>
                        <input class="input<?= isset($fieldErrors['topic']) ? ' is-invalid' : '' ?>" id="topic" name="topic" maxlength="100" data-req value="<?= htmlspecialchars($old['category'] === 'cancel' ? '' : $old['topic']) ?>" placeholder="<?= e('report.topic_ph') ?>">
                        <?= $fe('topic') ?>
                    </div>
                    <div class="field">
                        <label class="label" for="description"><?= e('report.content') ?> <span class="opt" data-for="cancel" <?= $old['category'] !== 'cancel' ? 'hidden' : '' ?>>(<?= e('common.optional') ?>)</span></label>
                        <textarea class="textarea<?= isset($fieldErrors['description']) ? ' is-invalid' : '' ?>" id="description" name="description" rows="5" placeholder="<?= e('report.content_ph') ?>"><?= htmlspecialchars($old['description']) ?></textarea>
                        <?= $fe('description') ?>
                    </div>
                    <div class="field">
                        <span class="label"><?= e('report.photo') ?> <span class="opt">(<?= e('common.optional') ?>)</span></span>
                        <label class="upload">
                            <input type="file" name="image" accept="image/*" data-compress>
                            <?= icon('image', 26) ?>
                            <strong data-upload-label data-empty="<?= e('report.photo_cta') ?>"><?= e('report.photo_cta') ?></strong>
                            <span class="small"><?= e('report.photo_hint') ?></span>
                        </label>
                    </div>

                    <div class="row mt-3">
                        <button type="submit" class="btn btn-primary"><?= icon('send', 18) ?> <?= e('report.submit') ?></button>
                        <a href="<?= SITE_URL ?>/reports" class="btn btn-outline"><?= e('report.cancel_btn') ?></a>
                    </div>
                </div>
            </form>

            <aside class="card sticky" id="history">
                <div class="card-head"><h2><?= icon('file', 18) ?> <?= e('report.details') ?></h2><span class="badge"><?= count($reports) ?></span></div>
                <?php if (!$reports): ?>
                    <div class="empty"><?= icon('message', 40) ?><p class="mb-0"><?= e('report.empty') ?></p></div>
                <?php else: ?>
                <ul class="report-list">
                    <?php foreach ($reports as $r): $cat = $r['category'] ?? 'other'; ?>
                    <li>
                        <details>
                            <summary>
                                <span class="report-ico"><?= icon($catIcons[$cat] ?? 'help', 16) ?></span>
                                <span class="report-sum">
                                    <strong><?= htmlspecialchars($r['topic']) ?></strong>
                                    <small class="muted"><?= e('report.cat_' . $cat) ?> · <?= htmlspecialchars(formatDateLocalized($r['created_at'])) ?></small>
                                </span>
                                <span class="badge <?= $statusClass[$r['status']] ?? '' ?>"><?= e('report.status_' . $r['status']) ?></span>
                            </summary>
                            <div class="report-body">
                                <p><?= nl2br(htmlspecialchars($r['description'])) ?></p>
                                <?php if ($r['image_path']): ?>
                                    <a href="<?= SITE_URL ?>/<?= htmlspecialchars($r['image_path']) ?>" target="_blank" rel="noopener"><img src="<?= SITE_URL ?>/<?= htmlspecialchars($r['image_path']) ?>" alt="<?= e('report.photo') ?>" class="report-img" loading="lazy"></a>
                                <?php endif; ?>
                                <?php if (!empty($r['admin_notes'])): ?>
                                    <div class="note"><?= icon('message', 16) ?> <div><strong><?= e('report.reply') ?>:</strong> <?= nl2br(htmlspecialchars($r['admin_notes'])) ?></div></div>
                                <?php endif; ?>
                                <p class="hint mb-0"><?= htmlspecialchars($r['report_code']) ?> · <?= e('report.sent_on') ?> <?= htmlspecialchars(formatDateLocalized($r['created_at'])) ?> <?= date('H:i', strtotime($r['created_at'])) ?></p>
                            </div>
                        </details>
                    </li>
                    <?php endforeach; ?>
                </ul>
                <?php endif; ?>
            </aside>
        </div>
    </div>
</main>
<script>
(function () {
    // สลับช่องกรอกตามหัวข้อ: ยกเลิกการจอง = เลือกการจอง + บัญชีรับเงินคืน (บังคับ) / หัวข้ออื่น = หัวเรื่อง + ข้อความ (บังคับ)
    var sync = function () {
        var cat = (document.querySelector('input[name=category]:checked') || {}).value;
        var isCancel = cat === 'cancel';
        document.querySelectorAll('[data-for]').forEach(function (el) {
            el.hidden = (el.dataset.for === 'cancel') !== isCancel;
            el.querySelectorAll('[data-req]').forEach(function (i) { i.required = !el.hidden; });
        });
        if (document.querySelector('[data-for=general]').hidden === false) {
            document.querySelectorAll('[data-for=general] [data-req]').forEach(function (i) { i.required = true; });
        }
        document.getElementById('description').required = !isCancel;
    };
    document.querySelectorAll('input[name=category]').forEach(function (r) { r.addEventListener('change', sync); });
    sync();
})();
</script>
<?php include '../includes/footer.php'; ?>
