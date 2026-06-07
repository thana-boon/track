<?php
$yearId       = (int)($yearId ?? 0);
$years        = $years ?? [];
$term         = (int)($term ?? 1);
$rooms        = $rooms ?? [];
$role         = (string)($role ?? 'teacher');
$defaultLevel = (string)($defaultLevel ?? '');
$defaultRoom  = (int)($defaultRoom ?? 0);
$defaultYm    = (string)($defaultYm ?? date('Y-m'));

$defaultRoomKey = $defaultLevel !== '' && $defaultRoom > 0 ? ($defaultLevel . '|' . $defaultRoom) : '';
$defaultYmStart = $defaultYm;
$defaultYmEnd   = $defaultYm;

// Thai month selector setup
$ymParts       = explode('-', $defaultYm);
$exCeYear      = (int)($ymParts[0] ?? date('Y'));
$exMonth       = (int)($ymParts[1] ?? date('n'));
$exMonthNames  = ['มกราคม','กุมภาพันธ์','มีนาคม','เมษายน','พฤษภาคม','มิถุนายน','กรกฎาคม','สิงหาคม','กันยายน','ตุลาคม','พฤศจิกายน','ธันวาคม'];
$exYearRange   = range((int)date('Y') - 2, (int)date('Y') + 1);

// Group rooms by level
$grouped = [];
foreach ($rooms as $r) {
    $lvl = (string)($r['class_level'] ?? '');
    $grouped[$lvl][] = $r;
}

$basePath = '/tracks/class_room_export';
?>

