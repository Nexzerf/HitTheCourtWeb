<?php
// includes/booking_rules.php
// กติกาการจองทั้งหมดอยู่ที่ไฟล์นี้ที่เดียว (ใช้ทั้งตอนแสดงราคาในหน้าจอง และตอนบันทึกการจองจริง)
//
// - ผู้ใช้ทั่วไป: จองล่วงหน้าได้ 2 วัน, จองได้ 1 ช่วงเวลาต่อการจอง 1 ครั้ง
// - สมาชิก: จองล่วงหน้าได้ 7 วัน, จองได้ไม่จำกัดช่วงเวลา (สนามเดียวกัน)
// - อุปกรณ์: 1 สนามยืมได้ไม่เกินครึ่งหนึ่งของสต๊อก และต้องมีของว่างในช่วงเวลานั้น
// - สมาชิกยืมอุปกรณ์ฟรี N unit แรกตามที่กำหนดในตาราง equipment.member_free_units
// - ส่วนลดสมาชิก (ค่าสนาม) ใช้อันที่คุ้มที่สุดอันเดียว: 30% วันที่ 1/16, 20% จองติดกัน 3 ช่วงขึ้นไป, 10% จองกีฬานั้นครั้งแรก
// - สะสมแต้ม: จ่ายเงินสำเร็จได้ 1 แต้ม ครบ 10 แต้มแลกค่าสนามฟรี 1 รอบ (สมาชิก)

const BOOKING_HOLD_MINUTES = 15;       // ล็อกสนามไว้ให้ระหว่างรอจ่ายเงิน
const ADVANCE_DAYS_MEMBER = 7;
const ADVANCE_DAYS_REGULAR = 2;
const POINTS_FOR_FREE_ROUND = 10;

/** ผู้ใช้เป็นสมาชิกที่ยังไม่หมดอายุหรือไม่ */
function isActiveMember(array $user): bool {
    return !empty($user['is_member']) && !empty($user['member_expire']) && $user['member_expire'] >= date('Y-m-d');
}

/** เงื่อนไข SQL สำหรับการจองที่ยังถือสนามอยู่ (จ่ายแล้ว หรือกำลังรอจ่ายและยังไม่หมดเวลา) */
function activeBookingSql(string $alias = 'b'): string {
    return "($alias.booking_status = 'active' AND ($alias.payment_status = 'paid'
             OR ($alias.payment_status = 'pending' AND $alias.expires_at IS NOT NULL AND $alias.expires_at > NOW())))";
}

/** ช่วงเวลาที่ถูกจองแล้วของกีฬานั้นในวันนั้น: ['courtId_slotId' => true] */
function getTakenSlots(PDO $pdo, int $sportId, string $date, ?int $ignoreBookingId = null): array {
    $stmt = $pdo->prepare("
        SELECT b.court_id, bs.slot_id
        FROM bookings b
        JOIN booking_slots bs ON bs.booking_id = b.booking_id
        JOIN courts c ON c.court_id = b.court_id
        WHERE c.sport_id = ? AND b.booking_date = ? AND b.booking_id <> ? AND " . activeBookingSql('b')
    );
    $stmt->execute([$sportId, $date, $ignoreBookingId ?? 0]);
    $taken = [];
    foreach ($stmt->fetchAll() as $row) {
        $taken[$row['court_id'] . '_' . $row['slot_id']] = true;
    }
    return $taken;
}

