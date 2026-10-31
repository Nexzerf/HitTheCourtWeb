<?php

// ============================================
// TIMEZONE - ต้องอยู่บนสุดก่อนทุกอย่าง
// ============================================
date_default_timezone_set('Asia/Bangkok');
/**
 * HIT THE COURT - Main Configuration File
 * 
 * ไฟล์นี้คือ "ศูนย์กลางการตั้งค่า" ของระบบทั้งหมดเลยครับ
 * ไม่ว่าจะเป็นการตั้งค่าฐานข้อมูล, การจัดการ Session (การล็อกอิน),
 * และฟังก์ชันช่วยเหลือ (Helper Functions) ที่ใช้ร่วมกันทั้งเว็บ
 */

// ============================================
// ENVIRONMENT
// ============================================
// ค่าลับ (รหัสผ่านฐานข้อมูล, API Key) จะไม่เขียนไว้ในโค้ดแล้ว
// - บน Vercel: ตั้งค่าใน Project Settings > Environment Variables
// - บนโฮสต์ทั่วไป (เช่น InfinityFree): สร้างไฟล์ config.local.php (ดูตัวอย่างใน config.local.example.php)
$localConfig = file_exists(__DIR__ . '/config.local.php') ? require __DIR__ . '/config.local.php' : [];

/**
 * อ่านค่าตั้งค่าจาก Environment Variable ก่อน ถ้าไม่มีค่อยดูใน config.local.php
 */
function env($key, $default = null) {
    global $localConfig;
    $value = getenv($key);
    if ($value !== false && $value !== '') return $value;
    return $localConfig[$key] ?? $default;
}

// รันอยู่บน Vercel หรือไม่ (api/index.php จะ define ค่านี้ให้)
define('ON_VERCEL', defined('VERCEL_RUNTIME') || getenv('VERCEL'));

// ============================================
// ERROR REPORTING
// ============================================
// บน Vercel ปิดการแสดง Error เป็นค่าเริ่มต้น (เปิดได้ด้วย APP_DEBUG=1)
$debug = filter_var(env('APP_DEBUG', ON_VERCEL ? '0' : '1'), FILTER_VALIDATE_BOOLEAN);
ini_set('display_errors', $debug ? 1 : 0);
ini_set('display_startup_errors', $debug ? 1 : 0);
error_reporting(E_ALL);

// ============================================
// DATABASE CONFIGURATION
// ============================================
// ตั้งค่า "ชื่อที่อยู่" และ "รหัสผ่าน" สำหรับเข้าถึงฐานข้อมูล
// DB_DRIVER: mysql (โฮสต์ทั่วไป เช่น InfinityFree) หรือ pgsql (Supabase / PostgreSQL)
define('DB_DRIVER', env('DB_DRIVER', 'mysql') === 'pgsql' ? 'pgsql' : 'mysql');
define('DB_HOST', env('DB_HOST', 'localhost'));
define('DB_PORT', env('DB_PORT', DB_DRIVER === 'pgsql' ? '5432' : '3306'));
define('DB_NAME', env('DB_NAME', 'hit_the_court'));
define('DB_USER', env('DB_USER', 'root'));
define('DB_PASS', env('DB_PASS', ''));
// ฐานข้อมูลบนคลาวด์ (Supabase, TiDB ฯลฯ) ส่วนใหญ่บังคับใช้ SSL ให้ตั้ง DB_SSL=1
define('DB_SSL', filter_var(env('DB_SSL', '0'), FILTER_VALIDATE_BOOLEAN));
// PostgreSQL: schema ที่เก็บตารางของเว็บนี้ (ใน Supabase ใช้ hitthecourt แยกจากตารางอื่นในโปรเจกต์)
define('DB_SCHEMA', env('DB_SCHEMA', DB_DRIVER === 'pgsql' ? 'hitthecourt' : ''));

// ============================================
// SITE CONFIGURATION
// ============================================
define('SITE_NAME', 'Hit The Court');

