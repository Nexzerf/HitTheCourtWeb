<?php
// includes/booking_summary.php
// แสดงรายละเอียดราคาจากผลของ quoteBooking() (ใช้ในหน้าจองและหน้าชำระเงิน)
// ต้องมีตัวแปร $quote ก่อน include
$courtWordSum = ($quote['sport']['slug'] ?? '') === 'table-tennis' ? t('booking.table') : t('booking.court');
?>
<?php if (!empty($quote['court']) && !empty($quote['slots'])): ?>
    <div class="sum-title"><?= htmlspecialchars(localized($quote['sport'], 'sport_name', 'name_th')) ?> · <?= htmlspecialchars($courtWordSum) ?> <?= (int) $quote['court']['court_number'] ?></div>
    <div class="sum-sub">
        <?= htmlspecialchars(formatDateLocalized($quote['date'], true)) ?><br>
        <?= htmlspecialchars(implode(', ', array_map(fn($s) => formatTime($s['start_time']) . '–' . formatTime($s['end_time']), $quote['slots']))) ?>
    </div>
<?php endif; ?>
<dl class="kv">
    <div class="kv-row"><dt><?= e('price.court_fee', ['n' => $quote['rounds'], 'price' => formatPrice($quote['price_per_round'])]) ?></dt><dd><?= formatPrice($quote['court_fee']) ?></dd></div>
    <?php if (!empty($quote['discount'])): ?>
        <div class="kv-row discount"><dt><?= htmlspecialchars($quote['discount']['label']) ?> (<?= (int) $quote['discount']['percent'] ?>%)</dt><dd>−<?= formatPrice($quote['discount_amount']) ?></dd></div>
    <?php endif; ?>
    <?php if (!empty($quote['use_points'])): ?>
        <div class="kv-row discount"><dt><?= e('price.points_free_round', ['need' => POINTS_FOR_FREE_ROUND]) ?></dt><dd>−<?= formatPrice($quote['points_discount']) ?></dd></div>
    <?php endif; ?>
    <?php foreach ($quote['equipment'] as $line): ?>
        <div class="kv-row">
            <dt><?= htmlspecialchars($line['name']) ?> × <?= (int) $line['qty'] ?><?= $line['free'] ? ' <span class="badge badge-gold">' . e('price.free_n', ['n' => $line['free']]) . '</span>' : '' ?></dt>
            <dd><?= formatPrice($line['subtotal']) ?></dd>
        </div>
    <?php endforeach; ?>
</dl>
<div class="kv-total"><span><?= e('booking.total') ?></span><span class="amount"><?= formatPrice($quote['total']) ?></span></div>
<?php if (!empty($quote['errors'])): ?>
    <div class="alert alert-danger sum-errors"><?= icon('alert') ?><ul><?php foreach ($quote['errors'] as $err): ?><li><?= htmlspecialchars($err) ?></li><?php endforeach; ?></ul></div>
<?php endif; ?>
