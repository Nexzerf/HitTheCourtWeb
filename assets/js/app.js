// assets/js/app.js
// สคริปต์กลางของหน้าที่ใช้ดีไซน์ใหม่: แท็บ, ช่องอัปโหลดไฟล์, ยืนยันก่อนกด, แจ้งเตือน
(function () {
    var BASE = ((document.currentScript && document.currentScript.src) || '').replace(/\/assets\/js\/app\.js.*$/, '');
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

    function shrinkImage(file, max, done) {
        var img = new Image(), url = URL.createObjectURL(file);
        img.onload = function () {
            var scale = Math.min(1, max / Math.max(img.naturalWidth, img.naturalHeight));
            var c = document.createElement('canvas');
            c.width = Math.round(img.naturalWidth * scale); c.height = Math.round(img.naturalHeight * scale);
            var ctx = c.getContext('2d'); ctx.fillStyle = '#fff'; ctx.fillRect(0, 0, c.width, c.height);
            ctx.drawImage(img, 0, 0, c.width, c.height);
            URL.revokeObjectURL(url);
            c.toBlob(done, 'image/jpeg', 0.85);
        };
        img.onerror = function () { URL.revokeObjectURL(url); done(null); };
        img.src = url;
    }

    // ---------- ตรวจว่ารูปสลิปมี QR code (ใช้ jsQR ในเบราว์เซอร์) ----------
    function loadJsQR(done) {
        if (window.jsQR) return done();
        var s = document.createElement('script');
        s.src = BASE + '/assets/js/jsqr.js';
        s.onload = done; s.onerror = done;
        document.head.appendChild(s);
    }
    function scanQR(file, done) {
        loadJsQR(function () {
            if (!window.jsQR) return done(null);
            var img = new Image(), url = URL.createObjectURL(file);
            img.onload = function () {
                var found = null, sizes = [1000, 1600, 700, 2200];
                for (var i = 0; i < sizes.length && !found; i++) {
                    var scale = Math.min(1, sizes[i] / Math.max(img.naturalWidth, img.naturalHeight));
                    var c = document.createElement('canvas');
                    c.width = Math.max(1, Math.round(img.naturalWidth * scale)); c.height = Math.max(1, Math.round(img.naturalHeight * scale));
                    var ctx = c.getContext('2d'); ctx.fillStyle = '#fff'; ctx.fillRect(0, 0, c.width, c.height);
                    ctx.drawImage(img, 0, 0, c.width, c.height);
                    var data = ctx.getImageData(0, 0, c.width, c.height);
                    var code = window.jsQR(data.data, c.width, c.height, { inversionAttempts: 'attemptBoth' });
                    if (code && code.data) found = code.data;
                    if (scale === 1) break;
                }
                URL.revokeObjectURL(url);
                done(found);
            };
            img.onerror = function () { URL.revokeObjectURL(url); done(null); };
            img.src = url;
        });
    }
    document.querySelectorAll('input[type=file][data-require-qr]').forEach(function (input) {
        var form = input.form;
        var hidden = form.querySelector('input[name=qr_text]');
        var status = form.querySelector('[data-qr-status]');
        var state = 'empty';
        var show = function (kind, text) {
            if (!status) return;
            status.hidden = !text;
            status.className = 'qr-status qr-' + kind;
            status.textContent = text || '';
            var zone = input.closest('.upload');
            if (zone) zone.classList.toggle('is-invalid', kind === 'bad');
        };
        input.addEventListener('change', function () {
            var file = input.files && input.files[0];
            hidden.value = '';
            if (!file) { state = 'empty'; show('', ''); return; }
            if (input._compressing) return;
            state = 'scanning';
            show('wait', status.dataset.scanning);
            scanQR(file, function (text) {
                if (input.files[0] !== file) return;
                state = text ? 'ok' : 'bad';
                hidden.value = text ? text.slice(0, 500) : '';
                show(text ? 'ok' : 'bad', text ? status.dataset.ok : status.dataset.bad);
            });
        });
        form.addEventListener('submit', function (e) {
            if (state === 'ok') return;
            e.preventDefault();
            if (state === 'scanning') show('wait', status.dataset.scanning);
            else if (state === 'empty') show('bad', status.dataset.empty);
            else show('bad', status.dataset.bad);
            status.scrollIntoView({ behavior: 'smooth', block: 'center' });
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
            // รูปจากมือถือมักใหญ่เกิน 4 MB หรือเป็นชนิดแปลก ๆ -> ย่อเป็น JPEG ก่อนส่ง
            if (file && input.hasAttribute('data-compress') && !input._compressed && file.type.indexOf('image/') === 0
                && (file.size > 1500000 || !/^image\/(jpeg|png)$/.test(file.type)) && window.DataTransfer) {
                input._compressing = true;
                shrinkImage(file, 1600, function (blob) {
                    input._compressing = false;
                    if (!blob) return;
                    var dt = new DataTransfer();
                    dt.items.add(new File([blob], file.name.replace(/\.[^.]+$/, '') + '.jpg', { type: 'image/jpeg' }));
                    input.files = dt.files;
                    input._compressed = true;
                    input.dispatchEvent(new Event('change'));
                    input._compressed = false;
                });
                return;
            }
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
        form.addEventListener('submit', function (e) {
            setTimeout(function () {
                if (e.defaultPrevented) return;
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
