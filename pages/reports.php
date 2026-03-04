<?php
// ดึงเอาไฟล์ตั้งค่าระบบ (config) เข้ามาก่อน เพื่อเชื่อมต่อฐานข้อมูลและใช้ฟังก์ชันระบบ
require_once '../config.php';
// เพิ่มไว้บนสุดของไฟล์ หลัง require_once
date_default_timezone_set('Asia/Bangkok');
// เช็คเลยว่าล็อกอินแล้วยัง? ถ้ายังไม่ล็อกอินก็ไม่ให้เข้ามาหน้านี้
requireLogin();

// Fetch user's reports
// ไปดึงเอารายการรายงาน (Reports) ที่ User คนนี้เคยแจ้งเข้ามาทั้งหมด เรียงจากอันใหม่สุดก่อน
 $stmt = $pdo->prepare("SELECT * FROM reports WHERE user_id = ? ORDER BY created_at DESC");
 $stmt->execute([$_SESSION['user_id']]);
 $reports = $stmt->fetchAll();

// เตรียมตัวแปรไว้เก็บข้อความสำเร็จและข้อผิดพลาด
 $success = '';
 $error = '';

// Check for success message from redirect
// ถ้าตอนเข้ามาหน้านี้แล้วเจอ ?success ต่อท้าย URL แสดงว่าเพิ่งส่งรายงานสำเร็จ
if (isset($_GET['success'])) {
    $success = 'Report submitted successfully!';
}

