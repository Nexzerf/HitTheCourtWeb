-- ============================================================
-- 001_spec_update.sql
-- ปรับข้อมูลกีฬา อุปกรณ์ และโครงสร้างตารางให้ตรงกับสเปกล่าสุด
-- รันหลังจาก import ฐานข้อมูลหลักและ database/vercel_tables.sql แล้ว
-- รันครั้งเดียว (ใช้ได้ทั้ง MySQL, MariaDB และ TiDB)
-- ============================================================

-- ---------- ชื่อภาษาไทย ----------
ALTER TABLE `sports`    ADD COLUMN `name_th` varchar(100) DEFAULT NULL AFTER `sport_name`;
ALTER TABLE `equipment` ADD COLUMN `name_th` varchar(150) DEFAULT NULL AFTER `eq_name`;
-- รายละเอียดว่า 1 unit มีอะไรบ้าง (เช่น เสื้อแบ่งทีม 1 unit = 22 ตัว)
ALTER TABLE `equipment` ADD COLUMN `unit_note` varchar(255) DEFAULT NULL AFTER `name_th`;
ALTER TABLE `equipment` ADD COLUMN `unit_note_th` varchar(255) DEFAULT NULL AFTER `unit_note`;
-- จำนวนที่สมาชิกยืมฟรี (กี่ unit แรก)
ALTER TABLE `equipment` ADD COLUMN `member_free_units` int(11) NOT NULL DEFAULT 0 AFTER `max_per_court`;

-- ---------- กีฬา (ตามสเปก) ----------
UPDATE `sports` SET name_th = 'แบดมินตัน',  duration_minutes = 60,  price = 130, price_per_round = 130, max_courts = 4, total_courts = 4 WHERE slug = 'badminton';
UPDATE `sports` SET name_th = 'ฟุตบอล',     duration_minutes = 100, price = 500, price_per_round = 500, max_courts = 2, total_courts = 2 WHERE slug = 'football';
UPDATE `sports` SET name_th = 'เทนนิส',     duration_minutes = 120, price = 200, price_per_round = 200, max_courts = 4, total_courts = 4 WHERE slug = 'tennis';
UPDATE `sports` SET name_th = 'วอลเลย์บอล', duration_minutes = 120, price = 150, price_per_round = 150, max_courts = 4, total_courts = 4 WHERE slug = 'volleyball';
UPDATE `sports` SET name_th = 'บาสเกตบอล',  duration_minutes = 60,  price = 130, price_per_round = 130, max_courts = 8, total_courts = 8 WHERE slug = 'basketball';
UPDATE `sports` SET name_th = 'ปิงปอง', sport_name = 'Table Tennis (Ping-Pong)', duration_minutes = 60, price = 40, price_per_round = 40, max_courts = 6, total_courts = 6 WHERE slug = 'table-tennis';
UPDATE `sports` SET name_th = 'ฟุตซอล',     duration_minutes = 60,  price = 130, price_per_round = 130, max_courts = 2, total_courts = 2 WHERE slug = 'futsal';

-- ---------- อุปกรณ์ (ตามสเปก) ----------
-- 1 สนามยืมได้มากที่สุดครึ่งหนึ่งของสต๊อก (max_per_court = stock / 2)
UPDATE `equipment` SET eq_name = 'Badminton Racket', name_th = 'ไม้แบดมินตัน', price = 10, stock = 50, max_per_court = 25, member_free_units = 5, status = 'available' WHERE eq_id = 1;
UPDATE `equipment` SET eq_name = 'Football', name_th = 'ลูกฟุตบอล', price = 50, stock = 40, max_per_court = 20, member_free_units = 2, status = 'available' WHERE eq_id = 2;
UPDATE `equipment` SET eq_name = 'Team Bibs', name_th = 'เสื้อแบ่งทีม', price = 30, stock = 10, max_per_court = 5, member_free_units = 1,
    unit_note = '1 unit = 22 bibs (11 red + 11 blue)', unit_note_th = '1 unit = 22 ตัว (แดง 11 + น้ำเงิน 11)', status = 'available' WHERE eq_id = 3;
UPDATE `equipment` SET eq_name = 'Cones', name_th = 'กรวย', price = 20, stock = 10, max_per_court = 5, member_free_units = 1,
    unit_note = '1 unit = 20 cones', unit_note_th = '1 unit = 20 กรวย', status = 'available' WHERE eq_id = 4;
UPDATE `equipment` SET eq_name = 'Training Equipment Set', name_th = 'เซตอุปกรณ์ซ้อม', price = 300, stock = 6, max_per_court = 3, member_free_units = 0,
    unit_note = '1 unit = 6 cones, 4 hurdles, 15 markers, 1 speed ladder', unit_note_th = '1 unit = กรวย 6, แท่นกระโดด 4, มาร์กเกอร์ 15, speed ladder 1', status = 'available' WHERE eq_id = 5;