<div class="grid gap-6">
  <section class="rounded-3xl border border-black/5 bg-white/80 p-5 shadow-sm backdrop-blur">
    <div class="flex flex-wrap items-end justify-between gap-3">
      <div>
        <h1 class="text-xl font-semibold tracking-tight">📊 Export ผลการเรียน</h1>
        <p class="mt-1 text-sm text-ink-800/70">เลือกห้องและช่วงเดือน แล้วดาวน์โหลดไฟล์ CSV (เปิดใน Excel ได้)</p>
      </div>
      <a class="rounded-2xl border border-black/10 bg-white px-4 py-2 text-sm hover:bg-black/5" href="/tracks/class_room?year_id=<?= (int)$yearId ?>&amp;term=<?= (int)$term ?>&amp;class_level=<?= urlencode($defaultLevel) ?>&amp;class_room=<?= (int)$defaultRoom ?>&amp;ym=<?= urlencode($defaultYm) ?>">← กลับ</a>
    </div>

    <form method="get" action="<?= e($basePath) ?>" class="mt-5 space-y-6">
      <input type="hidden" name="export" value="1" />
      <input type="hidden" name="term" value="<?= (int)$term ?>" />

      <!-- Year (admin only) -->
      <?php if ($role === 'admin'): ?>
        <div class="flex flex-wrap items-center gap-4">
          <div class="flex items-center gap-2">
            <label class="text-sm font-medium">ปีการศึกษา</label>
            <select name="year_id" class="rounded-xl border border-black/10 bg-white px-3 py-2 text-sm outline-none focus:border-calm-500">
              <?php foreach ($years as $y): ?>
                <?php
                  $yid   = (int)($y['id'] ?? 0);
                  $ylbl  = (string)($y['title'] ?? '') . ' (' . (string)($y['year_be'] ?? '') . ')';
                  $act   = (int)($y['is_active'] ?? 0) === 1 ? ' • active' : '';
                ?>
                <option value="<?= $yid ?>" <?= $yid === $yearId ? 'selected' : '' ?>><?= e($ylbl . $act) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
        </div>
      <?php else: ?>
        <input type="hidden" name="year_id" value="<?= (int)$yearId ?>" />
      <?php endif; ?>

      <!-- Room checkboxes -->
      <div>
        <div class="mb-3 flex items-center gap-3">
          <p class="text-sm font-semibold">เลือกห้องเรียน</p>
          <button type="button" onclick="(function(){document.querySelectorAll('.room-cb').forEach(function(c){c.checked=true});})()" class="rounded-lg border border-black/10 bg-white px-2.5 py-1 text-xs hover:bg-black/5">เลือกทั้งหมด</button>
          <button type="button" onclick="(function(){document.querySelectorAll('.room-cb').forEach(function(c){c.checked=false});})()" class="rounded-lg border border-black/10 bg-white px-2.5 py-1 text-xs hover:bg-black/5">ยกเลิกทั้งหมด</button>
        </div>

        <?php if (empty($rooms)): ?>
          <p class="text-sm text-ink-800/60">ไม่พบห้องเรียนในปีการศึกษานี้</p>
        <?php endif; ?>

        <?php foreach ($grouped as $lvl => $lvlRooms): ?>
          <div class="mb-3">
            <div class="mb-1.5 text-xs font-semibold text-ink-800/60 uppercase tracking-wide"><?= e($lvl) ?></div>
            <div class="flex flex-wrap gap-2">
              <?php foreach ($lvlRooms as $r): ?>
                <?php
                  $rl  = (string)($r['class_level'] ?? '');
                  $rr  = (int)($r['class_room'] ?? 0);
                  $cnt = (int)($r['student_count'] ?? 0);
                  $key = $rl . '|' . (string)$rr;
                  $checked = ($key === $defaultRoomKey) ? 'checked' : '';
                ?>
                <label class="flex cursor-pointer items-center gap-1.5 rounded-xl border border-black/10 bg-white px-3 py-2 text-sm hover:bg-sand-50 has-[:checked]:border-calm-400 has-[:checked]:bg-calm-50">
                  <input type="checkbox" name="rooms[]" value="<?= e($key) ?>" class="room-cb accent-calm-500" <?= $checked ?> />
                  <span><?= e($rl) ?>/<?= (int)$rr ?></span>
                  <span class="text-xs text-ink-800/50">(<?= $cnt ?> คน)</span>
                </label>
              <?php endforeach; ?>
            </div>
          </div>
        <?php endforeach; ?>
      </div>

      <!-- Month range (Thai) -->
      <div>
        <p class="mb-3 text-sm font-semibold">ช่วงเดือน (พ.ศ.)</p>

        <input type="hidden" name="ym_start" id="exYmStartVal" value="<?= e($defaultYmStart) ?>" />
        <input type="hidden" name="ym_end"   id="exYmEndVal"   value="<?= e($defaultYmEnd) ?>" />

        <div class="flex flex-wrap items-center gap-3">
          <div class="flex items-center gap-1.5">
            <label class="text-sm text-ink-800/70">ตั้งแต่</label>
            <select id="exStartMonth" onchange="exUpdYm('Start')"
              class="rounded-xl border border-black/10 bg-white px-3 py-2 text-sm outline-none focus:border-calm-500">
              <?php for ($m = 1; $m <= 12; $m++): ?>
                <option value="<?= str_pad((string)$m, 2, '0', STR_PAD_LEFT) ?>" <?= $m === $exMonth ? 'selected' : '' ?>><?= e($exMonthNames[$m - 1]) ?></option>
              <?php endfor; ?>
            </select>
            <select id="exStartYear" onchange="exUpdYm('Start')"
              class="rounded-xl border border-black/10 bg-white px-3 py-2 text-sm outline-none focus:border-calm-500">
              <?php foreach ($exYearRange as $ceY): ?>
                <option value="<?= $ceY ?>" <?= $ceY === $exCeYear ? 'selected' : '' ?>><?= $ceY + 543 ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <span class="text-ink-800/40">—</span>
          <div class="flex items-center gap-1.5">
            <label class="text-sm text-ink-800/70">ถึง</label>
            <select id="exEndMonth" onchange="exUpdYm('End')"
              class="rounded-xl border border-black/10 bg-white px-3 py-2 text-sm outline-none focus:border-calm-500">
              <?php for ($m = 1; $m <= 12; $m++): ?>
                <option value="<?= str_pad((string)$m, 2, '0', STR_PAD_LEFT) ?>" <?= $m === $exMonth ? 'selected' : '' ?>><?= e($exMonthNames[$m - 1]) ?></option>
              <?php endfor; ?>
            </select>
            <select id="exEndYear" onchange="exUpdYm('End')"
              class="rounded-xl border border-black/10 bg-white px-3 py-2 text-sm outline-none focus:border-calm-500">
              <?php foreach ($exYearRange as $ceY): ?>
                <option value="<?= $ceY ?>" <?= $ceY === $exCeYear ? 'selected' : '' ?>><?= $ceY + 543 ?></option>
              <?php endforeach; ?>
            </select>
          </div>
        </div>
        <p class="mt-1.5 text-xs text-ink-800/50">ข้อมูลจะรวมทุกวิชาที่มีในช่วงเดือนที่เลือก</p>
      </div>
      <script>
      function exUpdYm(which) {
        var m = document.getElementById('ex' + which + 'Month').value;
        var y = document.getElementById('ex' + which + 'Year').value;
        document.getElementById('exYm' + which + 'Val').value = y + '-' + m;
      }
      </script>

      <!-- Submit -->
      <div class="flex items-center gap-3 border-t border-black/5 pt-4">
        <button type="submit"
          class="inline-flex items-center gap-2 rounded-2xl bg-emerald-600 px-5 py-2.5 text-sm font-medium text-white hover:bg-emerald-700 focus:outline-none">
          📥 ดาวน์โหลด Excel / CSV
        </button>
        <p class="text-xs text-ink-800/50">ไฟล์ .csv รองรับ Excel, Google Sheets (รองรับภาษาไทย)</p>
      </div>
    </form>
  </section>

  <!-- Format info -->
  <section class="rounded-3xl border border-black/5 bg-white/80 p-5 shadow-sm backdrop-blur">
    <h2 class="text-sm font-semibold">รูปแบบข้อมูลใน CSV</h2>
    <div class="mt-3 overflow-hidden rounded-2xl border border-black/5">
      <table class="w-full text-xs">
        <thead class="bg-sand-50 text-left">
          <tr>
            <th class="px-3 py-2 font-semibold">คอลัมน์</th>
            <th class="px-3 py-2 font-semibold">ตัวอย่าง</th>
            <th class="px-3 py-2 font-semibold">หมายเหตุ</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-black/5">
          <tr><td class="px-3 py-1.5">ห้อง</td><td class="px-3 py-1.5 font-mono">ม.5/1</td><td class="px-3 py-1.5 text-ink-800/60">ระดับชั้น/ห้อง</td></tr>
          <tr><td class="px-3 py-1.5">เลขที่</td><td class="px-3 py-1.5 font-mono">1</td><td class="px-3 py-1.5 text-ink-800/60">เลขที่ในห้อง</td></tr>
          <tr><td class="px-3 py-1.5">ชื่อ</td><td class="px-3 py-1.5 font-mono">นายธนิก</td><td class="px-3 py-1.5 text-ink-800/60">ชื่อต้น</td></tr>
          <tr><td class="px-3 py-1.5">นามสกุล</td><td class="px-3 py-1.5 font-mono">คมปรียารัตน์</td><td class="px-3 py-1.5 text-ink-800/60">นามสกุล</td></tr>
          <tr><td class="px-3 py-1.5">รหัสนักเรียน</td><td class="px-3 py-1.5 font-mono">03167</td><td class="px-3 py-1.5 text-ink-800/60"></td></tr>
          <tr><td class="px-3 py-1.5">วิชา</td><td class="px-3 py-1.5 font-mono">คณิตศาสตร์สูง</td><td class="px-3 py-1.5 text-ink-800/60">1 แถวต่อ 1 วิชา</td></tr>
          <tr><td class="px-3 py-1.5">ผล</td><td class="px-3 py-1.5 font-mono">ยอดเยี่ยม / ผ่าน / ไม่ผ่าน / รอดำเนินการ</td><td class="px-3 py-1.5 text-ink-800/60">ผลรวมของเดือนที่เลือก</td></tr>
          <tr><td class="px-3 py-1.5">วันที่ล่าสุด</td><td class="px-3 py-1.5 font-mono">2026-04-26</td><td class="px-3 py-1.5 text-ink-800/60">รอบเรียนล่าสุด</td></tr>
        </tbody>
      </table>
    </div>
  </section>
</div>
