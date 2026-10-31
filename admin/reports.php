<?php
// admin/reports.php
// Customer service: คำรายงานทั้งหมด (หัวข้อ, ข้อความ, รูป, วันที่รายงาน) พร้อมอัปเดตสถานะ/ตอบกลับ และปุ่มลบ
require_once '../config.php';
requireAdmin();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();
    $id = intval($_POST['report_id'] ?? 0);
    if (($_POST['action'] ?? '') === 'update') {
        $status = in_array($_POST['status'] ?? '', ['new', 'in_progress', 'resolved'], true) ? $_POST['status'] : 'new';
        $resolved = $status === 'resolved';
        $pdo->prepare("UPDATE reports SET status = ?, admin_notes = ?, resolved_by = ?, resolved_at = ? WHERE report_id = ?")
            ->execute([$status, trim($_POST['admin_notes'] ?? '') ?: null, $resolved ? (int) $_SESSION['admin_id'] : null,
                $resolved ? date('Y-m-d H:i:s') : null, $id]);
        $_SESSION['flash_success'] = t('admin.report_updated');
    } elseif (($_POST['action'] ?? '') === 'delete') {
        $pdo->prepare("DELETE FROM reports WHERE report_id = ?")->execute([$id]);
        $_SESSION['flash_success'] = t('admin.report_deleted');
    }
    redirect('/admin/reports.php?' . http_build_query(array_intersect_key($_GET, array_flip(['status', 'category']))));
}

