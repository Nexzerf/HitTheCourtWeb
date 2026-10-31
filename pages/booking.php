<?php
// pages/booking.php
// หน้าจองสนาม: เลือกวัน → กดเลือกสนามจากแผนที่ → เลือกช่วงเวลา → อุปกรณ์ → ข้อมูลผู้จองและวิธีชำระเงิน
// ราคาด้านขวาคำนวณจากเซิร์ฟเวอร์ (api/quote.php) ด้วยกติกาเดียวกับตอนบันทึกจริง (includes/booking_rules.php)
require_once '../config.php';
require_once '../includes/court_svg.php';
requireLogin();

// โหมดแก้ไขการจอง (?edit=ID) ใช้หน้าเดียวกัน แต่เติมข้อมูลเดิมไว้ให้
$editId = intval($_GET['edit'] ?? 0);
$editing = null;
if ($editId) {
    $editing = loadBookingDetails($pdo, $editId);
    if (!$editing || (int) $editing['user_id'] !== (int) $_SESSION['user_id'] || !bookingIsEditable($editing)) redirect('/reservations');
    $_GET['sport_id'] = $editing['sport_id'];
    if (!isset($_GET['date'])) $_GET['date'] = $editing['booking_date'];
}

$sportId = intval($_GET['sport_id'] ?? 0);
$data = $sportId ? loadSportBookingData($pdo, $sportId) : null;
if (!$data) redirect('/courts');
['sport' => $sport, 'courts' => $courts, 'slots' => $slots, 'equipment' => $equipment] = $data;

$userStmt = $pdo->prepare("SELECT * FROM users WHERE user_id = ?");
$userStmt->execute([$_SESSION['user_id']]);
$user = $userStmt->fetch();
$isMember = isActiveMember($user);

// --- วันที่ที่จองได้ ---
$today = date('Y-m-d');
$advanceDays = $isMember ? ADVANCE_DAYS_MEMBER : ADVANCE_DAYS_REGULAR;
$selectedDate = $_GET['date'] ?? $today;
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $selectedDate) || $selectedDate < $today
    || $selectedDate > date('Y-m-d', strtotime("+{$advanceDays} days"))) {
    $selectedDate = $today;
}

// --- สถานะสนาม / อุปกรณ์ของวันที่เลือก ---
$taken = getTakenSlots($pdo, $sportId, $selectedDate, $editId ?: null);
$usage = getEquipmentUsage($pdo, $sportId, $selectedDate, $editId ?: null);
$nowTime = date('H:i:s');

$slotState = [];      // [courtId][slotId] => free|taken|past|closed
$freeCount = [];      // [courtId] => จำนวนช่วงเวลาที่ว่าง
foreach ($courts as $c) {
    $freeCount[$c['court_id']] = 0;
    foreach ($slots as $s) {
        if ($c['status'] !== 'available') $state = 'closed';
        elseif (isset($taken[$c['court_id'] . '_' . $s['slot_id']])) $state = 'taken';
        elseif ($selectedDate === $today && $s['start_time'] <= $nowTime) $state = 'past';
        else $state = 'free';
        $slotState[$c['court_id']][$s['slot_id']] = $state;
        if ($state === 'free') $freeCount[$c['court_id']]++;
    }
}

// จำนวนอุปกรณ์ว่างของแต่ละช่วงเวลา ให้ JS จำกัดปุ่ม + ได้ถูกต้อง
$equipmentJs = [];
foreach ($equipment as $eq) {
    $availableBySlot = [];
    foreach ($slots as $s) $availableBySlot[$s['slot_id']] = max(0, (int) $eq['stock'] - ($usage[$eq['eq_id']][$s['slot_id']] ?? 0));
    $equipmentJs[$eq['eq_id']] = ['perCourt' => maxPerCourt($eq), 'bySlot' => $availableBySlot];
}

$sportName = localized($sport, 'sport_name', 'name_th');
$hours = intdiv((int) $sport['duration_minutes'], 60);
$mins = (int) $sport['duration_minutes'] % 60;
$durationText = trim(($hours ? t('unit.hours', ['n' => $hours]) : '') . ' ' . ($mins ? t('unit.minutes', ['n' => $mins]) : ''));
$courtWord = $sport['slug'] === 'table-tennis' ? t('booking.table') : t('booking.court');

