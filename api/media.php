<?php
// api/media.php
// แสดงไฟล์อัปโหลด (สลิป, รูปแนบรายงาน) ที่เก็บไว้ในตาราง uploaded_files
// ใช้เมื่อ UPLOAD_DRIVER=database (ค่าเริ่มต้นบน Vercel) เปิดดูผ่าน /media/{id}
require_once '../config.php';

// สลิปเป็นข้อมูลส่วนตัว ให้ดูได้เฉพาะคนที่ล็อกอินแล้ว
if (!isLoggedIn() && !isAdmin()) {
    http_response_code(403);
    exit('Forbidden');
}

$stmt = $pdo->prepare("SELECT filename, mime_type, data FROM uploaded_files WHERE id = ?");
$stmt->execute([intval($_GET['id'] ?? 0)]);
$file = $stmt->fetch();

if (!$file) {
    http_response_code(404);
    exit('File not found');
}

// เปิดในเบราว์เซอร์ได้เฉพาะรูปภาพ ไฟล์ชนิดอื่นบังคับให้ดาวน์โหลด (กันไฟล์ HTML ปลอมตัวมาเป็นรูป)
$isImage = in_array($file['mime_type'], ['image/jpeg', 'image/png', 'image/gif', 'image/webp'], true);
$safeName = preg_replace('/[^A-Za-z0-9._-]/', '_', $file['filename']);

header('Content-Type: ' . ($isImage ? $file['mime_type'] : 'application/octet-stream'));
header('Content-Length: ' . strlen($file['data']));
header('Content-Disposition: ' . ($isImage ? 'inline' : 'attachment') . '; filename="' . $safeName . '"');
header('Cache-Control: private, max-age=86400');
header('X-Content-Type-Options: nosniff');
echo $file['data'];
