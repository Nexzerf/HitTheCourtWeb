<?php
// database/postgres/export_mysql_data.php
// แปลงข้อมูลจากฐานข้อมูล MySQL เดิมเป็นคำสั่ง INSERT ของ PostgreSQL (schema hitthecourt)
//
// วิธีใช้ (รันบนเครื่องที่ต่อ MySQL ได้):
//   php database/postgres/export_mysql_data.php "mysql:host=127.0.0.1;dbname=hit_the_court;charset=utf8mb4" user pass > data.sql
// แล้วนำ data.sql ไปรันใน Supabase (SQL Editor) หลังจากรัน schema.sql แล้ว

[$_, $dsn, $user, $pass] = $argv + [null, null, null, null];
if (!$dsn) { fwrite(STDERR, "usage: php export_mysql_data.php <mysql-dsn> <user> <pass>\n"); exit(1); }
$my = new PDO($dsn, $user, $pass, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]);

// เรียงตาม foreign key: [ตาราง => คอลัมน์ id]
$tables = [
    'admins' => 'admin_id', 'sports' => 'sport_id', 'courts' => 'court_id', 'time_slots' => 'slot_id',
    'equipment' => 'eq_id', 'users' => 'user_id', 'membership_plans' => 'plan_id', 'user_membership' => 'id',
    'bookings' => 'booking_id', 'booking_slots' => 'id', 'booking_equipment' => 'id', 'payments' => 'payment_id',
    'reports' => 'report_id', 'settings' => 'setting_id',
];

// ค่าที่ MySQL ยอมรับ (enum ว่าง) แต่ Postgres ไม่ยอม
$fixes = [
    'bookings' => ['payment_status' => ['' => 'failed']],
    'user_membership' => ['payment_status' => ['' => 'pending']],
];

function pgLiteral($v): string {
    if ($v === null) return 'NULL';
    if (is_int($v) || is_float($v)) return (string) $v;
    return "'" . str_replace("'", "''", (string) $v) . "'";
}

echo "-- ข้อมูลที่แปลงจาก MySQL เมื่อ " . date('Y-m-d H:i:s') . "\nBEGIN;\nSET search_path TO hitthecourt;\n\n";
foreach ($tables as $table => $idCol) {
    $rows = $my->query("SELECT * FROM `$table` ORDER BY `$idCol`")->fetchAll();
    echo "-- $table (" . count($rows) . " rows)\n";
    foreach (array_chunk($rows, 200) as $chunk) {
        $cols = array_keys($chunk[0]);
        $values = [];
        foreach ($chunk as $row) {
            foreach ($fixes[$table] ?? [] as $col => $map) {
                if (array_key_exists((string) $row[$col], $map)) $row[$col] = $map[(string) $row[$col]];
            }
            // วันที่ศูนย์ของ MySQL ใช้ไม่ได้ใน Postgres
            foreach ($row as $k => $v) if (is_string($v) && str_starts_with($v, '0000-00-00')) $row[$k] = null;
            $values[] = '(' . implode(', ', array_map('pgLiteral', array_values($row))) . ')';
        }
        echo "INSERT INTO $table (" . implode(', ', $cols) . ") OVERRIDING SYSTEM VALUE VALUES\n" . implode(",\n", $values) . ";\n";
    }
    // ให้ id ถัดไปต่อจากค่ามากสุดที่มีอยู่
    echo "SELECT setval(pg_get_serial_sequence('hitthecourt.$table', '$idCol'), COALESCE((SELECT MAX($idCol) FROM $table), 0) + 1, false);\n\n";
}
echo "COMMIT;\n";
