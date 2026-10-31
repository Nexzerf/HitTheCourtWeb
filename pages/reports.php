<?php
// pages/reports.php
// แจ้งปัญหา / ขอยกเลิกการจอง: เลือกหัวข้อ, พิมพ์ข้อความ, แนบรูป แล้วส่ง
// ด้านล่างเป็นประวัติการรายงาน (Report details) พร้อมสถานะ
require_once '../config.php';
requireLogin();

const REPORT_CATEGORIES = ['cancel', 'damage', 'service', 'other'];

$errors = [];
$old = ['category' => $_GET['category'] ?? 'damage', 'topic' => '', 'description' => ''];
if (!in_array($old['category'], REPORT_CATEGORIES, true)) $old['category'] = 'other';

// มาจากปุ่ม "ขอยกเลิกการจอง" ในหน้าการจองของฉัน
if (!empty($_GET['booking'])) {
    $old['topic'] = t('report.cancel_topic', ['code' => substr($_GET['booking'], 0, 20)]);
    $old['description'] = t('report.cancel_template', ['code' => substr($_GET['booking'], 0, 20)]);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $old = [
        'category' => in_array($_POST['category'] ?? '', REPORT_CATEGORIES, true) ? $_POST['category'] : 'other',
        'topic' => trim($_POST['topic'] ?? ''),
        'description' => trim($_POST['description'] ?? ''),
    ];
    if ($old['topic'] === '' || mb_strlen($old['topic']) > 100) $errors[] = t('report.err_topic');
    if ($old['description'] === '') $errors[] = t('report.err_content');

    $imagePath = null;
    if (!$errors && !empty($_FILES['image']['name'])) {
        $file = $_FILES['image'];
        $mime = $file['error'] === UPLOAD_ERR_OK ? mime_content_type($file['tmp_name']) : '';
        if (!in_array($mime, ['image/jpeg', 'image/png'], true)) $errors[] = t('pay.err_type');
        elseif ($file['size'] > 4 * 1024 * 1024) $errors[] = t('pay.err_size');
        else {
            $upload = saveUploadedFile($file, 'reports', uniqid() . ($mime === 'image/png' ? '.png' : '.jpg'));
            if (!$upload) $errors[] = t('pay.err_upload');
            else $imagePath = $upload['path'];
        }
    }

    if (!$errors) {
        $code = 'RP' . date('Ymd') . strtoupper(substr(uniqid(), -6));
        $pdo->prepare("INSERT INTO reports (report_code, user_id, category, topic, description, image_path, status) VALUES (?, ?, ?, ?, ?, ?, 'new')")
            ->execute([$code, $_SESSION['user_id'], $old['category'], $old['topic'], $old['description'], $imagePath]);
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

                    <div class="alert alert-info mb-3" data-cancel-help <?= $old['category'] !== 'cancel' ? 'hidden' : '' ?>>
                        <?= icon('info') ?><div><?= e('report.cancel_help') ?></div>
                    </div>

                    <div class="field">
                        <label class="label" for="topic"><?= e('report.topic') ?></label>
                        <input class="input" id="topic" name="topic" required maxlength="100" value="<?= htmlspecialchars($old['topic']) ?>" placeholder="<?= e('report.topic_ph') ?>">
                    </div>
                    <div class="field">
                        <label class="label" for="description"><?= e('report.content') ?></label>
                        <textarea class="textarea" id="description" name="description" required rows="5" placeholder="<?= e('report.content_ph') ?>"><?= htmlspecialchars($old['description']) ?></textarea>
                    </div>
                    <div class="field">
                        <span class="label"><?= e('report.photo') ?> <span class="opt">(<?= e('common.optional') ?>)</span></span>
                        <label class="upload">
                            <input type="file" name="image" accept="image/jpeg,image/png">
                            <?= icon('image', 26) ?>
                            <strong data-upload-label data-empty="<?= e('report.photo_cta') ?>"><?= e('report.photo_cta') ?></strong>
                            <span class="small"><?= e('pay.upload_hint') ?></span>
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
document.querySelectorAll('input[name=category]').forEach(function (r) {
    r.addEventListener('change', function () { document.querySelector('[data-cancel-help]').hidden = r.value !== 'cancel'; });
});
</script>
<?php include '../includes/footer.php'; ?>
