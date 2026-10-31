<?php
// admin/bookings.php
// ประวัติการจองทั้งหมด: ข้อมูลลูกค้า (ชื่อ นามสกุล เบอร์โทร) + รายละเอียดการจองครบถ้วน + รูปสลิป
// พร้อมปุ่มอัปเดตสถานะ และปุ่มลบ
require_once '../config.php';
requireAdmin();

// ---------- จัดการ POST ----------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();
    $id = intval($_POST['booking_id'] ?? 0);
    $action = $_POST['action'] ?? '';

    if ($action === 'update' && $id) {
        $pay = in_array($_POST['payment_status'] ?? '', ['pending', 'paid', 'failed', 'refunded'], true) ? $_POST['payment_status'] : null;
        $status = in_array($_POST['booking_status'] ?? '', ['active', 'cancelled', 'completed'], true) ? $_POST['booking_status'] : null;
        $notes = trim($_POST['notes'] ?? '');
        $before = $pdo->prepare("SELECT payment_status, booking_status, user_id, points_redeemed, payment_method, total_price FROM bookings WHERE booking_id = ?");
        $before->execute([$id]);
        $prev = $before->fetch();
        if ($prev && $pay && $status) {
            $pdo->beginTransaction();
            $pdo->prepare("UPDATE bookings SET payment_status = ?, booking_status = ?, notes = ? WHERE booking_id = ?")
                ->execute([$pay, $status, $notes ?: null, $id]);
            if ($pay === 'paid') $pdo->prepare("UPDATE bookings SET expires_at = NULL WHERE booking_id = ?")->execute([$id]);
            // แอดมินยืนยันการจ่ายเงินเอง: บันทึกการชำระเงินเหมือนจ่ายผ่านเว็บ
            if ($prev['payment_status'] !== 'paid' && $pay === 'paid') {
                $pdo->prepare("INSERT INTO payments (booking_id, payment_method, amount, payment_status, verified_by, verified_at) VALUES (?, ?, ?, 'verified', ?, ?)")
                    ->execute([$id, $prev['payment_method'] ?: 'promptpay', $prev['total_price'], (int) $_SESSION['admin_id'], date('Y-m-d H:i:s')]);
            }
            // แต้ม: การจองที่ "จ่ายแล้วและไม่ถูกยกเลิก" ได้ +1 แต้ม (และหักแต้มที่แลก)
            // ถ้าสถานะเปลี่ยนเข้า/ออกจากกลุ่มนี้ ให้ปรับแต้มกลับให้ถูกต้อง
            adjustBookingPoints($pdo, $prev, $pay, $status);
            $pdo->commit();
            $_SESSION['flash_success'] = t('admin.booking_updated');
        }
    } elseif ($action === 'delete' && $id) {
        $pdo->beginTransaction();
        $before = $pdo->prepare("SELECT payment_status, booking_status, user_id, points_redeemed FROM bookings WHERE booking_id = ?");
        $before->execute([$id]);
        if ($prev = $before->fetch()) adjustBookingPoints($pdo, $prev, 'deleted', 'deleted');
        foreach (['payments', 'booking_equipment', 'booking_slots', 'bookings'] as $table) {
            $pdo->prepare("DELETE FROM $table WHERE booking_id = ?")->execute([$id]);
        }
        $pdo->commit();
        $_SESSION['flash_success'] = t('admin.booking_deleted');
    }
    redirect('/admin/bookings.php?' . http_build_query(array_intersect_key($_GET, array_flip(['q', 'sport', 'date', 'status', 'page']))));
}

// ---------- ตัวกรอง ----------
$q = trim($_GET['q'] ?? '');
$sportFilter = intval($_GET['sport'] ?? 0);
$dateFilter = preg_match('/^\d{4}-\d{2}-\d{2}$/', $_GET['date'] ?? '') ? $_GET['date'] : '';
$statusFilter = $_GET['status'] ?? '';
$page = max(1, intval($_GET['page'] ?? 1));
$perPage = 25;

