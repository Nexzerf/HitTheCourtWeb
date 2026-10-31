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
        $pdo->prepare("UPDATE courts SET status = IF(status = 'available', 'maintenance', 'available') WHERE court_id = ?")->execute([$num('court_id')]);
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
        <div class="grid grid-2 mb-3">
            <form method="POST" class="row" style="align-items:flex-end">
                <?= csrfField() ?><input type="hidden" name="action" value="sport_update"><input type="hidden" name="sport_id" value="<?= $sid ?>">
                <div class="field"><label class="label small"><?= e('admin.price_round') ?></label><input class="input" type="number" name="price" min="1" value="<?= (int) $s['price'] ?>" style="max-width:140px"></div>
                <div class="field"><label class="label small"><?= e('admin.status') ?></label>
                    <select class="select" name="status" style="width:auto"><option value="active"><?= e('admin.shown') ?></option><option value="inactive" <?= $s['status'] !== 'active' ? 'selected' : '' ?>><?= e('admin.hidden') ?></option></select></div>
                <button class="btn btn-outline btn-sm" type="submit"><?= icon('check', 16) ?> <?= e('admin.save') ?></button>
            </form>
            <div>
                <p class="label small mb-1"><?= e('admin.courts_toggle') ?></p>
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

        <h3 class="row"><?= icon('box', 18) ?> <?= e('admin.equipment') ?></h3>
        <div class="table-wrap">
            <table class="table">
                <thead><tr>
                    <th><?= e('admin.eq_name') ?></th><th><?= e('admin.eq_name_th') ?></th><th><?= e('admin.eq_price') ?></th><th><?= e('admin.eq_stock') ?></th>
                    <th><?= e('admin.eq_fine') ?></th><th><?= e('admin.eq_free') ?></th><th><?= e('admin.eq_per_court') ?></th><th></th>
                </tr></thead>
                <tbody>
                <?php foreach ($equipment[$sid] ?? [] as $eq): $fid = 'eq' . $eq['eq_id']; ?>
                    <tr>
                        <td><input form="<?= $fid ?>" class="input input-sm input-name" name="eq_name" value="<?= htmlspecialchars($eq['eq_name']) ?>" required>
                            <input form="<?= $fid ?>" class="input input-sm input-name mt-1" name="unit_note" value="<?= htmlspecialchars($eq['unit_note'] ?? '') ?>" placeholder="<?= e('admin.unit_note') ?>"></td>
                        <td><input form="<?= $fid ?>" class="input input-sm input-name" name="name_th" value="<?= htmlspecialchars($eq['name_th'] ?? '') ?>"></td>
                        <td><input form="<?= $fid ?>" class="input input-sm" type="number" min="0" name="price" value="<?= (int) $eq['price'] ?>"></td>
                        <td><input form="<?= $fid ?>" class="input input-sm" type="number" min="0" name="stock" value="<?= (int) $eq['stock'] ?>"></td>
                        <td><input form="<?= $fid ?>" class="input input-sm" type="number" min="0" name="fine_amount" value="<?= (int) $eq['fine_amount'] ?>"></td>
                        <td><input form="<?= $fid ?>" class="input input-sm" type="number" min="0" name="member_free_units" value="<?= (int) $eq['member_free_units'] ?>"></td>
                        <td class="strong"><?= maxPerCourt($eq) ?></td>
                        <td>
                            <form id="<?= $fid ?>" method="POST" class="row" style="flex-wrap:nowrap">
                                <?= csrfField() ?><input type="hidden" name="action" value="eq_update"><input type="hidden" name="eq_id" value="<?= $eq['eq_id'] ?>"><input type="hidden" name="sport_id" value="<?= $sid ?>">
                                <button class="btn btn-primary btn-sm" type="submit" title="<?= e('admin.save') ?>"><?= icon('check', 16) ?> <?= e('admin.save') ?></button>
                            </form>
                            <form method="POST" class="mt-1" data-confirm="<?= e('admin.confirm_delete_eq', ['name' => $eq['eq_name']]) ?>">
                                <?= csrfField() ?><input type="hidden" name="action" value="eq_delete"><input type="hidden" name="eq_id" value="<?= $eq['eq_id'] ?>"><input type="hidden" name="sport_id" value="<?= $sid ?>">
                                <button class="btn btn-danger-outline btn-sm" type="submit"><?= icon('minus', 16) ?> <?= e('admin.delete') ?></button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
                    <tr class="add-row">
                        <td><input form="add<?= $sid ?>" class="input input-sm input-name" name="eq_name" placeholder="<?= e('admin.new_equipment') ?>" required>
                            <input form="add<?= $sid ?>" class="input input-sm input-name mt-1" name="unit_note" placeholder="<?= e('admin.unit_note') ?>"></td>
                        <td><input form="add<?= $sid ?>" class="input input-sm input-name" name="name_th" placeholder="<?= e('admin.eq_name_th') ?>"></td>
                        <td><input form="add<?= $sid ?>" class="input input-sm" type="number" min="0" name="price" value="0"></td>
                        <td><input form="add<?= $sid ?>" class="input input-sm" type="number" min="0" name="stock" value="0"></td>
                        <td><input form="add<?= $sid ?>" class="input input-sm" type="number" min="0" name="fine_amount" value="50"></td>
                        <td><input form="add<?= $sid ?>" class="input input-sm" type="number" min="0" name="member_free_units" value="0"></td>
                        <td class="muted">–</td>
                        <td><form id="add<?= $sid ?>" method="POST">
                            <?= csrfField() ?><input type="hidden" name="action" value="eq_add"><input type="hidden" name="sport_id" value="<?= $sid ?>">
                            <button class="btn btn-dark btn-sm" type="submit"><?= icon('plus', 16) ?> <?= e('admin.add') ?></button>
                        </form></td>
                    </tr>
                </tbody>
            </table>
        </div>
        <p class="hint mt-1 mb-0"><?= e('admin.per_court_hint') ?></p>
    </div>
</section>
<?php endforeach; ?>
<style>
.guide-toc { display: flex; flex-wrap: wrap; gap: 8px; }
.guide-toc a { padding: 6px 12px; border-radius: 999px; background: #F1F5F9; color: var(--c-ink) !important; font-size: .85rem; font-weight: 600; text-decoration: none; }
section[id] { scroll-margin-top: 80px; }
.add-row td { background: #F8FAFC; }
</style>
<?php include '../includes/admin_layout_bottom.php'; ?>
