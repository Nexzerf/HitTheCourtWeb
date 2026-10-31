<?php
// admin/sports.php
// รายละเอียดสนามของทุกกีฬา (มุมแอดมิน): ราคา/รอบ, เปิด-ปิดสนาม และอุปกรณ์ให้เช่า
// อุปกรณ์: แก้จำนวนในสต๊อก/ราคา/ค่าปรับ/จำนวนที่สมาชิกยืมฟรี, เพิ่ม และลบ
require_once '../config.php';
requireAdmin();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();
    $action = $_POST['action'] ?? '';
    $num = fn($k, $min = 0) => max($min, intval($_POST[$k] ?? 0));

    if ($action === 'court_toggle') {
        $pdo->prepare("UPDATE courts SET status = CASE WHEN status = 'available' THEN 'maintenance' ELSE 'available' END WHERE court_id = ?")->execute([$num('court_id')]);
        $_SESSION['flash_success'] = t('admin.court_updated');
    } elseif ($action === 'sport_update') {
        $pdo->prepare("UPDATE sports SET price = ?, price_per_round = ?, status = ? WHERE sport_id = ?")
            ->execute([$num('price', 1), $num('price', 1), ($_POST['status'] ?? '') === 'inactive' ? 'inactive' : 'active', $num('sport_id')]);
        $_SESSION['flash_success'] = t('admin.sport_updated');
    } elseif ($action === 'eq_update' || $action === 'eq_add') {
        $name = trim($_POST['eq_name'] ?? '');
        if ($name === '') {
            $_SESSION['flash_error'] = [t('admin.err_eq_name')];
        } else {
            $values = [$name, trim($_POST['name_th'] ?? '') ?: null, trim($_POST['unit_note'] ?? '') ?: null, $num('price'), $num('stock'),
                $num('fine_amount'), intdiv($num('stock'), 2), $num('member_free_units')];
            if ($action === 'eq_add') {
                $pdo->prepare("INSERT INTO equipment (eq_name, name_th, unit_note, price, stock, fine_amount, max_per_court, member_free_units, sport_id, status)
                               VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'available')")->execute([...$values, $num('sport_id')]);
                $_SESSION['flash_success'] = t('admin.eq_added', ['name' => $name]);
            } else {
                $pdo->prepare("UPDATE equipment SET eq_name = ?, name_th = ?, unit_note = ?, price = ?, stock = ?, fine_amount = ?, max_per_court = ?, member_free_units = ?, status = 'available'
                               WHERE eq_id = ?")->execute([...$values, $num('eq_id')]);
                $_SESSION['flash_success'] = t('admin.eq_updated', ['name' => $name]);
            }
        }
    } elseif ($action === 'eq_delete') {
        $id = $num('eq_id');
        $used = $pdo->prepare("SELECT COUNT(*) FROM booking_equipment WHERE eq_id = ?");
        $used->execute([$id]);
        if ($used->fetchColumn() > 0) {
            // มีประวัติการจองอ้างอิงอยู่ จึงซ่อนไว้แทนการลบจริง
            $pdo->prepare("UPDATE equipment SET status = 'unavailable' WHERE eq_id = ?")->execute([$id]);
        } else {
            $pdo->prepare("DELETE FROM equipment WHERE eq_id = ?")->execute([$id]);
        }
        $_SESSION['flash_success'] = t('admin.eq_deleted');
    }
    redirect('/admin/sports.php#sport-' . intval($_POST['sport_id'] ?? 0));
}

$sports = $pdo->query("SELECT * FROM sports ORDER BY sport_id")->fetchAll();
$courts = [];
foreach ($pdo->query("SELECT * FROM courts ORDER BY sport_id, court_number") as $c) $courts[$c['sport_id']][] = $c;
$equipment = [];
foreach ($pdo->query("SELECT * FROM equipment WHERE status = 'available' ORDER BY eq_id") as $eq) $equipment[$eq['sport_id']][] = $eq;
$slots = $pdo->query("SELECT sport_id, COUNT(*) FROM time_slots WHERE status = 'active' GROUP BY sport_id")->fetchAll(PDO::FETCH_KEY_PAIR);

