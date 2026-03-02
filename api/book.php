<?php
// ดึงเอาไฟล์ตั้งค่าหลัก (config.php) เข้ามาก่อน เพื่อเชื่อมฐานข้อมูลและใช้ฟังก์ชันต่างๆ
require_once '../config.php';
// เช็คเลยว่า "ล็อกอินแล้วหรือยัง?" ถ้ายังไม่ล็อกอินจะไม่ให้ทำต่อ
requireLogin();

// เช็ควิธีการเข้าหน้านี้: ถ้าไม่ใช่การกดส่งฟอร์ม (POST) แต่เข้ามาตรงๆ ก็ให้ดีดไปหน้าเลือกสนาม
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('/courts');
}

// 1. Get Data
// รับข้อมูลที่ส่งมาจากฟอร์มการจอง
 $sportId       = intval($_POST['sport_id'] ?? 0);
 $bookingDate   = $_POST['booking_date'] ?? '';
 $slotCourt     = $_POST['slot_court'] ?? ''; // ค่านี้จะเป็นรูปแบบ "courtId_slotId"
 $equipmentData = $_POST['equipment'] ?? []; // รายการอุปกรณ์ที่เลือกมา

// Validation: เช็คเบื้องต้นว่าข้อมูลครบไหม ถ้าไม่ครบให้แจ้งเตือนแล้วจบการทำงาน
if (!$sportId || !$bookingDate || !$slotCourt) {
    die("Please select a time slot.");
}

// แยก string ออกมาเป็น courtId กับ slotId (เช่น "1_5" แยกเป็น 1 กับ 5)
list($courtId, $slotId) = explode('_', $slotCourt);

