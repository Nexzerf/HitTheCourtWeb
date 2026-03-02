<?php
require_once '../config.php';
requireLogin();

// ดึงเฉพาะ booking ที่จ่ายเงินแล้ว (paid) เท่านั้น
$stmt = $pdo->prepare("
    SELECT b.*, s.sport_name, c.court_number, ts.start_time, ts.end_time
    FROM bookings b
    JOIN courts c ON b.court_id = c.court_id
    JOIN sports s ON c.sport_id = s.sport_id
    JOIN time_slots ts ON b.slot_id = ts.slot_id
    WHERE b.user_id = ? AND b.payment_status = 'paid'
    ORDER BY b.booking_date DESC, ts.start_time DESC
");
$stmt->execute([$_SESSION['user_id']]);
$bookings = $stmt->fetchAll();

$upcoming = [];
$past     = [];

foreach ($bookings as $booking) {
    $bookingDateTime = strtotime($booking['booking_date'] . ' ' . $booking['start_time']);
    if ($bookingDateTime >= strtotime('today')) {
        $upcoming[] = $booking;
    } else {
        $past[] = $booking;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Reservations - Hit The Court</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&family=Space+Grotesk:wght@500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../style.css">
    <link rel="stylesheet" href="<?= SITE_URL ?>/assets/css/home.css">
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
                <span class="bar"></span><span class="bar"></span><span class="bar"></span>
            </div>
        </button>
        <ul class="nav-menu">
            <li class="nav-item"><a href="/courts"       class="nav-link">Courts</a></li>
            <li class="nav-item"><a href="/reservations" class="nav-link">Reservations</a></li>
            <li class="nav-item"><a href="/reports"      class="nav-link">Contact Us</a></li>
            <li class="nav-item"><a href="/guidebook"    class="nav-link">Guidebook</a></li>
        </ul>
        <div class="nav-auth">
            <?php if (isLoggedIn()): ?>
            <div class="user-menu">
                <button class="user-btn">
                    <div class="user-avatar"><?= strtoupper(substr($_SESSION['username'], 0, 1)) ?></div>
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
            <a href="/login"    class="btn btn-ghost">Login</a>
            <a href="/register" class="btn btn-primary">Sign Up</a>
            <?php endif; ?>
        </div>
    </div>
</nav>

<!-- MAIN -->
<main class="section" style="padding-top:7rem;">
    <div class="container">

        <div class="section-header" style="text-align:left; margin-bottom:2rem;">
            <h1>My Reservations</h1>
            <p class="text-muted">Track your bookings and history</p>
        </div>

        <!-- Tabs -->
        <div class="reservations-tabs" data-tabs>
            <button class="reservation-tab active" data-tab="upcoming">Upcoming (<?= count($upcoming) ?>)</button>
            <button class="reservation-tab"        data-tab="history" >History  (<?= count($past)     ?>)</button>
        </div>

        <!-- UPCOMING -->
        <div class="reservations-grid" data-panel="upcoming">
            <?php if (empty($upcoming)): ?>
            <div class="card">
                <div class="card-body text-center p-5">
                    <svg width="64" height="64" viewBox="0 0 24 24" fill="none" stroke="var(--gray-400)" stroke-width="1.5" class="mb-3">
                        <rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect>
                        <line x1="16" y1="2" x2="16" y2="6"></line>
                        <line x1="8"  y1="2" x2="8"  y2="6"></line>
                        <line x1="3"  y1="10" x2="21" y2="10"></line>
                    </svg>
                    <h3 class="mb-2">No Upcoming Bookings</h3>
                    <p class="text-muted mb-4">Ready to play? Book a court now!</p>
                    <a href="<?= SITE_URL ?>/courts" class="btn btn-primary">Book a Court</a>
                </div>
            </div>
            <?php else: ?>
                <?php foreach ($upcoming as $booking): ?>
                <div class="reservation-card">
                    <div class="reservation-card-header">
                        <div>
                            <div class="reservation-card-id"><?= htmlspecialchars($booking['booking_code']) ?></div>
                            <div class="text-muted" style="font-size:0.875rem;">
                                Booked on <?= date('d M Y', strtotime($booking['created_at'])) ?>
                            </div>
                        </div>
                        <span class="reservation-card-status status-paid">
                            Confirmed
                        </span>
                    </div>
                    <div class="reservation-card-body">
                        <div class="reservation-details">
                            <div class="reservation-detail">
                                <div class="reservation-detail-label">Sport</div>
                                <div class="reservation-detail-value"><?= htmlspecialchars($booking['sport_name']) ?></div>
                            </div>
                            <div class="reservation-detail">
                                <div class="reservation-detail-label">Court</div>
                                <div class="reservation-detail-value">Court <?= $booking['court_number'] ?></div>
                            </div>
                            <div class="reservation-detail">
                                <div class="reservation-detail-label">Date</div>
                                <div class="reservation-detail-value"><?= date('d M Y', strtotime($booking['booking_date'])) ?></div>
                            </div>
                            <div class="reservation-detail">
                                <div class="reservation-detail-label">Time</div>
                                <div class="reservation-detail-value">
                                    <?= date('g:i A', strtotime($booking['start_time'])) ?> –
                                    <?= date('g:i A', strtotime($booking['end_time']))   ?>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="reservation-card-footer">
                        <span style="font-family:var(--font-display); font-weight:600; font-size:1.125rem;">
                            <?= number_format($booking['total_price']) ?> THB
                        </span>
                        <span style="color:var(--success,#28a745); font-weight:600; font-size:0.85rem;">
                            ✅ Payment Confirmed
                        </span>
                    </div>
                </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <!-- HISTORY -->
        <div class="reservations-grid" data-panel="history" style="display:none;">
            <?php if (empty($past)): ?>
            <div class="card">
                <div class="card-body text-center p-5">
                    <p class="text-muted">No past bookings found.</p>
                </div>
            </div>
            <?php else: ?>
                <?php foreach ($past as $booking): ?>
                <div class="reservation-card">
                    <div class="reservation-card-header">
                        <div>
                            <div class="reservation-card-id"><?= htmlspecialchars($booking['booking_code']) ?></div>
                            <div class="text-muted" style="font-size:0.875rem;">
                                <?= date('d M Y', strtotime($booking['booking_date'])) ?>
                            </div>
                        </div>
                        <span class="reservation-card-status status-completed">
                            Completed
                        </span>
                    </div>
                    <div class="reservation-card-body">
                        <div class="reservation-details">
                            <div class="reservation-detail">
                                <div class="reservation-detail-label">Sport</div>
                                <div class="reservation-detail-value"><?= htmlspecialchars($booking['sport_name']) ?></div>
                            </div>
                            <div class="reservation-detail">
                                <div class="reservation-detail-label">Court</div>
                                <div class="reservation-detail-value">Court <?= $booking['court_number'] ?></div>
                            </div>
                            <div class="reservation-detail">
                                <div class="reservation-detail-label">Total</div>
                                <div class="reservation-detail-value"><?= number_format($booking['total_price']) ?> THB</div>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

    </div>
</main>

<!-- FOOTER -->
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
                    <li><a href="tel:111-222-3">111-222-3</a></li>
                    <li><a href="mailto:peoplecmucamt@gmail.com">peoplecmucamt@gmail.com</a></li>
                </ul>
            </div>
        </div>
        <div class="footer-bottom"><p>HIT THE COURT</p></div>
    </div>
</footer>

<!-- JAVASCRIPT -->
<script>
document.addEventListener('DOMContentLoaded', function () {

    // Navbar
    const toggleBtn = document.querySelector('.mobile-toggle');
    const navbar    = document.getElementById('navbar');
    const userMenu  = document.querySelector('.user-menu');
    const body      = document.body;

    if (toggleBtn && navbar) {
        toggleBtn.addEventListener('click', function (e) {
            e.stopPropagation();
            navbar.classList.toggle('menu-open');
            body.style.overflow = navbar.classList.contains('menu-open') ? 'hidden' : '';
        });
    }
    if (userMenu) {
        userMenu.querySelector('.user-btn').addEventListener('click', function (e) {
            if (window.innerWidth <= 768) { e.stopPropagation(); userMenu.classList.toggle('active'); }
        });
    }
    document.addEventListener('click', function (e) {
        if (navbar?.classList.contains('menu-open') && !navbar.contains(e.target)) {
            navbar.classList.remove('menu-open'); body.style.overflow = '';
        }
        if (userMenu?.classList.contains('active') && !userMenu.contains(e.target)) {
            userMenu.classList.remove('active');
        }
    });

    // Tab Switching
    document.querySelectorAll('.reservation-tab').forEach(tab => {
        tab.addEventListener('click', function () {
            document.querySelectorAll('.reservation-tab').forEach(t => t.classList.remove('active'));
            this.classList.add('active');
            document.querySelectorAll('[data-panel]').forEach(p => p.style.display = 'none');
            document.querySelector(`[data-panel="${this.dataset.tab}"]`).style.display = 'grid';
        });
    });

});
</script>
</body>
</html>