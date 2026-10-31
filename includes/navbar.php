<?php
// includes/navbar.php
// แถบเมนูด้านบน (Navbar) ที่ใช้ร่วมกันทุกหน้า แก้ที่ไฟล์นี้ที่เดียว ทุกหน้าจะเปลี่ยนตาม
// หน้าตาอยู่ใน assets/css/navbar.css (โหลดใน includes/head.php) และการทำงาน (เปิด/ปิดเมนูมือถือ) อยู่ใน assets/js/navbar.js

// หาว่าตอนนี้อยู่หน้าไหน เพื่อทำไฮไลต์เมนูที่กำลังเปิดอยู่
$currentPage = basename(parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH) ?: 'index', '.php');
$navLinks = [
    'courts'       => t('nav.courts'),
    'reservations' => t('nav.reservations'),
    'reports'      => t('nav.contact'),
    'guidebook'    => t('nav.guidebook'),
];
// หน้าที่เป็นขั้นตอนต่อจากเมนูหลัก ให้ไฮไลต์เมนูต้นทาง
$activeAlias = ['booking' => 'courts', 'pay_booking' => 'reservations', 'payment_success' => 'reservations'];
$activeNav = $activeAlias[$currentPage] ?? $currentPage;
?>

<nav class="site-nav" id="navbar" aria-label="Main">
    <div class="sn-container">
        <a href="<?= SITE_URL ?>/" class="sn-logo">HIT THE <span>COURT</span></a>

        <!-- เมนูหลัก (PC แสดงตรงกลาง / มือถือกลายเป็นแผงที่เลื่อนลงมา) -->
        <div class="sn-panel" id="sn-panel">
            <ul class="sn-links">
                <?php foreach ($navLinks as $slug => $label): ?>
                <li><a href="<?= SITE_URL ?>/<?= $slug ?>" class="sn-link<?= $activeNav === $slug ? ' is-active' : '' ?>"<?= $activeNav === $slug ? ' aria-current="page"' : '' ?>><?= htmlspecialchars($label) ?></a></li>
                <?php endforeach; ?>
            </ul>

            <!-- ส่วนบัญชีผู้ใช้ในแผงเมนูมือถือ -->
            <div class="sn-panel-account">
                <?php if (isLoggedIn()): ?>
                    <a href="<?= SITE_URL ?>/reservations" class="sn-link"><?= e('nav.my_bookings') ?></a>
                    <a href="<?= SITE_URL ?>/profile" class="sn-link<?= $activeNav === 'profile' ? ' is-active' : '' ?>"><?= e('nav.profile') ?></a>
                    <a href="<?= SITE_URL ?>/membership" class="sn-link<?= $activeNav === 'membership' ? ' is-active' : '' ?>"><?= e('nav.membership') ?></a>
                    <a href="<?= SITE_URL ?>/api/auth.php?action=logout&amp;t=<?= csrfToken() ?>" class="sn-link sn-danger"><?= e('nav.logout') ?></a>
                <?php else: ?>
                    <a href="<?= SITE_URL ?>/login" class="sn-btn sn-btn-outline"><?= e('nav.login') ?></a>
                    <a href="<?= SITE_URL ?>/register" class="sn-btn sn-btn-primary"><?= e('nav.signup') ?></a>
                <?php endif; ?>
            </div>
        </div>

        <div class="sn-actions">
            <?php if (isLoggedIn()): ?>
                <div class="sn-user" id="sn-user">
                    <button type="button" class="sn-user-btn" aria-haspopup="true" aria-expanded="false">
                        <span class="sn-avatar"><?= htmlspecialchars(strtoupper(mb_substr($_SESSION['username'], 0, 1))) ?></span>
                        <span class="sn-username"><?= htmlspecialchars($_SESSION['username']) ?></span>
                        <svg class="sn-caret" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="6 9 12 15 18 9"></polyline></svg>
                    </button>
                    <div class="sn-dropdown" role="menu">
                        <a href="<?= SITE_URL ?>/reservations" role="menuitem"><?= e('nav.my_bookings') ?></a>
                        <a href="<?= SITE_URL ?>/profile" role="menuitem"><?= e('nav.profile') ?></a>
                        <a href="<?= SITE_URL ?>/membership" role="menuitem"><?= e('nav.membership') ?></a>
                        <a href="<?= SITE_URL ?>/api/auth.php?action=logout&amp;t=<?= csrfToken() ?>" role="menuitem" class="sn-danger"><?= e('nav.logout') ?></a>
                    </div>
                </div>
            <?php else: ?>
                <a href="<?= SITE_URL ?>/login" class="sn-btn sn-btn-ghost sn-desktop-only"><?= e('nav.login') ?></a>
                <a href="<?= SITE_URL ?>/register" class="sn-btn sn-btn-primary sn-desktop-only"><?= e('nav.signup') ?></a>
            <?php endif; ?>

            <!-- ปุ่มสลับภาษา -->
            <div class="sn-lang" role="group" aria-label="Language">
                <a href="<?= htmlspecialchars(langSwitchUrl('th')) ?>" class="<?= LANG === 'th' ? 'is-active' : '' ?>" lang="th" hreflang="th">TH</a>
                <a href="<?= htmlspecialchars(langSwitchUrl('en')) ?>" class="<?= LANG === 'en' ? 'is-active' : '' ?>" lang="en" hreflang="en">EN</a>
            </div>

            <!-- ปุ่มเมนูสำหรับมือถือ -->
            <button type="button" class="sn-toggle" aria-label="<?= e('nav.open_menu') ?>" aria-controls="sn-panel" aria-expanded="false">
                <span></span><span></span><span></span>
            </button>
        </div>
    </div>
</nav>
<div class="sn-backdrop" id="sn-backdrop"></div>
<script src="<?= SITE_URL ?>/assets/js/navbar.js" defer></script>
