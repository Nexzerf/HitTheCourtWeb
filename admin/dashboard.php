<?php
// admin/dashboard.php
// ภาพรวม: ตัวเลขสำคัญ, ตารางการใช้สนามวันนี้ และการจองล่าสุด
require_once '../config.php';
requireAdmin();

$today = date('Y-m-d');
$monthStart = date('Y-m-01');
$kpi = [
    'today' => (int) $pdo->query("SELECT COUNT(*) FROM bookings WHERE booking_date = '$today' AND payment_status = 'paid' AND booking_status = 'active'")->fetchColumn(),
    'revenue' => (float) $pdo->query("SELECT COALESCE(SUM(total_price), 0) FROM bookings WHERE payment_status = 'paid' AND booking_status <> 'cancelled' AND booking_date >= '$monthStart'")->fetchColumn(),
    'members' => (int) $pdo->query("SELECT COUNT(*) FROM users WHERE is_member = 1 AND member_expire >= '$today'")->fetchColumn(),
    'reports' => (int) $pdo->query("SELECT COUNT(*) FROM reports WHERE status = 'new'")->fetchColumn(),
    'pending' => (int) $pdo->query("SELECT COUNT(*) FROM bookings b WHERE b.payment_status = 'pending' AND " . activeBookingSql('b'))->fetchColumn(),
];

$todayIds = $pdo->query("SELECT b.booking_id FROM bookings b JOIN time_slots ts ON ts.slot_id = b.slot_id
                         WHERE b.booking_date = '$today' AND b.payment_status = 'paid' AND b.booking_status = 'active' ORDER BY ts.start_time")->fetchAll(PDO::FETCH_COLUMN);
$todayBookings = array_filter(array_map(fn($id) => loadBookingDetails($pdo, (int) $id), $todayIds));
$recentIds = $pdo->query("SELECT booking_id FROM bookings ORDER BY created_at DESC, booking_id DESC LIMIT 8")->fetchAll(PDO::FETCH_COLUMN);
$recent = array_filter(array_map(fn($id) => loadBookingDetails($pdo, (int) $id), $recentIds));

$adminPage = 'dashboard';
$pageTitle = t('admin.nav_dashboard');
include '../includes/admin_layout_top.php';
?>
<div class="kpis">
    <div class="card kpi"><div class="kpi-label"><?= icon('calendar', 16) ?> <?= e('admin.kpi_today') ?></div><div class="kpi-value"><?= $kpi['today'] ?></div><div class="kpi-sub"><?= htmlspecialchars(formatDateLocalized($today, true)) ?></div></div>
    <div class="card kpi"><div class="kpi-label"><?= icon('tag', 16) ?> <?= e('admin.kpi_revenue') ?></div><div class="kpi-value"><?= formatPrice($kpi['revenue']) ?></div><div class="kpi-sub"><?= e('admin.this_month') ?></div></div>
    <div class="card kpi"><div class="kpi-label"><?= icon('crown', 16) ?> <?= e('admin.kpi_members') ?></div><div class="kpi-value"><?= $kpi['members'] ?></div></div>
    <a class="card kpi" href="<?= SITE_URL ?>/admin/reports.php?status=new" style="text-decoration:none"><div class="kpi-label"><?= icon('message', 16) ?> <?= e('admin.kpi_reports') ?></div><div class="kpi-value"><?= $kpi['reports'] ?></div></a>
    <a class="card kpi" href="<?= SITE_URL ?>/admin/bookings.php?status=pending" style="text-decoration:none"><div class="kpi-label"><?= icon('clock', 16) ?> <?= e('status.awaiting_payment') ?></div><div class="kpi-value"><?= $kpi['pending'] ?></div></a>
</div>

<div class="grid grid-2 dash-grid">
    <section class="card">
        <div class="card-head"><h2><?= icon('clock', 18) ?> <?= e('admin.today_schedule') ?></h2><a class="btn btn-ghost btn-sm" href="<?= SITE_URL ?>/admin/bookings.php?date=<?= $today ?>"><?= e('admin.view_all') ?></a></div>
        <?php if (!$todayBookings): ?><div class="empty"><?= icon('calendar', 40) ?><p class="mb-0"><?= e('admin.no_today') ?></p></div>
        <?php else: ?>
        <div class="table-wrap"><table class="table">
            <thead><tr><th><?= e('success.time') ?></th><th><?= e('success.sport') ?></th><th><?= e('success.customer') ?></th></tr></thead>
            <tbody><?php foreach ($todayBookings as $b): ?>
                <tr><td class="strong"><?= htmlspecialchars($b['time_label']) ?></td><td><?= htmlspecialchars($b['sport_label']) ?> · <?= htmlspecialchars($b['court_label']) ?></td>
                    <td><?= htmlspecialchars($b['customer_name']) ?><div class="adm-sub"><?= htmlspecialchars($b['customer_phone'] ?: '') ?></div></td></tr>
            <?php endforeach; ?></tbody>
        </table></div>
        <?php endif; ?>
    </section>

    <section class="card">
        <div class="card-head"><h2><?= icon('list', 18) ?> <?= e('admin.recent_bookings') ?></h2><a class="btn btn-ghost btn-sm" href="<?= SITE_URL ?>/admin/bookings.php"><?= e('admin.view_all') ?></a></div>
        <div class="table-wrap"><table class="table">
            <thead><tr><th><?= e('pay.booking_id') ?></th><th><?= e('success.customer') ?></th><th><?= e('booking.total') ?></th><th><?= e('admin.status') ?></th></tr></thead>
            <tbody><?php foreach ($recent as $b): [$st, $cls] = bookingStatusBadge($b); ?>
                <tr><td><a href="<?= SITE_URL ?>/admin/bookings.php?q=<?= urlencode($b['booking_code']) ?>" class="strong"><?= htmlspecialchars($b['booking_code']) ?></a>
                        <div class="adm-sub"><?= htmlspecialchars($b['sport_label']) ?> · <?= htmlspecialchars(formatDateLocalized($b['booking_date'])) ?></div></td>
                    <td><?= htmlspecialchars($b['customer_name']) ?></td>
                    <td><?= formatPrice($b['total_price']) ?></td>
                    <td><span class="badge <?= $cls ?>"><?= htmlspecialchars($st) ?></span></td></tr>
            <?php endforeach; ?></tbody>
        </table></div>
    </section>
</div>
<?php include '../includes/admin_layout_bottom.php'; ?>
