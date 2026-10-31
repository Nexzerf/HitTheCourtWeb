<?php
// admin/payments.php
// การชำระเงินรวมอยู่ในหน้าการจองแล้ว (ดูสลิปและอัปเดตสถานะได้ที่ admin/bookings.php)
require_once '../config.php';
requireAdmin();
redirect('/admin/bookings.php?status=pending');
