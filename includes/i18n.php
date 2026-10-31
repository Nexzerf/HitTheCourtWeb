<?php
// includes/i18n.php
// ระบบสองภาษา (ไทย / อังกฤษ)
// - เปลี่ยนภาษาด้วย ?lang=th หรือ ?lang=en (จำไว้ใน Cookie 1 ปี)
// - ข้อความอยู่ใน lang/en.php และ lang/th.php
// - ในหน้าเว็บใช้ t('key') หรือ t('key', ['name' => 'value']) เพื่อแทนค่า {name}

const SUPPORTED_LANGS = ['en', 'th'];

$requestedLang = $_GET['lang'] ?? null;
if (in_array($requestedLang, SUPPORTED_LANGS, true)) {
    if (!headers_sent()) {
        setcookie('lang', $requestedLang, [
            'expires' => time() + 365 * 24 * 3600,
            'path' => '/',
            'samesite' => 'Lax',
        ]);
    }
    $_COOKIE['lang'] = $requestedLang;
}
define('LANG', in_array($_COOKIE['lang'] ?? '', SUPPORTED_LANGS, true) ? $_COOKIE['lang'] : 'en');

$GLOBALS['__translations'] = [
    'en' => require __DIR__ . '/../lang/en.php',
    'th' => require __DIR__ . '/../lang/th.php',
];

/**
 * แปลข้อความตาม key (ถ้าไม่มีในภาษาที่เลือก จะใช้ภาษาอังกฤษ และถ้าไม่มีเลยจะคืน key กลับไป)
 */
function t(string $key, array $vars = []): string {
    $text = $GLOBALS['__translations'][LANG][$key]
        ?? $GLOBALS['__translations']['en'][$key]
        ?? $key;
    foreach ($vars as $name => $value) {
        $text = str_replace('{' . $name . '}', (string) $value, $text);
    }
    return $text;
}

/** t() ที่ escape HTML แล้ว สำหรับพิมพ์ลงหน้าเว็บโดยตรง */
function e(string $key, array $vars = []): string {
    return htmlspecialchars(t($key, $vars), ENT_QUOTES, 'UTF-8');
}

/**
 * เลือกค่าจากฐานข้อมูลตามภาษา เช่น localized($sport, 'sport_name', 'name_th')
 * ถ้าไม่มีภาษาไทยจะใช้ภาษาอังกฤษแทน
 */
function localized(array $row, string $enField, string $thField): string {
    if (LANG === 'th' && !empty($row[$thField])) return $row[$thField];
    return (string) ($row[$enField] ?? '');
}

/** URL ของหน้าปัจจุบันแต่เปลี่ยนภาษา (ใช้กับปุ่มสลับภาษา) */
function langSwitchUrl(string $lang): string {
    $uri = $_SERVER['REQUEST_URI'] ?? '/';
    $path = parse_url($uri, PHP_URL_PATH) ?: '/';
    parse_str(parse_url($uri, PHP_URL_QUERY) ?? '', $query);
    $query['lang'] = $lang;
    return $path . '?' . http_build_query($query);
}

/** วันที่แบบอ่านง่ายตามภาษา (ภาษาไทยใช้ชื่อเดือนไทยและ พ.ศ.) */
function formatDateLocalized(string $date, bool $withWeekday = false): string {
    $ts = strtotime($date);
    if (LANG !== 'th') return date($withWeekday ? 'D, d M Y' : 'd M Y', $ts);

    $months = ['ม.ค.', 'ก.พ.', 'มี.ค.', 'เม.ย.', 'พ.ค.', 'มิ.ย.', 'ก.ค.', 'ส.ค.', 'ก.ย.', 'ต.ค.', 'พ.ย.', 'ธ.ค.'];
    $days = ['อา.', 'จ.', 'อ.', 'พ.', 'พฤ.', 'ศ.', 'ส.'];
    $text = date('j', $ts) . ' ' . $months[date('n', $ts) - 1] . ' ' . (date('Y', $ts) + 543);
    return $withWeekday ? $days[date('w', $ts)] . ' ' . $text : $text;
}

/** เวลาแบบ 24 ชม. เช่น 09:00 */
function formatTime(string $time): string {
    return date('H:i', strtotime($time));
}
