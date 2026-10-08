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

1. Import ไฟล์ `if0_41257064_hit_the_court.sql` (export ล่าสุดจาก InfinityFree)
2. รัน `database/vercel_tables.sql` เพื่อสร้างตาราง `sessions` และ `uploaded_files`

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
| `THUNDER_API_KEY` | | ใช้สร้าง QR และตรวจสลิป |
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
