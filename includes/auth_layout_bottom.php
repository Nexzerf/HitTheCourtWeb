<?php
// includes/auth_layout_bottom.php — ปิดโครงหน้าล็อกอิน / สมัครสมาชิก
?>
        </div>
        <a href="<?= SITE_URL ?>/" class="back-link auth-back"><?= icon('arrow-left', 16) ?> <?= e('auth.back_home') ?></a>
    </section>
    <aside class="auth-brand" aria-hidden="true">
        <div class="auth-brand-inner">
            <span class="badge badge-dark"><?= icon('zap', 12) ?> HIT THE COURT</span>
            <h2><?= e('auth.brand_title') ?></h2>
            <ul>
                <li><?= icon('grid', 18) ?> <?= e('auth.brand_1') ?></li>
                <li><?= icon('clock', 18) ?> <?= e('auth.brand_2') ?></li>
                <li><?= icon('crown', 18) ?> <?= e('auth.brand_3') ?></li>
            </ul>
        </div>
    </aside>
</div>
<script>
document.querySelectorAll('[data-pw]').forEach(function (b) {
    b.addEventListener('click', function () {
        var i = document.querySelector(b.dataset.pw);
        i.type = i.type === 'password' ? 'text' : 'password';
        b.classList.toggle('is-on', i.type === 'text');
    });
});
</script>
<script src="<?= SITE_URL ?>/assets/js/app.js" defer></script>
</body>
</html>