// เช็คว่าเข้าเว็บผ่าน HTTPS ไหม (รองรับกรณีอยู่หลัง Proxy อย่าง Vercel ด้วย)
$isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
    || (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https');

// "ที่อยู่เว็บไซต์" หลัก ถ้าไม่ได้ตั้ง SITE_URL ไว้ จะใช้โดเมนที่ผู้ใช้เข้ามาจริง
// (ทำให้ลิงก์ใช้ได้ทั้งโดเมนหลักและ Preview URL ของ Vercel)
$host = $_SERVER['HTTP_X_FORWARDED_HOST'] ?? $_SERVER['HTTP_HOST'] ?? 'localhost';
define('SITE_URL', rtrim(env('SITE_URL', ($isHttps ? 'https' : 'http') . '://' . $host), '/'));

// File Upload Paths
// กำหนดโฟลเดอร์สำหรับเก็บไฟล์ที่อัปโหลดเข้ามา
define('UPLOAD_PATH', __DIR__ . '/uploads/');

// บน Vercel ดิสก์เขียนถาวรไม่ได้ จึงเก็บ Session และไฟล์อัปโหลดไว้ในฐานข้อมูลแทน
define('SESSION_DRIVER', env('SESSION_DRIVER', ON_VERCEL ? 'database' : 'file'));
define('UPLOAD_DRIVER', env('UPLOAD_DRIVER', ON_VERCEL ? 'database' : 'file'));

// PAYMENT MODE
// demo    = โหมดสาธิต: แสดง QR พร้อมเพย์จริง แต่ไม่ต้องจ่ายเงิน กดยืนยันได้เลย (สลิปไม่บังคับ และไม่ตรวจสอบ)
// thunder = ตรวจสลิปจริงผ่าน Thunder API (ต้องตั้ง THUNDER_API_KEY)
define('PAYMENT_MODE', env('PAYMENT_MODE', 'demo'));

// THUNDER API
// กุญแจสำคัญสำหรับเรียกใช้งานบริการภายนอก (API) ที่ชื่อว่า Thunder
define('THUNDER_API_KEY', env('THUNDER_API_KEY', ''));

// ============================================
// DATABASE CONNECTION (PDO)
// ============================================
// ส่วนนี้คือการ "จับมือ" เชื่อมต่อกับฐานข้อมูลจริงๆ โดยใช้ PDO
// ข้อดีคือมันปลอดภัยกว่าวิธีเก่าๆ และป้องกัน SQL Injection ได้ดี
// (ต้องเชื่อมต่อก่อนเริ่ม Session เพราะ Session อาจเก็บอยู่ในฐานข้อมูล)
try {
    $pdoOptions = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION, // ให้มันร้องบอกทันทีถ้ามี Error
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,       // ดึงข้อมูลมาเป็น Array ง่ายๆ
        // MySQL: ใช้ prepared statement จริงของเซิร์ฟเวอร์
        // PostgreSQL: ให้ PDO จำลองเอง เพราะ connection pooler ของ Supabase ใช้ prepared statement ฝั่งเซิร์ฟเวอร์ไม่ได้
        // (ค่าที่ส่งเข้าไปยัง escape อย่างปลอดภัยเหมือนเดิม)
        PDO::ATTR_EMULATE_PREPARES   => DB_DRIVER === 'pgsql',
    ];
    if (DB_SSL && DB_DRIVER === 'mysql') {
        $caFile = env('DB_SSL_CA');
        if (!$caFile) {
            foreach (['/etc/pki/tls/certs/ca-bundle.crt', '/etc/ssl/certs/ca-certificates.crt', '/etc/ssl/cert.pem'] as $candidate) {
                if (is_readable($candidate)) { $caFile = $candidate; break; }
            }
        }
        if ($caFile) $pdoOptions[PDO::MYSQL_ATTR_SSL_CA] = $caFile;
        $pdoOptions[PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT] = true;
    }

    $dsn = DB_DRIVER === 'pgsql'
        ? "pgsql:host=" . DB_HOST . ";port=" . DB_PORT . ";dbname=" . DB_NAME . (DB_SSL ? ";sslmode=require" : "")
        : "mysql:host=" . DB_HOST . ";port=" . DB_PORT . ";dbname=" . DB_NAME . ";charset=utf8mb4";
    $pdo = new PDO($dsn, DB_USER, DB_PASS, $pdoOptions);

    if (DB_DRIVER === 'pgsql') {
        // ใช้ตารางใน schema ของเว็บนี้ และให้ now() เป็นเวลาไทยตรงกับ PHP
        if (DB_SCHEMA !== '' && preg_match('/^[a-z_][a-z0-9_]*$/', DB_SCHEMA)) {
            $pdo->exec("SET search_path TO " . DB_SCHEMA . ", public");
        }
        $pdo->exec("SET TIME ZONE 'Asia/Bangkok'");
    } else {
        // ให้ NOW() ในฐานข้อมูลเป็นเวลาไทยตรงกับ PHP (ฐานข้อมูลบนคลาวด์มักตั้งเป็น UTC)
        $dbTimezone = env('DB_TIMEZONE', ON_VERCEL ? '+07:00' : '');
        if ($dbTimezone !== '') {
            $pdo->prepare("SET time_zone = ?")->execute([$dbTimezone]);
        }
    }
} catch (PDOException $e) {
    // ถ้าจับมือไม่สำเร็จ (เชื่อมไม่ติด) ให้บันทึก Log เก็บไว้ลับๆ แล้วแสดงข้อความแจ้งเตือนแบบกว้างๆ
    // ไม่แนะนำให้บอก Error จริงกับผู้ใช้ เดี๋ยวจะรู้โครงสร้างระบบเรา
    error_log("Database Connection Failed: " . $e->getMessage());
    die("Connection failed. Please try again later.");
}

