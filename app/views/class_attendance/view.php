<?php
$csrf = (string)($csrf ?? csrf_token());
$session = $session ?? null;
$roster = $roster ?? [];

$sessionId = is_array($session) ? (int)($session['id'] ?? 0) : 0;
$subj = is_array($session) ? (string)($session['subject_title'] ?? '') : '';
$date = is_array($session) ? (string)($session['session_date'] ?? '') : '';
$note = is_array($session) ? trim((string)($session['note'] ?? '')) : '';

$returnDate = (string)($returnDate ?? '');
$returnYear = (int)($returnYear ?? 0);
$term = (int)($term ?? 1);
$term = ($term === 2) ? 2 : 1;
$backYear = $returnYear > 0 ? $returnYear : (is_array($session) ? (int)($session['year_id'] ?? 0) : 0);
$backDate = $returnDate !== '' ? $returnDate : $date;
$backQs = http_build_query(array_filter([
  'year_id' => $backYear,
  'term' => $term,
  'session_date' => $backDate,
], static fn($v) => $v !== '' && $v !== 0));
$backHref = '/tracks/class_attendance' . ($backQs !== '' ? ('?' . $backQs) : '');

$line2 = thai_date_long($date);
if ($note !== '') {
  $line2 .= ' • ' . $note;
}

$printHref = '/tracks/class_attendance_print?id=' . $sessionId . '&cols=6';
?>

