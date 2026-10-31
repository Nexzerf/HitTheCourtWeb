<?php
// api/media.php
// แสดงไฟล์อัปโหลด (สลิป, รูปแนบรายงาน)
// - /media/{id}        : ไฟล์ที่เก็บในตาราง uploaded_files
// - /uploads/slips/... : ไฟล์เก่าจากโฮสต์เดิม (ไม่เปิดเป็นไฟล์สาธารณะแล้ว ต้องผ่านการตรวจสิทธิ์ที่นี่)
// ดูได้เฉพาะแอดมิน หรือเจ้าของการจอง / การสมัครสมาชิก / รายงานที่ใช้ไฟล์นั้น
require_once '../config.php';

function denyMedia(int $code, string $msg): void {
    http_response_code($code);
    header('Content-Type: text/plain; charset=utf-8');
    exit($msg);
}

if (!isLoggedIn() && !isAdmin()) denyMedia(403, 'Forbidden');

$legacy = isset($_GET['legacy']) ? (string) $_GET['legacy'] : null;
if ($legacy !== null) {
    // อนุญาตเฉพาะไฟล์ในโฟลเดอร์ uploads/ (กัน ../ ออกไปอ่านไฟล์อื่น)
    if (!preg_match('#^(slips|reports)/[^/\\\\]+$#u', $legacy) || str_contains($legacy, '..')) denyMedia(404, 'File not found');
    $storedPath = 'uploads/' . $legacy;
} else {
    $id = intval($_GET['id'] ?? 0);
    if ($id <= 0) denyMedia(404, 'File not found');
    $storedPath = 'media/' . $id;
}

// ตรวจว่าไฟล์นี้เป็นของผู้ใช้คนนี้จริง
if (!isAdmin()) {
    $uid = (int) $_SESSION['user_id'];
    $own = $pdo->prepare("
        SELECT 1 FROM payments p JOIN bookings b ON b.booking_id = p.booking_id WHERE p.slip_image = ? AND b.user_id = ?
        UNION ALL SELECT 1 FROM user_membership WHERE slip_image = ? AND user_id = ?
        UNION ALL SELECT 1 FROM reports WHERE image_path = ? AND user_id = ?
        LIMIT 1");
    $own->execute([$storedPath, $uid, $storedPath, $uid, $storedPath, $uid]);
    if (!$own->fetchColumn()) denyMedia(403, 'Forbidden');
}

if ($legacy !== null) {
    $root = realpath(__DIR__ . '/../uploads');
    $full = $root ? realpath($root . '/' . $legacy) : false;
    if (!$full || !str_starts_with($full, $root . DIRECTORY_SEPARATOR) || !is_file($full)) denyMedia(404, 'File not found');
    $info = @getimagesize($full);
    $file = ['filename' => basename($full), 'mime_type' => $info['mime'] ?? 'application/octet-stream', 'data' => file_get_contents($full)];
} else {
    $stmt = $pdo->prepare("SELECT filename, mime_type, data FROM uploaded_files WHERE id = ?");
    $stmt->execute([$id]);
    $file = $stmt->fetch();
    if (!$file) denyMedia(404, 'File not found');
    $file['data'] = base64_decode($file['data']);
}

// เปิดในเบราว์เซอร์ได้เฉพาะรูปภาพ ไฟล์ชนิดอื่นบังคับให้ดาวน์โหลด (กันไฟล์ HTML ปลอมตัวมาเป็นรูป)
$isImage = in_array($file['mime_type'], ['image/jpeg', 'image/png', 'image/gif', 'image/webp'], true);
$safeName = preg_replace('/[^A-Za-z0-9._-]/', '_', $file['filename']);

while (ob_get_level() > 0) ob_end_clean();
header('Content-Type: ' . ($isImage ? $file['mime_type'] : 'application/octet-stream'));
header('Content-Length: ' . strlen($file['data']));
header('Content-Disposition: ' . ($isImage ? 'inline' : 'attachment') . '; filename="' . $safeName . '"');
header('Cache-Control: private, no-store');
header('X-Content-Type-Options: nosniff');
echo $file['data'];