// Handle new report submission
// ส่วนนี้คือจัดการตอนที่ User กดปุ่ม "ส่งรายงาน" (Method POST)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // รับข้อมูลจากฟอร์มและทำความสะอาดข้อมูล (sanitize) ก่อน
    $topic = sanitize($_POST['topic'] ?? '');
    $description = sanitize($_POST['description'] ?? '');
    $imagePath = '';
    
    // เช็คว่ากรอกข้อมูลครบไหม
    if (empty($topic) || empty($description)) {
        $error = 'Please fill in all required fields';
    } else {
        // Handle image upload
        // ถ้ามีการแนบรูปภาพมาด้วย
        if (!empty($_FILES['image']['name'])) {
            $file = $_FILES['image'];
            $allowedTypes = ['image/jpeg', 'image/png'];
            
            // เช็คว่าเป็นไฟล์รูปจริงไหม และขนาดไม่เกิน 5MB
            if (in_array($file['type'], $allowedTypes) && $file['size'] <= 5 * 1024 * 1024) {
                // เตรียมที่เก็บไฟล์
                $uploadDir = UPLOAD_PATH . 'reports/';
                if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);
                
                // ตั้งชื่อไฟล์ใหม่ให้ไม่ซ้ำ
                $filename = uniqid() . '_' . basename($file['name']);
                // ย้ายไฟล์ไปเก็บ
                if (move_uploaded_file($file['tmp_name'], $uploadDir . $filename)) {
                    $imagePath = 'uploads/reports/' . $filename;
                }
            }
        }
        
        // สร้างรหัสรายงาน (Report Code) แบบอัตโนมัติ เช่น RP20240101...
        $reportCode = 'RP' . date('Ymd') . strtoupper(substr(uniqid(), -6));
        
        // บันทึกข้อมูลลงฐานข้อมูล สถานะตั้งเป็น 'new'
        $insertStmt = $pdo->prepare("INSERT INTO reports (report_code, user_id, topic, description, image_path, status) VALUES (?, ?, ?, ?, ?, 'new')");
        if ($insertStmt->execute([$reportCode, $_SESSION['user_id'], $topic, $description, $imagePath])) {
            // --- FIX: Redirect to prevent form resubmission on refresh ---
            // ใช้เทคนิค Redirect (PRG Pattern) เพื่อกันปัญหากด Refresh แล้วข้อมูลส่งซ้ำ
            redirect('/reports?success=1');
        } else {
            $error = 'Failed to submit report. Please try again.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reports - Hit The Court</title>
    <!-- โหลด Font และ CSS มาแต่งหน้าตาเว็บ -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&family=Space+Grotesk:wght@500;600;700&display=swap" rel="stylesheet">
    <!-- เพิ่ม style.css กลับเข้าไป -->
    <link rel="stylesheet" href="../style.css">
    <link rel="stylesheet" href="<?= SITE_URL ?>/assets/css/home.css">
        <link rel="icon" type="image/png" sizes="32x32" href="/images/favicon-32x32.png">
    <link rel="icon" type="image/png" sizes="16x16" href="/images/favicon-48x48.png">
</head>
<body>
 <!-- NAVBAR -->
 <!-- เมนูด้านบนของเว็บ -->
<nav class="navbar-home" id="navbar">
<div class="navbar-container">

<a href="/" class="navbar-logo">HIT THE <span>COURT</span></a>

<!-- ปุ่มเมนูสำหรับมือถือ (Hamburger) -->
<button class="mobile-toggle" aria-label="Toggle menu">
<div class="hamburger-box">
<span class="bar"></span>
<span class="bar"></span>
<span class="bar"></span>
</div>
</button>

<ul class="nav-menu">

<li class="nav-item">
<a href="/courts" class="nav-link">Courts</a>
</li>

<li class="nav-item">
<a href="/reservations" class="nav-link">Reservations</a>
</li>

<li class="nav-item">
<a href="/reports" class="nav-link">Contact Us</a>
</li>

<li class="nav-item">
<a href="/guidebook" class="nav-link">Guidebook</a>
</li>

</ul>

<div class="nav-auth">

<?php if (isLoggedIn()): ?>

<!-- ถ้าล็อกอินแล้ว จะแสดงเมนู User -->
<div class="user-menu">

<button class="user-btn">
<div class="user-avatar">
<?= strtoupper(substr($_SESSION['username'], 0, 1)) ?>
</div>
<span><?= htmlspecialchars($_SESSION['username']) ?></span>
</button>

<div class="user-dropdown">

<a href="/reservations" class="dropdown-link">My Bookings</a>
<a href="/profile" class="dropdown-link">My Profile</a>
<a href="/membership" class="dropdown-link">Membership</a>

<a href="/api/auth.php?action=logout" 
class="dropdown-link" 
style="color:red;">
Logout
</a>

</div>
</div>

<?php else: ?>

<!-- ถ้ายังไม่ได้ล็อกอิน จะแสดงปุ่ม Login/Sign Up -->
<a href="/login" class="btn btn-ghost">Login</a>
<a href="/register" class="btn btn-primary">Sign Up</a>

<?php endif; ?>

</div>
</div>
</nav>

    <main class="section" style="padding-top: 7rem;">
    <div class="container">

        <!-- หัวข้อหน้ารายงาน -->
        <div class="reports-page-header">
            <h1>My Reports</h1>
            <p>Track your submitted issues and requests</p>
        </div>

        <div class="reports-layout">

            <!-- LEFT: Reports List -->
            <!-- ฝั่งซ้าย: แสดงรายการรายงานทั้งหมด -->
            <div>
                <!-- Tabs -->
                <!-- แถบเมนูย่อย (Tab) สำหรับกรองสถานะ -->
                <div class="reports-tabs">
                    <?php
                    // นับจำนวนรายงานในแต่ละสถานะมาแสดงเป็น Badge เล็กๆ
                    $counts = [
                        'new'         => count(array_filter($reports, fn($r) => $r['status'] === 'new')),
                        'in_progress' => count(array_filter($reports, fn($r) => $r['status'] === 'in_progress')),
                        'resolved'    => count(array_filter($reports, fn($r) => $r['status'] === 'resolved')),
                    ];
                    ?>
                    <button class="report-tab active" data-tab="new">
                        New <span class="tab-badge"><?= $counts['new'] ?></span>
                    </button>
                    <button class="report-tab" data-tab="in_progress">
                        In Progress <span class="tab-badge"><?= $counts['in_progress'] ?></span>
                    </button>
                    <button class="report-tab" data-tab="resolved">
                        Resolved <span class="tab-badge"><?= $counts['resolved'] ?></span>
                    </button>
                </div>

                <?php
                // ตั้งค่าข้อมูลสำหรับแต่ละ Panel (New, In Progress, Resolved)
                $panels = [
                    'new'         => ['label' => 'New',         'empty' => 'No new reports yet'],
                    'in_progress' => ['label' => 'In Progress', 'empty' => 'No reports in progress'],
                    'resolved'    => ['label' => 'Resolved',    'empty' => 'No resolved reports yet'],
                ];
                // วนลูปแสดงผลทีละ Panel
                foreach ($panels as $status => $meta):
                    $filtered = array_filter($reports, fn($r) => $r['status'] === $status);
                ?>
                <div class="reports-grid" data-panel="<?= $status ?>"
                     style="<?= $status !== 'new' ? 'display:none;' : '' ?>">

                    <?php if (empty($filtered)): ?>
                        <!-- ถ้าไม่มีรายงานในสถานะนี้ จะแสดงรูปและข้อความว่างๆ -->
                        <div class="reports-empty">
                            <div class="reports-empty-icon">
                                <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                          d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                                </svg>
                            </div>
                            <h3><?= $meta['empty'] ?></h3>
                            <p>Submit a report using the form on the right.</p>
                        </div>

                    <?php else: ?>
                        <!-- ถ้ามีรายงาน ก็วนลูปแสดงเป็นการ์ดๆ -->
                        <?php foreach ($filtered as $report): ?>
                        <div class="report-card" data-status="<?= htmlspecialchars($report['status']) ?>">

                            <div class="report-card-header">
                                <h4 class="report-card-title"><?= htmlspecialchars($report['topic']) ?></h4>
                                <!-- แสดงป้ายสถานะ (New, In Progress, Resolved) -->
                                <span class="report-status <?= htmlspecialchars($report['status']) ?>">
                                    <?= $meta['label'] ?>
                                </span>
                            </div>

                            <p class="report-card-content"><?= htmlspecialchars($report['description']) ?></p>

                            <!-- ถ้า Admin มีการตอบกลับมา (admin_notes) ก็จะแสดงตรงนี้ -->
                            <?php if (!empty($report['admin_notes'])): ?>
                            <div class="admin-response">
                                <div class="admin-response-label">Admin Response</div>
                                <p><?= htmlspecialchars($report['admin_notes']) ?></p>
                            </div>
                            <?php endif; ?>

                            <div class="report-card-footer">
                                <span class="report-card-date">
                                    <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                        <rect x="3" y="4" width="18" height="18" rx="2"/>
                                        <line x1="16" y1="2" x2="16" y2="6"/>
                                        <line x1="8" y1="2" x2="8" y2="6"/>
                                        <line x1="3" y1="10" x2="21" y2="10"/>
                                    </svg>
                                    <?= date('d M Y, g:i A', strtotime($report['created_at'])) ?>
                                </span>
                                <?php if (!empty($report['report_code'])): ?>
                                    <span class="report-code"><?= htmlspecialchars($report['report_code']) ?></span>
                                <?php endif; ?>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    <?php endif; ?>

                </div>
                <?php endforeach; ?>
            </div>

            <!-- RIGHT: Submit Form -->
            <!-- ฝั่งขวา: ฟอร์มสำหรับส่งรายงานใหม่ -->
            <div class="submit-card">
                <div class="submit-card-header">
                    <h3>Submit a Report</h3>
                    <p>We'll get back to you as soon as possible</p>
                </div>
                <div class="submit-card-body">

                    <!-- แสดงข้อความสำเร็จ (ถ้ามี) -->
                    <?php if ($success): ?>
                    <div class="alert-toast success">
                        <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>
                        <?= htmlspecialchars($success) ?>
                    </div>
                    <?php endif; ?>

                    <!-- แสดงข้อความผิดพลาด (ถ้ามี) -->
                    <?php if ($error): ?>
                    <div class="alert-toast error">
                        <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
                        <?= htmlspecialchars($error) ?>
                    </div>
                    <?php endif; ?>

                    <form method="POST" action="" enctype="multipart/form-data">
                        <div class="form-group">
                            <label class="form-label">Topic</label>
                            <input type="text" name="topic" class="form-control"
                                   placeholder="Brief description of the issue" required>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Description</label>
                            <textarea name="description" class="form-control" rows="4"
                                      placeholder="Provide detailed information about the issue..." required></textarea>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Attach Image <span style="font-weight:400;color:#9ca3af">(Optional)</span></label>
                            <input type="file" name="image" class="form-control" accept="image/jpeg,image/png">
                            <span class="form-hint">JPG or PNG, max 5MB</span>
                        </div>
                        <button type="submit" class="btn-submit">
                            <svg width="15" height="15" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                <line x1="22" y1="2" x2="11" y2="13"/><polygon points="22 2 15 22 11 13 2 9 22 2"/>
                            </svg>
                            Submit Report
                        </button>
                    </form>
                </div>
            </div>

        </div>
    </div>
</main>

    <!-- เรียกใช้ไฟล์ Javascript หลัก -->
    <script src="<?= SITE_URL ?>/assets/js/main.js"></script>
    <script>
    // Script สำหรับจัดการการกด Tab สลับหมวดหมู่รายงาน (New, In Progress, Resolved)
    document.querySelectorAll('.report-tab').forEach(tab => {
        tab.addEventListener('click', function() {
            // เอา class active ออกจากทุกปุ่ม
            document.querySelectorAll('.report-tab').forEach(t => t.classList.remove('active'));
            // ใส่ class active ให้ปุ่มที่กด
            this.classList.add('active');
            // ซ่อนทุก Panel ที่เคยแสดงอยู่
            document.querySelectorAll('[data-panel]').forEach(p => p.style.display = 'none');
            // แสดง Panel ที่ตรงกับปุ่มที่กด
            document.querySelector(`[data-panel="${this.dataset.tab}"]`).style.display = 'grid';
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
                <p style='text-align: center;'>HIT THE COURT</p>
            </div>
        </div>
    </footer>
</html>