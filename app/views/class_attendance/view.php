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
  <section class="rounded-3xl border border-black/5 bg-white/80 p-5 shadow-sm backdrop-blur">
    <div class="flex flex-wrap items-end justify-between gap-3">
      <div>
        <h1 class="text-xl font-semibold tracking-tight">✅ เช็คชื่อ: <?= e($subj) ?></h1>
        <p class="mt-1 text-sm text-ink-800/70"><?= e($line2) ?></p>
      </div>

      <div class="flex flex-wrap items-center gap-2">
        <a class="rounded-2xl border border-black/10 bg-white px-4 py-2.5 text-sm hover:bg-black/5" href="<?= e($backHref) ?>">← รายการรอบเรียน</a>
        <a target="_blank" rel="noopener" class="rounded-2xl bg-ink-900 px-4 py-2.5 text-sm font-medium text-white shadow-sm hover:opacity-95" href="<?= e($printHref) ?>">🖨️ ใบเช็คชื่อ</a>
      </div>
    </div>

    <?php if (!empty($success)): ?>
      <div class="mt-4 rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-900"><?= e((string)$success) ?></div>
    <?php endif; ?>

    <?php if (!empty($error)): ?>
      <div class="mt-4 rounded-2xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-900"><?= e((string)$error) ?></div>
    <?php endif; ?>

    <?php if (!empty($passNote)): ?>
      <div class="mt-4 rounded-2xl border border-black/10 bg-sand-50 px-4 py-3 text-xs text-ink-800/70"><?= e((string)$passNote) ?></div>
    <?php endif; ?>

    <form id="attendanceForm" class="mt-4 overflow-hidden rounded-3xl border border-black/5 bg-white/80" method="post">
      <input type="hidden" name="_csrf" value="<?= e((string)$csrf) ?>" />
      <input type="hidden" name="action" value="save_check" />

      <div class="flex flex-wrap items-center justify-between gap-2 bg-sand-100 px-4 py-3">
        <div class="text-sm font-semibold">👥 รายชื่อนักเรียน (<?= count($roster) ?> คน)</div>
        <div class="flex flex-wrap items-center gap-2">
          <button type="button" class="rounded-2xl border border-black/10 bg-white px-3 py-2.5 text-sm hover:bg-black/5" onclick="(function(){document.querySelectorAll('select[name^=\'morning[\']').forEach(function(el){el.value='1';});})();">🌅 มาเช้าทั้งหมด</button>
          <button type="button" class="rounded-2xl border border-black/10 bg-white px-3 py-2.5 text-sm hover:bg-black/5" onclick="(function(){document.querySelectorAll('select[name^=\'afternoon[\']').forEach(function(el){el.value='1';});})();">🌇 มาบ่ายทั้งหมด</button>
          <button type="button" class="rounded-2xl border border-black/10 bg-white px-3 py-2 text-sm hover:bg-black/5" onclick="(function(){document.querySelectorAll('select[name^=\'result[\']').forEach(function(el){el.value='excellent';});})();">⭐ ยอดเยี่ยมทั้งหมด</button>
          <button type="button" class="rounded-2xl border border-black/10 bg-white px-3 py-2 text-sm hover:bg-black/5" onclick="(function(){document.querySelectorAll('select[name^=\'result[\']').forEach(function(el){el.value='pass';});})();">🟢 ผ่านทั้งหมด</button>
          <button class="rounded-2xl bg-calm-600 px-4 py-2 text-sm font-medium text-white shadow-sm hover:bg-calm-500">💾 บันทึก</button>
        </div>
      </div>

      <div class="divide-y divide-black/5">
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
              <div class="flex flex-wrap items-center gap-3 px-4 py-3 hover:bg-sand-50">
                <div class="min-w-0 flex-1">
                  <div class="flex flex-wrap items-center gap-1.5">
                    <span class="font-medium text-sm"><?= e($name !== '' ? $name : $code) ?></span>
                    <span class="rounded-full bg-pastel-sky/60 px-2 py-0.5 text-xs text-ink-800/70 ring-1 ring-black/5"><?= e($code) ?></span>
                  </div>
                  <?php if ($meta !== '/ เลขที่'): ?>
                    <div class="mt-0.5 text-xs text-ink-800/60"><?= e($meta) ?></div>
                  <?php endif; ?>
                </div>
                <div class="flex flex-wrap items-end gap-2">
                  <div class="flex flex-col gap-0.5">
                    <span class="text-[10px] text-ink-800/60 pl-1">เช้า</span>
                    <select name="morning[<?= e($code) ?>]" class="rounded-xl border border-black/10 bg-white px-3 py-2.5 text-sm outline-none focus:border-calm-500 min-w-[88px]">
                      <option value="" <?= $morningVal === '' ? 'selected' : '' ?>>—</option>
                      <option value="1" <?= $morningVal === '1' ? 'selected' : '' ?>>✅ มา</option>
                      <option value="0" <?= $morningVal === '0' ? 'selected' : '' ?>>❌ ขาด</option>
                    </select>
                  </div>
                  <div class="flex flex-col gap-0.5">
                    <span class="text-[10px] text-ink-800/60 pl-1">บ่าย</span>
                    <select name="afternoon[<?= e($code) ?>]" class="rounded-xl border border-black/10 bg-white px-3 py-2.5 text-sm outline-none focus:border-calm-500 min-w-[88px]">
                      <option value="" <?= $afternoonVal === '' ? 'selected' : '' ?>>—</option>
                      <option value="1" <?= $afternoonVal === '1' ? 'selected' : '' ?>>✅ มา</option>
                      <option value="0" <?= $afternoonVal === '0' ? 'selected' : '' ?>>❌ ขาด</option>
                    </select>
                  </div>
                  <div class="flex flex-col gap-0.5">
                    <span class="text-[10px] text-ink-800/60 pl-1">ผล</span>
                    <select name="result[<?= e($code) ?>]" class="rounded-xl border border-black/10 bg-white px-3 py-2.5 text-sm outline-none focus:border-calm-500 min-w-[130px]">
                      <option value="pending" <?= $res === 'pending' ? 'selected' : '' ?>>⏳ รอดำเนินการ</option>
                      <option value="excellent" <?= $res === 'excellent' ? 'selected' : '' ?>>⭐ ยอดเยี่ยม</option>
                      <option value="pass" <?= $res === 'pass' ? 'selected' : '' ?>>🟢 ผ่าน</option>
                      <option value="fail" <?= $res === 'fail' ? 'selected' : '' ?>>🔴 ไม่ผ่าน</option>
                    </select>
                  </div>
                </div>
              </div>
            <?php endforeach; ?>

            <?php if (empty($roster)): ?>
              <div class="px-4 py-10 text-center text-sm text-ink-800/70">ยังไม่มีรายชื่อนักเรียนในรอบเรียนนี้</div>
            <?php endif; ?>
        </div>

      <div class="flex items-center justify-between gap-2 border-t border-black/5 bg-white px-4 py-3">
        <div class="text-xs text-ink-800/60">บันทึกแล้วจะคงค่าที่เลือกไว้</div>
        <button class="rounded-2xl bg-calm-600 px-4 py-2 text-sm font-medium text-white shadow-sm hover:bg-calm-500">💾 บันทึก</button>
      </div>
    </form>

    <script>
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