// ============================================
// SESSION CONFIGURATION
// ============================================
/**
 * เก็บ Session ไว้ในตาราง `sessions` ของฐานข้อมูล
 * จำเป็นบน Vercel เพราะแต่ละ Request อาจไปรันคนละเครื่อง ไฟล์ Session จึงหายได้
 */
class DatabaseSessionHandler implements SessionHandlerInterface
{
    public function __construct(private PDO $pdo) {}

    public function open(string $path, string $name): bool { return true; }

    public function close(): bool { return true; }

    public function read(string $id): string|false
    {
        $stmt = $this->pdo->prepare("SELECT data FROM sessions WHERE id = ?");
        $stmt->execute([$id]);
        $data = $stmt->fetchColumn();
        return $data === false ? '' : $data;
    }

    public function write(string $id, string $data): bool
    {
        // ผู้เข้าชมที่ยังไม่ได้ล็อกอินไม่มีข้อมูลใน Session ไม่ต้องเก็บแถวว่าง ๆ ให้รกฐานข้อมูล
        if ($data === '') return $this->destroy($id);

        $stmt = $this->pdo->prepare(DB_DRIVER === 'pgsql'
            ? "INSERT INTO sessions (id, data, last_activity) VALUES (?, ?, ?)
               ON CONFLICT (id) DO UPDATE SET data = EXCLUDED.data, last_activity = EXCLUDED.last_activity"
            : "INSERT INTO sessions (id, data, last_activity) VALUES (?, ?, ?)
               ON DUPLICATE KEY UPDATE data = VALUES(data), last_activity = VALUES(last_activity)"
        );
        return $stmt->execute([$id, $data, time()]);
    }

    public function destroy(string $id): bool
    {
        return $this->pdo->prepare("DELETE FROM sessions WHERE id = ?")->execute([$id]);
    }

    public function gc(int $max_lifetime): int|false
    {
        $stmt = $this->pdo->prepare("DELETE FROM sessions WHERE last_activity < ?");
        $stmt->execute([time() - $max_lifetime]);
        return $stmt->rowCount();
    }
}

// Secure session settings
// ส่วนนี้คือการตั้งค่าความปลอดภัยให้กับ "Session" (ตัวยืนยันตัวตนตอนล็อกอิน)
ini_set('session.cookie_httponly', 1); // ป้องกันไม่ให้ JavaScript มาขโมย Session ID ได้
ini_set('session.use_only_cookies', 1); // บังคับให้ใช้ Cookie เท่านั้นในการเก็บ Session
ini_set('session.cookie_secure', $isHttps ? 1 : 0); // ถ้าเว็บมี HTTPS ให้ส่ง Cookie ผ่าน HTTPS เท่านั้น
ini_set('session.cookie_samesite', 'Lax'); // ไม่ส่ง Cookie ไปกับฟอร์มที่ยิงมาจากเว็บอื่น (กัน CSRF ชั้นแรก)

