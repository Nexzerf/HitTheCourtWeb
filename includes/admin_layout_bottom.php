<?php // includes/admin_layout_bottom.php — ปิดโครงหน้าแอดมิน ?>
        </main>
    </div>
</div>
<script>
(function () {
    var side = document.getElementById('adminSide'), burger = document.getElementById('adminBurger'), backdrop = document.getElementById('adminBackdrop');
    function set(open) { document.body.classList.toggle('admin-open', open); burger.setAttribute('aria-expanded', open); }
    burger.addEventListener('click', function () { set(!document.body.classList.contains('admin-open')); });
    backdrop.addEventListener('click', function () { set(false); });
    document.addEventListener('keydown', function (e) { if (e.key === 'Escape') set(false); });
})();
</script>
<script src="<?= SITE_URL ?>/assets/js/app.js" defer></script>
</body>
</html>
