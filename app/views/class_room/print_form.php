<?php
$yearId       = (int)($yearId ?? 0);
$years        = $years ?? [];
$term         = (int)($term ?? 1);
$rooms        = $rooms ?? [];
$role         = (string)($role ?? 'teacher');
$defaultLevel = (string)($defaultLevel ?? '');
$defaultRoom  = (int)($defaultRoom ?? 0);
$defaultYm    = (string)($defaultYm ?? date('Y-m'));

$defaultRoomKey = ($defaultLevel !== '' && $defaultRoom > 0) ? ($defaultLevel . '|' . $defaultRoom) : '';

// Parse default ym for Thai selectors
$ymParts        = explode('-', $defaultYm);
$defaultCeYear  = (int)($ymParts[0] ?? date('Y'));
$defaultMonth   = (int)($ymParts[1] ?? date('n'));

$thaiMonthNames = ['มกราคม','กุมภาพันธ์','มีนาคม','เมษายน','พฤษภาคม','มิถุนายน','กรกฎาคม','สิงหาคม','กันยายน','ตุลาคม','พฤศจิกายน','ธันวาคม'];
$ceYearRange    = range((int)date('Y') - 2, (int)date('Y') + 1);

$grouped = [];
foreach ($rooms as $r) {
    $grouped[(string)($r['class_level'] ?? '')][] = $r;
}
?>