$where = ['1=1'];
$params = [];
if ($q !== '') {
    $like = dbLike();
    $where[] = "(b.booking_code $like ? OR b.first_name $like ? OR b.last_name $like ? OR b.phone $like ? OR u.username $like ?)";
    array_push($params, ...array_fill(0, 5, '%' . $q . '%'));
}
if ($sportFilter) { $where[] = 'c.sport_id = ?'; $params[] = $sportFilter; }
if ($dateFilter) { $where[] = 'b.booking_date = ?'; $params[] = $dateFilter; }
if ($statusFilter === 'paid') $where[] = "b.payment_status = 'paid' AND b.booking_status = 'active'";
elseif ($statusFilter === 'pending') $where[] = "b.payment_status = 'pending' AND b.booking_status = 'active'";
elseif ($statusFilter === 'cancelled') $where[] = "b.booking_status = 'cancelled'";
$whereSql = implode(' AND ', $where);

$countStmt = $pdo->prepare("SELECT COUNT(*) FROM bookings b JOIN courts c ON c.court_id = b.court_id JOIN users u ON u.user_id = b.user_id WHERE $whereSql");
$countStmt->execute($params);
$total = (int) $countStmt->fetchColumn();
$pages = max(1, (int) ceil($total / $perPage));
$page = min($page, $pages);

