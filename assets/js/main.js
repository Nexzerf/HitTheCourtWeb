// ===================================
// HIT THE COURT - Main JavaScript
// ===================================
// ไฟล์หลักที่เอาไว้จัดการ Interaction ต่างๆ บนหน้าเว็บ ตั้งแต่เมนู, Tab, จนถึง Animation

document.addEventListener('DOMContentLoaded', function() {
    // เมื่อหน้าเว็บโหลดเสร็จแล้ว (DOMContentLoaded) ให้เริ่มเรียก Function สำหรับเตรียมระบบต่างๆ ทีละตัว
    initNavbar();
    initTabs();
    initAnimations();
    initMobileNav();
});

// ===================================
// Navbar Scroll Effect
// ===================================
// จัดการ Effect ของ Navbar เวลาเลื่อนหน้าจอ
function initNavbar() {
    const navbar = document.querySelector('.navbar');
    if (!navbar) return;

    // ฟังก์ชันนี้คือ เวลาเลื่อนหน้าจอลงมาเกิน 50px จะเติม Class 'scrolled' เข้าไป
    // ปกติเวลาอยู่ด้านบนสุด Navbar อาจจะโปร่งใส พอเลื่อนลงมาก็จะเปลี่ยนเป็นสีทึบ (ตาม CSS ที่เขียนไว้)
    window.addEventListener('scroll', function() {
        navbar.classList.toggle('scrolled', window.scrollY > 50);
    });
}

// ===================================
// Tabs (used in reports.php, reservations.php)
// ===================================
// จัดการระบบ Tab หรือแท็บสลับหน้า (เช่น แท็บ New / In Progress / Resolved)
function initTabs() {
    const tabGroups = document.querySelectorAll('[data-tabs]');

    tabGroups.forEach(group => {
        const tabs = group.querySelectorAll('[data-tab]');
        const panels = group.querySelectorAll('[data-panel]');

        tabs.forEach(tab => {
            tab.addEventListener('click', function() {
                const target = this.dataset.tab;

                // เอา active ออกจากทุกแท็บ แล้วใส่ active ให้แท็บที่กด
                tabs.forEach(t => t.classList.remove('active'));
                this.classList.add('active');

                // ซ่อนทุก Panel แล้วโชว์ Panel ที่ตรงกับแท็บที่เลือก
                panels.forEach(p => {
                    p.classList.remove('active');
                    if (p.dataset.panel === target) p.classList.add('active');
                });
            });
        });
    });
}

// ===================================
// Scroll Reveal Animation
// ===================================
// Animation เวลาเลื่อนหน้าจอมาเจอ Element (Scroll Reveal)
function initAnimations() {
    // สร้าง Observer คอยดูว่า Element โผล่เข้ามาในหน้าจอหรือยัง
    const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            // เมื่อ Element โผล่เข้ามาในจอ (isIntersecting) ก็ให้เล่น Animation (addClass)
            if (entry.isIntersecting) {
                entry.target.classList.add('animate-slideUp');
                observer.unobserve(entry.target); // เลิกสังเกตการณ์หลังจากเล่นแล้ว
            }
        });
    }, { threshold: 0.1 });

    // เลือก Element ที่มี class 'animate-on-scroll' มาสังเกตการณ์
    document.querySelectorAll('.animate-on-scroll').forEach(el => observer.observe(el));
}

// ===================================
// Mobile Nav (used in ALL pages)
// ===================================
// จัดการเมนูบนมือถือ (Hamburger menu) ที่ใช้ทุกหน้า
function initMobileNav() {
    const toggleBtn = document.querySelector('.mobile-toggle');
    const navbar = document.getElementById('navbar');
    const userMenu = document.querySelector('.user-menu');
    const body = document.body;

    // Hamburger toggle
    // เวลากดปุ่ม Hamburger
    if (toggleBtn && navbar) {
        toggleBtn.addEventListener('click', function(e) {
            e.stopPropagation(); // กันไม่ให้ Event ฟังแพร่ไปที่ element แม่ (กันกดปิดตัวเอง)
            navbar.classList.toggle('menu-open'); // เปิด/ปิดเมนู
            // เวลาเมนูเปิด ให้ล็อค scroll ตัว body ไม่ให้เลื่อน
            body.style.overflow = navbar.classList.contains('menu-open') ? 'hidden' : '';

            // เปลี่ยนไอคอนจาก Hamburger (3 ขีด) เป็น X (ปิด)
            const icon = this.querySelector('svg');
            if (icon) {
                icon.innerHTML = navbar.classList.contains('menu-open')
                    ? '<line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line>'
                    : '<line x1="3" y1="12" x2="21" y2="12"></line><line x1="3" y1="6" x2="21" y2="6"></line><line x1="3" y1="18" x2="21" y2="18"></line>';
            }
        });
    }

    // User menu toggle (mobile)
    // จัดการเมนู User บนมือถือ (กด Avatar แล้วเมนูจะโผล่)
    if (userMenu) {
        userMenu.querySelector('.user-btn')?.addEventListener('click', function(e) {
            e.stopPropagation();
            userMenu.classList.toggle('active');
        });
    }

    // Close on outside click
    // เวลากดพื้นที่นอกเมนู ให้ปิดเมนูทั้งหมด
    document.addEventListener('click', function(e) {
        if (navbar?.classList.contains('menu-open') && !navbar.contains(e.target)) {
            navbar.classList.remove('menu-open');
            body.style.overflow = '';
        }
        if (userMenu?.classList.contains('active') && !userMenu.contains(e.target)) {
            userMenu.classList.remove('active');
        }
    });
}

// ===================================
// Toast Notification (used globally via HitTheCourt.showToast)
// ===================================
// ฟังก์ชันแสดงข้อความแจ้งเตือนแบบโผล่ขึ้นมาแล้วค่อยๆ จางหาย
function showToast(message, type = 'info') {
    // หากล่อง Toast container ถ้ายังไม่มีก็สร้างใหม่
    let container = document.querySelector('.toast-container');
    if (!container) {
        container = document.createElement('div');
        container.className = 'toast-container';
        document.body.appendChild(container);
    }

    // สร้าง Element ของ Toast แล้วยัดข้อความใส่
    const toast = document.createElement('div');
    toast.className = `toast ${type}`;
    toast.textContent = message;
    container.appendChild(toast);

    // ตั้งเวลาให้มันค่อยๆ จางหายไปใน 3 วินาที
    setTimeout(() => {
        toast.style.opacity = '0';
        setTimeout(() => toast.remove(), 300);
    }, 3000);
}

// ===================================
// Global API
// ===================================
// ส่งออกฟังก์ชัน showToast ไว้ใต้ Object ชื่อ HitTheCourt
// เพื่อให้เรียกใช้ได้จากที่ไหนก็ได้ในเว็บ เช่น HitTheCourt.showToast('Hello')
window.HitTheCourt = { showToast };