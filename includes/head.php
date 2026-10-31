<?php
// includes/head.php
// ส่วน <head> ที่ใช้ร่วมกันของหน้าที่ใช้ดีไซน์ใหม่ (assets/css/app.css)
// ก่อน include ให้ตั้ง $pageTitle และ (ถ้ามี) $pageStyles = ['booking'] เพื่อโหลด assets/css/booking.css
define('APP_HEAD', true);
$pageStyles = $pageStyles ?? [];
?>
<!DOCTYPE html>
<html lang="<?= LANG ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <meta name="theme-color" content="#0F172A">
    <title><?= htmlspecialchars(($pageTitle ?? '') !== '' ? $pageTitle . ' · Hit The Court' : 'Hit The Court') ?></title>
    <link rel="icon" type="image/png" sizes="32x32" href="<?= SITE_URL ?>/images/favicon-32x32.png">
    <link rel="icon" type="image/png" sizes="48x48" href="<?= SITE_URL ?>/images/favicon-48x48.png">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Space+Grotesk:wght@500;600;700&family=IBM+Plex+Sans+Thai:wght@400;500;600;700&display=swap">
    <link rel="stylesheet" href="<?= SITE_URL ?>/assets/css/app.css">
    <link rel="stylesheet" href="<?= SITE_URL ?>/assets/css/navbar.css">
    <?php foreach ($pageStyles as $style): ?>
    <link rel="stylesheet" href="<?= SITE_URL ?>/assets/css/<?= htmlspecialchars($style) ?>.css">
    <?php endforeach; ?>
</head>
<body class="app lang-<?= LANG ?>">
<?php include __DIR__ . '/navbar.php'; ?>