$statusFilter = in_array($_GET['status'] ?? '', ['new', 'in_progress', 'resolved'], true) ? $_GET['status'] : '';
$catFilter = in_array($_GET['category'] ?? '', ['cancel', 'damage', 'service', 'other'], true) ? $_GET['category'] : '';
$where = ['1=1']; $params = [];
if ($statusFilter) { $where[] = 'r.status = ?'; $params[] = $statusFilter; }
if ($catFilter) { $where[] = 'r.category = ?'; $params[] = $catFilter; }
$stmt = $pdo->prepare("SELECT r.*, u.username, u.email, u.phone FROM reports r JOIN users u ON u.user_id = r.user_id
                       WHERE " . implode(' AND ', $where) . " ORDER BY CASE r.status WHEN 'new' THEN 1 WHEN 'in_progress' THEN 2 ELSE 3 END, r.created_at DESC");
$stmt->execute($params);
$reports = $stmt->fetchAll();
$counts = $pdo->query("SELECT status, COUNT(*) FROM reports GROUP BY status")->fetchAll(PDO::FETCH_KEY_PAIR);

$catIcons = ['cancel' => 'x-circle', 'damage' => 'alert', 'service' => 'message', 'other' => 'help'];
$statusClass = ['new' => 'badge-primary', 'in_progress' => 'badge-warning', 'resolved' => 'badge-success'];

$adminPage = 'reports';
$pageTitle = t('admin.nav_reports');
include '../includes/admin_layout_top.php';
?>
<div class="kpis">
    <?php foreach (['new' => 'flag', 'in_progress' => 'clock', 'resolved' => 'check-circle'] as $st => $ico): ?>
    <a class="card kpi" href="?status=<?= $st ?>" style="text-decoration:none">
        <div class="kpi-label"><?= icon($ico, 16) ?> <?= e('report.status_' . $st) ?></div>
        <div class="kpi-value"><?= (int) ($counts[$st] ?? 0) ?></div>
    </a>
    <?php endforeach; ?>
</div>

<form class="filters card card-pad" method="GET">
    <div class="field"><label class="label" for="status"><?= e('admin.status') ?></label>
        <select class="select" id="status" name="status"><option value=""><?= e('admin.all') ?></option>
            <?php foreach (['new', 'in_progress', 'resolved'] as $st): ?><option value="<?= $st ?>" <?= $statusFilter === $st ? 'selected' : '' ?>><?= e('report.status_' . $st) ?></option><?php endforeach; ?>
        </select></div>
    <div class="field"><label class="label" for="category"><?= e('report.topic_type') ?></label>
        <select class="select" id="category" name="category"><option value=""><?= e('admin.all') ?></option>
            <?php foreach (array_keys($catIcons) as $c): ?><option value="<?= $c ?>" <?= $catFilter === $c ? 'selected' : '' ?>><?= e('report.cat_' . $c) ?></option><?php endforeach; ?>
        </select></div>
    <div class="filter-actions">
        <button class="btn btn-primary" type="submit"><?= e('admin.filter') ?></button>
        <a class="btn btn-ghost" href="<?= SITE_URL ?>/admin/reports.php"><?= e('admin.clear') ?></a>
    </div>
</form>

<?php if (!$reports): ?><div class="card empty"><?= icon('message', 44) ?><h3><?= e('report.empty') ?></h3></div><?php endif; ?>

<div class="adm-list">
<?php foreach ($reports as $r): $cat = $r['category'] ?? 'other'; ?>
    <article class="card">
        <div class="card-head">
            <h3><span class="report-ico"><?= icon($catIcons[$cat] ?? 'help', 16) ?></span> <?= htmlspecialchars($r['topic']) ?></h3>
            <span class="badge <?= $statusClass[$r['status']] ?? '' ?>"><?= e('report.status_' . $r['status']) ?></span>
        </div>
        <div class="card-body adm-report">
            <div>
                <div class="meta mb-2">
                    <span><?= icon('tag', 14) ?> <?= e('report.cat_' . $cat) ?></span>
                    <span><?= icon('calendar', 14) ?> <?= htmlspecialchars(formatDateLocalized($r['created_at'])) ?> <?= date('H:i', strtotime($r['created_at'])) ?></span>
                    <span><?= icon('user', 14) ?> <?= htmlspecialchars($r['username']) ?> · <?= htmlspecialchars($r['phone'] ?: $r['email']) ?></span>
                    <span><?= icon('file', 14) ?> <?= htmlspecialchars($r['report_code']) ?></span>
                </div>
                <p class="report-text"><?= nl2br(htmlspecialchars($r['description'])) ?></p>
                <form method="POST" class="report-form">
                    <?= csrfField() ?><input type="hidden" name="action" value="update"><input type="hidden" name="report_id" value="<?= $r['report_id'] ?>">
                    <div class="field report-reply"><label class="label small"><?= e('report.reply') ?></label>
                        <input class="input" name="admin_notes" value="<?= htmlspecialchars($r['admin_notes'] ?? '') ?>"></div>
                    <div class="field"><label class="label small"><?= e('admin.status') ?></label>
                        <select class="select" name="status">
                            <?php foreach (['new', 'in_progress', 'resolved'] as $st): ?><option value="<?= $st ?>" <?= $r['status'] === $st ? 'selected' : '' ?>><?= e('report.status_' . $st) ?></option><?php endforeach; ?>
                        </select></div>
                    <button class="btn btn-primary" type="submit"><?= icon('refresh', 16) ?> <?= e('admin.update') ?></button>
                </form>
                <form method="POST" class="mt-2" data-confirm="<?= e('admin.confirm_delete_report') ?>">
                    <?= csrfField() ?><input type="hidden" name="action" value="delete"><input type="hidden" name="report_id" value="<?= $r['report_id'] ?>">
                    <button class="btn btn-danger-outline btn-sm" type="submit"><?= icon('trash', 16) ?> <?= e('admin.delete') ?></button>
                </form>
            </div>
            <div>
                <?php if ($r['image_path']): ?>
                    <a href="<?= SITE_URL ?>/<?= htmlspecialchars($r['image_path']) ?>" target="_blank" rel="noopener"><img class="slip-thumb" src="<?= SITE_URL ?>/<?= htmlspecialchars($r['image_path']) ?>" alt="<?= e('report.photo') ?>" loading="lazy" onerror="this.parentNode.outerHTML='<p class=&quot;muted small&quot;><?= e('admin.no_photo') ?></p>'"></a>
                <?php else: ?><p class="muted small"><?= icon('image', 16) ?> <?= e('admin.no_photo') ?></p><?php endif; ?>
            </div>
        </div>
    </article>
<?php endforeach; ?>
</div>
<style>
.adm-report { display: grid; grid-template-columns: minmax(0, 1fr) 220px; gap: 28px; }
.report-ico { display: inline-grid; place-items: center; width: 32px; height: 32px; border-radius: 8px; background: #F1F5F9; flex-shrink: 0; }
.report-text { margin: 0 0 20px; padding: 14px 16px; border-radius: 8px; background: #F8FAFC; border: 1px solid var(--c-border); white-space: normal; }
.report-form { display: grid; grid-template-columns: minmax(0, 1fr) 180px auto; gap: 12px; align-items: end; }
.report-form .field + .field { margin-top: 0; }
@media (max-width: 760px) {
    .adm-report { grid-template-columns: minmax(0, 1fr); gap: 16px; }
    .report-form { grid-template-columns: minmax(0, 1fr) auto; }
    .report-form .report-reply { grid-column: 1 / -1; }
}
</style>
<?php include '../includes/admin_layout_bottom.php'; ?>
