<?php
// includes/auth_layout_top.php
// โครงหน้าล็อกอิน / สมัครสมาชิก (ฟอร์มด้านซ้าย ภาพแบรนด์ด้านขวา) ใช้คู่กับ auth_layout_bottom.php
// ก่อน include ให้ตั้ง $authTitle และ $authSubtitle
?>
<!DOCTYPE html>
<html lang="<?= LANG ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <meta name="theme-color" content="#0F172A">
    <title><?= htmlspecialchars($authTitle) ?> · Hit The Court</title>
    <link rel="icon" type="image/png" sizes="32x32" href="<?= SITE_URL ?>/images/favicon-32x32.png">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Space+Grotesk:wght@500;600;700&family=IBM+Plex+Sans+Thai:wght@400;500;600;700&display=swap">
    <link rel="stylesheet" href="<?= SITE_URL ?>/assets/css/app.css">
    <link rel="stylesheet" href="<?= SITE_URL ?>/assets/css/auth.css">
</head>
<body class="app auth-page lang-<?= LANG ?>">
<div class="auth-shell">
    <section class="auth-form-side">
        <div class="auth-topbar">
            <a href="<?= SITE_URL ?>/" class="auth-logo">HIT THE <span>COURT</span></a>
            <div class="auth-lang">
                <a href="<?= htmlspecialchars(langSwitchUrl('th')) ?>" class="<?= LANG === 'th' ? 'is-active' : '' ?>">TH</a>
                <a href="<?= htmlspecialchars(langSwitchUrl('en')) ?>" class="<?= LANG === 'en' ? 'is-active' : '' ?>">EN</a>
            </div>
        </div>
        <div class="auth-box">
            <h1><?= htmlspecialchars($authTitle) ?></h1>
            <p class="muted mb-3"><?= htmlspecialchars($authSubtitle) ?></p>
