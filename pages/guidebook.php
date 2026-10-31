<?php
// pages/guidebook.php
// คู่มือการใช้งานเว็บไซต์: การจอง, การแก้ไขการจอง, การยกเลิกการจอง และการรายงานปัญหา
require_once '../config.php';

// แต่ละหัวข้อ: [ไอคอน, key หัวข้อ, จำนวนขั้นตอน, [key หมายเหตุ...]]
$sections = [
    'booking' => ['calendar', 5, ['guide.booking_note1', 'guide.booking_note2']],
    'edit'    => ['edit', 3, ['guide.edit_note']],
    'cancel'  => ['x-circle', 4, []],
    'report'  => ['flag', 4, ['guide.report_note']],
];

$pageTitle = t('nav.guidebook');
include '../includes/head.php';
?>
<main class="page">
    <div class="wrap wrap-narrow">
        <header class="page-head center">
            <span class="eyebrow"><?= icon('book', 14) ?> <?= e('nav.guidebook') ?></span>
            <h1><?= e('guide.title') ?></h1>
            <p><?= e('guide.intro') ?></p>
        </header>

        <nav class="guide-toc card card-pad mb-3" aria-label="<?= e('guide.contents') ?>">
            <?php foreach ($sections as $key => [$ico]): ?>
                <a href="#<?= $key ?>"><?= icon($ico, 16) ?> <?= e('guide.' . $key . '_title') ?></a>
            <?php endforeach; ?>
        </nav>

        <?php foreach ($sections as $key => [$ico, $steps, $notes]): ?>
        <section class="card mb-3" id="<?= $key ?>">
            <div class="card-head"><h2><?= icon($ico, 20) ?> <?= e('guide.' . $key . '_title') ?></h2></div>
            <div class="card-body">
                <?php if (t('guide.' . $key . '_desc') !== 'guide.' . $key . '_desc'): ?><p class="muted"><?= e('guide.' . $key . '_desc') ?></p><?php endif; ?>
                <ol class="steps">
                    <?php for ($i = 1; $i <= $steps; $i++): ?>
                        <li><?= e('guide.' . $key . '_' . $i) ?></li>
                    <?php endfor; ?>
                </ol>
                <?php foreach ($notes as $note): ?>
                    <p class="note"><?= icon('alert', 16) ?> <?= e($note) ?></p>
                <?php endforeach; ?>
            </div>
        </section>
        <?php endforeach; ?>

        <div class="center mt-4">
            <a href="<?= SITE_URL ?>/courts" class="btn btn-primary btn-lg"><?= icon('calendar', 18) ?> <?= e('courts.book_now') ?></a>
        </div>
    </div>
</main>
<style>
.guide-toc { display: flex; flex-wrap: wrap; gap: 8px; }
.guide-toc a { display: inline-flex; align-items: center; gap: 6px; padding: 8px 14px; border-radius: 999px; background: #F1F5F9; color: var(--c-ink); font-size: .9rem; font-weight: 600; text-decoration: none; }
.guide-toc a:hover { background: var(--c-primary-soft); color: var(--c-primary); }
section[id] { scroll-margin-top: calc(var(--nav-h) + 16px); }
</style>
<?php include '../includes/footer.php'; ?>