<div class="grid gap-6">
  <section class="rounded-3xl border border-black/5 bg-white/80 p-5 shadow-sm backdrop-blur">
    <div class="flex flex-wrap items-end justify-between gap-3">
      <div>
        <h1 class="text-xl font-semibold tracking-tight">🖨️ รายงาน PDF การเข้าเรียน</h1>
        <p class="mt-1 text-sm text-ink-800/70">เลือกห้องและช่วงเดือน แล้วดูรายงานในแท็บใหม่</p>
      </div>
      <a class="rounded-2xl border border-black/10 bg-white px-4 py-2 text-sm hover:bg-black/5"
         href="/tracks/class_room?year_id=<?= (int)$yearId ?>&amp;term=<?= (int)$term ?>&amp;class_level=<?= urlencode($defaultLevel) ?>&amp;class_room=<?= (int)$defaultRoom ?>&amp;ym=<?= urlencode($defaultYm) ?>">← กลับ</a>
    </div>

    <form method="get" action="/tracks/class_room_print" target="_blank" class="mt-5 space-y-6">
      <input type="hidden" name="term" value="<?= (int)$term ?>" />

      <!-- Year (admin only) -->
      <?php if ($role === 'admin'): ?>
        <div class="flex items-center gap-2">
          <label class="text-sm font-medium">ปีการศึกษา</label>
          <select name="year_id" class="rounded-xl border border-black/10 bg-white px-3 py-2 text-sm outline-none focus:border-calm-500">
            <?php foreach ($years as $y): ?>
              <?php $yid = (int)($y['id'] ?? 0); $yact = (int)($y['is_active'] ?? 0) === 1 ? ' • active' : ''; ?>
              <option value="<?= $yid ?>" <?= $yid === $yearId ? 'selected' : '' ?>><?= e((string)($y['title'] ?? '') . ' (' . (string)($y['year_be'] ?? '') . ')' . $yact) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
      <?php else: ?>
        <input type="hidden" name="year_id" value="<?= (int)$yearId ?>" />
      <?php endif; ?>

      <!-- Room checkboxes -->
      <div>
        <div class="mb-3 flex items-center gap-3">
          <p class="text-sm font-semibold">เลือกห้องเรียน</p>
          <button type="button" onclick="document.querySelectorAll('.pr-room-cb').forEach(function(c){c.checked=true})"
            class="rounded-lg border border-black/10 bg-white px-2.5 py-1 text-xs hover:bg-black/5">เลือกทั้งหมด</button>
          <button type="button" onclick="document.querySelectorAll('.pr-room-cb').forEach(function(c){c.checked=false})"
            class="rounded-lg border border-black/10 bg-white px-2.5 py-1 text-xs hover:bg-black/5">ยกเลิก</button>
        </div>
        <?php if (empty($rooms)): ?>
          <p class="text-sm text-ink-800/60">ไม่พบห้องเรียน</p>
        <?php endif; ?>
        <?php foreach ($grouped as $lvl => $lvlRooms): ?>
          <div class="mb-3">
            <div class="mb-1.5 text-xs font-semibold uppercase tracking-wide text-ink-800/50"><?= e($lvl) ?></div>
            <div class="flex flex-wrap gap-2">
              <?php foreach ($lvlRooms as $r): ?>
                <?php
                  $rl  = (string)($r['class_level'] ?? '');
                  $rr  = (int)($r['class_room'] ?? 0);
                  $cnt = (int)($r['student_count'] ?? 0);
                  $key = $rl . '|' . $rr;
                ?>
                <label class="flex cursor-pointer items-center gap-1.5 rounded-xl border border-black/10 bg-white px-3 py-2 text-sm hover:bg-sand-50 has-[:checked]:border-calm-400 has-[:checked]:bg-calm-50">
                  <input type="checkbox" name="rooms[]" value="<?= e($key) ?>" class="pr-room-cb accent-calm-500"
                    <?= $key === $defaultRoomKey ? 'checked' : '' ?> />
                  <?= e($rl) ?>/<?= $rr ?> <span class="text-xs text-ink-800/50">(<?= $cnt ?> คน)</span>
                </label>
              <?php endforeach; ?>
            </div>
          </div>
        <?php endforeach; ?>
      </div>

      <!-- Month range (Thai) -->
      <div>
        <p class="mb-3 text-sm font-semibold">ช่วงเดือน (พ.ศ.)</p>

        <!-- Hidden inputs for form submission -->
        <input type="hidden" name="ym_start" id="prYmStartVal" value="<?= e($defaultYm) ?>" />
        <input type="hidden" name="ym_end"   id="prYmEndVal"   value="<?= e($defaultYm) ?>" />

        <div class="flex flex-wrap items-center gap-3">
          <div class="flex items-center gap-1.5">
            <label class="text-sm text-ink-800/70">ตั้งแต่</label>
            <select id="prStartMonth" onchange="prUpdYm('Start')"
              class="rounded-xl border border-black/10 bg-white px-3 py-2 text-sm outline-none focus:border-calm-500">
              <?php for ($m = 1; $m <= 12; $m++): ?>
                <option value="<?= str_pad((string)$m, 2, '0', STR_PAD_LEFT) ?>" <?= $m === $defaultMonth ? 'selected' : '' ?>><?= e($thaiMonthNames[$m - 1]) ?></option>
              <?php endfor; ?>
            </select>
            <select id="prStartYear" onchange="prUpdYm('Start')"
              class="rounded-xl border border-black/10 bg-white px-3 py-2 text-sm outline-none focus:border-calm-500">
              <?php foreach ($ceYearRange as $ceY): ?>
                <option value="<?= $ceY ?>" <?= $ceY === $defaultCeYear ? 'selected' : '' ?>><?= $ceY + 543 ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <span class="text-ink-800/40">—</span>
          <div class="flex items-center gap-1.5">
            <label class="text-sm text-ink-800/70">ถึง</label>
            <select id="prEndMonth" onchange="prUpdYm('End')"
              class="rounded-xl border border-black/10 bg-white px-3 py-2 text-sm outline-none focus:border-calm-500">
              <?php for ($m = 1; $m <= 12; $m++): ?>
                <option value="<?= str_pad((string)$m, 2, '0', STR_PAD_LEFT) ?>" <?= $m === $defaultMonth ? 'selected' : '' ?>><?= e($thaiMonthNames[$m - 1]) ?></option>
              <?php endfor; ?>
            </select>
            <select id="prEndYear" onchange="prUpdYm('End')"
              class="rounded-xl border border-black/10 bg-white px-3 py-2 text-sm outline-none focus:border-calm-500">
              <?php foreach ($ceYearRange as $ceY): ?>
                <option value="<?= $ceY ?>" <?= $ceY === $defaultCeYear ? 'selected' : '' ?>><?= $ceY + 543 ?></option>
              <?php endforeach; ?>
            </select>
          </div>
        </div>
        <p class="mt-1.5 text-xs text-ink-800/50">แสดงคอลัมน์วันที่+วิชาตามรอบเรียนที่มีจริงในช่วงที่เลือก</p>
      </div>

      <div class="flex items-center gap-3 border-t border-black/5 pt-4">
        <button type="submit"
          class="inline-flex items-center gap-2 rounded-2xl bg-teal-600 px-5 py-2.5 text-sm font-medium text-white hover:bg-teal-700">
          🖨️ ดูรายงาน PDF (เปิดแท็บใหม่)
        </button>
        <p class="text-xs text-ink-800/50">รายงานจะเปิดในแท็บใหม่ พร้อมกล่องพิมพ์อัตโนมัติ</p>
      </div>
    </form>
  </section>
</div>

<script>
function prUpdYm(which) {
  var m = document.getElementById('pr' + which + 'Month').value;
  var y = document.getElementById('pr' + which + 'Year').value;
  document.getElementById('prYm' + which + 'Val').value = y + '-' + m;
}
</script>