$adminPage = 'sports';
$pageTitle = t('admin.nav_sports');
include '../includes/admin_layout_top.php';
?>
<nav class="guide-toc card card-pad mb-3">
    <?php foreach ($sports as $s): ?><a href="#sport-<?= $s['sport_id'] ?>"><?= htmlspecialchars(localized($s, 'sport_name', 'name_th')) ?></a><?php endforeach; ?>
</nav>

<?php foreach ($sports as $s): $sid = (int) $s['sport_id']; ?>
<section class="card mb-3" id="sport-<?= $sid ?>">
    <div class="card-head">
        <h2><?= icon('grid', 18) ?> <?= htmlspecialchars(localized($s, 'sport_name', 'name_th')) ?>
            <?php if ($s['status'] !== 'active'): ?><span class="badge badge-danger"><?= e('admin.hidden') ?></span><?php endif; ?></h2>
        <div class="meta">
            <span><?= icon('grid', 14) ?> <?= e('booking.courts_n', ['n' => count($courts[$sid] ?? [])]) ?></span>
            <span><?= icon('clock', 14) ?> <?= (int) $s['duration_minutes'] ?> <?= e('admin.min') ?></span>
            <span><?= icon('calendar', 14) ?> <?= e('courts.rounds_per_day', ['n' => (int) ($slots[$sid] ?? 0)]) ?></span>
        </div>
    </div>
    <div class="card-body">
        <div class="sport-settings">
            <form method="POST" class="sport-form">
                <?= csrfField() ?><input type="hidden" name="action" value="sport_update"><input type="hidden" name="sport_id" value="<?= $sid ?>">
                <div class="field"><label class="label" for="price<?= $sid ?>"><?= e('admin.price_round') ?></label><input class="input" id="price<?= $sid ?>" type="number" name="price" min="1" value="<?= (int) $s['price'] ?>"></div>
                <div class="field"><label class="label" for="status<?= $sid ?>"><?= e('admin.status') ?></label>
                    <select class="select" id="status<?= $sid ?>" name="status"><option value="active"><?= e('admin.shown') ?></option><option value="inactive" <?= $s['status'] !== 'active' ? 'selected' : '' ?>><?= e('admin.hidden') ?></option></select></div>
                <button class="btn btn-outline" type="submit"><?= icon('check', 16) ?> <?= e('admin.save') ?></button>
            </form>
            <div>
                <p class="label mb-1"><?= e('admin.courts_toggle') ?></p>
                <div class="court-chips">
                    <?php foreach ($courts[$sid] ?? [] as $c): $closed = $c['status'] !== 'available'; ?>
                    <form method="POST">
                        <?= csrfField() ?><input type="hidden" name="action" value="court_toggle"><input type="hidden" name="court_id" value="<?= $c['court_id'] ?>"><input type="hidden" name="sport_id" value="<?= $sid ?>">
                        <button type="submit" class="court-chip<?= $closed ? ' is-closed' : '' ?>" title="<?= e($closed ? 'admin.open_court' : 'admin.close_court') ?>">
                            <span class="dot"></span> <?= (int) $c['court_number'] ?> · <?= e($closed ? 'booking.closed' : 'admin.open') ?>
                        </button>
                    </form>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>

        <h3 class="section-title"><?= icon('box', 18) ?> <?= e('admin.equipment') ?></h3>
        <div class="eq-list">
        <?php
        $eqRows = $equipment[$sid] ?? [];
        $eqRows[] = null; // แถวสุดท้าย = เพิ่มอุปกรณ์ใหม่
        foreach ($eqRows as $eq):
            $isNew = $eq === null;
            $fid = $isNew ? 'add' . $sid : 'eq' . $eq['eq_id'];
            $v = fn($k, $d = '') => htmlspecialchars((string) ($eq[$k] ?? $d));
        ?>
            <div class="eq-item<?= $isNew ? ' is-new' : '' ?>">
                <?php if ($isNew): ?><p class="eq-new-title"><?= icon('plus', 16) ?> <?= e('admin.new_equipment') ?></p><?php endif; ?>
                <div class="eq-fields">
                    <div class="field eq-wide"><label class="label small"><?= e('admin.eq_name') ?></label>
                        <input form="<?= $fid ?>" class="input" name="eq_name" value="<?= $v('eq_name') ?>" required></div>
                    <div class="field eq-wide"><label class="label small"><?= e('admin.eq_name_th') ?></label>
                        <input form="<?= $fid ?>" class="input" name="name_th" value="<?= $v('name_th') ?>"></div>
                    <div class="field eq-wide"><label class="label small"><?= e('admin.unit_note') ?></label>
                        <input form="<?= $fid ?>" class="input" name="unit_note" value="<?= $v('unit_note') ?>"></div>
                    <div class="field"><label class="label small"><?= e('admin.eq_price') ?></label>
                        <input form="<?= $fid ?>" class="input" type="number" min="0" name="price" value="<?= $v('price', '0') ?>"></div>
                    <div class="field"><label class="label small"><?= e('admin.eq_stock') ?></label>
                        <input form="<?= $fid ?>" class="input" type="number" min="0" name="stock" value="<?= $v('stock', '0') ?>"></div>
                    <div class="field"><label class="label small"><?= e('admin.eq_fine') ?></label>
                        <input form="<?= $fid ?>" class="input" type="number" min="0" name="fine_amount" value="<?= $v('fine_amount', '50') ?>"></div>
                    <div class="field"><label class="label small"><?= e('admin.eq_free') ?></label>
                        <input form="<?= $fid ?>" class="input" type="number" min="0" name="member_free_units" value="<?= $v('member_free_units', '0') ?>"></div>
                </div>
                <div class="eq-foot">
                    <span class="muted small"><?= e('admin.eq_per_court') ?>: <strong class="text-ink"><?= $isNew ? '–' : maxPerCourt($eq) ?></strong></span>
                    <div class="row">
                        <?php if (!$isNew): ?>
                        <form method="POST" data-confirm="<?= e('admin.confirm_delete_eq', ['name' => $eq['eq_name']]) ?>">
                            <?= csrfField() ?><input type="hidden" name="action" value="eq_delete"><input type="hidden" name="eq_id" value="<?= $eq['eq_id'] ?>"><input type="hidden" name="sport_id" value="<?= $sid ?>">
                            <button class="btn btn-danger-outline btn-sm" type="submit"><?= icon('trash', 16) ?> <?= e('admin.delete') ?></button>
                        </form>
                        <?php endif; ?>
                        <form id="<?= $fid ?>" method="POST">
                            <?= csrfField() ?><input type="hidden" name="action" value="<?= $isNew ? 'eq_add' : 'eq_update' ?>"><?php if (!$isNew): ?><input type="hidden" name="eq_id" value="<?= $eq['eq_id'] ?>"><?php endif; ?><input type="hidden" name="sport_id" value="<?= $sid ?>">
                            <button class="btn <?= $isNew ? 'btn-dark' : 'btn-primary' ?> btn-sm" type="submit"><?= icon($isNew ? 'plus' : 'check', 16) ?> <?= e($isNew ? 'admin.add' : 'admin.save') ?></button>
                        </form>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
        </div>
        <p class="hint mt-2 mb-0"><?= e('admin.per_court_hint') ?></p>
    </div>
</section>
<?php endforeach; ?>
<style>
.guide-toc { display: flex; flex-wrap: wrap; gap: 8px; }
.guide-toc a { padding: 6px 12px; border-radius: 999px; background: #F1F5F9; color: var(--c-ink) !important; font-size: .85rem; font-weight: 600; text-decoration: none; }
section[id] { scroll-margin-top: 80px; }
</style>
<?php include '../includes/admin_layout_bottom.php'; ?>
