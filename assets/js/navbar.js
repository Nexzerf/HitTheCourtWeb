// assets/js/navbar.js
// การทำงานของ Navbar กลาง: เงาตอนเลื่อนจอ, เปิด/ปิดเมนูมือถือ, เปิด/ปิดเมนูผู้ใช้
(function () {
    var nav = document.getElementById('navbar');
    if (!nav || !nav.classList.contains('site-nav')) return;

    var toggle = nav.querySelector('.sn-toggle');
    var backdrop = document.getElementById('sn-backdrop');
    var user = document.getElementById('sn-user');
    var userBtn = user && user.querySelector('.sn-user-btn');

    function onScroll() { nav.classList.toggle('is-scrolled', window.scrollY > 10); }
    window.addEventListener('scroll', onScroll, { passive: true });
    onScroll();

    function setMenu(open) {
        nav.classList.toggle('is-open', open);
        document.body.classList.toggle('sn-lock', open);
        toggle.setAttribute('aria-expanded', open ? 'true' : 'false');
        toggle.setAttribute('aria-label', open ? 'Close menu' : 'Open menu');
    }
    function setUser(open) {
        if (!user) return;
        user.classList.toggle('is-open', open);
        userBtn.setAttribute('aria-expanded', open ? 'true' : 'false');
    }

    toggle.addEventListener('click', function (e) {
        e.stopPropagation();
        setUser(false);
        setMenu(!nav.classList.contains('is-open'));
    });
    if (backdrop) backdrop.addEventListener('click', function () { setMenu(false); });

    if (userBtn) {
        userBtn.addEventListener('click', function (e) {
            e.stopPropagation();
            // มือถือ: เมนูบัญชีอยู่ในแผงเมนูหลัก / PC: เปิด dropdown
            if (window.innerWidth <= 900) { setMenu(!nav.classList.contains('is-open')); return; }
            setUser(!user.classList.contains('is-open'));
        });
    }

    // คลิกที่อื่น / กด Esc / ขยายจอกลับเป็น PC ให้ปิดเมนู
    document.addEventListener('click', function (e) {
        if (user && !user.contains(e.target)) setUser(false);
    });
    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape') { setMenu(false); setUser(false); }
    });
    window.addEventListener('resize', function () {
        if (window.innerWidth > 900) setMenu(false);
    });
})();
