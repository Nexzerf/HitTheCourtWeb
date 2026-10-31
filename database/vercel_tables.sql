-- ตารางเพิ่มเติมที่ต้องมีเมื่อรันบน Vercel
-- รันไฟล์นี้หลังจาก import ฐานข้อมูลหลักแล้ว

-- เก็บ Session การล็อกอิน (SESSION_DRIVER=database)
CREATE TABLE IF NOT EXISTS `sessions` (
  `id` varchar(128) NOT NULL,
  `data` mediumblob NOT NULL,
  `last_activity` int unsigned NOT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_sessions_last_activity` (`last_activity`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- เก็บไฟล์อัปโหลด เช่น สลิป และรูปแนบรายงาน (UPLOAD_DRIVER=database) เก็บเป็น base64
CREATE TABLE IF NOT EXISTS `uploaded_files` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `folder` varchar(50) NOT NULL,
  `filename` varchar(255) NOT NULL,
  `mime_type` varchar(100) NOT NULL,
  `data` longtext NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
