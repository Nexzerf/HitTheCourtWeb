**โปรดอ่าน**

- ไฟล์ในนี้สามารถโหลดไปอ่านทำความเข้าใจได้เท่านั้น
  __***แต่ไม่สามารถนำไปรันบน Localhost ของตัวเองได้***__

- ถ้าหากต้องการเปิดเว็ปให้เข้าลิ้งก์นี้
  https://hitthecourt.gt.tc/

- และหากต้องการเข้าหน้า Admin Dashboard ให้พิมพ์หรือกดเข้าลิ้งก์นี้
  https://hitthecourt.gt.tc/admin/login.php


---

## Deploy บน Vercel

โปรเจกต์นี้รันบน Vercel ได้ผ่าน runtime [`vercel-php`](https://github.com/vercel-community/php)
ทุก Request จะเข้า `api/index.php` ซึ่งทำหน้าที่แทน `.htaccess` (ดู `vercel.json`)

### 1. เตรียมฐานข้อมูล MySQL ที่เชื่อมต่อจากภายนอกได้
MySQL ของ InfinityFree ไม่อนุญาตให้เชื่อมต่อจากภายนอก จึงต้องใช้ผู้ให้บริการอื่น เช่น TiDB Cloud, Aiven หรือ Railway

รันไฟล์ SQL ตามลำดับนี้ (ตั้ง charset เป็น **utf8mb4** ตอน import ไม่อย่างนั้นภาษาไทยจะเพี้ยน
เช่น `mysql --default-character-set=utf8mb4 ...`)

1. `if0_41257064_hit_the_court.sql` (export ล่าสุดจาก InfinityFree)
2. `database/vercel_tables.sql` — ตาราง `sessions` และ `uploaded_files`
3. `database/migrations/001_spec_update.sql` — ข้อมูลกีฬา/อุปกรณ์ตามสเปก, ชื่อภาษาไทย, การจองหลายช่วงเวลา (รันครั้งเดียว)

### 2. สร้างโปรเจกต์บน Vercel
Import repo นี้ที่ https://vercel.com/new (Framework Preset: **Other**, ไม่ต้องมี Build Command)

### 3. ตั้งค่า Environment Variables (Project Settings > Environment Variables)

| ชื่อ | ตัวอย่าง | หมายเหตุ |
|---|---|---|
| `DB_HOST` | `gateway01.ap-southeast-1.prod.aws.tidbcloud.com` | |
| `DB_PORT` | `4000` | ค่าเริ่มต้น 3306 |
| `DB_NAME` | `hit_the_court` | |
| `DB_USER` | | |
| `DB_PASS` | | |
| `DB_SSL` | `1` | ฐานข้อมูลบนคลาวด์ส่วนใหญ่บังคับ SSL |
| `PAYMENT_MODE` | `demo` | `demo` (ค่าเริ่มต้น) = แสดง QR พร้อมเพย์แต่ไม่ต้องจ่ายจริง กดยืนยันได้เลย / `thunder` = ตรวจสลิปจริง |
| `THUNDER_API_KEY` | | ใช้เมื่อ `PAYMENT_MODE=thunder` เท่านั้น |
| `SITE_URL` | `https://hitthecourt.vercel.app` | ไม่บังคับ ถ้าไม่ตั้งจะใช้โดเมนที่เข้ามาจริง |
| `APP_DEBUG` | `1` | ไม่บังคับ เปิดแสดง Error ชั่วคราวตอนแก้ปัญหา |

แล้วกด Redeploy

### ข้อจำกัดบน Vercel
- Session และไฟล์อัปโหลด (สลิป, รูปแนบรายงาน) เก็บในฐานข้อมูล เปิดดูผ่าน `/media/{id}` (ต้องล็อกอิน)
- อัปโหลดไฟล์ได้ไม่เกิน 4MB ต่อครั้ง (Vercel จำกัด Request ไว้ที่ 4.5MB)
- สลิปเก่าในโฟลเดอร์ `uploads/` ยังเปิดดูได้ตามเดิม

### รันบนโฮสต์ทั่วไป (เช่น InfinityFree)
คัดลอก `config.local.example.php` เป็น `config.local.php` แล้วใส่รหัสผ่านฐานข้อมูลและ API Key
(ไฟล์นี้ไม่ถูก commit ขึ้น Git)

---

## ภาพรวมระบบ

- **สองภาษา:** สลับ TH / EN ได้ที่แถบเมนู (จำไว้ใน Cookie) ข้อความอยู่ใน `lang/en.php` และ `lang/th.php`
- **กติกาการจอง:** อยู่ที่ `includes/booking_rules.php` ที่เดียว (ใช้ทั้งหน้าแสดงราคาและตอนบันทึก)
  - ผู้ใช้ทั่วไป: จองล่วงหน้า 2 วัน, 1 ช่วงเวลาต่อการจอง
  - สมาชิก: จองล่วงหน้า 7 วัน, ไม่จำกัดช่วงเวลา, ยืมอุปกรณ์ฟรีตามจำนวนที่กำหนด, ส่วนลด 30% วันที่ 1/16,
    20% จองติดกัน 3 ช่วงขึ้นไป, 10% จองกีฬานั้นครั้งแรก (ใช้ส่วนลดที่มากที่สุดอันเดียว), 10 แต้มแลกจองฟรี 1 รอบ
  - อุปกรณ์: 1 สนามยืมได้ไม่เกินครึ่งหนึ่งของสต๊อก และต้องมีของว่างในช่วงเวลานั้น
  - การจองที่ยังไม่จ่ายเงินจะล็อกสนามไว้ 15 นาที
- **หน้าแอดมิน:** `/admin/login.php` — การจองทั้งหมด (อัปเดต/ลบ), สนามและอุปกรณ์ (เพิ่ม/แก้สต๊อก/ลบ),
  ฝ่ายบริการลูกค้า (รายงานปัญหา), สมาชิก และสถิติ
