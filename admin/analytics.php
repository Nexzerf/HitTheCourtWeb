<?php
// ดึงเอาไฟล์ตั้งค่าหลัก (config) เข้ามาก่อน เพื่อเชื่อมฐานข้อมูล
require_once '../config.php';
// เช็คสิทธิ์ว่าเป็น Admin ไหม? ถ้าไม่ใช่จะไม่ให้เข้าหน้านี้
requireAdmin();

// --- Sidebar Data ---
// นับจำนวนรายงานที่ยังไม่เสร็จ (new, in_progress) เพื่อเอาไปแสดงเป็นตัวเลขแจ้งเตือนสีแดงที่เมนู Reports
 $pendingReports = $pdo->query("SELECT COUNT(*) as count FROM reports WHERE status IN ('new', 'in_progress')")->fetch()['count'];

// --- Filter Logic ---
// รับค่าตัวกรองช่วงเวลาจาก URL (ค่า default คือ 'monthly' หรือแสดงเดือนนี้)
 $period = sanitize($_GET['period'] ?? 'monthly');
 $startDate = $_GET['start_date'] ?? '';
 $endDate = $_GET['end_date'] ?? '';

// Set Date Range based on Period
// เตรียมเงื่อนไข SQL เบื้องต้นว่าเอาเฉพาะที่จ่ายเงินแล้ว (payment_status = 'paid')
 $sqlWhere = "b.payment_status = 'paid'";
 $dateFormat = '%Y-%m-%d';
 $title = "Overview";

// groupFormat ใช้สำหรับ GROUP BY ให้ละเอียดพอที่จะไม่ซ้ำข้ามช่วงเวลา
 $groupFormat = '%Y-%m-%d'; // default

// สวิตช์เลือก Logic ตามช่วงเวลาที่เลือกมา
switch ($period) {
    case 'daily':
        // ถ้าเลือก Daily: เอาเฉพาะวันนี้ (แปลงเวลาจาก UTC เป็นเวลาไทย +07:00)
        $sqlWhere .= " AND DATE(CONVERT_TZ(b.created_at, '+00:00', '+07:00')) = DATE(CONVERT_TZ(NOW(), '+00:00', '+07:00'))";
        $dateFormat = '%H:00'; // แสดงเป็นชั่วโมง เช่น 10:00, 11:00
        $groupFormat = '%Y-%m-%d %H';
        $title = "Today's Overview (" . date('d M Y') . ")";
        break;
    case 'weekly':
        // ถ้าเลือก Weekly: เอาเฉพาะสัปดาห์นี้
        $sqlWhere .= " AND YEARWEEK(CONVERT_TZ(b.created_at, '+00:00', '+07:00'), 1) = YEARWEEK(CONVERT_TZ(NOW(), '+00:00', '+07:00'), 1)";
        $dateFormat = '%a (%d)'; // แสดงเป็นชื่อวัน เช่น Mon (01)
        $groupFormat = '%Y-%m-%d';
        $title = "This Week's Overview";
        break;
    case 'monthly':
        // ถ้าเลือก Monthly: เอาเฉพาะเดือนนี้
        $sqlWhere .= " AND YEAR(CONVERT_TZ(b.created_at, '+00:00', '+07:00')) = YEAR(CONVERT_TZ(NOW(), '+00:00', '+07:00')) AND MONTH(CONVERT_TZ(b.created_at, '+00:00', '+07:00')) = MONTH(CONVERT_TZ(NOW(), '+00:00', '+07:00'))";
        $dateFormat = '%d %b'; // แสดงเป็นวันที่ เช่น 01 Jan
        $groupFormat = '%Y-%m-%d';
        $title = "This Month's Overview (" . date('F Y') . ")";
        break;
    case 'quarterly':
        // ถ้าเลือก Quarterly: คำนวณไตรมาสปัจจุบัน
        $currentMonth = date('n');
        $quarterStartMonth = floor(($currentMonth - 1) / 3) * 3 + 1;
        $startDateQ = date('Y-' . str_pad($quarterStartMonth, 2, '0', STR_PAD_LEFT) . '-01');
        $sqlWhere .= " AND CONVERT_TZ(b.created_at, '+00:00', '+07:00') >= '$startDateQ'";
        $dateFormat = '%M'; // แสดงเป็นชื่อเดือน
        $groupFormat = '%Y-%m';
        $title = "Quarterly Overview";
        break;
    case 'yearly':
        // ถ้าเลือก Yearly: เอาเฉพาะปีนี้
        $sqlWhere .= " AND YEAR(CONVERT_TZ(b.created_at, '+00:00', '+07:00')) = YEAR(CONVERT_TZ(NOW(), '+00:00', '+07:00'))";
        $dateFormat = '%M';
        $groupFormat = '%Y-%m';
        $title = "Yearly Overview (" . date('Y') . ")";
        break;
    case 'custom':
        // ถ้าเลือก Custom: ใช้วันที่ที่ผู้ใช้กรอกมา
        if ($startDate && $endDate) {
            $sqlWhere .= " AND DATE(CONVERT_TZ(b.created_at, '+00:00', '+07:00')) BETWEEN '$startDate' AND '$endDate'";
            $title = "Custom Overview ($startDate to $endDate)";
        } else {
            $title = "Custom Overview (Select Dates)";
        }
        break;
}