try {
    // เปิด Transaction: เป็นการ "มัดรวม" ขั้นตอนทั้งหมดไว้ด้วยกัน
    // ถ้าทำสำเร็จทุกอย่างถึงจะบันทึก ถ้าผิดพลาดตรงไหนจะยกเลิกทั้งหมด (Rollback) ป้องกันข้อมูลพัง
    $pdo->beginTransaction();

    // CHECK MEMBERSHIP
    // ไปดึงข้อมูลผู้ใช้ว่าเป็นสมาชิกหรือเปล่า และสมาชิกหมดอายุหรือยัง
    $userStmt = $pdo->prepare("SELECT is_member, member_expire FROM users WHERE user_id = ?");
    $userStmt->execute([$_SESSION['user_id']]);
    $user = $userStmt->fetch();

    $isPremium = false;
    // ถ้าเป็นสมาชิก และ วันหมดอายุยังไม่ถึง ก็ถือว่าเป็น Premium
    if ($user['is_member'] && $user['member_expire'] >= date('Y-m-d')) {
        $isPremium = true;
    }

    // 1. CHECK ADVANCE BOOKING LIMIT
    // เช็คว่าจองล่วงหน้าได้กี่วัน (สมาชิกจองได้ 7 วัน, คนทั่วไป 3 วัน)
    $maxDays = $isPremium ? 7 : 3;
    $maxDate = date('Y-m-d', strtotime("+{$maxDays} days"));
    // ถ้าวันที่จองเกินกว่าที่กำหนด ก็แจ้ง Error
    if ($bookingDate > $maxDate) {
        throw new Exception("Booking limit exceeded. You can only book up to {$maxDays} days in advance.");
    }

    // 2. CHECK COURT STATUS
    // เช็คว่าสนามที่เลือกมา สถานะเป็น 'available' จริงๆ ไหม หรือปิดปรับปรุงไปแล้ว
    $courtStmt = $pdo->prepare("SELECT status FROM courts WHERE court_id = ?");
    $courtStmt->execute([$courtId]);
    $courtData = $courtStmt->fetch();
    if (!$courtData || $courtData['status'] !== 'available') {
        throw new Exception("This court is currently unavailable.");
    }

    // 3. CHECK OVERLAP — เช็คแค่ paid เท่านั้น
    // ส่วนสำคัญ! เช็คว่าช่วงเวลานี้ถูกจองไปแล้วหรือยัง
    // โค้ดชุดนี้เช็คเฉพาะการจองที่จ่ายเงินแล้ว (paid) ว่าไปชนกับช่วงเวลานี้ไหม
    $check = $pdo->prepare("
        SELECT booking_id FROM bookings 
        WHERE court_id = ? 
        AND slot_id = ? 
        AND booking_date = ? 
        AND payment_status = 'paid'
    ");
    $check->execute([$courtId, $slotId, $bookingDate]);
    if ($check->fetch()) {
        throw new Exception("Sorry, this slot is already booked.");
    }

    // 4. CALCULATE PRICES
    // ดึงราคาหลักของกีฬานั้นๆ มาคำนวณ
    $sportStmt = $pdo->prepare("SELECT price, duration_minutes, sport_name FROM sports WHERE sport_id = ?");
    $sportStmt->execute([$sportId]);
    $sport = $sportStmt->fetch();

    $baseCourtPrice = $sport['price'] ?? 0;
    $duration       = $sport['duration_minutes'] ?? 60;
    $courtPrice     = $baseCourtPrice;
    $discountAmount = 0;

    // MEMBER DISCOUNTS
    // ถ้าเป็นสมาชิก Premium จะเริ่มมีสิทธิพิเศษลดราคา
    if ($isPremium) {
        // A. 30% off on 1st & 16th
        // ลด 30% ถ้าจองวันที่ 1 หรือ 16 ของเดือน (โปรโมชั่นพิเศษ)
        $dayOfMonth = date('j', strtotime($bookingDate));
        if ($dayOfMonth == 1 || $dayOfMonth == 16) {
            $discountAmt     = $courtPrice * 0.30;
            $discountAmount += $discountAmt;
            $courtPrice     -= $discountAmt;
        }

        // B. 10% off first booking of this sport
        // ลด 10% ถ้าเป็นการจองกีฬานี้ครั้งแรกของเค้า
        $firstCheck = $pdo->prepare("
            SELECT COUNT(*) FROM bookings b 
            JOIN courts c ON b.court_id = c.court_id 
            WHERE b.user_id = ? AND c.sport_id = ? AND b.payment_status = 'paid'
        ");
        $firstCheck->execute([$_SESSION['user_id'], $sportId]);
        if ($firstCheck->fetchColumn() == 0) {
            $discountAmt     = $baseCourtPrice * 0.10;
            $discountAmount += $discountAmt;
            $courtPrice     -= $discountAmt;
        }
    }

    $totalPrice      = $courtPrice;
    $equipmentTotal  = 0;
    $equipmentDetails = [];

    // EQUIPMENT
    // ตารางผังกำหนดว่า ถ้าเป็นสมาชิก อุปกรณ์แบบไหนได้ฟรีกี่ชิ้น (เช่น ไม้แบด 5 คู่ฟรี)
    $freeUnitsMap = [
        'badminton racket' => 5, 'badminton' => 5,
        'football'         => 2,
        'team bib'         => 1, 'bib'       => 1,
        'cone'             => 1, 'training cone' => 1,
        'tennis racket'    => 2, 'tennis'    => 2,
        'tennis ball'      => 3,
        'volleyball'       => 2,
        'basketball'       => 2,
        'ping-pong ball'   => 5, 'table tennis ball'   => 5,
        'ping-pong racket' => 2, 'table tennis racket' => 2,
        'futsal ball'      => 3, 'futsal'    => 3
    ];

    // วนลูปคำนวณราคาอุปกรณ์ที่เลือกมาทีละชิ้น
    foreach ($equipmentData as $eqId => $qty) {
        $qty = intval($qty);
        if ($qty > 0) {
            $eqStmt = $pdo->prepare("SELECT * FROM equipment WHERE eq_id = ?");
            $eqStmt->execute([$eqId]);
            $eq = $eqStmt->fetch();

            if (!$eq) continue;
            // เช็คว่าของใน Stock พอไหม ถ้าไม่พอก็แจ้ง Error ทันที
            if ($qty > $eq['stock']) {
                throw new Exception("Not enough stock for " . $eq['eq_name']);
            }

            // Calculate Free Units
            // คำนวณว่ามีสิทธิ์ฟรีกี่ชิ้น (ถ้าเป็นสมาชิก)
            $freeQty = 0;
            if ($isPremium) {
                $eqNameLower = strtolower($eq['eq_name']);
                foreach ($freeUnitsMap as $name => $limit) {
                    if (strpos($eqNameLower, $name) !== false) {
                        $freeQty = $limit;
                        break;
                    }
                }
            }

            // เอาจำนวนที่เลือก ลบ ด้วยจำนวนที่ฟรี = จำนวนที่ต้องจ่ายเงิน
            $paidQty  = max(0, $qty - $freeQty);
            $subtotal = $paidQty * $eq['price'];

            $equipmentTotal += $subtotal;
            $totalPrice     += $subtotal;

            // เก็บข้อมูลไว้เพื่อเตรียมบันทึก
            $equipmentDetails[] = [
                'id'       => $eqId,
                'qty'      => $qty,
                'price'    => $eq['price'],
                'subtotal' => $subtotal,
                'free_qty' => $freeQty
            ];
        }
    }

    // 5. INSERT BOOKING
    // เตรียมบันทึกข้อมูลการจองลงฐานข้อมูล
    $bookingCode = generateBookingCode();

    // สั่ง INSERT ข้อมูลการจอง โดยสถานะจะเป็น 'pending' (รอจ่ายเงิน)
    // หมายเหตุ: ในโค้ดชุดนี้จะไม่มีการกำหนด expires_at (เวลาหมดอายุการจองชั่วคราว)
    $stmt = $pdo->prepare("
        INSERT INTO bookings (
            user_id, court_id, slot_id, booking_date, booking_code,
            duration_minutes, court_price, equipment_total, discount_amount, total_price,
            payment_status, booking_status
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending', 'active')
    ");

    $stmt->execute([
        $_SESSION['user_id'],
        $courtId,
        $slotId,
        $bookingDate,
        $bookingCode,
        $duration,
        $courtPrice,
        $equipmentTotal,
        $discountAmount,
        $totalPrice
    ]);

    // ดึง ID ของการจองที่พึ่งสร้างขึ้นมา
    $bookingId = $pdo->lastInsertId();

    // 6. SAVE EQUIPMENT
    // บันทึกรายการอุปกรณ์ที่เลือก
    foreach ($equipmentDetails as $item) {
        $stmt = $pdo->prepare("
            INSERT INTO booking_equipment (booking_id, eq_id, quantity, unit_price, subtotal) 
            VALUES (?, ?, ?, ?, ?)
        ");
        $stmt->execute([$bookingId, $item['id'], $item['qty'], $item['price'], $item['subtotal']]);
    }

    // ยืนยันการทำธุรกรรมทั้งหมด (Commit)
    // เมื่อมาถึงตรงนี้ได้แสดงว่าผ่านเงื่อนไขทั้งหมดแล้ว ก็ให้บันทึกข้อมูลลงไปจริง
    $pdo->commit();

    // พาผู้ใช้ไปหน้าชำระเงิน พร้อมส่ง ID การจองไปด้วย
    redirect('/pay_booking?id=' . $bookingId);

} catch (Exception $e) {
    // ถ้ามี Error ตรงไหน ให้ยกเลิกการทำธุรกรรมทั้งหมด (Rollback)
    // เพื่อป้องกันข้อมูลเหลือค้างอยู่ (เช่น จองสนามสำเร็จแต่จองอุปกรณ์พัง ก็ต้องยกเลิกทั้งคู่)
    $pdo->rollBack();
    die("Booking failed: " . $e->getMessage());
}
?>