// Start the session
// สั่งให้ Session เริ่มทำงาน ถ้ายังไม่ได้เริ่มก็ให้เริ่มเลย (ใช้คำสั่ง session_start)
if (session_status() === PHP_SESSION_NONE) {
    if (SESSION_DRIVER === 'database') {
        session_set_save_handler(new DatabaseSessionHandler($pdo), true);
        ini_set('session.gc_probability', 1); // ลบ Session ที่หมดอายุออกจากตารางเป็นระยะ (ราว 1 ใน 100 Request)
        ini_set('session.gc_divisor', 100);
    }
    session_start();
}

// ============================================
// LANGUAGE / ICONS / BOOKING RULES
// ============================================
// ระบบสองภาษา (t(), e(), localized()), ไอคอน SVG (icon()) และกติกาการจอง
require_once __DIR__ . '/includes/i18n.php';
require_once __DIR__ . '/includes/icons.php';
require_once __DIR__ . '/includes/booking_rules.php';

// ============================================
// FILE UPLOADS
// ============================================
/**
 * บันทึกไฟล์ที่ผู้ใช้อัปโหลด
 * - UPLOAD_DRIVER=file     : เก็บในโฟลเดอร์ uploads/ เหมือนเดิม
 * - UPLOAD_DRIVER=database : เก็บในตาราง `uploaded_files` แล้วเปิดดูผ่าน /media/{id}
 *
 * คืนค่า ['path' => ที่อยู่สำหรับบันทึกลงฐานข้อมูล, 'local' => ไฟล์บนเครื่องที่ใช้ได้ระหว่าง Request นี้]
 * หรือ null ถ้าบันทึกไม่สำเร็จ
 */
function saveUploadedFile(array $file, string $folder, string $filename): ?array {
    global $pdo;

    if (UPLOAD_DRIVER === 'database') {
        $localFile = sys_get_temp_dir() . '/' . uniqid() . '_' . $filename;
        if (!move_uploaded_file($file['tmp_name'], $localFile)) return null;

        $stmt = $pdo->prepare("INSERT INTO uploaded_files (folder, filename, mime_type, data) VALUES (?, ?, ?, ?)");
        // เก็บเป็น base64 (ข้อความ) ใช้ได้เหมือนกันทั้ง MySQL และ PostgreSQL
        $stmt->execute([$folder, $filename, mime_content_type($localFile) ?: 'application/octet-stream', base64_encode(file_get_contents($localFile))]);
        return ['path' => 'media/' . $pdo->lastInsertId(), 'local' => $localFile];
    }

    $uploadDir = UPLOAD_PATH . $folder . '/';
    if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);
    if (!move_uploaded_file($file['tmp_name'], $uploadDir . $filename)) return null;
    return ['path' => 'uploads/' . $folder . '/' . $filename, 'local' => $uploadDir . $filename];
}

// ============================================
// GENERAL HELPER FUNCTIONS
// ============================================

/**
 * Redirect to a specific URL relative to SITE_URL.
 * ฟังก์ชันนี้คือ "เครื่องเทเลพอร์ต" ครับ คือสั่งให้เว็บวิ่งไปหน้าอื่นทันที
 * @param string $url
 */
function redirect($url) {
    if (strpos($url, 'http') === 0) {
        header("Location: " . $url);
    } else {
        header("Location: " . SITE_URL . '/' . ltrim($url, '/'));
    }
    exit();
}

/**
 * Check if a user is logged in.
 * เช็คง่ายๆ ว่า "ผู้ใช้ล็อกอินแล้วหรือยัง?"
 * ดูจากมี user_id ใน Session หรือเปล่า
 * @return bool
 */
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

/**
 * Check if an admin is logged in.
 * อันนี้ก็เหมือนกัน แต่เช็คว่าเป็น "แอดมิน" หรือยัง?
 * @return bool
 */
function isAdmin() {
    return isset($_SESSION['admin_id']);
}

/**
 * Force user login requirement.
 * ฟังก์ชันนี้เป็นเหมือน "บอดี้การ์ด" คอยขวางไว้
 * ถ้ายังไม่ได้ล็อกอิน จะไล่ไปหน้าล็อกอินทันที
 */
