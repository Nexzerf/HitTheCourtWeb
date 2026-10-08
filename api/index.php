<?php
// api/index.php
// "ประตูหน้า" ของเว็บเมื่อรันบน Vercel
// Vercel ไม่มี Apache (.htaccess ใช้ไม่ได้) ทุก Request จึงถูกส่งมาที่ไฟล์นี้ (ดู vercel.json)
// แล้วไฟล์นี้จะเลือกไฟล์ PHP ที่ถูกต้องมารันให้ ตามกฎเดียวกับใน .htaccess

define('VERCEL_RUNTIME', true);

$root = realpath(__DIR__ . '/..');
$path = trim(rawurldecode(parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?? '/'), '/');

// URL สวย ๆ (เหมือน RewriteRule ใน .htaccess)
$prettyRoutes = [
    ''                   => 'index.php',
    'index'              => 'index.php',
    'login'              => 'pages/login.php',
    'register'           => 'pages/register.php',
    'booking'            => 'pages/booking.php',
    'courts'             => 'pages/courts.php',
    'guidebook'          => 'pages/guidebook.php',
    'membership_payment' => 'pages/membership_payment.php',
    'membership'         => 'pages/membership.php',
    'pay_booking'        => 'pages/pay_booking.php',
    'profile'            => 'pages/profile.php',
    'reports'            => 'pages/reports.php',
    'reservations'       => 'pages/reservations.php',
    'payment_success'    => 'pages/payment_success.php',
];

// ไฟล์ที่ห้ามเรียกตรง ๆ จากเบราว์เซอร์
$blocked = ['config.php', 'config.local.php', 'config.local.example.php', 'api/index.php', 'api/thunder_api.php'];

$script = null;
if (isset($prettyRoutes[$path])) {
    $script = $prettyRoutes[$path];
} elseif (preg_match('#^media/(\d+)$#', $path, $m)) {
    // ไฟล์อัปโหลดที่เก็บในฐานข้อมูล
    $_GET['id'] = $m[1];
    $script = 'api/media.php';
} elseif (preg_match('#^(?:(?:pages|admin|api)/)?[A-Za-z0-9_-]+(?:\.php)?$#', $path)) {
    // /pages/login.php หรือ /pages/login (ตัด .php ออกได้)
    $script = str_ends_with($path, '.php') ? $path : $path . '.php';
}

$target = $script !== null && !in_array($script, $blocked, true) ? realpath($root . '/' . $script) : false;

if ($target === false || !str_starts_with($target, $root . DIRECTORY_SEPARATOR) || !is_file($target)) {
    // ไม่เจอหน้า -> แสดงหน้าแรก (เหมือน ErrorDocument 404 ใน .htaccess)
    http_response_code(404);
    $target = $root . '/index.php';
}

// ทำให้ไฟล์ปลายทางเห็นตัวเองเหมือนถูกเรียกตรง ๆ
$scriptName = '/' . ltrim(substr($target, strlen($root)), '/');
$_SERVER['SCRIPT_FILENAME'] = $target;
$_SERVER['SCRIPT_NAME'] = $scriptName;
$_SERVER['PHP_SELF'] = $scriptName;

// ไฟล์เดิมใช้ require_once '../config.php' ซึ่งอ้างอิงจากโฟลเดอร์ปัจจุบัน จึงต้องย้ายไปโฟลเดอร์ของไฟล์นั้นก่อน
chdir(dirname($target));
require $target;
