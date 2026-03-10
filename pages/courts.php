<?php
// เรียกไฟล์ config.php เข้ามาก่อน เพื่อเชื่อมฐานข้อมูลและใช้ฟังก์ชันต่างๆ ของระบบ
require_once '../config.php';

// Fetch all active sports
// ส่วนนี้คือไปดึงข้อมูลกีฬาทั้งหมดจากฐานข้อมูล มาเก็บไว้ในตัวแปร $sports
 $stmt = $pdo->query("SELECT * FROM sports ORDER BY sport_id ASC");
 $sports = $stmt->fetchAll();

// Image mapping for sports (fallback if no image in DB)
// สร้าง Array สำรองไว้สำหรับรูปภาพของแต่ละกีฬา
// ถ้าในฐานข้อมูลไม่มีรูป ก็จะดึงรูปพวกนี้จาก Unsplash มาแสดงแทน
 $sportImages = [
    'Badminton' => 'https://images.unsplash.com/photo-1626224583764-f87db24ac4ea?auto=format&fit=crop&w=800&q=80',
    'Football' => 'https://images.unsplash.com/photo-1574629810360-7efbbe195018?auto=format&fit=crop&w=800&q=80',
    'Tennis' => 'https://images.unsplash.com/photo-1554068865-24cecd4e34b8?auto=format&fit=crop&w=800&q=80',
    'Volleyball' => 'https://images.unsplash.com/photo-1547347298-4074fc3086f0?auto=format&fit=crop&w=800&q=80',
    'Basketball' => 'https://images.unsplash.com/photo-1546519638-68e109498ffc?auto=format&fit=crop&w=800&q=80',
    'Table Tennis' => 'https://images.unsplash.com/photo-1534158914592-062992fbe900?auto=format&fit=crop&w=800&q=80',
    'Futsal' => 'https://images.unsplash.com/photo-1517466787929-bc90951d0974?auto=format&fit=crop&w=800&q=80',
];

// Duration formatting helper
// ฟังก์ชันช่วยแปลง "นาที" เป็นข้อความที่อ่านง่าย เช่น 60 นาที -> 1 Hour, 90 นาที -> 1 Hour 30 Min
function formatDuration($minutes) {
    if ($minutes >= 60) {
        $hrs = floor($minutes / 60);
        $mins = $minutes % 60;
        return $hrs . ' Hour' . ($mins ? ' ' . $mins . ' Min' : '');
    }
    return $minutes . ' Min';
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Select Your Sport - Hit The Court</title>
    
    <!-- Fonts -->
    <!-- โหลดฟอนต์ Inter และ Space Grotesk จาก Google Fonts มาใช้ -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Space+Grotesk:wght@500;600;700&display=swap" rel="stylesheet">
    
    <!-- CSS Files -->
    <!-- โหลดไฟล์ CSS สำหรับตกแต่งหน้าเว็บ -->
    <link rel="stylesheet" href="<?= SITE_URL ?>/assets/css/home.css"> <!-- Reuse Navbar/Footer styles -->
    <link rel="stylesheet" href="<?= SITE_URL ?>/assets/css/courts.css"> <!-- Page specific styles -->
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


    <!-- PAGE HERO -->
    <!-- ส่วนหัวของหน้านี้ (Hero Section) -->
    <div class="main-content">
        <header class="page-hero">
            <div class="page-hero-content">
                <h1>SELECT YOUR SPORT COURT</h1>
                <p>Because every match matters. Start your journey here.</p>
            </div>
        </header>

        <!-- SPORTS GRID -->
        <!-- ส่วนแสดงการ์ดกีฬาทั้งหมด -->
        <section class="courts-section">
            <div class="courts-grid">
                <?php foreach ($sports as $sport): 
                    // Get image from mapping or default
                    // เลือกรูปภาพมาแสดง: ถ้า Map มีชื่อกีฬานี้ก็ใช้รูปจาก Map ถ้าไม่มีก็ใช้รูป Default
                    $img = $sportImages[$sport['sport_name']] ?? 'https://images.unsplash.com/photo-1461896836934- voices?auto=format&fit=crop&w=800&q=80';
                ?>
                    <div class="sport-card" id="sport-<?= $sport['sport_id'] ?>">
                        <!-- Badge Example (Optional logic) -->
                        <!-- ถ้าเป็นกีฬา Badminton หรือ Football ให้แสดงป้าย "Popular" -->
                        <?php if ($sport['sport_name'] == 'Badminton' || $sport['sport_name'] == 'Football'): ?>
                        <div class="sport-badge">Popular</div>
                        <?php endif; ?>

                        <!-- รูปภาพประกอบกีฬา -->
                        <div class="sport-card-image">
                            <img src="<?= $img ?>" alt="<?= htmlspecialchars($sport['sport_name']) ?>">
                        </div>
                        
                        <div class="sport-card-content">
                            <div class="sport-card-header">
                                <!-- ชื่อกีฬา -->
                                <h2 class="sport-title"><?= htmlspecialchars($sport['sport_name']) ?></h2>
                                <!-- ราคาต่อรอบ -->
                                <div class="sport-price">
                                    <h3><?= number_format($sport['price_per_round']) ?></h3>
                                    <span>THB / Round</span>
                                </div>
                            </div>
                            
                            <div class="sport-details">
                                <!-- แสดงระยะเวลาต่อรอบ (ใช้ฟังก์ชัน formatDuration ที่เขียนไว้ด้านบน) -->
                                <div class="detail-item">
                                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"></circle><polyline points="12 6 12 12 16 14"></polyline></svg>
                                    <?= formatDuration($sport['duration_minutes']) ?>
                                </div>
                                <!-- แสดงจำนวนสนาม -->
                                <div class="detail-item">
                                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="7" height="7"></rect><rect x="14" y="3" width="7" height="7"></rect><rect x="14" y="14" width="7" height="7"></rect><rect x="3" y="14" width="7" height="7"></rect></svg>
                                    <?= $sport['total_courts'] ?> Courts
                                </div>
                            </div>

                            <div class="sport-card-footer">
                                <!-- ปุ่มจอง: ถ้าล็อกอินแล้วจะให้กดจองได้เลย ถ้ายังจะให้ไปหน้า Login ก่อน -->
                                <?php if (isLoggedIn()): ?>
                                    <a href="<?= SITE_URL ?>/booking?sport_id=<?= $sport['sport_id'] ?>" class="btn-book">
                                        Book Now
                                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="5" y1="12" x2="19" y2="12"></line><polyline points="12 5 19 12 12 19"></polyline></svg>
                                    </a>
                                <?php else: ?>
                                    <a href="<?= SITE_URL ?>/login" class="btn-book" style="background: var(--gray-500);">
                                        Login to Book
                                    </a>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </section>
    </div>

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

    <!-- Scripts -->
    <script>
        // Navbar Shadow on Scroll
        // สคริปต์สำหรับเพิ่มเงาให้ Navbar เมื่อเลื่อนหน้าจอลง
        window.addEventListener('scroll', function() {
            const navbar = document.getElementById('navbar');
            if (window.scrollY > 50) {
                navbar.classList.add('scrolled');
            } else {
                navbar.classList.remove('scrolled');
            }
        });

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
</html>