function requireLogin() {
    if (!isLoggedIn()) {
        // จำหน้าที่ตั้งใจจะเข้า ล็อกอินเสร็จแล้วจะพากลับมาที่เดิม
        redirect('/login?next=' . urlencode($_SERVER['REQUEST_URI'] ?? '/'));
    }
}

/** URL ปลายทางหลังล็อกอิน (รับเฉพาะ path ภายในเว็บ กันการพาไปเว็บอื่น) */
function safeNextUrl(?string $next): string {
    // ต้องขึ้นต้นด้วย / ตัวเดียว (ไม่ใช่ // หรือ /\ ซึ่งเบราว์เซอร์ตีความเป็นเว็บอื่น) และห้ามมีขึ้นบรรทัดใหม่
    $next = (string) $next;
    if (strlen($next) > 1 && $next[0] === '/' && $next[1] !== '/' && $next[1] !== '\\' && !preg_match('/[\r\n]/', $next)) return $next;
    return '/';
}

/**
 * Force admin login requirement.
 * อันนี้คือบอดี้การ์ดของหน้าแอดมิน ถ้าไม่ใช่แอดมินให้ไปหน้าล็อกอินแอดมินทันที
 */
function requireAdmin() {
    if (!isAdmin()) {
        redirect('/admin/login.php');
    }
}

/** ตัวดำเนินการค้นหาแบบไม่สนตัวพิมพ์เล็กใหญ่ (MySQL ทำอยู่แล้ว, PostgreSQL ต้องใช้ ILIKE) */
function dbLike(): string {
    return DB_DRIVER === 'pgsql' ? 'ILIKE' : 'LIKE';
}

/**
 * CSRF token สำหรับฟอร์มที่เปลี่ยนแปลงข้อมูล (ใช้ในหน้าแอดมิน)
 * ในฟอร์มใส่ <?= csrfField() ?> และตอนรับ POST เรียก verifyCsrf()
 */
function csrfToken(): string {
    if (empty($_SESSION['csrf'])) $_SESSION['csrf'] = bin2hex(random_bytes(16));
    return $_SESSION['csrf'];
}
function csrfField(): string {
    return '<input type="hidden" name="csrf" value="' . csrfToken() . '">';
}
function verifyCsrf(): void {
    if (!hash_equals(csrfToken(), (string) ($_POST['csrf'] ?? ''))) {
        http_response_code(400);
        exit(t('err.csrf'));
    }
}

/**
 * Sanitize user input.
 * ฟังก์ชันนี้คือ "เครื่องกรองน้ำ" ครับ ใช้ทำความสะอาดข้อมูลที่ผู้ใช้กรอกเข้ามา
 * ตัด Tag อันตราย, ตัดช่องว่าง และแปลงอักขระพิเศษเพื่อป้องกัน XSS
 * @param string $data
 * @return string
 */
function sanitize($data) {
    return htmlspecialchars(strip_tags(trim($data)));
}

/**
 * Generate a unique booking code.
 * อันนี้คือเครื่องมือสร้าง "รหัสการจอง" แบบไม่ซ้ำใคร
 * รูปแบบจะเป็น BK + วันที่ + รหัสสุ่ม
 * @return string
 */
function generateBookingCode() {
    return 'BK' . date('Ymd') . strtoupper(substr(uniqid(), -6));
}

/**
 * Format price with Thai Baht suffix.
 * จัดรูปแบบราคาให้สวยงาม มีคอมม่าคั่น และต่อท้ายด้วย "THB"
 * เช่น 1500 -> 1,500 THB
 * @param float $price
 * @return string
 */
function formatPrice($price) {
    $decimals = floor($price) == $price ? 0 : 2;
    return number_format($price, $decimals) . ' ' . t('currency');
}

/**
 * Format date to readable string.
 * เปลี่ยนวันที่จากตัวเลขอ่านยาก ให้เป็นรูปแบบที่คนอ่านออก เช่น 01 Jan 2025
 * @param string $date
 * @return string
 */
function formatDate($date) {
    return formatDateLocalized($date);
}



?>