// --- Fetch Data for Charts ---

// 1. Revenue & Bookings Over Time
// ดึงข้อมูลสำหรับกราฟเส้นและกราฟแท่ง (ยอดเงินและจำนวนการจองตามช่วงเวลา)
 $stmt = $pdo->prepare("
    SELECT 
        DATE_FORMAT(CONVERT_TZ(b.created_at, '+00:00', '+07:00'), '$dateFormat') as time_label,
        DATE_FORMAT(CONVERT_TZ(b.created_at, '+00:00', '+07:00'), '$groupFormat') as group_key,
        SUM(b.total_price) as total_revenue,
        COUNT(b.booking_id) as total_bookings
    FROM bookings b
    WHERE $sqlWhere
    GROUP BY group_key
    ORDER BY group_key ASC
");
 $stmt->execute();
 $timelineData = $stmt->fetchAll();

// แยกข้อมูลออกมาเป็น Array สำหรับส่งให้ Javascript
 $labels = [];
 $revenueData = [];
 $bookingCountData = [];

foreach ($timelineData as $row) {
    $labels[] = $row['time_label'];
    $revenueData[] = $row['total_revenue'];
    $bookingCountData[] = $row['total_bookings'];
}

// 2. Bookings by Sport (Pie Chart)
// ดึงข้อมูลสำหรับกราฟวงกลม (สัดส่วนการจองแต่ละกีฬา)
 $stmtSport = $pdo->prepare("
    SELECT s.sport_name, COUNT(b.booking_id) as count
    FROM bookings b
    JOIN courts c ON b.court_id = c.court_id
    JOIN sports s ON c.sport_id = s.sport_id
    WHERE $sqlWhere
    GROUP BY s.sport_name
");
 $stmtSport->execute();
 $sportData = $stmtSport->fetchAll();

 $sportLabels = [];
 $sportCounts = [];
foreach ($sportData as $row) {
    $sportLabels[] = $row['sport_name'];
    $sportCounts[] = $row['count'];
}

// 3. Summary Cards
// คำนวณตัวเลขสรุปรวมสำหรับการ์ดด้านบน
 $totalRevenue = array_sum($revenueData);
 $totalBookings = array_sum($bookingCountData);
 $totalUsers = $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Analytics - Admin</title>
    <!-- โหลด Font และ CSS -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Space+Grotesk:wght@500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= SITE_URL ?>/assets/css/admin.css">
    <link rel="icon" type="image/png" sizes="32x32" href="/images/favicon-32x32.png">
    <link rel="icon" type="image/png" sizes="16x16" href="/images/favicon-48x48.png">
    <!-- โหลด Library สำหรับวาดกราฟ (Chart.js) -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>
<!-- Mobile Sidebar Toggle -->
<!-- ปุ่ม Toggle สำหรับมือถือ (กดแล้วเมนูด้านข้างจะโผล่) -->
<button class="admin-sidebar-toggle" id="sidebarToggle" aria-label="Toggle sidebar">
    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" 
         fill="none" stroke="currentColor" stroke-width="2">
        <line x1="3" y1="12" x2="21" y2="12"></line>
        <line x1="3" y1="6" x2="21" y2="6"></line>
        <line x1="3" y1="18" x2="21" y2="18"></line>
    </svg>
</button>
<!-- พื้นที่ดำๆ เวลาเปิดเมนูมือถือ (กดแล้วเมนูจะปิด) -->
<div class="admin-sidebar-overlay" id="sidebarOverlay"></div>
    <div class="admin-layout">
        
        <!-- Sidebar -->
        <!-- เมนูด้านข้างของหน้า Admin -->
        <aside class="admin-sidebar">
            <div class="admin-brand">
                <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <rect x="3" y="3" width="18" height="18" rx="2" ry="2"></rect>
                    <line x1="3" y1="9" x2="21" y2="9"></line>
                    <line x1="9" y1="21" x2="9" y2="9"></line>
                </svg>
                Hit The Court
            </div>
            
            <nav class="admin-nav">
                <!-- ลิงก์เมนูต่างๆ -->
                <a href="dashboard.php" class="admin-nav-item">
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="7" height="7"></rect><rect x="14" y="3" width="7" height="7"></rect><rect x="14" y="14" width="7" height="7"></rect><rect x="3" y="14" width="7" height="7"></rect></svg>
                    Dashboard
                </a>
                <a href="analytics.php" class="admin-nav-item active">
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="20" x2="18" y2="10"></line><line x1="12" y1="20" x2="12" y2="4"></line><line x1="6" y1="20" x2="6" y2="14"></line></svg>
                    Analytics
                </a>
                <a href="sports.php" class="admin-nav-item">
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"></circle></svg>
                    Sports & Courts
                </a>
                <a href="bookings.php" class="admin-nav-item">
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline></svg>
                    Bookings
                </a>
                <a href="payments.php" class="admin-nav-item">
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="1" x2="12" y2="23"></line><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"></path></svg>
                    Payments
                </a>
                <a href="members.php" class="admin-nav-item">
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path><circle cx="9" cy="7" r="4"></circle><path d="M23 21v-2a4 4 0 0 0-3-3.87"></path><path d="M16 3.13a4 4 0 0 1 0 7.75"></path></svg>
                    Members
                </a>
                <a href="reports.php" class="admin-nav-item">
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"></path></svg>
                    Reports
                    <!-- แสดงตัวเลขแจ้งเตือนถ้ามีรายงานรอดำเนินการ -->
                    <?php if ($pendingReports > 0): ?>
                    <span style="background: #DC2626; color: white; padding: 2px 8px; border-radius: 999px; font-size: 0.7rem; margin-left: auto;"><?= $pendingReports ?></span>
                    <?php endif; ?>
                </a>
            </nav>
            
            <div style="margin-top: auto; padding: 1rem; border-top: 1px solid rgba(255,255,255,0.1);">
                <!-- ปุ่มล็อกเอาท์ -->
                <a href="<?= SITE_URL ?>/api/auth.php?action=admin_logout" class="admin-nav-item">
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path><polyline points="16 17 21 12 16 7"></polyline><line x1="21" y1="12" x2="9" y2="12"></line></svg>
                    Logout
                </a>
            </div>
        </aside>
        
        <!-- Main Content -->
        <!-- เนื้อหาหลักของหน้า Analytics -->
        <main class="admin-main">
            
            <div class="admin-header">
                <h1 class="admin-title">Analytics & Statistics</h1>
                <p style="color: var(--admin-muted);"><?= $title ?></p>
            </div>

            <!-- Filter Controls -->
            <!-- ส่วนตัวกรอง (เลือกช่วงเวลา) -->
            <div class="admin-card" style="margin-bottom: 1.5rem;">
                <div class="admin-card-body" style="padding: 1rem 1.5rem;">
                    <form method="GET" class="filter-bar">
                        <div class="filter-group">
                            <label class="filter-label">Period</label>
                            <!-- Dropdown เลือกช่วงเวลา -->
                            <select name="period" class="admin-input" onchange="toggleCustomDates(this.value)">
                                <option value="daily" <?= $period == 'daily' ? 'selected' : '' ?>>Daily (Today)</option>
                                <option value="weekly" <?= $period == 'weekly' ? 'selected' : '' ?>>Weekly (This Week)</option>
                                <option value="monthly" <?= $period == 'monthly' ? 'selected' : '' ?>>Monthly (This Month)</option>
                                <option value="quarterly" <?= $period == 'quarterly' ? 'selected' : '' ?>>Quarterly</option>
                                <option value="yearly" <?= $period == 'yearly' ? 'selected' : '' ?>>Yearly</option>
                                <option value="custom" <?= $period == 'custom' ? 'selected' : '' ?>>Custom</option>
                            </select>
                        </div>
                        
                        <!-- ช่องกรอกวันที่เอง (แสดงเฉพาะตอนเลือก Custom) -->
                        <div id="custom-dates" style="display: <?= $period == 'custom' ? 'flex' : 'none' ?>; gap: 1rem;">
                            <div class="filter-group">
                                <label class="filter-label">Start Date</label>
                                <input type="date" name="start_date" class="admin-input" value="<?= $startDate ?>">
                            </div>
                            <div class="filter-group">
                                <label class="filter-label">End Date</label>
                                <input type="date" name="end_date" class="admin-input" value="<?= $endDate ?>">
                            </div>
                        </div>

                        <button type="submit" class="btn btn-primary">Apply Filter</button>
                    </form>
                </div>
            </div>

            <!-- Summary Cards -->
            <!-- การ์ดสรุปตัวเลขด้านบน -->
            <div class="dashboard-grid" style="grid-template-columns: repeat(4, 1fr); margin-bottom: 1.5rem;">
                <!-- การ์ด 1: รายได้รวม -->
                <div class="stat-card">
                    <div class="stat-card-icon" style="background: #DCFCE7; color: #166534;">
                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="1" x2="12" y2="23"></line><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"></path></svg>
                    </div>
                    <div class="stat-card-content">
                        <span class="stat-card-label">Total Revenue</span>
                        <h3 class="stat-card-value">฿<?= number_format($totalRevenue) ?></h3>
                    </div>
                </div>
                <!-- การ์ด 2: จำนวนการจองรวม -->
                <div class="stat-card">
                    <div class="stat-card-icon" style="background: #DBEAFE; color: #1E40AF;">
                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect><line x1="16" y1="2" x2="16" y2="6"></line><line x1="8" y1="2" x2="8" y2="6"></line><line x1="3" y1="10" x2="21" y2="10"></line></svg>
                    </div>
                    <div class="stat-card-content">
                        <span class="stat-card-label">Total Bookings</span>
                        <h3 class="stat-card-value"><?= $totalBookings ?></h3>
                    </div>
                </div>
                <!-- การ์ด 3: จำนวนผู้ใช้รวม -->
                <div class="stat-card">
                    <div class="stat-card-icon" style="background: #FEE2E2; color: #991B1B;">
                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path><circle cx="9" cy="7" r="4"></circle></svg>
                    </div>
                    <div class="stat-card-content">
                        <span class="stat-card-label">Total Users</span>
                        <h3 class="stat-card-value"><?= $totalUsers ?></h3>
                    </div>
                </div>
                <!-- การ์ด 4: ค่าเฉลี่ยต่อการจอง -->
                <div class="stat-card">
                    <div class="stat-card-icon" style="background: #FEF3C7; color: #92400E;">
                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20.59 13.41l-7.17 7.17a2 2 0 0 1-2.83 0L2 12V2h10l8.59 8.59a2 2 0 0 1 0 2.82z"></path><line x1="7" y1="7" x2="7.01" y2="7"></line></svg>
                    </div>
                    <div class="stat-card-content">
                        <span class="stat-card-label">Avg. Check</span>
                        <h3 class="stat-card-value">฿<?= $totalBookings > 0 ? number_format($totalRevenue / $totalBookings) : 0 ?></h3>
                    </div>
                </div>
            </div>

            <!-- Charts -->
            <!-- ส่วนแสดงกราฟ -->
            <div class="dashboard-grid" style="margin-bottom: 1.5rem;">
                <!-- กราฟใหญ่: แท่งและเส้น (ยอดเงินและจำนวนจอง) -->
                <div class="admin-card" style="grid-column: span 2;">
                    <div class="admin-card-header">
                        <h3>Revenue & Bookings Trend</h3>
                    </div>
                    <div class="admin-card-body">
                        <canvas id="revenueChart" style="height: 300px;"></canvas>
                    </div>
                </div>
                
                <!-- กราฟเล็ก: วงกลม (สัดส่วนกีฬา) -->
                <div class="admin-card">
                    <div class="admin-card-header">
                        <h3>Bookings by Sport</h3>
                    </div>
                    <div class="admin-card-body" style="display: flex; justify-content: center; align-items: center;">
                        <canvas id="sportChart" style="max-height: 300px; max-width: 300px;"></canvas>
                    </div>
                </div>
            </div>

        </main>
    </div>

    <script>
        // ฟังก์ชันสำหรับเปิด/ปิดช่องกรอกวันที่เอง
        function toggleCustomDates(val) {
            document.getElementById('custom-dates').style.display = val === 'custom' ? 'flex' : 'none';
        }

        // ส่วนตั้งค่ากราฟ Revenue & Bookings
        const ctxRevenue = document.getElementById('revenueChart').getContext('2d');
        const revenueChart = new Chart(ctxRevenue, {
            type: 'bar', // ประเภทกราฟแท่ง
            data: {
                labels: <?= json_encode($labels) ?>, // ข้อมูลแกน X (เวลา)
                datasets: [{
                    label: 'Revenue (THB)',
                    data: <?= json_encode($revenueData) ?>, // ข้อมูลยอดเงิน
                    backgroundColor: 'rgba(59, 130, 246, 0.5)',
                    borderColor: 'rgba(59, 130, 246, 1)',
                    borderWidth: 1,
                    yAxisID: 'y', // ใช้แกน Y ด้านซ้าย
                }, {
                    label: 'Bookings',
                    data: <?= json_encode($bookingCountData) ?>, // ข้อมูลจำนวนจอง
                    type: 'line', // แสดงเป็นเส้น
                    borderColor: 'rgba(16, 185, 129, 1)',
                    backgroundColor: 'rgba(16, 185, 129, 0.2)',
                    tension: 0.4,
                    fill: false,
                    yAxisID: 'y1', // ใช้แกน Y ด้านขวา
                }]
            },
            options: {
                responsive: true,
                interaction: { mode: 'index', intersect: false },
                scales: {
                    y: { type: 'linear', display: true, position: 'left', title: { display: true, text: 'Revenue (THB)' } },
                    y1: { type: 'linear', display: true, position: 'right', grid: { drawOnChartArea: false }, title: { display: true, text: 'Bookings' } },
                }
            }
        });

        // ส่วนตั้งค่ากราฟ Bookings by Sport (วงกลม)
        const ctxSport = document.getElementById('sportChart').getContext('2d');
        const sportChart = new Chart(ctxSport, {
            type: 'doughnut',
            data: {
                labels: <?= json_encode($sportLabels) ?>,
                datasets: [{
                    label: 'Bookings',
                    data: <?= json_encode($sportCounts) ?>,
                    backgroundColor: [
                        'rgba(59, 130, 246, 0.8)',
                        'rgba(16, 185, 129, 0.8)',
                        'rgba(249, 115, 22, 0.8)',
                        'rgba(139, 92, 246, 0.8)',
                        'rgba(236, 72, 153, 0.8)',
                        'rgba(20, 184, 166, 0.8)'
                    ],
                    borderWidth: 0
                }]
            },
            options: {
                responsive: true,
                plugins: { legend: { position: 'bottom' } }
            }
        });
    </script>
    <!-- Script สำหรับเปิด/ปิด Sidebar บนมือถือ -->
    <script>
        const toggle  = document.getElementById('sidebarToggle');
        const sidebar = document.querySelector('.admin-sidebar');
        const overlay = document.getElementById('sidebarOverlay');

        toggle?.addEventListener('click', () => {
            sidebar.classList.toggle('active');
            overlay.classList.toggle('active');
        });

        overlay?.addEventListener('click', () => {
            sidebar.classList.remove('active');
            overlay.classList.remove('active');
        });
    </script>
</body>
</html>