UPDATE `equipment` SET eq_name = 'Tennis Racket', name_th = 'ไม้เทนนิส', price = 50, stock = 16, max_per_court = 8, member_free_units = 2, status = 'available' WHERE eq_id = 6;
UPDATE `equipment` SET eq_name = 'Tennis Ball', name_th = 'ลูกเทนนิส', price = 15, stock = 20, max_per_court = 10, member_free_units = 3, status = 'available' WHERE eq_id = 7;
UPDATE `equipment` SET eq_name = 'Volleyball', name_th = 'ลูกวอลเลย์บอล', price = 50, stock = 20, max_per_court = 10, member_free_units = 2, status = 'available' WHERE eq_id = 8;
UPDATE `equipment` SET eq_name = 'Basketball', name_th = 'ลูกบาสเกตบอล', price = 50, stock = 30, max_per_court = 15, member_free_units = 2, status = 'available' WHERE eq_id = 9;
UPDATE `equipment` SET eq_name = 'Ping-Pong Racket', name_th = 'ไม้ปิงปอง', price = 10, stock = 24, max_per_court = 12, member_free_units = 2, status = 'available' WHERE eq_id = 10;
UPDATE `equipment` SET eq_name = 'Ping-Pong Ball', name_th = 'ลูกปิงปอง', price = 5, stock = 24, max_per_court = 12, member_free_units = 5, status = 'available' WHERE eq_id = 11;
UPDATE `equipment` SET eq_name = 'Futsal Ball', name_th = 'ลูกฟุตซอล', price = 40, stock = 40, max_per_court = 20, member_free_units = 3, status = 'available' WHERE eq_id = 12;
-- ลูกแบดไม่อยู่ในสเปก ปิดไว้ (ไม่ลบ เพราะอาจมีประวัติการจองอ้างอิงอยู่)
UPDATE `equipment` SET name_th = 'ลูกแบดมินตัน', status = 'unavailable' WHERE eq_id = 13;

-- ---------- การจองหลายช่วงเวลา (สมาชิกจองได้ไม่จำกัดช่วงเวลา) ----------
CREATE TABLE IF NOT EXISTS `booking_slots` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `booking_id` int(11) NOT NULL,
  `slot_id` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_booking_slot` (`booking_id`, `slot_id`),
  KEY `idx_booking_slots_slot` (`slot_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- การจองเก่ามีช่วงเวลาเดียว ย้ายเข้าตารางใหม่
INSERT IGNORE INTO `booking_slots` (booking_id, slot_id) SELECT booking_id, slot_id FROM `bookings`;

-- ---------- ข้อมูลลูกค้าในการจอง ----------
ALTER TABLE `bookings` ADD COLUMN `first_name` varchar(100) DEFAULT NULL AFTER `user_id`;
ALTER TABLE `bookings` ADD COLUMN `last_name` varchar(100) DEFAULT NULL AFTER `first_name`;
ALTER TABLE `bookings` ADD COLUMN `phone` varchar(20) DEFAULT NULL AFTER `last_name`;
ALTER TABLE `bookings` ADD COLUMN `email` varchar(100) DEFAULT NULL AFTER `phone`;
ALTER TABLE `bookings` ADD COLUMN `payment_method` enum('promptpay','bank_transfer') DEFAULT 'promptpay' AFTER `total_price`;
ALTER TABLE `bookings` ADD COLUMN `discount_note` varchar(255) DEFAULT NULL AFTER `discount_amount`;
ALTER TABLE `bookings` ADD COLUMN `points_redeemed` int(11) NOT NULL DEFAULT 0 AFTER `discount_note`;
ALTER TABLE `booking_equipment` ADD COLUMN `free_quantity` int(11) NOT NULL DEFAULT 0 AFTER `quantity`;

-- ---------- ข้อมูลผู้สมัครสมาชิก ----------
ALTER TABLE `user_membership` ADD COLUMN `first_name` varchar(100) DEFAULT NULL AFTER `plan_id`;
ALTER TABLE `user_membership` ADD COLUMN `last_name` varchar(100) DEFAULT NULL AFTER `first_name`;
ALTER TABLE `user_membership` ADD COLUMN `email` varchar(100) DEFAULT NULL AFTER `last_name`;
ALTER TABLE `user_membership` ADD COLUMN `phone` varchar(20) DEFAULT NULL AFTER `email`;
ALTER TABLE `user_membership` ADD COLUMN `payment_method` enum('promptpay','bank_transfer') DEFAULT 'promptpay' AFTER `total_price`;

-- ---------- ผู้ใช้ ----------
ALTER TABLE `users` ADD COLUMN `first_name` varchar(100) DEFAULT NULL AFTER `username`;
ALTER TABLE `users` ADD COLUMN `last_name` varchar(100) DEFAULT NULL AFTER `first_name`;

-- ---------- รายงานปัญหา ----------
-- หัวข้อ: cancel (ยกเลิกการจอง), damage (ชำรุด), service (บริการ), other (อื่น ๆ)
ALTER TABLE `reports` ADD COLUMN `category` enum('cancel','damage','service','other') NOT NULL DEFAULT 'other' AFTER `user_id`;

-- ---------- แพ็กเกจสมาชิก ----------
UPDATE `membership_plans` SET price = 499, duration_months = 3, advance_booking_days = 7,
    discount_day1 = 30, discount_day16 = 30, discount_consecutive = 20, discount_first_booking = 10
WHERE plan_id = 1;
