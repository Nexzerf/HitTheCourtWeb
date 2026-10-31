<?php
// includes/footer.php
// ส่วนท้ายเว็บที่ใช้ร่วมกันของหน้าที่ใช้ดีไซน์ใหม่ (ปิด </body></html> ให้ด้วย)
?>
<footer class="site-footer">
    <div class="wrap footer-grid">
        <div>
            <a href="<?= SITE_URL ?>/" class="footer-logo">HIT THE <span>COURT</span></a>
            <p class="footer-text"><?= nl2br(e('footer.address')) ?></p>
        </div>
        <div>
            <h4><?= e('footer.menu') ?></h4>
            <ul>
                <li><a href="<?= SITE_URL ?>/courts"><?= e('nav.courts') ?></a></li>
                <li><a href="<?= SITE_URL ?>/guidebook"><?= e('nav.guidebook') ?></a></li>
                <li><a href="<?= SITE_URL ?>/membership"><?= e('nav.membership') ?></a></li>
                <li><a href="<?= SITE_URL ?>/reports"><?= e('nav.contact') ?></a></li>
            </ul>
        </div>
        <div>
            <h4><?= e('footer.contact') ?></h4>
            <ul>
                <li><a href="tel:1112223"><?= icon('phone', 16) ?> 111-222-3</a></li>
                <li><a href="mailto:peoplecmucamt@gmail.com"><?= icon('mail', 16) ?> peoplecmucamt@gmail.com</a></li>
            </ul>
        </div>
    </div>
    <div class="wrap footer-bottom">© <?= date('Y') ?> Hit The Court. <?= e('footer.rights') ?></div>
</footer>
<script src="<?= SITE_URL ?>/assets/js/app.js" defer></script>
</body>
</html>
