// assets/js/app.js
// สคริปต์กลางของหน้าที่ใช้ดีไซน์ใหม่: แท็บ, ช่องอัปโหลดไฟล์, ยืนยันก่อนกด, แจ้งเตือน
(function () {
    // ---------- แท็บ: <div data-tabs> <button class="tab" data-tab="x"> ... <div class="tab-panel" data-panel="x"> ----------
    document.querySelectorAll('[data-tabs]').forEach(function (group) {
        var tabs = group.querySelectorAll('[data-tab]');
        var panels = group.querySelectorAll('[data-panel]');
        tabs.forEach(function (tab) {
            tab.addEventListener('click', function () {
                tabs.forEach(function (t) { t.classList.toggle('is-active', t === tab); t.setAttribute('aria-selected', t === tab); });
                panels.forEach(function (p) { p.hidden = p.dataset.panel !== tab.dataset.tab; });
            });
        });
    });

    // ---------- ช่องอัปโหลด: แสดงชื่อไฟล์และรูปตัวอย่าง ----------
    document.querySelectorAll('.upload').forEach(function (zone) {
        var input = zone.querySelector('input[type=file]');
        var label = zone.querySelector('[data-upload-label]');
        if (!input) return;
        ['dragenter', 'dragover'].forEach(function (ev) { zone.addEventListener(ev, function () { zone.classList.add('is-drag'); }); });
        ['dragleave', 'drop'].forEach(function (ev) { zone.addEventListener(ev, function () { zone.classList.remove('is-drag'); }); });
        input.addEventListener('change', function () {
            var file = input.files && input.files[0];
            zone.classList.toggle('has-file', !!file);
            if (label) label.textContent = file ? file.name : label.dataset.empty;
            var preview = zone.querySelector('.upload-preview');
            if (file && file.type.indexOf('image/') === 0) {
                if (!preview) { preview = document.createElement('img'); preview.className = 'upload-preview'; preview.alt = ''; zone.appendChild(preview); }
                preview.src = URL.createObjectURL(file);
            } else if (preview) {
                preview.remove();
            }
        });
    });

    // ---------- ปุ่ม/ฟอร์มที่ต้องยืนยันก่อน: data-confirm="ข้อความ" ----------
    document.addEventListener('click', function (e) {
        var el = e.target.closest('[data-confirm]');
        if (el && el.tagName !== 'FORM' && !window.confirm(el.dataset.confirm)) e.preventDefault();
    });
    document.querySelectorAll('form[data-confirm]').forEach(function (form) {
        form.addEventListener('submit', function (e) { if (!window.confirm(form.dataset.confirm)) e.preventDefault(); });
    });

    // ---------- กันกดส่งฟอร์มซ้ำ ----------
    document.querySelectorAll('form[data-once]').forEach(function (form) {
        form.addEventListener('submit', function () {
            setTimeout(function () {
                form.querySelectorAll('button[type=submit]').forEach(function (b) { b.disabled = true; });
            }, 0);
        });
    });

    // ---------- แจ้งเตือนสั้น ๆ: window.toast('ข้อความ') ----------
    window.toast = function (message) {
        var stack = document.querySelector('.toast-stack');
        if (!stack) { stack = document.createElement('div'); stack.className = 'toast-stack'; stack.setAttribute('role', 'status'); document.body.appendChild(stack); }
        var el = document.createElement('div');
        el.className = 'toast';
        el.textContent = message;
        stack.appendChild(el);
        setTimeout(function () { el.remove(); }, 3500);
    };
})();
