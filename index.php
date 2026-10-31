<?php
// index.php
// หน้าแรก: แนะนำสโมสร, กีฬาทั้ง 7 ประเภท, บริการ, เวลาเปิด-ปิด / สิ่งอำนวยความสะดวก และสิทธิสมาชิก
require_once 'config.php';
require_once 'includes/court_svg.php';

$sports = $pdo->query("SELECT s.*, (SELECT COUNT(*) FROM courts c WHERE c.sport_id = s.sport_id) AS n_courts
                       FROM sports s WHERE s.status = 'active' ORDER BY s.sport_id")->fetchAll();
$totalCourts = array_sum(array_column($sports, 'n_courts'));

$pageTitle = '';
$pageStyles = ['home'];
include 'includes/head.php';
?>
<main class="home">
    <!-- HERO -->
    <section class="hero" style="background-image: linear-gradient(100deg, rgba(15,23,42,.92) 0%, rgba(15,23,42,.7) 45%, rgba(15,23,42,.25) 100%), url('<?= SITE_URL ?>/images/hero.png');">
        <div class="wrap hero-inner">
            <span class="badge badge-dark hero-badge"><?= icon('map-pin', 12) ?> <?= e('home.location') ?></span>
            <h1 class="hero-title">HIT THE <span>COURT</span></h1>
            <p class="hero-sub"><?= e('home.tagline') ?></p>
            <div class="row">
                <a href="<?= SITE_URL ?>/courts" class="btn btn-primary btn-lg"><?= icon('calendar', 18) ?> <?= e('home.cta_book') ?></a>
                <a href="<?= SITE_URL ?>/membership" class="btn btn-lg hero-ghost"><?= icon('crown', 18) ?> <?= e('home.cta_member') ?></a>
            </div>
            <ul class="hero-stats">
                <li><strong><?= count($sports) ?></strong><span><?= e('home.stat_sports') ?></span></li>
                <li><strong><?= $totalCourts ?></strong><span><?= e('home.stat_courts') ?></span></li>
                <li><strong>7</strong><span><?= e('home.stat_days') ?></span></li>
            </ul>
        </div>
    </section>

    <!-- SPORTS -->
    <section class="section">
        <div class="wrap">
            <div class="row-between mb-2">
                <div>
                    <span class="eyebrow"><?= icon('grid', 14) ?> <?= e('nav.courts') ?></span>
                    <h2 class="mb-0"><?= e('home.sports_title') ?></h2>
                </div>
                <a href="<?= SITE_URL ?>/courts" class="btn btn-outline"><?= e('home.see_all') ?> <?= icon('arrow-right', 16) ?></a>
            </div>
            <div class="sport-tiles">
                <?php foreach ($sports as $s): ?>
                <a class="sport-tile surface-<?= htmlspecialchars($s['slug']) ?>" href="<?= SITE_URL ?>/booking?sport_id=<?= $s['sport_id'] ?>">
                    <?= courtSvg($s['slug']) ?>
                    <span class="tile-name"><?= htmlspecialchars(localized($s, 'sport_name', 'name_th')) ?></span>
                    <span class="tile-meta"><?= formatPrice($s['price']) ?> / <?= e('courts.round') ?> · <?= e($s['slug'] === 'table-tennis' ? 'booking.tables_n' : 'booking.courts_n', ['n' => $s['n_courts']]) ?></span>
                </a>
                <?php endforeach; ?>
            </div>
        </div>
    </section>

    <!-- ABOUT -->
    <section class="section section-alt" id="about">
        <div class="wrap about">
            <img src="<?= SITE_URL ?>/images/logo.png" alt="Hit The Court Sport Club" class="about-logo" width="200" height="200" loading="lazy">
            <div>
                <span class="eyebrow"><?= icon('info', 14) ?> <?= e('home.about') ?></span>
                <h2><?= e('home.about_title') ?></h2>
                <p><?= e('home.about_1') ?></p>
                <blockquote class="about-quote">“Play hard. Pay less. Hit the Court”</blockquote>
                <p class="mb-0"><?= e('home.about_2') ?></p>
            </div>
        </div>
    </section>

    <!-- SERVICES -->
    <section class="section">
        <div class="wrap">
            <div class="center mb-3">
                <span class="eyebrow"><?= icon('star', 14) ?> <?= e('home.services') ?></span>
                <h2><?= e('home.services_title') ?></h2>
            </div>
            <div class="grid grid-3">
                <?php foreach ([['grid', 'svc1'], ['gift', 'svc2'], ['box', 'svc3']] as [$ico, $k]): ?>
                <article class="card card-pad service">
                    <span class="service-ico"><?= icon($ico, 22) ?></span>
                    <h3><?= e('home.' . $k . '_title') ?></h3>
                    <p class="muted mb-0"><?= e('home.' . $k . '_body') ?></p>
                </article>
                <?php endforeach; ?>
            </div>
        </div>
    </section>

    <!-- HOURS & FACILITIES -->
    <section class="section section-alt">
        <div class="wrap grid grid-2">
            <article class="card card-pad">
                <h3 class="row"><?= icon('clock', 20) ?> <?= e('home.hours') ?></h3>
                <dl class="kv">
                    <div class="kv-row"><dt><?= e('home.weekdays') ?></dt><dd>09:00 – 22:00</dd></div>
                    <div class="kv-row"><dt><?= e('home.weekends') ?></dt><dd>09:00 – 22:00</dd></div>
                    <div class="kv-row"><dt><?= e('home.holidays') ?></dt><dd>09:00 – 22:00</dd></div>
                </dl>
            </article>
            <article class="card card-pad">
                <h3 class="row"><?= icon('shield', 20) ?> <?= e('home.facilities') ?></h3>
                <ul class="check-list">
                    <li><?= icon('check', 16) ?> <?= e('home.fac_courts', ['n' => $totalCourts]) ?></li>
                    <li><?= icon('check', 16) ?> <?= e('home.fac_restrooms') ?></li>
                    <li><?= icon('check', 16) ?> <?= e('home.fac_parking') ?></li>
                    <li><?= icon('check', 16) ?> <?= e('home.fac_cctv') ?></li>
                    <li><?= icon('check', 16) ?> <?= e('home.fac_aed') ?></li>
                </ul>
            </article>
        </div>
    </section>

    <!-- MEMBERSHIP CTA -->
    <section class="section">
        <div class="wrap">
            <div class="member-cta">
                <div>
                    <span class="badge badge-gold"><?= icon('crown', 12) ?> <?= e('nav.membership') ?></span>
                    <h2><?= e('home.member_title') ?></h2>
                    <ul class="check-list light">
                        <li><?= icon('check', 16) ?> <?= e('member.perk_advance') ?></li>
                        <li><?= icon('check', 16) ?> <?= e('member.perk_free_equipment') ?></li>
                        <li><?= icon('check', 16) ?> <?= e('home.member_deals') ?></li>
                        <li><?= icon('check', 16) ?> <?= e('member.perk_points_body') ?></li>
                    </ul>
                </div>
                <div class="member-cta-price">
                    <div class="price">499 <span><?= e('currency') ?> / <?= e('member.months', ['n' => 3]) ?></span></div>
                    <a href="<?= SITE_URL ?>/membership" class="btn btn-primary btn-lg btn-block"><?= e('courts.promo_cta') ?></a>
                </div>
            </div>
        </div>
    </section>
</main>
<?php include 'includes/footer.php'; ?>
