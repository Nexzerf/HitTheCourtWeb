<?php
// pages/courts.php
// หน้ารวมกีฬาทั้งหมด: ชื่อกีฬา, จำนวนสนาม, เวลาต่อรอบ, ราคาต่อรอบ, รายละเอียดรอบเวลาและอุปกรณ์ และปุ่มจอง
require_once '../config.php';
require_once '../includes/court_svg.php';

$sports = $pdo->query("SELECT * FROM sports WHERE status = 'active' ORDER BY sport_id")->fetchAll();
$courtCounts = $pdo->query("SELECT sport_id, COUNT(*) FROM courts GROUP BY sport_id")->fetchAll(PDO::FETCH_KEY_PAIR);
$slotsBySport = [];
foreach ($pdo->query("SELECT * FROM time_slots WHERE status = 'active' ORDER BY start_time") as $s) $slotsBySport[$s['sport_id']][] = $s;
$equipBySport = [];
foreach ($pdo->query("SELECT * FROM equipment WHERE status = 'available' ORDER BY eq_id") as $eq) $equipBySport[$eq['sport_id']][] = $eq;

// รูปประกอบของแต่ละกีฬา (ถ้าโหลดไม่ได้จะแสดงภาพสนามแทน)
$sportImages = [
    'badminton'    => 'https://images.unsplash.com/photo-1626224583764-f87db24ac4ea?auto=format&fit=crop&w=800&q=70',
    'football'     => 'https://images.unsplash.com/photo-1574629810360-7efbbe195018?auto=format&fit=crop&w=800&q=70',
    'tennis'       => 'https://images.unsplash.com/photo-1554068865-24cecd4e34b8?auto=format&fit=crop&w=800&q=70',
    'volleyball'   => 'https://images.unsplash.com/photo-1547347298-4074fc3086f0?auto=format&fit=crop&w=800&q=70',
    'basketball'   => 'https://images.unsplash.com/photo-1546519638-68e109498ffc?auto=format&fit=crop&w=800&q=70',
    'table-tennis' => 'https://images.unsplash.com/photo-1534158914592-062992fbe900?auto=format&fit=crop&w=800&q=70',
    'futsal'       => 'https://images.unsplash.com/photo-1517466787929-bc90951d0974?auto=format&fit=crop&w=800&q=70',
];

function durationText(int $minutes): string {
    $h = intdiv($minutes, 60); $m = $minutes % 60;
    return trim(($h ? t('unit.hours', ['n' => $h]) : '') . ' ' . ($m ? t('unit.minutes', ['n' => $m]) : ''));
}

$pageTitle = t('courts.title');
$pageStyles = ['courts'];
include '../includes/head.php';
?>
<main class="page">
    <div class="wrap">
        <header class="page-head center">
            <span class="eyebrow"><?= icon('grid', 14) ?> <?= e('courts.eyebrow') ?></span>
            <h1><?= e('courts.title') ?></h1>
            <p><?= e('courts.intro') ?></p>
        </header>

        <div class="sport-grid">
            <?php foreach ($sports as $sport):
                $name = localized($sport, 'sport_name', 'name_th');
                $nCourts = (int) ($courtCounts[$sport['sport_id']] ?? 0);
                $isTable = $sport['slug'] === 'table-tennis'; ?>
            <article class="card sport-card">
                <div class="sport-media">
                    <div class="sport-fallback surface-<?= htmlspecialchars($sport['slug']) ?>"><?= courtSvg($sport['slug']) ?></div>
                    <?php if (!empty($sportImages[$sport['slug']])): ?>
                        <img src="<?= $sportImages[$sport['slug']] ?>" alt="" loading="lazy" onerror="this.remove()">
                    <?php endif; ?>
                    <span class="sport-price"><strong><?= formatPrice($sport['price']) ?></strong> / <?= e('courts.round') ?></span>
                </div>
                <div class="card-body">
                    <h2 class="sport-name"><?= htmlspecialchars($name) ?></h2>
                    <ul class="sport-stats">
                        <li><?= icon('grid', 16) ?> <?= e($isTable ? 'booking.tables_n' : 'booking.courts_n', ['n' => $nCourts]) ?></li>
                        <li><?= icon('clock', 16) ?> <?= htmlspecialchars(t('booking.per_round', ['duration' => durationText((int) $sport['duration_minutes'])])) ?></li>
                        <li><?= icon('calendar', 16) ?> <?= e('courts.rounds_per_day', ['n' => count($slotsBySport[$sport['sport_id']] ?? [])]) ?></li>
                    </ul>

                    <details class="sport-details">
                        <summary><?= icon('chevron-down', 16) ?> <?= e('courts.view_details') ?></summary>
                        <h4><?= e('courts.time_rounds') ?></h4>
                        <ol class="round-list">
                            <?php foreach ($slotsBySport[$sport['sport_id']] ?? [] as $i => $s): ?>
                                <li><span><?= e('booking.round', ['n' => $i + 1]) ?></span> <?= formatTime($s['start_time']) ?>–<?= formatTime($s['end_time']) ?></li>
                            <?php endforeach; ?>
                        </ol>
                        <h4><?= e('booking.step_equipment') ?></h4>
                        <?php if (empty($equipBySport[$sport['sport_id']])): ?>
                            <p class="muted small mb-0"><?= e('booking.no_equipment') ?></p>
                        <?php else: ?>
                        <ul class="equip-mini">
                            <?php foreach ($equipBySport[$sport['sport_id']] as $eq): ?>
                            <li>
                                <span><?= htmlspecialchars(localized($eq, 'eq_name', 'name_th')) ?>
                                    <?php if ($note = localized($eq, 'unit_note', 'unit_note_th')): ?><small class="muted d-block"><?= htmlspecialchars($note) ?></small><?php endif; ?>
                                </span>
                                <span class="text-danger strong" title="<?= e('booking.fine', ['amount' => formatPrice($eq['fine_amount'])]) ?>"><?= formatPrice($eq['price']) ?></span>
                            </li>
                            <?php endforeach; ?>
                        </ul>
                        <p class="hint mt-1 mb-0"><?= icon('alert', 12) ?> <?= e('courts.red_note') ?></p>
                        <?php endif; ?>
                    </details>

                    <a href="<?= SITE_URL ?>/booking?sport_id=<?= $sport['sport_id'] ?>" class="btn btn-dark btn-block mt-2"><?= e('courts.book_now') ?> <?= icon('arrow-right', 18) ?></a>
                </div>
            </article>
            <?php endforeach; ?>
        </div>

        <section class="card card-pad promo mt-4">
            <div>
                <span class="badge badge-gold"><?= icon('crown', 12) ?> <?= e('nav.membership') ?></span>
                <h2 class="mt-1"><?= e('courts.promo_title') ?></h2>
                <p class="muted mb-0"><?= e('courts.promo_body') ?></p>
            </div>
            <a href="<?= SITE_URL ?>/membership" class="btn btn-primary"><?= e('courts.promo_cta') ?></a>
        </section>
    </div>
</main>
<?php include '../includes/footer.php'; ?>