$pageTitle = t('booking.title', ['sport' => $sportName]);
$pageStyles = ['booking'];
include '../includes/head.php';
?>
<main class="page booking-page">
    <div class="wrap">
        <a href="<?= SITE_URL ?>/courts" class="back-link"><?= icon('arrow-left', 16) ?> <?= e('booking.back') ?></a>

        <header class="booking-head">
            <div>
                <span class="eyebrow"><?= icon($editId ? 'edit' : 'calendar', 14) ?> <?= $editId ? e('edit.eyebrow', ['code' => $editing['booking_code']]) : e('booking.eyebrow') ?></span>
                <h1><?= htmlspecialchars($sportName) ?></h1>
                <div class="meta">
                    <span><?= icon('tag', 16) ?> <?= e('booking.price_per_round', ['price' => formatPrice($sport['price'])]) ?></span>
                    <span><?= icon('clock', 16) ?> <?= htmlspecialchars(t('booking.per_round', ['duration' => $durationText])) ?></span>
                    <span><?= icon('grid', 16) ?> <?= e($sport['slug'] === 'table-tennis' ? 'booking.tables_n' : 'booking.courts_n', ['n' => count($courts)]) ?></span>
                </div>
            </div>
            <?php if ($isMember): ?>
                <span class="badge badge-gold member-pill"><?= icon('crown', 14) ?> <?= e('booking.member_badge') ?></span>
            <?php endif; ?>
        </header>

        <?php if (!empty($_SESSION['flash_error'])): ?>
        <div class="alert alert-danger mb-3" role="alert">
            <?= icon('alert') ?>
            <ul><?php foreach ((array) $_SESSION['flash_error'] as $msg): ?><li><?= htmlspecialchars($msg) ?></li><?php endforeach; ?></ul>
        </div>
        <?php unset($_SESSION['flash_error']); endif; ?>

        <?php if ($editing): ?>
        <div class="alert alert-warning mb-3"><?= icon('edit') ?><div><?= $editing['payment_status'] === 'paid' ? e('edit.paid_note') : e('edit.pending_note') ?></div></div>
        <?php endif; ?>

        <?php if (!$isMember): ?>
        <div class="alert alert-info mb-3">
            <?= icon('info') ?>
            <div><?= t('booking.regular_rules', ['days' => ADVANCE_DAYS_REGULAR]) ?>
                <a href="<?= SITE_URL ?>/membership"><?= e('booking.see_membership') ?></a></div>
        </div>
        <?php endif; ?>

        <form action="<?= SITE_URL ?>/api/book.php" method="POST" id="bookingForm" class="split" data-once novalidate>
            <input type="hidden" name="sport_id" value="<?= $sportId ?>">
            <input type="hidden" name="date" value="<?= $selectedDate ?>">
            <input type="hidden" name="court_id" id="courtInput" value="">
            <?php if ($editId): ?><input type="hidden" name="booking_id" value="<?= $editId ?>"><?php endif; ?>

            <div class="stack">
                <!-- 1. วันที่ -->
                <section class="card">
                    <div class="card-head"><h2><span class="step-no">1</span> <?= e('booking.step_date') ?></h2></div>
                    <div class="card-body">
                        <div class="date-strip" role="list">
                            <?php for ($i = 0; $i <= ADVANCE_DAYS_MEMBER; $i++):
                                $d = date('Y-m-d', strtotime("+{$i} days"));
                                $locked = $i > $advanceDays;
                                $ts = strtotime($d); ?>
                                <a role="listitem"
                                   class="date-chip<?= $d === $selectedDate ? ' is-active' : '' ?><?= $locked ? ' is-locked' : '' ?>"
                                   href="<?= $locked ? SITE_URL . '/membership' : '?' . ($editId ? 'edit=' . $editId : 'sport_id=' . $sportId) . '&date=' . $d ?>"
                                   <?= $locked ? 'title="' . e('booking.member_only_day') . '"' : '' ?>>
                                    <span class="dc-day"><?= $i === 0 ? e('booking.today') : htmlspecialchars(LANG === 'th' ? ['อา.', 'จ.', 'อ.', 'พ.', 'พฤ.', 'ศ.', 'ส.'][date('w', $ts)] : date('D', $ts)) ?></span>
                                    <span class="dc-date"><?= date('j', $ts) ?></span>
                                    <span class="dc-month"><?= htmlspecialchars(LANG === 'th' ? ['ม.ค.', 'ก.พ.', 'มี.ค.', 'เม.ย.', 'พ.ค.', 'มิ.ย.', 'ก.ค.', 'ส.ค.', 'ก.ย.', 'ต.ค.', 'พ.ย.', 'ธ.ค.'][date('n', $ts) - 1] : date('M', $ts)) ?></span>
                                    <?php if ($locked): ?><span class="dc-lock"><?= icon('crown', 12) ?></span><?php endif; ?>
                                    <?php if (in_array((int) date('j', $ts), [1, 16], true)): ?><span class="dc-deal" title="<?= e('discount.day_1_16') ?>">-30%</span><?php endif; ?>
                                </a>
                            <?php endfor; ?>
                        </div>
                    </div>
                </section>

                <!-- 2. แผนที่สนาม -->
                <section class="card">
                    <div class="card-head">
                        <h2><span class="step-no">2</span> <?= e('booking.step_court', ['court' => $courtWord]) ?></h2>
                        <div class="map-legend">
                            <span><i class="lg lg-free"></i><?= e('booking.legend_free') ?></span>
                            <span><i class="lg lg-selected"></i><?= e('booking.legend_selected') ?></span>
                            <span><i class="lg lg-full"></i><?= e('booking.legend_full') ?></span>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="venue venue-<?= htmlspecialchars($sport['slug']) ?> venue-n<?= count($courts) ?>">
                            <div class="venue-label"><?= icon('map-pin', 14) ?> HIT THE COURT · <?= htmlspecialchars($sportName) ?></div>
                            <div class="venue-grid">
                                <?php foreach ($courts as $c):
                                    $free = $freeCount[$c['court_id']];
                                    $closed = $c['status'] !== 'available'; ?>
                                    <button type="button" class="court-btn<?= ($free === 0 || $closed) ? ' is-full' : '' ?>"
                                            data-court="<?= $c['court_id'] ?>" data-number="<?= $c['court_number'] ?>"
                                            aria-pressed="false"
                                            aria-label="<?= e('booking.court_aria', ['court' => $courtWord, 'n' => $c['court_number'], 'free' => $free]) ?>">
                                        <?= courtSvg($sport['slug']) ?>
                                        <span class="court-tag"><?= htmlspecialchars($courtWord) ?> <?= $c['court_number'] ?></span>
                                        <span class="court-free">
                                            <?php if ($closed): ?><?= e('booking.closed') ?>
                                            <?php elseif ($free === 0): ?><?= e('booking.full') ?>
                                            <?php else: ?><?= e('booking.free_slots', ['n' => $free]) ?><?php endif; ?>
                                        </span>
                                        <span class="court-check"><?= icon('check', 16) ?></span>
                                    </button>
                                <?php endforeach; ?>
                            </div>
                        </div>
                        <p class="hint mt-2 mb-0"><?= icon('info', 14) ?> <?= e('booking.map_hint') ?></p>
                    </div>
                </section>

                <!-- 3. ช่วงเวลา -->
                <section class="card">
                    <div class="card-head">
                        <h2><span class="step-no">3</span> <?= e('booking.step_time') ?></h2>
                        <span class="badge <?= $isMember ? 'badge-gold' : '' ?>"><?= $isMember ? e('booking.multi_slot') : e('booking.single_slot') ?></span>
                    </div>
                    <div class="card-body">
                        <p class="muted mb-0" id="slotPlaceholder"><?= e('booking.pick_court_first') ?></p>
                        <?php foreach ($courts as $c): ?>
                        <div class="slot-grid" data-slots-for="<?= $c['court_id'] ?>" hidden>
                            <?php foreach ($slots as $i => $s): $state = $slotState[$c['court_id']][$s['slot_id']]; ?>
                            <label class="slot slot-<?= $state ?>">
                                <input type="<?= $isMember ? 'checkbox' : 'radio' ?>" name="slot_ids[]" value="<?= $s['slot_id'] ?>"
                                       data-index="<?= $i ?>" <?= $state !== 'free' ? 'disabled' : '' ?>>
                                <span class="slot-box">
                                    <span class="slot-round"><?= e('booking.round', ['n' => $i + 1]) ?></span>
                                    <span class="slot-time"><?= formatTime($s['start_time']) ?>–<?= formatTime($s['end_time']) ?></span>
                                    <span class="slot-state"><?= e('booking.slot_' . $state) ?></span>
                                </span>
                            </label>
                            <?php endforeach; ?>
                        </div>
                        <?php endforeach; ?>
                        <?php if ($isMember): ?>
                            <p class="hint mt-2 mb-0"><?= icon('percent', 14) ?> <?= e('booking.consecutive_hint') ?></p>
                        <?php endif; ?>
                    </div>
                </section>

                <!-- 4. อุปกรณ์ -->
                <section class="card">
                    <div class="card-head"><h2><span class="step-no">4</span> <?= e('booking.step_equipment') ?> <span class="opt muted small">(<?= e('common.optional') ?>)</span></h2></div>
                    <div class="card-body">
                        <?php if (!$equipment): ?>
                            <p class="muted mb-0"><?= e('booking.no_equipment') ?></p>
                        <?php else: ?>
                        <ul class="equip-list">
                            <?php foreach ($equipment as $eq): $free = (int) $eq['member_free_units']; ?>
                            <li class="equip" data-eq="<?= $eq['eq_id'] ?>">
                                <div class="equip-info">
                                    <strong><?= htmlspecialchars(localized($eq, 'eq_name', 'name_th')) ?></strong>
                                    <span class="equip-price"><?= e('booking.per_unit', ['price' => formatPrice($eq['price'])]) ?></span>
                                    <?php if ($note = localized($eq, 'unit_note', 'unit_note_th')): ?>
                                        <span class="hint"><?= htmlspecialchars($note) ?></span>
                                    <?php endif; ?>
                                    <span class="equip-fine"><?= icon('alert', 13) ?> <?= e('booking.fine', ['amount' => formatPrice($eq['fine_amount'])]) ?></span>
                                    <span class="hint" data-eq-limit><?= e('booking.max_per_court', ['n' => maxPerCourt($eq)]) ?></span>
                                    <?php if ($isMember && $free > 0): ?>
                                        <span class="badge badge-gold"><?= icon('gift', 12) ?> <?= e('booking.free_units', ['n' => $free]) ?></span>
                                    <?php elseif (!$isMember && $free > 0): ?>
                                        <span class="hint"><?= icon('crown', 12) ?> <?= e('booking.member_free_units', ['n' => $free]) ?></span>
                                    <?php endif; ?>
                                </div>
                                <div class="qty">
                                    <button type="button" class="qty-btn" data-step="-1" aria-label="<?= e('common.decrease') ?>"><?= icon('minus', 16) ?></button>
                                    <input type="number" class="qty-input" name="equipment[<?= $eq['eq_id'] ?>]" value="0" min="0"
                                           max="<?= maxPerCourt($eq) ?>" inputmode="numeric" aria-label="<?= e('common.quantity') ?>">
                                    <button type="button" class="qty-btn" data-step="1" aria-label="<?= e('common.increase') ?>"><?= icon('plus', 16) ?></button>
                                </div>
                            </li>
                            <?php endforeach; ?>
                        </ul>
                        <p class="note mt-2 mb-0"><?= icon('shield', 16) ?> <?= e('booking.theft_rule') ?></p>
                        <?php endif; ?>
                    </div>
                </section>

                <!-- 5. ข้อมูลผู้จอง + วิธีชำระเงิน -->
                <section class="card">
                    <div class="card-head"><h2><span class="step-no">5</span> <?= e('booking.step_info') ?></h2></div>
                    <div class="card-body">
                        <div class="form-grid">
                            <div class="field">
                                <label class="label" for="first_name"><?= e('form.first_name') ?></label>
                                <input class="input" id="first_name" name="first_name" required maxlength="100" autocomplete="given-name" value="<?= htmlspecialchars($editing['first_name'] ?? $user['first_name'] ?? '') ?>">
                            </div>
                            <div class="field">
                                <label class="label" for="last_name"><?= e('form.last_name') ?></label>
                                <input class="input" id="last_name" name="last_name" required maxlength="100" autocomplete="family-name" value="<?= htmlspecialchars($editing['last_name'] ?? $user['last_name'] ?? '') ?>">
                            </div>
                            <div class="field">
                                <label class="label" for="phone"><?= e('form.phone') ?></label>
                                <input class="input" id="phone" name="phone" type="tel" required maxlength="20" autocomplete="tel" inputmode="tel"
                                       pattern="[0-9+\-\s]{9,20}" title="<?= e('err.phone_invalid') ?>" value="<?= htmlspecialchars($editing['phone'] ?? $user['phone'] ?? '') ?>">
                            </div>
                            <div class="field">
                                <label class="label" for="email"><?= e('form.email') ?></label>
                                <input class="input" id="email" name="email" type="email" required maxlength="100" autocomplete="email" value="<?= htmlspecialchars($editing['email'] ?? $user['email'] ?? '') ?>">
                            </div>
                        </div>

                        <p class="label mt-3 mb-1"><?= e('payment.method') ?></p>
                        <div class="choices">
                            <label class="choice">
                                <input type="radio" name="payment_method" value="promptpay" <?= ($editing['payment_method'] ?? 'promptpay') !== 'bank_transfer' ? 'checked' : '' ?>>
                                <span class="choice-box"><span class="choice-icon"><?= icon('qr') ?></span><span><strong><?= e('payment.promptpay') ?></strong><small><?= e('payment.promptpay_desc') ?></small></span></span>
                            </label>
                            <label class="choice">
                                <input type="radio" name="payment_method" value="bank_transfer" <?= ($editing['payment_method'] ?? '') === 'bank_transfer' ? 'checked' : '' ?>>
                                <span class="choice-box"><span class="choice-icon"><?= icon('bank') ?></span><span><strong><?= e('payment.bank') ?></strong><small><?= e('payment.bank_desc') ?></small></span></span>
                            </label>
                        </div>
                    </div>
                </section>
            </div>

            <!-- สรุปราคา -->
            <aside class="sticky">
                <div class="card summary-card">
                    <div class="card-head"><h2><?= icon('receipt', 18) ?> <?= e('booking.summary') ?></h2></div>
                    <div class="card-body">
                        <div id="summary" aria-live="polite"><p class="muted mb-0"><?= e('booking.summary_empty') ?></p></div>

                        <?php if ($isMember && !$editId): ?>
                        <label class="points-toggle<?= (int) $user['points'] >= POINTS_FOR_FREE_ROUND ? '' : ' is-disabled' ?>">
                            <input type="checkbox" name="use_points" value="1" <?= (int) $user['points'] >= POINTS_FOR_FREE_ROUND ? '' : 'disabled' ?>>
                            <span><?= icon('trophy', 16) ?> <?= e('booking.use_points', ['points' => (int) $user['points'], 'need' => POINTS_FOR_FREE_ROUND]) ?></span>
                        </label>
                        <?php endif; ?>

                        <button type="submit" class="btn btn-primary btn-lg btn-block mt-2" id="submitBtn" disabled>
                            <?= $editId ? e('edit.submit') : e('booking.continue') ?> <?= icon('arrow-right', 18) ?>
                        </button>
                        <p class="hint center mt-1 mb-0"><?= e('booking.hold_note', ['min' => BOOKING_HOLD_MINUTES]) ?></p>
                    </div>
                </div>
            </aside>
        </form>
    </div>

    <!-- แถบสรุปด้านล่างบนมือถือ -->
    <div class="mobile-bar" id="mobileBar" hidden>
        <div><span class="muted small"><?= e('booking.total') ?></span><strong id="mobileTotal">-</strong></div>
        <button type="button" class="btn btn-primary" id="mobileSubmit"><?= e('booking.continue') ?></button>
    </div>
</main>

<script>
window.BOOKING = {
    quoteUrl: <?= json_encode(SITE_URL . '/api/quote.php') ?>,
    isMember: <?= $isMember ? 'true' : 'false' ?>,
    equipment: <?= json_encode($equipmentJs) ?>,
    preset: <?= json_encode($editing && $editing['booking_date'] === $selectedDate ? [
        'court' => (string) $editing['court_id'],
        'slots' => array_map(fn($s) => (string) $s['slot_id'], $editing['slots']),
        'equipment' => array_column($editing['equipment'], 'quantity', 'eq_id'),
    ] : ($editing ? ['court' => null, 'slots' => [], 'equipment' => array_column($editing['equipment'], 'quantity', 'eq_id')] : null)) ?>,
    text: {
        selectCourt: <?= json_encode(t('booking.summary_empty')) ?>,
        error: <?= json_encode(t('err.generic')) ?>
    }
};
</script>
<script src="<?= SITE_URL ?>/assets/js/booking.js" defer></script>
<?php include '../includes/footer.php'; ?>
