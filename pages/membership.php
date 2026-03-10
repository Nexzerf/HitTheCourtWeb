<?php
// อันดับแรกเลย ดึงไฟล์ config.php มาเพื่อเชื่อมฐานข้อมูลและใช้ฟังก์ชันต่างๆ
require_once '../config.php';
// เช็คทันทีว่าล็อกอินแล้วยัง? ถ้ายังไม่ล็อกอินก็ไม่ให้เข้าหน้านี้
requireLogin();

// ดึงข้อมูล "ฉัน" (User ที่ล็อกอินอยู่) จากฐานข้อมูลมาเก็บไว้ในตัวแปร $user
 $userStmt = $pdo->prepare("SELECT * FROM users WHERE user_id = ?");
 $userStmt->execute([$_SESSION['user_id']]);
 $user = $userStmt->fetch();

 // ดึงข้อมูลแพ็กเกจสมาชิก (Plan) ที่ยังเปิดให้บริการอยู่ (status = active) ออกมาแสดง จำกัดไว้ 1 แพ็กเกจนะ
 $planStmt = $pdo->query("SELECT * FROM membership_plans WHERE status = 'active' LIMIT 1");
 $plan = $planStmt->fetch();

 // เตรียมตัวแปรไว้เก็บ Error ต่างๆ
 $errors = [];

