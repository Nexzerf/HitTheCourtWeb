<?php
// includes/admin_layout_top.php
// โครงหน้าแอดมิน: แถบเมนูด้านข้าง (มือถือเป็นเมนูเลื่อนลง) + หัวหน้า ใช้คู่กับ admin_layout_bottom.php
// ก่อน include ให้ตั้ง $adminPage (คีย์ของเมนูที่เปิดอยู่) และ $pageTitle
$adminMenu = [
    'dashboard' => ['chart', 'admin.nav_dashboard', '/admin/dashboard.php'],
    'bookings'  => ['calendar', 'admin.nav_bookings', '/admin/bookings.php'],
    'sports'    => ['grid', 'admin.nav_sports', '/admin/sports.php'],
    'reports'   => ['message', 'admin.nav_reports', '/admin/reports.php'],
    'members'   => ['users', 'admin.nav_members', '/admin/members.php'],
    'analytics' => ['zap', 'admin.nav_analytics', '/admin/analytics.php'],
];
$newReports = (int) $pdo->query("SELECT COUNT(*) FROM reports WHERE status = 'new'")->fetchColumn();
?>
<!DOCTYPE html>
<html lang="<?= LANG ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex">
    <title><?= htmlspecialchars($pageTitle) ?> · Admin · Hit The Court</title>
    <link rel="icon" type="image/png" sizes="32x32" href="<?= SITE_URL ?>/images/favicon-32x32.png">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Space+Grotesk:wght@500;600;700&family=IBM+Plex+Sans+Thai:wght@400;500;600;700&display=swap">
    <link rel="stylesheet" href="<?= SITE_URL ?>/assets/css/app.css">
    <link rel="stylesheet" href="<?= SITE_URL ?>/assets/css/admin.css">
</head>
<body class="app admin lang-<?= LANG ?>">
<div class="admin-shell">
    <aside class="admin-side" id="adminSide">
        <a href="<?= SITE_URL ?>/admin/dashboard.php" class="admin-logo">HIT THE <span>COURT</span><small>Admin</small></a>
        <nav class="admin-nav">
            <?php foreach ($adminMenu as $key => [$ico, $label, $url]): ?>
                <a href="<?= SITE_URL . $url ?>" class="<?= $adminPage === $key ? 'is-active' : '' ?>" <?= $adminPage === $key ? 'aria-current="page"' : '' ?>>
                    <?= icon($ico, 18) ?> <span><?= e($label) ?></span>
                    <?php if ($key === 'reports' && $newReports): ?><span class="badge badge-danger"><?= $newReports ?></span><?php endif; ?>
                </a>
            <?php endforeach; ?>
        </nav>
        <div class="admin-side-foot">
            <a href="<?= SITE_URL ?>/" target="_blank" rel="noopener"><?= icon('home', 16) ?> <?= e('admin.view_site') ?></a>
            <a href="<?= SITE_URL ?>/api/auth.php?action=admin_logout"><?= icon('logout', 16) ?> <?= e('nav.logout') ?></a>
        </div>
    </aside>
    <div class="admin-backdrop" id="adminBackdrop"></div>

    <div class="admin-main">
        <header class="admin-top">
            <button type="button" class="admin-burger" id="adminBurger" aria-label="<?= e('nav.open_menu') ?>" aria-controls="adminSide" aria-expanded="false"><?= icon('list', 22) ?></button>
            <h1 class="admin-title"><?= htmlspecialchars($pageTitle) ?></h1>
            <div class="admin-top-right">
                <div class="auth-lang">
                    <a href="<?= htmlspecialchars(langSwitchUrl('th')) ?>" class="<?= LANG === 'th' ? 'is-active' : '' ?>">TH</a>
                    <a href="<?= htmlspecialchars(langSwitchUrl('en')) ?>" class="<?= LANG === 'en' ? 'is-active' : '' ?>">EN</a>
                </div>
                <span class="admin-user"><?= icon('shield', 16) ?> <?= htmlspecialchars($_SESSION['admin_username'] ?? 'admin') ?></span>
            </div>
        </header>
        <main class="admin-content">
            <?php if (!empty($_SESSION['flash_success'])): ?>
                <div class="alert alert-success mb-2" role="status"><?= icon('check-circle') ?><div><?= htmlspecialchars($_SESSION['flash_success']) ?></div></div>
            <?php unset($_SESSION['flash_success']); endif; ?>
            <?php if (!empty($_SESSION['flash_error'])): ?>
                <div class="alert alert-danger mb-2" role="alert"><?= icon('alert') ?><div><?= htmlspecialchars(implode(' ', (array) $_SESSION['flash_error'])) ?></div></div>
            <?php unset($_SESSION['flash_error']); endif; ?>
