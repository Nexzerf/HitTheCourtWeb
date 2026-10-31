// assets/js/booking.js
// การทำงานของหน้าจอง: เลือกสนามจากแผนที่, เลือกช่วงเวลา, ปรับจำนวนอุปกรณ์ และขอราคาจากเซิร์ฟเวอร์
(function () {
    var cfg = window.BOOKING;
    var form = document.getElementById('bookingForm');
    if (!cfg || !form) return;

    var courtInput = document.getElementById('courtInput');
    var placeholder = document.getElementById('slotPlaceholder');
    var summary = document.getElementById('summary');
    var submitBtn = document.getElementById('submitBtn');
    var mobileBar = document.getElementById('mobileBar');
    var mobileTotal = document.getElementById('mobileTotal');
    var mobileSubmit = document.getElementById('mobileSubmit');

    // ---------- เลือกสนาม ----------
    document.querySelectorAll('.court-btn').forEach(function (btn) {
        btn.addEventListener('click', function () {
            if (btn.classList.contains('is-full')) return;
            selectCourt(btn.dataset.court);
        });
    });

    function selectCourt(courtId) {
        courtInput.value = courtId;
        document.querySelectorAll('.court-btn').forEach(function (b) {
            var on = b.dataset.court === courtId;
            b.classList.toggle('is-selected', on);
            b.setAttribute('aria-pressed', on ? 'true' : 'false');
        });
        // แสดงเฉพาะช่วงเวลาของสนามที่เลือก (ช่องของสนามอื่นถูกปิดไม่ให้ส่งไปกับฟอร์ม)
        document.querySelectorAll('[data-slots-for]').forEach(function (grid) {
            var on = grid.dataset.slotsFor === courtId;
            grid.hidden = !on;
            grid.querySelectorAll('input').forEach(function (inp) {
                if (!on) { inp.checked = false; inp.dataset.off = '1'; inp.disabled = true; }
                else if (inp.dataset.off) { delete inp.dataset.off; inp.disabled = inp.closest('.slot').classList.contains('slot-free') ? false : true; }
            });
        });
        placeholder.hidden = true;
        var grid = document.querySelector('[data-slots-for="' + courtId + '"]');
        if (grid && window.innerWidth < 1025) grid.scrollIntoView({ behavior: 'smooth', block: 'center' });
        refresh();
    }
    // ปิดทุกช่องก่อนเลือกสนาม
    document.querySelectorAll('[data-slots-for] input').forEach(function (inp) { inp.dataset.off = '1'; inp.disabled = true; });

    // ---------- เลือกช่วงเวลา ----------
    form.addEventListener('change', function (e) {
        if (e.target.name === 'slot_ids[]' || e.target.name === 'use_points' || e.target.name === 'payment_method') refresh();
    });

    function selectedSlotIds() {
        return Array.prototype.map.call(form.querySelectorAll('input[name="slot_ids[]"]:checked'), function (i) { return i.value; });
    }

    // ---------- จำนวนอุปกรณ์ ----------
    function equipLimit(eqId) {
        var info = cfg.equipment[eqId];
        if (!info) return 0;
        var limit = info.perCourt;
        selectedSlotIds().forEach(function (slotId) {
            if (info.bySlot[slotId] !== undefined) limit = Math.min(limit, info.bySlot[slotId]);
        });
        return limit;
    }
    function clampEquipment() {
        document.querySelectorAll('.equip').forEach(function (row) {
            var input = row.querySelector('.qty-input');
            var max = equipLimit(row.dataset.eq);
            input.max = max;
            var v = Math.max(0, Math.min(max, parseInt(input.value, 10) || 0));
            input.value = v;
            row.querySelector('[data-step="-1"]').disabled = v <= 0;
            row.querySelector('[data-step="1"]').disabled = v >= max;
        });
    }
    document.querySelectorAll('.equip').forEach(function (row) {
        var input = row.querySelector('.qty-input');
        row.querySelectorAll('.qty-btn').forEach(function (b) {
            b.addEventListener('click', function () {
                input.value = (parseInt(input.value, 10) || 0) + parseInt(b.dataset.step, 10);
                clampEquipment();
                refresh();
            });
        });
        input.addEventListener('input', function () { clampEquipment(); refresh(); });
    });

    // ---------- ขอราคาจากเซิร์ฟเวอร์ ----------
    var timer = null, seq = 0;
    function refresh() {
        clampEquipment();
        clearTimeout(timer);
        timer = setTimeout(fetchQuote, 150);
    }
    function fetchQuote() {
        var mine = ++seq;
        if (!courtInput.value || selectedSlotIds().length === 0) {
            summary.innerHTML = '<p class="muted mb-0">' + escapeHtml(cfg.text.selectCourt) + '</p>';
            setReady(false, null);
            return;
        }
        fetch(cfg.quoteUrl, { method: 'POST', body: new FormData(form), credentials: 'same-origin' })
            .then(function (r) { return r.json(); })
            .then(function (res) {
                if (mine !== seq) return;
                summary.innerHTML = res.html;
                setReady(res.ok, res.total_text);
            })
            .catch(function () {
                if (mine !== seq) return;
                summary.innerHTML = '<div class="alert alert-danger">' + escapeHtml(cfg.text.error) + '</div>';
                setReady(false, null);
            });
    }
    function setReady(ok, totalText) {
        submitBtn.disabled = !ok;
        if (mobileSubmit) mobileSubmit.disabled = !ok;
        if (mobileBar) {
            mobileBar.hidden = !totalText;
            if (totalText) mobileTotal.textContent = totalText;
        }
    }
    if (mobileSubmit) mobileSubmit.addEventListener('click', function () {
        if (typeof form.requestSubmit === 'function') form.requestSubmit(submitBtn); else submitBtn.click();
    });

    // ตรวจข้อมูลผู้จองก่อนส่ง
    form.addEventListener('submit', function (e) {
        var bad = Array.prototype.find.call(form.querySelectorAll('.input[required]'), function (i) { return !i.checkValidity(); });
        if (bad) {
            e.preventDefault();
            e.stopImmediatePropagation();
            bad.reportValidity();
            bad.focus();
        }
    }, true);

    function escapeHtml(s) { var d = document.createElement('div'); d.textContent = s; return d.innerHTML; }

    // โหมดแก้ไข: เติมสนาม ช่วงเวลา และอุปกรณ์เดิมไว้ให้
    if (cfg.preset) {
        Object.keys(cfg.preset.equipment || {}).forEach(function (eqId) {
            var inp = form.querySelector('.equip[data-eq="' + eqId + '"] .qty-input');
            if (inp) inp.value = cfg.preset.equipment[eqId];
        });
        if (cfg.preset.court && document.querySelector('.court-btn[data-court="' + cfg.preset.court + '"]')) {
            selectCourt(cfg.preset.court);
            cfg.preset.slots.forEach(function (id) {
                var inp = form.querySelector('[data-slots-for="' + cfg.preset.court + '"] input[value="' + id + '"]');
                if (inp && !inp.disabled) inp.checked = true;
            });
            refresh();
        }
    }

    // ถ้ามีสนามเดียว ให้เลือกไว้ให้เลย
    var firstFree = document.querySelector('.court-btn:not(.is-full)');
    if (!courtInput.value && document.querySelectorAll('.court-btn').length === 1 && firstFree) selectCourt(firstFree.dataset.court);
    clampEquipment();
})();
