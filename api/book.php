<?php
// api/book.php
// บันทึกการจองใหม่ (ตรวจกติกาทั้งหมดซ้ำอีกรอบด้วย quoteBooking) แล้วพาไปหน้าชำระเงิน
require_once '../config.php';
requireLogin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') redirect('/courts');

$sportId = intval($_POST['sport_id'] ?? 0);
$date = $_POST['date'] ?? '';
$editId = intval($_POST['booking_id'] ?? 0);
$backUrl = $editId
    ? '/booking?edit=' . $editId . '&date=' . urlencode($date)
    : '/booking?sport_id=' . $sportId . '&date=' . urlencode($date);

// ข้อมูลผู้จอง
$firstName = trim($_POST['first_name'] ?? '');
$lastName  = trim($_POST['last_name'] ?? '');
$phone     = trim($_POST['phone'] ?? '');
$email     = trim($_POST['email'] ?? '');
$method    = ($_POST['payment_method'] ?? '') === 'bank_transfer' ? 'bank_transfer' : 'promptpay';

$fail = function (array $errors) use ($backUrl) {
    $_SESSION['flash_error'] = $errors;
    redirect($backUrl);
};

$errors = [];
if ($firstName === '' || $lastName === '') $errors[] = t('err.name_required');
if (!preg_match('/^[0-9+\-\s]{9,20}$/', $phone)) $errors[] = t('err.phone_invalid');
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = t('err.email_invalid');
if ($errors) $fail($errors);