<div class="grid gap-6">
  <section class="card border border-base-300 bg-base-100/80 p-5 shadow-sm backdrop-blur">
    <div class="flex flex-wrap items-end justify-between gap-3">
      <div>
        <h1 class="text-xl font-semibold tracking-tight">✅ เช็คชื่อ: <?= e($subj) ?></h1>
        <p class="mt-1 text-sm text-base-content/70"><?= e($line2) ?></p>
      </div>

      <div class="flex flex-wrap items-center gap-2">
        <a class="btn btn-ghost rounded-2xl border border-base-300" href="<?= e($backHref) ?>">← รายการรอบเรียน</a>
        <a target="_blank" rel="noopener" class="btn btn-neutral rounded-2xl" href="<?= e($printHref) ?>">🖨️ ใบเช็คชื่อ</a>
      </div>
    </div>

    <?php if (!empty($success)): ?>
      <div role="alert" class="alert alert-success mt-4 text-sm"><span><?= e((string)$success) ?></span></div>
    <?php endif; ?>

    <?php if (!empty($error)): ?>
      <div role="alert" class="alert alert-error mt-4 text-sm"><span><?= e((string)$error) ?></span></div>
    <?php endif; ?>

    <?php if (!empty($passNote)): ?>
      <div class="mt-4 rounded-2xl border border-base-300 bg-base-200 px-4 py-3 text-xs text-base-content/70"><?= e((string)$passNote) ?></div>
    <?php endif; ?>

    <form id="attendanceForm" class="mt-4 overflow-hidden rounded-2xl border border-base-300 bg-base-100" method="post">
      <input type="hidden" name="_csrf" value="<?= e((string)$csrf) ?>" />
      <input type="hidden" name="action" value="save_check" />

      <div class="border-b border-base-300 bg-base-200 px-4 py-4">
        <div class="flex flex-wrap items-center justify-between gap-3">
          <div class="flex items-center gap-2">
            <span class="inline-flex h-8 w-8 items-center justify-center rounded-full bg-base-100 text-base shadow-sm ring-1 ring-base-300">👥</span>
            <span class="text-sm font-semibold text-base-content">รายชื่อนักเรียน</span>
            <span class="badge badge-sm border-none bg-base-100 font-medium text-primary"><?= count($roster) ?> คน</span>
          </div>
          <button class="btn btn-neutral btn-sm rounded-2xl">💾 บันทึก</button>
        </div>

        <div class="mt-3 flex flex-wrap items-center gap-1.5">
          <span class="mr-0.5 text-[11px] font-medium uppercase tracking-wide text-base-content/40">ทางลัด</span>
          <button type="button" class="btn btn-xs rounded-full border-base-300 bg-base-100 font-normal text-base-content/80 hover:bg-base-200" onclick="(function(){document.querySelectorAll('select[name^=\'morning[\']').forEach(function(el){el.value='1';});repaintAllAtt();})();">🌅 มาเช้าทั้งหมด</button>
          <button type="button" class="btn btn-xs rounded-full border-base-300 bg-base-100 font-normal text-base-content/80 hover:bg-base-200" onclick="(function(){document.querySelectorAll('select[name^=\'afternoon[\']').forEach(function(el){el.value='1';});repaintAllAtt();})();">🌇 มาบ่ายทั้งหมด</button>
          <button type="button" class="btn btn-xs rounded-full border-base-300 bg-base-100 font-normal text-base-content/80 hover:bg-base-200" onclick="(function(){document.querySelectorAll('select[name^=\'result[\']').forEach(function(el){el.value='excellent';});repaintAllAtt();})();">⭐ ยอดเยี่ยมทั้งหมด</button>
          <button type="button" class="btn btn-xs rounded-full border-base-300 bg-base-100 font-normal text-base-content/80 hover:bg-base-200" onclick="(function(){document.querySelectorAll('select[name^=\'result[\']').forEach(function(el){el.value='pass';});repaintAllAtt();})();">🟢 ผ่านทั้งหมด</button>
          <button type="button" class="btn btn-xs rounded-full border-primary/30 bg-primary/15 font-medium text-primary hover:bg-primary/25" onclick="autoEvalResults()">🎯 ประเมินผลอัตโนมัติ</button>
        </div>
      </div>

      <div class="divide-y divide-base-300">
            <?php foreach ($roster as $r): ?>
              <?php
                $code = (string)($r['student_code'] ?? '');
                $name = trim((string)($r['first_name'] ?? '') . ' ' . (string)($r['last_name'] ?? ''));
                $meta = trim((string)($r['class_level'] ?? '') . '/' . (string)($r['class_room'] ?? '') . ' เลขที่ ' . (string)($r['number_in_room'] ?? ''));
                $att = $r['attend_status'];
                $res = (string)($r['result_status'] ?? 'pending');
                $attVal = $att === null ? '' : ((int)$att === 1 ? '1' : '0');
                $morning = $r['attend_morning'] ?? null;
                $afternoon = $r['attend_afternoon'] ?? null;
                $morningVal = $morning === null ? '' : ((int)$morning === 1 ? '1' : '0');
                $afternoonVal = $afternoon === null ? '' : ((int)$afternoon === 1 ? '1' : '0');
                if (!in_array($res, ['pending', 'excellent', 'pass', 'fail'], true)) $res = 'pending';
              ?>
              <div class="px-4 py-3 transition hover:bg-base-200">
                <div class="flex flex-wrap items-center gap-3">
                  <div class="min-w-0 flex-1">
                    <div class="flex flex-wrap items-center gap-1.5">
                      <span class="font-medium text-sm"><?= e($name !== '' ? $name : $code) ?></span>
                      <span class="badge badge-sm border-none bg-secondary/15 text-base-content/70"><?= e($code) ?></span>
                    </div>
                    <?php if ($meta !== '/ เลขที่'): ?>
                      <div class="mt-0.5 text-xs text-base-content/60"><?= e($meta) ?></div>
                    <?php endif; ?>
                  </div>

                  <!-- เดสก์ท็อป: dropdown เดิม (ยังเป็นค่าที่ถูกบันทึกจริง) -->
                  <div class="hidden md:flex flex-wrap items-end gap-2">
                    <div class="flex flex-col gap-0.5">
                      <span class="text-[10px] text-base-content/60 pl-1">เช้า</span>
                      <select name="morning[<?= e($code) ?>]" class="select select-bordered select-sm min-w-[88px]">
                        <option value="" <?= $morningVal === '' ? 'selected' : '' ?>>—</option>
                        <option value="1" <?= $morningVal === '1' ? 'selected' : '' ?>>✅ มา</option>
                        <option value="0" <?= $morningVal === '0' ? 'selected' : '' ?>>❌ ขาด</option>
                      </select>
                    </div>
                    <div class="flex flex-col gap-0.5">
                      <span class="text-[10px] text-base-content/60 pl-1">บ่าย</span>
                      <select name="afternoon[<?= e($code) ?>]" class="select select-bordered select-sm min-w-[88px]">
                        <option value="" <?= $afternoonVal === '' ? 'selected' : '' ?>>—</option>
                        <option value="1" <?= $afternoonVal === '1' ? 'selected' : '' ?>>✅ มา</option>
                        <option value="0" <?= $afternoonVal === '0' ? 'selected' : '' ?>>❌ ขาด</option>
                      </select>
                    </div>
                    <div class="flex flex-col gap-0.5">
                      <span class="text-[10px] text-base-content/60 pl-1">ผล</span>
                      <select name="result[<?= e($code) ?>]" class="select select-bordered select-sm min-w-[130px]">
                        <option value="pending" <?= $res === 'pending' ? 'selected' : '' ?>>⏳ รอดำเนินการ</option>
                        <option value="excellent" <?= $res === 'excellent' ? 'selected' : '' ?>>⭐ ยอดเยี่ยม</option>
                        <option value="pass" <?= $res === 'pass' ? 'selected' : '' ?>>🟢 ผ่าน</option>
                        <option value="fail" <?= $res === 'fail' ? 'selected' : '' ?>>🔴 ไม่ผ่าน</option>
                      </select>
                    </div>
                  </div>
                </div>

                <!-- มือถือ: ปุ่มแตะเลือก (เขียนค่าลง select ด้านบน) -->
                <div class="mt-3 grid gap-2 md:hidden">
                  <div class="flex items-center gap-2">
                    <span class="w-9 shrink-0 text-xs font-medium text-base-content/60">เช้า</span>
                    <div class="att-group grid flex-1 grid-cols-2 gap-2" data-field="morning" data-code="<?= e($code) ?>">
                      <button type="button" data-val="1" data-on="bg-emerald-500 text-white ring-emerald-500" class="att-btn rounded-xl px-3 py-2.5 text-sm font-medium ring-1 ring-base-300 bg-base-200 text-base-content/70 transition-colors">✅ มา</button>
                      <button type="button" data-val="0" data-on="bg-rose-400 text-white ring-rose-400" class="att-btn rounded-xl px-3 py-2.5 text-sm font-medium ring-1 ring-base-300 bg-base-200 text-base-content/70 transition-colors">❌ ขาด</button>
                    </div>
                  </div>
                  <div class="flex items-center gap-2">
                    <span class="w-9 shrink-0 text-xs font-medium text-base-content/60">บ่าย</span>
                    <div class="att-group grid flex-1 grid-cols-2 gap-2" data-field="afternoon" data-code="<?= e($code) ?>">
                      <button type="button" data-val="1" data-on="bg-emerald-500 text-white ring-emerald-500" class="att-btn rounded-xl px-3 py-2.5 text-sm font-medium ring-1 ring-base-300 bg-base-200 text-base-content/70 transition-colors">✅ มา</button>
                      <button type="button" data-val="0" data-on="bg-rose-400 text-white ring-rose-400" class="att-btn rounded-xl px-3 py-2.5 text-sm font-medium ring-1 ring-base-300 bg-base-200 text-base-content/70 transition-colors">❌ ขาด</button>
                    </div>
                  </div>
                  <div class="flex items-center gap-2">
                    <span class="w-9 shrink-0 text-xs font-medium text-base-content/60">ผล</span>
                    <div class="att-group grid flex-1 grid-cols-2 gap-1.5" data-field="result" data-code="<?= e($code) ?>">
                      <button type="button" data-val="excellent" data-on="bg-amber-500 text-white ring-amber-500" class="att-btn rounded-xl px-3 py-2.5 text-sm font-medium ring-1 ring-base-300 bg-base-200 text-base-content/70 transition-colors">⭐ ยอดเยี่ยม</button>
                      <button type="button" data-val="pass" data-on="bg-emerald-500 text-white ring-emerald-500" class="att-btn rounded-xl px-3 py-2.5 text-sm font-medium ring-1 ring-base-300 bg-base-200 text-base-content/70 transition-colors">🟢 ผ่าน</button>
                      <button type="button" data-val="fail" data-on="bg-rose-400 text-white ring-rose-400" class="att-btn rounded-xl px-3 py-2.5 text-sm font-medium ring-1 ring-base-300 bg-base-200 text-base-content/70 transition-colors">🔴 ไม่ผ่าน</button>
                      <button type="button" data-val="pending" data-on="bg-slate-500 text-white ring-slate-500" class="att-btn rounded-xl px-3 py-2.5 text-sm font-medium ring-1 ring-base-300 bg-base-200 text-base-content/70 transition-colors">⏳ รอ</button>
                    </div>
                  </div>
                </div>
              </div>
            <?php endforeach; ?>

            <?php if (empty($roster)): ?>
              <div class="px-4 py-10 text-center text-sm text-base-content/70">ยังไม่มีรายชื่อนักเรียนในรอบเรียนนี้</div>
            <?php endif; ?>
        </div>

      <div class="sticky bottom-0 z-10 flex items-center justify-between gap-2 border-t border-base-300 bg-base-100/95 px-4 py-3 shadow-[0_-4px_12px_rgba(0,0,0,0.06)] backdrop-blur md:shadow-none">
        <div class="hidden text-xs text-base-content/60 sm:block">บันทึกแล้วจะคงค่าที่เลือกไว้</div>
        <button class="btn btn-neutral w-full rounded-2xl sm:w-auto">💾 บันทึก</button>
      </div>
    </form>

    <script>
      // ----- มือถือ: ปุ่มแตะเลือก ↔ <select> -----
      function paintAttGroup(group) {
        if (!group) return;
        var field = group.getAttribute('data-field');
        var code = group.getAttribute('data-code');
        var form = document.getElementById('attendanceForm');
        var cssEsc = (window.CSS && typeof CSS.escape === 'function')
          ? CSS.escape
          : function (s) { return String(s).replace(/[^a-zA-Z0-9_\-]/g, '\\$&'); };
        var sel = form ? form.querySelector("select[name='" + field + "[" + cssEsc(code) + "]']") : null;
        var current = sel ? sel.value : '';
        group.querySelectorAll('.att-btn').forEach(function (btn) {
          var on = (btn.getAttribute('data-on') || '').split(' ').filter(Boolean);
          var idle = ['bg-base-200', 'text-base-content/70', 'ring-base-300'];
          var active = (current !== '' && btn.getAttribute('data-val') === current);
          if (active) {
            idle.forEach(function (c) { btn.classList.remove(c); });
            on.forEach(function (c) { btn.classList.add(c); });
          } else {
            on.forEach(function (c) { btn.classList.remove(c); });
            idle.forEach(function (c) { btn.classList.add(c); });
          }
        });
      }

      function repaintAllAtt() {
        document.querySelectorAll('.att-group').forEach(paintAttGroup);
      }

      (function () {
        var form = document.getElementById('attendanceForm');
        if (!form) return;
        var cssEsc = (window.CSS && typeof CSS.escape === 'function')
          ? CSS.escape
          : function (s) { return String(s).replace(/[^a-zA-Z0-9_\-]/g, '\\$&'); };
        form.addEventListener('click', function (e) {
          var btn = e.target.closest ? e.target.closest('.att-btn') : null;
          if (!btn) return;
          var group = btn.closest('.att-group');
          if (!group) return;
          var field = group.getAttribute('data-field');
          var code = group.getAttribute('data-code');
          var val = btn.getAttribute('data-val');
          var sel = form.querySelector("select[name='" + field + "[" + cssEsc(code) + "]']");
          if (sel) {
            // แตะปุ่มที่เลือกอยู่ซ้ำ = ยกเลิก (กลับเป็น —) เฉพาะเช้า/บ่าย
            if (sel.value === val && field !== 'result') { sel.value = ''; }
            else { sel.value = val; }
          }
          paintAttGroup(group);
        });
        repaintAllAtt();
      })();

      function autoEvalResults() {
        var form = document.getElementById('attendanceForm');
        if (!form) return;
        var cssEsc = (window.CSS && typeof CSS.escape === 'function')
          ? CSS.escape
          : function(s) { return String(s).replace(/[^a-zA-Z0-9_\-]/g, '\\$&'); };
        var results = Array.prototype.slice.call(form.querySelectorAll("select[name^='result[']"));
        var changed = 0;
        results.forEach(function(resEl) {
          var m = String(resEl.name || '').match(/^result\[(.+)\]$/);
          if (!m) return;
          var code = m[1];
          var mEl = form.querySelector("select[name='morning[" + cssEsc(code) + "]']");
          var aEl = form.querySelector("select[name='afternoon[" + cssEsc(code) + "]']");
          var mCame = mEl && mEl.value === '1';
          var aCame = aEl && aEl.value === '1';
          var newVal = (mCame && aCame) ? 'excellent' : (mCame || aCame) ? 'pass' : 'fail';
          if (resEl.value !== newVal) { resEl.value = newVal; changed++; }
        });
        repaintAllAtt();
        if (changed === 0 && typeof Swal !== 'undefined') {
          Swal.fire({ title: 'ไม่มีการเปลี่ยนแปลง', text: 'ผลทุกคนตรงกับการเข้าเรียนอยู่แล้ว', icon: 'info', confirmButtonText: 'ตกลง' });
        }
      }

      (function () {
        var form = document.getElementById('attendanceForm');
        if (!form) return;

        var cssEscape = (window.CSS && typeof CSS.escape === 'function')
          ? CSS.escape
          : function (s) { return String(s).replace(/[^a-zA-Z0-9_\-]/g, '\\$&'); };

        function extractCode(name) {
          var m = String(name || '').match(/^result\[(.+)\]$/);
          return m ? m[1] : '';
        }

        form.addEventListener('submit', function (e) {
          if (form.dataset.confirmed === '1') return;

          var passSelects = Array.prototype.slice.call(form.querySelectorAll("select[name^='result[']"));
          var needConfirm = 0;

          passSelects.forEach(function (resEl) {
            if (!resEl || (resEl.value !== 'pass' && resEl.value !== 'excellent')) return;
            var code = extractCode(resEl.name);
            if (!code) return;
            var morningEl = form.querySelector("select[name='morning[" + cssEscape(code) + "]']");
            var afternoonEl = form.querySelector("select[name='afternoon[" + cssEscape(code) + "]']");
            var morningVal = morningEl ? morningEl.value : '';
            var afternoonVal = afternoonEl ? afternoonEl.value : '';
            if (morningVal !== '1' && afternoonVal !== '1') needConfirm++;
          });

          if (needConfirm <= 0) return;

          e.preventDefault();

          var msg = 'มีนักเรียน ' + needConfirm + ' คน ตั้งผลเป็น “ผ่าน” แต่ยังไม่ได้เลือก “เข้าเรียน”\nต้องการบันทึกจริงๆ ใช่ไหม?';

          if (typeof Swal === 'undefined') return;

          Swal.fire({
            title: 'ยืนยันการบันทึก',
            text: msg,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'ตกลง บันทึก',
            cancelButtonText: 'ยกเลิก'
          }).then(function (r) {
            if (r && r.isConfirmed) {
              form.dataset.confirmed = '1';
              form.submit();
            }
          });
        });
      })();
    </script>
  </section>
</div>