$listStmt = $pdo->prepare("SELECT b.booking_id FROM bookings b JOIN courts c ON c.court_id = b.court_id JOIN users u ON u.user_id = b.user_id
                           WHERE $whereSql ORDER BY b.created_at DESC, b.booking_id DESC LIMIT $perPage OFFSET " . (($page - 1) * $perPage));
$listStmt->execute($params);
$bookings = array_filter(array_map(fn($id) => loadBookingDetails($pdo, (int) $id), $listStmt->fetchAll(PDO::FETCH_COLUMN)));
$sports = $pdo->query("SELECT sport_id, sport_name, name_th FROM sports ORDER BY sport_id")->fetchAll();

$adminPage = 'bookings';
$pageTitle = t('admin.nav_bookings');
include '../includes/admin_layout_top.php';
?>
<form class="filters card card-pad" method="GET">
    <div class="field"><label class="label" for="q"><?= e('admin.search') ?></label>
        <input class="input" id="q" name="q" value="<?= htmlspecialchars($q) ?>" placeholder="<?= e('admin.search_ph') ?>"></div>
    <div class="field"><label class="label" for="sport"><?= e('success.sport') ?></label>
        <select class="select" id="sport" name="sport"><option value=""><?= e('admin.all') ?></option>
            <?php foreach ($sports as $s): ?><option value="<?= $s['sport_id'] ?>" <?= $sportFilter === (int) $s['sport_id'] ? 'selected' : '' ?>><?= htmlspecialchars(localized($s, 'sport_name', 'name_th')) ?></option><?php endforeach; ?>
        </select></div>
    <div class="field"><label class="label" for="date"><?= e('success.date') ?></label>
        <input class="input" id="date" name="date" type="date" value="<?= htmlspecialchars($dateFilter) ?>"></div>
    <div class="field"><label class="label" for="status"><?= e('admin.status') ?></label>
        <select class="select" id="status" name="status">
            <option value=""><?= e('admin.all') ?></option>
            <option value="paid" <?= $statusFilter === 'paid' ? 'selected' : '' ?>><?= e('status.confirmed') ?></option>
            <option value="pending" <?= $statusFilter === 'pending' ? 'selected' : '' ?>><?= e('status.awaiting_payment') ?></option>
            <option value="cancelled" <?= $statusFilter === 'cancelled' ? 'selected' : '' ?>><?= e('status.cancelled') ?></option>
        </select></div>
    <div class="filter-actions">
        <button class="btn btn-primary" type="submit"><?= icon('eye', 16) ?> <?= e('admin.filter') ?></button>
        <a class="btn btn-ghost" href="<?= SITE_URL ?>/admin/bookings.php"><?= e('admin.clear') ?></a>
    </div>
</form>

<p class="muted small"><?= e('admin.showing', ['n' => count($bookings), 'total' => $total]) ?></p>

<?php if (!$bookings): ?>
    <div class="card empty"><?= icon('calendar', 44) ?><h3><?= e('admin.no_bookings') ?></h3></div>
<?php endif; ?>

<div class="adm-list">
<?php foreach ($bookings as $b): [$statusText, $statusClass] = bookingStatusBadge($b); ?>
    <details class="card adm-item">
        <summary>
            <div><div class="adm-code"><?= htmlspecialchars($b['booking_code']) ?></div><div class="adm-sub"><?= htmlspecialchars(formatDateLocalized($b['created_at'])) ?></div></div>
            <div><strong><?= htmlspecialchars($b['customer_name']) ?></strong><div class="adm-sub"><?= htmlspecialchars($b['customer_phone'] ?: '-') ?></div></div>
            <div class="hide-md"><?= htmlspecialchars($b['sport_label']) ?> · <?= htmlspecialchars($b['court_label']) ?><div class="adm-sub"><?= htmlspecialchars(formatDateLocalized($b['booking_date'])) ?> · <?= htmlspecialchars($b['time_label']) ?></div></div>
            <div class="hide-md strong"><?= formatPrice($b['total_price']) ?></div>
            <div><span class="badge <?= $statusClass ?>"><?= htmlspecialchars($statusText) ?></span></div>
            <span class="chev"><?= icon('chevron-down', 18) ?></span>
        </summary>
        <div class="adm-body">
            <div>
                <h4><?= e('success.customer') ?></h4>
                <dl class="kv">
                    <div class="kv-row"><dt><?= e('form.first_name') ?></dt><dd><?= htmlspecialchars($b['first_name'] ?: '-') ?></dd></div>
                    <div class="kv-row"><dt><?= e('form.last_name') ?></dt><dd><?= htmlspecialchars($b['last_name'] ?: '-') ?></dd></div>
                    <div class="kv-row"><dt><?= e('form.phone') ?></dt><dd><?= htmlspecialchars($b['customer_phone'] ?: '-') ?></dd></div>
                    <div class="kv-row"><dt><?= e('form.email') ?></dt><dd><?= htmlspecialchars($b['email'] ?: $b['user_email']) ?></dd></div>
                    <div class="kv-row"><dt><?= e('auth.username') ?></dt><dd><?= htmlspecialchars($b['username']) ?></dd></div>
                </dl>
            </div>
            <div>
                <h4><?= e('success.details') ?></h4>
                <dl class="kv">
                    <div class="kv-row"><dt><?= e('pay.booking_id') ?></dt><dd><?= htmlspecialchars($b['booking_code']) ?></dd></div>
                    <div class="kv-row"><dt><?= e('success.sport') ?></dt><dd><?= htmlspecialchars($b['sport_label']) ?></dd></div>
                    <div class="kv-row"><dt><?= e('success.court') ?></dt><dd><?= htmlspecialchars($b['court_label']) ?></dd></div>
                    <div class="kv-row"><dt><?= e('success.date') ?></dt><dd><?= htmlspecialchars(formatDateLocalized($b['booking_date'], true)) ?></dd></div>
                    <div class="kv-row"><dt><?= e('admin.rounds') ?></dt><dd><?= htmlspecialchars($b['time_label']) ?></dd></div>
                    <div class="kv-row"><dt><?= e('success.equipment') ?></dt><dd>
                        <?php if (!$b['equipment']): ?>-<?php else: foreach ($b['equipment'] as $eq): ?>
                            <div><?= htmlspecialchars(LANG === 'th' && $eq['name_th'] ? $eq['name_th'] : $eq['eq_name']) ?> × <?= (int) $eq['quantity'] ?></div>
                        <?php endforeach; endif; ?></dd></div>
                    <div class="kv-row"><dt><?= e('success.paid') ?></dt><dd class="text-primary"><?= formatPrice($b['total_price']) ?></dd></div>
                    <?php if ((float) $b['discount_amount'] > 0): ?><div class="kv-row discount"><dt><?= e('price.discount') ?></dt><dd>−<?= formatPrice($b['discount_amount']) ?></dd></div><?php endif; ?>
                    <div class="kv-row"><dt><?= e('success.booked_on') ?></dt><dd><?= htmlspecialchars(formatDateLocalized($b['created_at'])) ?> <?= date('H:i', strtotime($b['created_at'])) ?></dd></div>
                    <div class="kv-row"><dt><?= e('payment.method') ?></dt><dd><?= e($b['payment_method'] === 'bank_transfer' ? 'payment.bank' : 'payment.promptpay') ?></dd></div>
                </dl>
            </div>
            <div>
                <h4><?= e('admin.slip') ?></h4>
                <?php if ($b['slip_image']): ?>
                    <a href="<?= SITE_URL ?>/<?= htmlspecialchars($b['slip_image']) ?>" target="_blank" rel="noopener"><img class="slip-thumb" src="<?= SITE_URL ?>/<?= htmlspecialchars($b['slip_image']) ?>" alt="<?= e('admin.slip') ?>" loading="lazy"></a>
                <?php else: ?>
                    <p class="muted small"><?= e('admin.no_slip') ?></p>
                <?php endif; ?>
                <?php if ($b['notes']): ?><p class="note mt-2"><?= icon('info', 16) ?> <?= htmlspecialchars($b['notes']) ?></p><?php endif; ?>
            </div>
        </div>
        <div class="adm-actions">
            <form method="POST">
                <?= csrfField() ?>
                <input type="hidden" name="action" value="update">
                <input type="hidden" name="booking_id" value="<?= $b['booking_id'] ?>">
                <div class="field"><label class="label small"><?= e('admin.payment_status') ?></label>
                    <select class="select" name="payment_status">
                        <?php foreach (['pending' => 'status.awaiting_payment', 'paid' => 'admin.paid', 'failed' => 'admin.failed', 'refunded' => 'status.refunded'] as $v => $k): ?>
                            <option value="<?= $v ?>" <?= $b['payment_status'] === $v ? 'selected' : '' ?>><?= e($k) ?></option>
                        <?php endforeach; ?>
                    </select></div>
                <div class="field"><label class="label small"><?= e('admin.booking_status') ?></label>
                    <select class="select" name="booking_status">
                        <?php foreach (['active' => 'admin.active', 'completed' => 'status.completed', 'cancelled' => 'status.cancelled'] as $v => $k): ?>
                            <option value="<?= $v ?>" <?= $b['booking_status'] === $v ? 'selected' : '' ?>><?= e($k) ?></option>
                        <?php endforeach; ?>
                    </select></div>
                <div class="field" style="flex:1 1 200px"><label class="label small"><?= e('admin.notes') ?></label>
                    <input class="input" name="notes" value="<?= htmlspecialchars($b['notes'] ?? '') ?>" style="min-height:38px"></div>
                <button class="btn btn-primary btn-sm" type="submit"><?= icon('refresh', 16) ?> <?= e('admin.update') ?></button>
            </form>
            <form method="POST" data-confirm="<?= e('admin.confirm_delete_booking', ['code' => $b['booking_code']]) ?>">
                <?= csrfField() ?>
                <input type="hidden" name="action" value="delete">
                <input type="hidden" name="booking_id" value="<?= $b['booking_id'] ?>">
                <button class="btn btn-danger-outline btn-sm" type="submit"><?= icon('trash', 16) ?> <?= e('admin.delete') ?></button>
            </form>
        </div>
    </details>
<?php endforeach; ?>
</div>

<?php if ($pages > 1): ?>
<nav class="row mt-3" style="justify-content:center" aria-label="Pagination">
    <?php for ($p = 1; $p <= $pages; $p++): ?>
        <a class="btn btn-sm <?= $p === $page ? 'btn-primary' : 'btn-outline' ?>" href="?<?= http_build_query(array_merge($_GET, ['page' => $p])) ?>"><?= $p ?></a>
    <?php endfor; ?>
</nav>
<?php endif; ?>
<?php include '../includes/admin_layout_bottom.php'; ?>