/** จำนวนอุปกรณ์ที่ถูกยืมไปแล้วในแต่ละช่วงเวลา: [eqId][slotId] => qty */
function getEquipmentUsage(PDO $pdo, int $sportId, string $date, ?int $ignoreBookingId = null): array {
    $stmt = $pdo->prepare("
        SELECT be.eq_id, bs.slot_id, SUM(be.quantity) AS qty
        FROM booking_equipment be
        JOIN bookings b ON b.booking_id = be.booking_id
        JOIN booking_slots bs ON bs.booking_id = b.booking_id
        JOIN courts c ON c.court_id = b.court_id
        WHERE c.sport_id = ? AND b.booking_date = ? AND b.booking_id <> ? AND " . activeBookingSql('b') . "
        GROUP BY be.eq_id, bs.slot_id
    ");
    $stmt->execute([$sportId, $date, $ignoreBookingId ?? 0]);
    $usage = [];
    foreach ($stmt->fetchAll() as $row) {
        $usage[$row['eq_id']][$row['slot_id']] = (int) $row['qty'];
    }
    return $usage;
}

/** ข้อมูลทั้งหมดที่หน้าจองต้องใช้ */
function loadSportBookingData(PDO $pdo, int $sportId): ?array {
    $stmt = $pdo->prepare("SELECT * FROM sports WHERE sport_id = ? AND status = 'active'");
    $stmt->execute([$sportId]);
    $sport = $stmt->fetch();
    if (!$sport) return null;

    $stmt = $pdo->prepare("SELECT * FROM courts WHERE sport_id = ? ORDER BY court_number");
    $stmt->execute([$sportId]);
    $courts = $stmt->fetchAll();

    $stmt = $pdo->prepare("SELECT * FROM time_slots WHERE sport_id = ? AND status = 'active' ORDER BY start_time");
    $stmt->execute([$sportId]);
    $slots = $stmt->fetchAll();

    $stmt = $pdo->prepare("SELECT * FROM equipment WHERE sport_id = ? AND status = 'available' AND stock > 0 ORDER BY eq_id");
    $stmt->execute([$sportId]);
    $equipment = $stmt->fetchAll();

    return compact('sport', 'courts', 'slots', 'equipment');
}

/** จำนวนสูงสุดที่ 1 สนามยืมได้ = ครึ่งหนึ่งของสต๊อก */
function maxPerCourt(array $eq): int {
    return max(0, intdiv((int) $eq['stock'], 2));
}

/**
 * คำนวณราคาและตรวจสอบเงื่อนไขทั้งหมดของการจอง
 * คืนค่า ['ok' => bool, 'errors' => [...], ...รายละเอียดราคา]
 *
 * $input = [court_id, slot_ids[], date, equipment[eqId => qty], use_points]
 */
function quoteBooking(PDO $pdo, array $user, int $sportId, array $input, ?int $ignoreBookingId = null): array {
    $errors = [];
    $data = loadSportBookingData($pdo, $sportId);
    if (!$data) return ['ok' => false, 'errors' => [t('err.sport_not_found')]];

    $sport = $data['sport'];
    $isMember = isActiveMember($user);
    $date = (string) ($input['date'] ?? '');
    $courtId = (int) ($input['court_id'] ?? 0);
    $slotIds = array_values(array_unique(array_map('intval', (array) ($input['slot_ids'] ?? []))));
    $equipmentInput = (array) ($input['equipment'] ?? []);

    // --- วันที่ ---
    $today = date('Y-m-d');
    $advanceDays = $isMember ? ADVANCE_DAYS_MEMBER : ADVANCE_DAYS_REGULAR;
    $maxDate = date('Y-m-d', strtotime("+{$advanceDays} days"));
    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date) || $date < $today) {
        $errors[] = t('err.invalid_date');
    } elseif ($date > $maxDate) {
        $errors[] = t('err.advance_limit', ['days' => $advanceDays]);
    }

    // --- สนาม ---
    $court = null;
    foreach ($data['courts'] as $c) if ((int) $c['court_id'] === $courtId) $court = $c;
    if (!$court) {
        $errors[] = t('err.choose_court');
    } elseif ($court['status'] !== 'available') {
        $errors[] = t('err.court_unavailable');
    }

    // --- ช่วงเวลา ---
    $slotIndex = [];
    foreach ($data['slots'] as $i => $s) $slotIndex[(int) $s['slot_id']] = $i;
    $selectedSlots = [];
    foreach ($slotIds as $id) {
        if (!isset($slotIndex[$id])) { $errors[] = t('err.invalid_slot'); continue; }
        $selectedSlots[] = $data['slots'][$slotIndex[$id]];
    }
    usort($selectedSlots, fn($a, $b) => strcmp($a['start_time'], $b['start_time']));

    if (!$selectedSlots) {
        $errors[] = t('err.choose_slot');
    } elseif (!$isMember && count($selectedSlots) > 1) {
        $errors[] = t('err.one_slot_only');
    }

    if ($court && $selectedSlots && !$errors) {
        $taken = getTakenSlots($pdo, $sportId, $date, $ignoreBookingId);
        foreach ($selectedSlots as $s) {
            if (isset($taken[$courtId . '_' . $s['slot_id']])) {
                $errors[] = t('err.slot_taken', ['time' => formatTime($s['start_time'])]);
            }
            if ($date === $today && $s['start_time'] <= date('H:i:s')) {
                $errors[] = t('err.slot_past', ['time' => formatTime($s['start_time'])]);
            }
        }
    }

    // --- ค่าสนาม + ส่วนลด ---
    $rounds = count($selectedSlots);
    $pricePerRound = (float) $sport['price'];
    $courtFee = $pricePerRound * $rounds;

    // หาว่ามีช่วงเวลาติดกันกี่ช่วง (ติดกันตามลำดับรอบของกีฬานั้น)
    $longestRun = 0; $run = 0; $prev = null;
    foreach ($selectedSlots as $s) {
        $idx = $slotIndex[(int) $s['slot_id']];
        $run = ($prev !== null && $idx === $prev + 1) ? $run + 1 : 1;
        $longestRun = max($longestRun, $run);
        $prev = $idx;
    }

    $discountOptions = [];
    if ($isMember && $rounds > 0) {
        $day = (int) date('j', strtotime($date ?: $today));
        if ($day === 1 || $day === 16) $discountOptions[] = ['percent' => 30, 'label' => t('discount.day_1_16')];
        if ($longestRun >= 3)         $discountOptions[] = ['percent' => 20, 'label' => t('discount.consecutive')];

        $first = $pdo->prepare("SELECT COUNT(*) FROM bookings b JOIN courts c ON c.court_id = b.court_id
                                WHERE b.user_id = ? AND c.sport_id = ? AND b.payment_status = 'paid' AND b.booking_id <> ?");
        $first->execute([$user['user_id'], $sportId, $ignoreBookingId ?? 0]);
        if ((int) $first->fetchColumn() === 0) $discountOptions[] = ['percent' => 10, 'label' => t('discount.first_booking')];
    }
    usort($discountOptions, fn($a, $b) => $b['percent'] <=> $a['percent']);
    $discount = $discountOptions[0] ?? null;
    $discountAmount = $discount ? round($courtFee * $discount['percent'] / 100, 2) : 0.0;

    // แลกแต้ม: ค่าสนามฟรี 1 รอบ
    $points = (int) ($user['points'] ?? 0);
    $canUsePoints = $isMember && $points >= POINTS_FOR_FREE_ROUND && $rounds > 0;
    $usePoints = $canUsePoints && !empty($input['use_points']);
    $pointsDiscount = 0.0;
    if ($usePoints) {
        // ฟรี 1 รอบ (คิดจากราคาหลังหักส่วนลดแล้ว)
        $pointsDiscount = round(($courtFee - $discountAmount) / $rounds, 2);
    }

    // --- อุปกรณ์ ---
    $usage = ($selectedSlots && $date) ? getEquipmentUsage($pdo, $sportId, $date, $ignoreBookingId) : [];
    $equipmentLines = [];
    $equipmentTotal = 0.0;
    foreach ($data['equipment'] as $eq) {
        $qty = max(0, (int) ($equipmentInput[$eq['eq_id']] ?? 0));
        if ($qty === 0) continue;

        $usedMax = 0;
        foreach ($selectedSlots as $s) $usedMax = max($usedMax, $usage[$eq['eq_id']][$s['slot_id']] ?? 0);
        $available = max(0, (int) $eq['stock'] - $usedMax);
        $limit = min(maxPerCourt($eq), $available);
        $name = localized($eq, 'eq_name', 'name_th');
        if ($qty > $limit) {
            $errors[] = t('err.equipment_limit', ['name' => $name, 'max' => $limit]);
        }

        $free = $isMember ? min($qty, (int) $eq['member_free_units']) : 0;
        $subtotal = ($qty - $free) * (float) $eq['price'];
        $equipmentTotal += $subtotal;
        $equipmentLines[] = [
            'eq_id' => (int) $eq['eq_id'], 'name' => $name, 'qty' => $qty, 'free' => $free,
            'unit_price' => (float) $eq['price'], 'subtotal' => $subtotal,
        ];
    }
    foreach (array_keys($equipmentInput) as $eqId) {
        $known = false;
        foreach ($data['equipment'] as $eq) if ((int) $eq['eq_id'] === (int) $eqId) $known = true;
        if (!$known && (int) $equipmentInput[$eqId] > 0) $errors[] = t('err.equipment_invalid');
    }

    $total = max(0, $courtFee - $discountAmount - $pointsDiscount + $equipmentTotal);

    return [
        'ok' => !$errors,
        'errors' => array_values(array_unique($errors)),
        'is_member' => $isMember,
        'sport' => $sport,
        'court' => $court,
        'slots' => $selectedSlots,
        'date' => $date,
        'rounds' => $rounds,
        'price_per_round' => $pricePerRound,
        'court_fee' => $courtFee,
        'discount' => $discount,
        'discount_amount' => $discountAmount,
        'can_use_points' => $canUsePoints,
        'points' => $points,
        'use_points' => $usePoints,
        'points_discount' => $pointsDiscount,
        'equipment' => $equipmentLines,
        'equipment_total' => $equipmentTotal,
        'total' => round($total, 2),
    ];
}

/** ข้อความช่วงเวลาของการจอง เช่น "09:00 – 11:10" (รวมหลายช่วงที่เลือก) */
function bookingTimeRange(PDO $pdo, int $bookingId): string {
    $stmt = $pdo->prepare("SELECT ts.start_time, ts.end_time FROM booking_slots bs
                           JOIN time_slots ts ON ts.slot_id = bs.slot_id
                           WHERE bs.booking_id = ? ORDER BY ts.start_time");
    $stmt->execute([$bookingId]);
    $parts = array_map(fn($r) => formatTime($r['start_time']) . '–' . formatTime($r['end_time']), $stmt->fetchAll());
    return implode(', ', $parts);
}
