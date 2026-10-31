<?php
// api/promptpay.php
// สร้างข้อมูล QR พร้อมเพย์ (มาตรฐาน EMVCo ของ ธปท.) เองในเครื่อง ไม่ต้องเรียก API ภายนอก
// ได้เป็นข้อความยาว ๆ แล้วหน้าเว็บจะวาดเป็นรูป QR ด้วย assets/js/qrcode.js

/**
 * สร้าง Payload สำหรับ QR พร้อมเพย์
 * @param string $target เบอร์โทร (10 หลัก), เลขบัตรประชาชน/เลขผู้เสียภาษี (13 หลัก) หรือ e-Wallet ID (15 หลัก)
 * @param float|null $amount ยอดเงิน (null = ให้ผู้จ่ายกรอกเอง)
 */
function promptpayPayload(string $target, ?float $amount = null): string {
    $target = preg_replace('/[^0-9]/', '', $target);

    if (strlen($target) >= 15) {
        $account = ['03', $target];                       // e-Wallet ID
    } elseif (strlen($target) >= 13) {
        $account = ['02', $target];                       // เลขบัตรประชาชน / เลขผู้เสียภาษี
    } else {
        // เบอร์โทร: ตัด 0 ข้างหน้า เติมรหัสประเทศ 66 แล้วเติม 0 ด้านหน้าให้ครบ 13 หลัก
        $account = ['01', str_pad('66' . preg_replace('/^0/', '', $target), 13, '0', STR_PAD_LEFT)];
    }

    $field = fn(string $id, string $value) => $id . sprintf('%02d', strlen($value)) . $value;

    $payload = $field('00', '01')                                     // Payload Format Indicator
        . $field('01', $amount !== null ? '12' : '11')                // 12 = ใช้ครั้งเดียว (มียอดเงิน), 11 = ใช้ซ้ำได้
        . $field('29', $field('00', 'A000000677010111') . $field($account[0], $account[1])) // ข้อมูลพร้อมเพย์
        . $field('58', 'TH')                                          // ประเทศ
        . $field('53', '764');                                        // สกุลเงินบาท
    if ($amount !== null) {
        $payload .= $field('54', number_format($amount, 2, '.', ''));  // ยอดเงิน
    }
    $payload .= '6304';                                               // Checksum (ต่อท้ายด้านล่าง)

    return $payload . strtoupper(sprintf('%04x', promptpayCrc16($payload)));
}

/**
 * CRC16-CCITT (0x1021, ค่าเริ่มต้น 0xFFFF) ตามที่มาตรฐาน QR ชำระเงินกำหนด
 */
function promptpayCrc16(string $data): int {
    $crc = 0xFFFF;
    for ($i = 0; $i < strlen($data); $i++) {
        $crc ^= ord($data[$i]) << 8;
        for ($bit = 0; $bit < 8; $bit++) {
            $crc = ($crc & 0x8000) ? (($crc << 1) ^ 0x1021) : ($crc << 1);
            $crc &= 0xFFFF;
        }
    }
    return $crc;
}
