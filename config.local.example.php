<?php
// ตัวอย่างไฟล์ตั้งค่าสำหรับโฮสต์ที่ตั้ง Environment Variables ไม่ได้ (เช่น InfinityFree)
// วิธีใช้: คัดลอกไฟล์นี้เป็น config.local.php แล้วใส่ค่าจริง (config.local.php จะไม่ถูก commit ขึ้น Git)
// บน Vercel ไม่ต้องใช้ไฟล์นี้ ให้ตั้งค่าเดียวกันใน Project Settings > Environment Variables แทน
return [
    'DB_DRIVER'       => 'mysql',      // mysql หรือ pgsql (Supabase)
    'DB_HOST'         => 'localhost',
    'DB_PORT'         => '3306',
    'DB_NAME'         => 'hit_the_court',
    'DB_USER'         => 'root',
    'DB_PASS'         => '',
    'DB_SSL'          => '0',
    'THUNDER_API_KEY' => '',
    // 'SITE_URL'     => 'https://hitthecourt.gt.tc',
];
