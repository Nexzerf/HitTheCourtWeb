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

### 1. ฐานข้อมูล: Supabase (PostgreSQL)
ฐานข้อมูลอยู่ใน Supabase โปรเจกต์ **Project2** ใน schema `hitthecourt` (แยกจากตารางอื่นในโปรเจกต์)
เว็บเชื่อมต่อด้วย role `hitthecourt_app` ที่เข้าถึงได้เฉพาะ schema นี้ และเปิด RLS ทุกตาราง
(anon key / REST API ของ Supabase อ่านข้อมูลเว็บนี้ไม่ได้)

ถ้าต้องสร้างใหม่ในโปรเจกต์อื่น:
1. รัน `database/postgres/schema.sql` ใน SQL Editor
2. แปลงข้อมูลจาก MySQL เดิม: `php database/postgres/export_mysql_data.php "mysql:host=...;dbname=...;charset=utf8mb4" user pass > data.sql` แล้วรัน `data.sql`
3. ตั้งรหัสผ่าน: `ALTER ROLE hitthecourt_app WITH LOGIN PASSWORD '...';`

> ยังใช้ MySQL ได้เหมือนเดิม (ตั้ง `DB_DRIVER=mysql`) โดยรันไฟล์ SQL ตามลำดับ (import แบบ **utf8mb4**):
> `if0_41257064_hit_the_court.sql` → `database/vercel_tables.sql` → `database/migrations/001_spec_update.sql`

### 2. สร้างโปรเจกต์บน Vercel
Import repo นี้ที่ https://vercel.com/new (Framework Preset: **Other**, ไม่ต้องมี Build Command)

### 3. ตั้งค่า Environment Variables (Project Settings > Environment Variables)

| ชื่อ | ตัวอย่าง | หมายเหตุ |
|---|---|---|
| `DB_DRIVER` | `pgsql` | `pgsql` = Supabase, `mysql` = MySQL |
| `DB_HOST` | `aws-0-ap-southeast-1.pooler.supabase.com` | Supabase → ปุ่ม **Connect** → **Session pooler** (Vercel ใช้ IPv6 ไม่ได้ จึงต้องใช้ pooler) |
| `DB_PORT` | `5432` | Session pooler (ถ้าใช้ Transaction pooler = 6543 ก็ใช้ได้) |
| `DB_NAME` | `postgres` | |
| `DB_USER` | `hitthecourt_app.wgpumjrmowscxckqxshi` | role ของเว็บ + จุด + project ref |
| `DB_PASS` | | รหัสผ่านของ `hitthecourt_app` |
| `DB_SSL` | `1` | |
| `DB_SCHEMA` | `hitthecourt` | ไม่บังคับ (ค่าเริ่มต้นของ pgsql) |
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