try {
    $pdo->beginTransaction();

    // ล็อกแถวผู้ใช้ไว้ กันการกดจองซ้อนกันพร้อม ๆ กัน
    $stmt = $pdo->prepare("SELECT * FROM users WHERE user_id = ? FOR UPDATE");
    $stmt->execute([$_SESSION['user_id']]);
    $user = $stmt->fetch();

    // ---------- แก้ไขการจองเดิม ----------
    $editing = null;
    if ($editId) {
        $editing = loadBookingDetails($pdo, $editId);
        if (!$editing || (int) $editing['user_id'] !== (int) $user['user_id'] || !bookingIsEditable($editing)
            || (int) $editing['sport_id'] !== $sportId) {
            $pdo->rollBack();
            $fail([t('edit.err_not_editable')]);
        }
        $pdo->prepare("SELECT booking_id FROM bookings WHERE booking_id = ? FOR UPDATE")->execute([$editId]);
    }

    $quote = quoteBooking($pdo, $user, $sportId, [
        'court_id'   => $_POST['court_id'] ?? 0,
        'slot_ids'   => $_POST['slot_ids'] ?? [],
        'date'       => $date,
        'equipment'  => $_POST['equipment'] ?? [],
        'use_points' => !empty($_POST['use_points']) && !$editing,
        'keep_points' => $editing && (int) $editing['points_redeemed'] > 0,
    ], $editId ?: null);
    if (!$quote['ok']) {
        $pdo->rollBack();
        $fail($quote['errors']);
    }

    if ($editing) {
        $pointsDiscount = $quote['points_discount'];
        $newTotal = $quote['total'];
        $oldTotal = (float) $editing['total_price'];
        $isPaid = $editing['payment_status'] === 'paid';
        $diff = round($newTotal - $oldTotal, 2);

        $note = null;
        if ($isPaid && $diff > 0) $note = t('edit.note_pay_more', ['amount' => formatPrice($diff)]);
        if ($isPaid && $diff < 0) $note = t('edit.note_refund', ['amount' => formatPrice(-$diff)]);

        $pdo->prepare("UPDATE bookings SET first_name = ?, last_name = ?, phone = ?, email = ?, court_id = ?, slot_id = ?, booking_date = ?,
                duration_minutes = ?, court_price = ?, equipment_total = ?, discount_amount = ?, discount_note = ?, total_price = ?,
                payment_method = ?, notes = ?, expires_at = ? WHERE booking_id = ?")
            ->execute([$firstName, $lastName, $phone, $email, $quote['court']['court_id'], $quote['slots'][0]['slot_id'], $quote['date'],
                (int) $quote['sport']['duration_minutes'] * $quote['rounds'], $quote['court_fee'], $quote['equipment_total'],
                $quote['discount_amount'] + $pointsDiscount,
                $quote['discount'] ? $quote['discount']['label'] . ' ' . $quote['discount']['percent'] . '%' : null,
                $isPaid ? $oldTotal : $newTotal, $method, $note, $isPaid ? null : holdExpiresAt(), $editId]);

        $pdo->prepare("DELETE FROM booking_slots WHERE booking_id = ?")->execute([$editId]);
        $slotStmt = $pdo->prepare("INSERT INTO booking_slots (booking_id, slot_id) VALUES (?, ?)");
        foreach ($quote['slots'] as $s) $slotStmt->execute([$editId, $s['slot_id']]);
        $pdo->prepare("DELETE FROM booking_equipment WHERE booking_id = ?")->execute([$editId]);
        $eqStmt = $pdo->prepare("INSERT INTO booking_equipment (booking_id, eq_id, quantity, free_quantity, unit_price, subtotal) VALUES (?, ?, ?, ?, ?, ?)");
        foreach ($quote['equipment'] as $line) $eqStmt->execute([$editId, $line['eq_id'], $line['qty'], $line['free'], $line['unit_price'], $line['subtotal']]);

        $taken = getTakenSlots($pdo, $sportId, $quote['date'], $editId);
        foreach ($quote['slots'] as $s) {
            if (isset($taken[$quote['court']['court_id'] . '_' . $s['slot_id']])) {
                $pdo->rollBack();
                $fail([t('err.slot_taken', ['time' => formatTime($s['start_time'])])]);
            }
        }
        $pdo->commit();

        $_SESSION['flash_success'] = $note ? t('edit.saved') . ' ' . $note : t('edit.saved');
        redirect($isPaid ? '/reservations' : '/pay_booking?id=' . $editId);
    }

    $bookingCode = generateBookingCode();
    $firstSlot = $quote['slots'][0];
    $notes = [];
    if ($quote['discount']) $notes[] = $quote['discount']['label'] . ' ' . $quote['discount']['percent'] . '%';
    if ($quote['use_points']) $notes[] = t('price.points_free_round', ['need' => POINTS_FOR_FREE_ROUND]);

    $stmt = $pdo->prepare("
        INSERT INTO bookings (booking_code, user_id, first_name, last_name, phone, email, court_id, slot_id, booking_date,
            duration_minutes, court_price, equipment_total, discount_amount, discount_note, points_redeemed, total_price,
            payment_method, payment_status, booking_status, expires_at)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending', 'active', ?)
    ");
    $stmt->execute([
        $bookingCode, $user['user_id'], $firstName, $lastName, $phone, $email,
        $quote['court']['court_id'], $firstSlot['slot_id'], $quote['date'],
        (int) $quote['sport']['duration_minutes'] * $quote['rounds'],
        $quote['court_fee'], $quote['equipment_total'], $quote['discount_amount'] + $quote['points_discount'],
        $notes ? implode(', ', $notes) : null,
        $quote['use_points'] ? POINTS_FOR_FREE_ROUND : 0,
        $quote['total'], $method, holdExpiresAt(),
    ]);
    $bookingId = (int) $pdo->lastInsertId();

    $slotStmt = $pdo->prepare("INSERT INTO booking_slots (booking_id, slot_id) VALUES (?, ?)");
    foreach ($quote['slots'] as $s) $slotStmt->execute([$bookingId, $s['slot_id']]);

    $eqStmt = $pdo->prepare("INSERT INTO booking_equipment (booking_id, eq_id, quantity, free_quantity, unit_price, subtotal) VALUES (?, ?, ?, ?, ?, ?)");
    foreach ($quote['equipment'] as $line) {
        $eqStmt->execute([$bookingId, $line['eq_id'], $line['qty'], $line['free'], $line['unit_price'], $line['subtotal']]);
    }

    // จำชื่อ-นามสกุลไว้ใช้ครั้งหน้า
    $pdo->prepare("UPDATE users SET first_name = COALESCE(NULLIF(first_name, ''), ?), last_name = COALESCE(NULLIF(last_name, ''), ?),
                   phone = COALESCE(NULLIF(phone, ''), ?) WHERE user_id = ?")
        ->execute([$firstName, $lastName, $phone, $user['user_id']]);

    // เช็คซ้ำหลังบันทึก: ถ้ามีคนจองช่วงเดียวกันเข้ามาพร้อมกัน ให้ยกเลิกของเรา
    $taken = getTakenSlots($pdo, $sportId, $quote['date'], $bookingId);
    foreach ($quote['slots'] as $s) {
        if (isset($taken[$quote['court']['court_id'] . '_' . $s['slot_id']])) {
            $pdo->rollBack();
            $fail([t('err.slot_taken', ['time' => formatTime($s['start_time'])])]);
        }
    }

    $pdo->commit();
    redirect('/pay_booking?id=' . $bookingId);
} catch (PDOException $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    error_log('Booking failed: ' . $e->getMessage());
    $fail([t('err.generic')]);
}