// Handle Purchase Request
// ส่วนนี้คือจัดการตอนที่ User กดปุ่ม "ซื้อแพ็กเกจ" (Method POST)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $plan) {
    // เช็คก่อนว่า "เป็นสมาชิกอยู่แล้วไหม?" ถ้าใช่และยังไม่หมดอายุ ก็แจ้งเตือนว่า "คุณมีแล้วนะ"
    if ($user['is_member'] && $user['member_expire'] > date('Y-m-d')) {
        $errors['general'] = 'You already have an active membership.';
    } else {
        // ถ้ายังไม่มี ก็เริ่มคำนวณวันที่
        // วันเริ่มต้นคือวันนี้
        $startDate = date('Y-m-d');
        // วันสิ้นสุดคือ เอาเดือนของแพ็กเกจมาบวกเพิ่มไป
        $endDate = date('Y-m-d', strtotime('+' . $plan['duration_months'] . ' months'));
        
        try {
            // Insert Order
            // บันทึกข้อมูลการสมัครลงตาราง user_membership สถานะตอนนี้คือ 'pending' (รอจ่ายเงิน)
            $stmt = $pdo->prepare("INSERT INTO user_membership (user_id, plan_id, start_date, end_date, total_price, payment_status) VALUES (?, ?, ?, ?, ?, 'pending')");
            $stmt->execute([$_SESSION['user_id'], $plan['plan_id'], $startDate, $endDate, $plan['price']]);
            
            // ดึง ID ล่าสุดที่เพิ่งบันทึกไปมาใช้
            $membershipId = $pdo->lastInsertId();
            
            // แก้ไขตรงนี้: เอา SITE_URL ออก
            // พอสร้างออเดอร์แล้ว ก็ส่ง User ไปหน้าจ่ายเงินทันที พร้อมส่ง ID ไปด้วย
            redirect('/membership_payment?id=' . $membershipId);
            
        } catch (PDOException $e) {
            // ถ้าฐานข้อมูลมีปัญหา ก็แจ้ง Error
            $errors['general'] = 'Database Error: ' . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<!-- ตรงนี้เริ่มส่วนของหน้าตาเว็บไซต์ (HTML) -->
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <!-- ตั้งชื่อหน้าเว็บ -->
    <title>Membership - Hit The Court</title>
    <!-- โหลด Font สวยๆ -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Space+Grotesk:wght@500;600;700&display=swap" rel="stylesheet">
    <!-- โหลดไฟล์ CSS สำหรับแต่งหน้านี้ -->
    <link rel="stylesheet" href="<?= SITE_URL ?>/assets/css/home.css">
    <link rel="stylesheet" href="<?= SITE_URL ?>/assets/css/membership.css">
        <link rel="icon" type="image/png" sizes="32x32" href="/images/favicon-32x32.png">
    <link rel="icon" type="image/png" sizes="16x16" href="/images/favicon-48x48.png">
</head>
<body>

 <!-- NAVBAR -->
<nav class="navbar-home" id="navbar">
<div class="navbar-container">

    <a href="/" class="navbar-logo">HIT THE <span>COURT</span></a>

    <button class="mobile-toggle" aria-label="Toggle menu">
        <div class="hamburger-box">
            <span class="bar"></span>
            <span class="bar"></span>
            <span class="bar"></span>
        </div>
    </button>

    <ul class="nav-menu">
        <li class="nav-item"><a href="/courts" class="nav-link">Courts</a></li>
        <li class="nav-item"><a href="/reservations" class="nav-link">Reservations</a></li>
        <li class="nav-item"><a href="/reports" class="nav-link">Contact Us</a></li>
        <li class="nav-item"><a href="/guidebook" class="nav-link">Guidebook</a></li>

        <?php if (!isLoggedIn()): ?>
        <!-- Login/SignUp เฉพาะ mobile overlay — desktop ซ่อนด้วย CSS -->
        <li class="nav-auth-mobile-item">
            <a href="/login"    class="btn btn-outline">Login</a>
            <a href="/register" class="btn btn-primary">Sign Up</a>
        </li>
        <?php endif; ?>
    </ul>

    <div class="nav-auth">
        <?php if (isLoggedIn()): ?>
            <div class="user-menu">
                <button class="user-btn">
                    <div class="user-avatar">
                        <?= strtoupper(substr($_SESSION['username'], 0, 1)) ?>
                    </div>
                    <span><?= htmlspecialchars($_SESSION['username']) ?></span>
                </button>
                <div class="user-dropdown">
                    <a href="/reservations" class="dropdown-link">My Bookings</a>
                    <a href="/profile"      class="dropdown-link">My Profile</a>
                    <a href="/membership"   class="dropdown-link">Membership</a>
                    <a href="/api/auth.php?action=logout" class="dropdown-link" style="color:red;">Logout</a>
                </div>
            </div>
        <?php else: ?>
            <!-- Desktop เท่านั้น — mobile ถูกซ่อนด้วย CSS -->
            <a href="/login"    class="btn btn-ghost">Login</a>
            <a href="/register" class="btn btn-primary">Sign Up</a>
        <?php endif; ?>
    </div>

</div>
</nav>


    <div class="membership-page">
        <!-- Hero -->
        <!-- ส่วนหัวข้อใหญ่ของหน้านี้ -->
        <section class="membership-hero">
            <h1>Unlock Your Full Potential</h1>
            <p>Get exclusive benefits, discounts, and priority booking.</p>
        </section>

        <!-- Content -->
        <!-- ส่วนเนื้อหาหลัก -->
        <div class="plan-container">
            
            <!-- Left -->
            <!-- ฝั่งซ้าย: แสดงรายละเอียดแพ็กเกจ -->
            <div>
                <?php if ($user['is_member'] && $user['member_expire'] > date('Y-m-d')): ?>
                <!-- ถ้า User เป็นสมาชิกอยู่แล้ว จะขึ้นกล่องสีเขียวบอกว่า "คุณเป็น Premium แล้ว" พร้อมวันหมดอายุ -->
                <div class="active-member-alert">
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path><polyline points="22 4 12 14.01 9 11.01"></polyline></svg>
                    <div>
                        <strong>You are a Premium Member!</strong>
                        <div style="font-size: 0.9rem; opacity: 0.8">Valid until: <?= date('d M Y', strtotime($user['member_expire'])) ?></div>
                    </div>
                </div>
                <?php endif; ?>

                <?php if (isset($errors['general'])): ?>
                <!-- ถ้ามี Error อะไรก็แสดงตรงนี้ -->
                <div class="active-member-alert" style="background: #FEF2F2; border-color: #FECACA; color: #991B1B;">
                    <?= $errors['general'] ?>
                </div>
                <?php endif; ?>

                <?php if ($plan): ?>
                <!-- การ์ดรายละเอียดแพ็กเกจ Premium -->
                <div class="premium-card">
                    <div class="premium-header">
                        <div class="premium-badge">Best Value</div>
                        <h2 class="premium-title"><?= htmlspecialchars($plan['plan_name']) ?></h2>
                        <!-- แสดงราคาและระยะเวลา -->
                        <p class="premium-price">
                            <?= number_format($plan['price']) ?>
                            <span>THB / <?= $plan['duration_months'] ?> Months</span>
                        </p>
                    </div>
                    <div class="premium-body">
                        <!-- ลิสต์สิทธิประโยชน์ต่างๆ -->
                        <ul class="feature-list">
                            <li>
                                <div class="feature-icon">✓</div>
                                <span><strong><?= $plan['advance_booking_days'] ?> Days</strong> Advance Booking</span>
                            </li>
                            <li>
                                <div class="feature-icon">✓</div>
                                <span><strong><?= $plan['discount_day1'] ?>% Discount</strong> on 1st & 16th</span>
                            </li>
                            <li>
                                <div class="feature-icon">✓</div>
                                <span><strong>Free Equipment</strong> (<?= $plan['free_equipment_limit'] ?> items/month)</span>
                            </li>
                        </ul>

                        <!-- ปุ่มกดซื้อ -->
                        <form method="POST" action="">
                            <!-- ถ้าเป็นสมาชิกแล้วก็จะ Disabled ปุ่มไว้ -->
                            <button type="submit" class="btn-get-premium" <?= ($user['is_member'] && $user['member_expire'] > date('Y-m-d')) ? 'disabled' : '' ?>>
                                <?php if ($user['is_member'] && $user['member_expire'] > date('Y-m-d')): ?>
                                    Already Active
                                <?php else: ?>
                                    Get Premium
                                <?php endif; ?>
                            </button>
                        </form>
                    </div>
                </div>
                <?php endif; ?>
            </div>

            <!-- Right -->
            <!-- ฝั่งขวา: ตารางเปรียบเทียบ "Normal vs Premium" -->
            <div class="comparison-card">
    <div class="comparison-header">
        <h3>Why Go Premium?</h3>
    </div>
    <!-- เพิ่มบรรทัดนี้ -->
    <div class="comparison-subheader">
        <span>Feature</span>
        <span>Normal</span>
        <span>Premium</span>
    </div>
    <table class="comparison-table">
        <thead>
            <tr>
                <th>Feature</th>
                <th style="text-align:center;">Normal</th>
                <th style="text-align:center;">Premium</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td>Advance Booking</td>
                <td style="text-align:center;">2 Days</td>
                <td style="text-align:center;" class="highlight-text">7 Days</td>
            </tr>
            <tr>
                <td>Special Discounts</td>
                <td style="text-align:center;">-</td>
                <td style="text-align:center;" class="highlight-text">Up to 30%</td>
            </tr>
            <tr>
                <td>Free Equipment</td>
                <td style="text-align:center;">-</td>
                <td style="text-align:center;" class="highlight-text">4 items/mo</td>
            </tr>
        </tbody>
    </table>
</div>
        </div>
    </div>
<script>
    document.addEventListener('DOMContentLoaded', function() {
    const toggleBtn = document.querySelector('.mobile-toggle');
    const navbar = document.getElementById('navbar');
    const body = document.body;

    // Toggle Mobile Menu
    if (toggleBtn) {
        toggleBtn.addEventListener('click', function() {
            navbar.classList.toggle('menu-open');
            
            // Toggle Icon (Hamburger to Close)
            const icon = this.querySelector('svg');
            if (navbar.classList.contains('menu-open')) {
                icon.innerHTML = '<line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line>'; // X icon
                body.style.overflow = 'hidden'; // Prevent scroll
            } else {
                icon.innerHTML = '<line x1="3" y1="12" x2="21" y2="12"></line><line x1="3" y1="6" x2="21" y2="6"></line><line x1="3" y1="18" x2="21" y2="18"></line>'; // Hamburger icon
                body.style.overflow = ''; // Enable scroll
            }
        });
    }

    // Handle Mobile Dropdowns (Click to open)
    const navItems = document.querySelectorAll('.nav-item');
    navItems.forEach(item => {
        const link = item.querySelector('.nav-link');
        const dropdown = item.querySelector('.dropdown-menu');
        
        if (dropdown && window.innerWidth <= 768) {
            link.addEventListener('click', function(e) {
                if (navbar.classList.contains('menu-open')) {
                     e.preventDefault(); // Prevent link jump
                     item.classList.toggle('mobile-sub-open');
                }
            });
        }
    });

    // Handle User Menu Click on Mobile
    const userMenu = document.querySelector('.user-menu');
    if (userMenu) {
        const userBtn = userMenu.querySelector('.user-btn');
        userBtn.addEventListener('click', function(e) {
            if (window.innerWidth <= 768) {
                e.stopPropagation();
                userMenu.classList.toggle('active');
            }
        });
    }
    
document.addEventListener('DOMContentLoaded', function() {
    const toggleBtn = document.querySelector('.mobile-toggle');
    const navbar = document.getElementById('navbar');
    const body = document.body;
    const userMenu = document.querySelector('.user-menu');

    if (toggleBtn) {
        toggleBtn.addEventListener('click', function(e) {
            e.stopPropagation();
            navbar.classList.toggle('menu-open');
            body.style.overflow = navbar.classList.contains('menu-open') ? 'hidden' : '';
        });
    }

    if (userMenu) {
        userMenu.querySelector('.user-btn').addEventListener('click', function(e) {
            e.stopPropagation();
            userMenu.classList.toggle('active');
        });
    }

    document.addEventListener('click', function(e) {
        if (navbar?.classList.contains('menu-open') && !navbar.contains(e.target)) {
            navbar.classList.remove('menu-open');
            body.style.overflow = '';
        }
        if (userMenu?.classList.contains('active') && !userMenu.contains(e.target)) {
            userMenu.classList.remove('active');
        }
    });
});
});
</script>
</body>
   <!-- FOOTER (Same as Homepage) -->
    <!-- ส่วนท้ายเว็บไซต์ (Footer) -->
    <footer class="footer">
        <div class="container">
            <div class="footer-grid">
                <div>
                    <span class="footer-logo">HIT THE COURT</span>
                    <p class="footer-text">
                        College of Arts, Media and Technology,<br>
                        Chiang Mai University<br>
                        © 2026 Hit the Court. All rights reserved.
                    </p>
                </div>
                
                <div class="footer-links">
                    <h4>Menu</h4>
                    <ul>
                        <li><a href="<?= SITE_URL ?>/courts">Court Reservation</a></li>
                        <li><a href="<?= SITE_URL ?>/index#about">About Us</a></li>
                        <li><a href="<?= SITE_URL ?>/guidebook">Guidebook</a></li>
                        <li><a href="<?= SITE_URL ?>/reports">Contact Us</a></li>
                    </ul>
                </div>
                
                <div class="footer-links">
                    <h4>Contact Us</h4>
                    <ul>
                        <li><a href="tel:111-222-3"><svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72"></path></svg> 111-222-3</a></li>
                        <li><a href="mailto:peoplecmucamt@gmail.com"><svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"></path><polyline points="22,6 12,13 2,6"></polyline></svg> peoplecmucamt@gmail.com</a></li>
                    </ul>
                </div>
            </div>
            <div class="footer-bottom">
                <p>HIT THE COURT</p>
            </div>
        </div>
    </footer>

</html>