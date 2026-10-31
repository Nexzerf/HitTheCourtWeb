<?php
// admin/analytics.php
// สถิติ: รายได้และจำนวนการจองแยกตามกีฬา, การจอง 14 วันล่าสุด และช่วงเวลายอดนิยม
require_once '../config.php';
requireAdmin();

$days = in_array((int) ($_GET['days'] ?? 30), [7, 30, 90, 365], true) ? (int) $_GET['days'] : 30;
$from = date('Y-m-d', strtotime("-{$days} days"));

$bySport = $pdo->prepare("SELECT s.sport_name, s.name_th, COUNT(b.booking_id) AS n, COALESCE(SUM(b.total_price), 0) AS revenue
    FROM sports s LEFT JOIN courts c ON c.sport_id = s.sport_id
    LEFT JOIN bookings b ON b.court_id = c.court_id AND b.payment_status = 'paid' AND b.booking_status <> 'cancelled' AND b.booking_date >= ?
    GROUP BY s.sport_id ORDER BY revenue DESC");
$bySport->execute([$from]);
$bySport = $bySport->fetchAll();
$maxRevenue = max(1, ...array_map(fn($r) => (float) $r['revenue'], $bySport));
$maxCount = max(1, ...array_map(fn($r) => (int) $r['n'], $bySport));

$daily = $pdo->prepare("SELECT booking_date, COUNT(*) FROM bookings WHERE payment_status = 'paid' AND booking_status <> 'cancelled' AND booking_date BETWEEN ? AND ? GROUP BY booking_date");
$daily->execute([date('Y-m-d', strtotime('-13 days')), date('Y-m-d')]);
$daily = $daily->fetchAll(PDO::FETCH_KEY_PAIR);
$maxDaily = max(1, ...array_values($daily ?: [0]));

$popular = $pdo->prepare("SELECT TIME_FORMAT(ts.start_time, '%H:%i') AS t, COUNT(*) AS n FROM booking_slots bs
    JOIN bookings b ON b.booking_id = bs.booking_id JOIN time_slots ts ON ts.slot_id = bs.slot_id
    WHERE b.payment_status = 'paid' AND b.booking_status <> 'cancelled' AND b.booking_date >= ?
    GROUP BY TIME_FORMAT(ts.start_time, '%H:%i') ORDER BY n DESC LIMIT 6");
$popular->execute([$from]);
$popular = $popular->fetchAll();
$maxPopular = max(1, ...array_map(fn($r) => (int) $r['n'], $popular ?: [['n' => 0]]));

$totals = ['revenue' => array_sum(array_column($bySport, 'revenue')), 'bookings' => array_sum(array_column($bySport, 'n'))];
$memberRevenue = $pdo->prepare("SELECT COALESCE(SUM(total_price), 0) FROM user_membership WHERE payment_status IN ('paid', 'verified') AND created_at >= ?");
$memberRevenue->execute([$from]);

$adminPage = 'analytics';
$pageTitle = t('admin.nav_analytics');
include '../includes/admin_layout_top.php';
?>
<div class="row mb-2">
    <?php foreach ([7, 30, 90, 365] as $d): ?>
        <a class="btn btn-sm <?= $d === $days ? 'btn-dark' : 'btn-outline' ?>" href="?days=<?= $d ?>"><?= e('admin.last_days', ['n' => $d]) ?></a>
    <?php endforeach; ?>
</div>
<div class="kpis">
    <div class="card kpi"><div class="kpi-label"><?= icon('tag', 16) ?> <?= e('admin.court_revenue') ?></div><div class="kpi-value"><?= formatPrice($totals['revenue']) ?></div></div>
    <div class="card kpi"><div class="kpi-label"><?= icon('crown', 16) ?> <?= e('admin.member_revenue') ?></div><div class="kpi-value"><?= formatPrice((float) $memberRevenue->fetchColumn()) ?></div></div>
    <div class="card kpi"><div class="kpi-label"><?= icon('calendar', 16) ?> <?= e('admin.paid_bookings') ?></div><div class="kpi-value"><?= (int) $totals['bookings'] ?></div></div>
</div>
<div class="grid grid-2">
    <section class="card card-pad">
        <h3 class="row"><?= icon('chart', 18) ?> <?= e('admin.revenue_by_sport') ?></h3>
        <div class="bars"><?php foreach ($bySport as $r): ?>
            <div class="bar-row"><span><?= htmlspecialchars(localized($r, 'sport_name', 'name_th')) ?></span>
                <span class="bar-track"><span class="bar-fill" style="width: <?= round($r['revenue'] * 100 / $maxRevenue, 1) ?>%"></span></span>
                <span class="bar-val"><?= formatPrice($r['revenue']) ?></span></div>
        <?php endforeach; ?></div>
    </section>
    <section class="card card-pad">
        <h3 class="row"><?= icon('calendar', 18) ?> <?= e('admin.bookings_by_sport') ?></h3>
        <div class="bars"><?php foreach ($bySport as $r): ?>
            <div class="bar-row"><span><?= htmlspecialchars(localized($r, 'sport_name', 'name_th')) ?></span>
                <span class="bar-track"><span class="bar-fill" style="width: <?= round($r['n'] * 100 / $maxCount, 1) ?>%"></span></span>
                <span class="bar-val"><?= (int) $r['n'] ?></span></div>
        <?php endforeach; ?></div>
    </section>
    <section class="card card-pad">
        <h3 class="row"><?= icon('zap', 18) ?> <?= e('admin.last_14') ?></h3>
        <div class="cols-chart">
            <?php for ($i = 13; $i >= 0; $i--): $d = date('Y-m-d', strtotime("-$i days")); $n = (int) ($daily[$d] ?? 0); ?>
                <div class="col"><strong><?= $n ?: '' ?></strong><span class="b" style="height: <?= round($n * 100 / $maxDaily) ?>%"></span><?= date('j', strtotime($d)) ?></div>
            <?php endfor; ?>
        </div>
    </section>
    <section class="card card-pad">
        <h3 class="row"><?= icon('clock', 18) ?> <?= e('admin.popular_times') ?></h3>
        <?php if (!$popular): ?><p class="muted"><?= e('admin.no_data') ?></p><?php endif; ?>
        <div class="bars"><?php foreach ($popular as $r): ?>
            <div class="bar-row"><span><?= htmlspecialchars($r['t']) ?></span>
                <span class="bar-track"><span class="bar-fill" style="width: <?= round($r['n'] * 100 / $maxPopular, 1) ?>%"></span></span>
                <span class="bar-val"><?= (int) $r['n'] ?></span></div>
        <?php endforeach; ?></div>
    </section>
</div>
<?php include '../includes/admin_layout_bottom.php'; ?>
