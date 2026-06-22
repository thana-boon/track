<?php
$role = (string)($role ?? 'teacher');
$years = $years ?? [];
$yearId = (int)($yearId ?? 0);
$term = (int)($term ?? 1);
$term = ($term === 2) ? 2 : 1;
$rooms = $rooms ?? [];
$selectedLevel = (string)($selectedLevel ?? '');
$selectedRoom = (int)($selectedRoom ?? 0);
$ym = (string)($ym ?? '');
$students = $students ?? [];
$dates = $dates ?? [];
$dailyMap = $dailyMap ?? [];
$totals = $totals ?? [];

$roomLabel = ($selectedLevel !== '' && $selectedRoom > 0) ? ($selectedLevel . '/' . (string)$selectedRoom) : '';

$basePath = '/tracks/class_room';
$baseQuery = [
  'year_id' => $yearId,
  'term' => $term,
  'class_level' => $selectedLevel,
  'class_room' => $selectedRoom,
  'ym' => $ym,
];
?>

<div class="grid gap-6">
  <section class="rounded-3xl border border-base-300 bg-base-100/80 p-5 shadow-sm backdrop-blur">
    <div class="flex flex-wrap items-end justify-between gap-3">
      <div>
        <h1 class="text-xl font-semibold tracking-tight">🏫 ดูนักเรียนรายห้อง<?= $roomLabel !== '' ? ' • ' . e($roomLabel) : '' ?></h1>
        <p class="mt-1 text-sm text-base-content/70">ตารางรายวันจะสร้างจาก “รอบเรียน” ที่มีนักเรียนห้องนี้อยู่</p>
      </div>

      <div class="flex flex-wrap items-center gap-2">
        <?php if ($role === 'admin'): ?>
          <div class="flex items-center gap-2">
            <label class="text-xs font-medium text-base-content/70">ปีการศึกษา</label>
            <select id="crYear" class="rounded-xl border border-base-300 bg-base-100 px-3 py-2 text-sm outline-none focus:border-calm-500" onchange="(function(){var y=document.getElementById('crYear'); var t=document.getElementById('crTerm'); if(!y||!t) return; location.href='<?= e((string)$basePath) ?>?'+new URLSearchParams({year_id:y.value,term:t.value,ym:'<?= e($ym) ?>'}).toString();})()">
              <?php foreach ($years as $y): ?>
                <?php
                  $id = (int)($y['id'] ?? 0);
                  $label = (string)($y['title'] ?? '') . ' (' . (string)($y['year_be'] ?? '') . ')';
                  $active = ((int)($y['is_active'] ?? 0) === 1) ? ' • active' : '';
                ?>
                <option value="<?= $id ?>" <?= $id === $yearId ? 'selected' : '' ?>><?= e($label . $active) ?></option>
              <?php endforeach; ?>
            </select>
          </div>

          <div class="flex items-center gap-2">
            <label class="text-xs font-medium text-base-content/70">เทอม</label>
            <select id="crTerm" class="rounded-xl border border-base-300 bg-base-100 px-3 py-2 text-sm outline-none focus:border-calm-500" onchange="(function(){var y=document.getElementById('crYear'); var t=document.getElementById('crTerm'); if(!y||!t) return; location.href='<?= e((string)$basePath) ?>?'+new URLSearchParams({year_id:y.value,term:t.value,class_level:'<?= e($selectedLevel) ?>',class_room:'<?= (int)$selectedRoom ?>',ym:'<?= e($ym) ?>'}).toString();})()">
              <option value="1" <?= $term === 1 ? 'selected' : '' ?>>เทอม 1</option>
              <option value="2" <?= $term === 2 ? 'selected' : '' ?>>เทอม 2</option>
            </select>
          </div>

          <div class="flex items-center gap-2">
            <label class="text-xs font-medium text-base-content/70">ชั้น/ห้อง</label>
            <select class="rounded-xl border border-base-300 bg-base-100 px-3 py-2 text-sm outline-none focus:border-calm-500" onchange="(function(v){var parts=v.split('|'); var lvl=parts[0]||''; var room=parts[1]||''; location.href='<?= e((string)$basePath) ?>?'+new URLSearchParams({year_id:'<?= (int)$yearId ?>',term:'<?= (int)$term ?>',class_level:lvl,class_room:room,ym:'<?= e($ym) ?>'}).toString();})(this.value)">
              <?php foreach ($rooms as $r): ?>
                <?php
                  $lvl = (string)($r['class_level'] ?? '');
                  $room = (int)($r['class_room'] ?? 0);
                  $count = (int)($r['student_count'] ?? 0);
                  $val = $lvl . '|' . (string)$room;
                  $sel = ($lvl === $selectedLevel && $room === $selectedRoom);
                ?>
                <option value="<?= e($val) ?>" <?= $sel ? 'selected' : '' ?>><?= e($lvl) ?>/<?= (int)$room ?> (<?= $count ?> คน)</option>
              <?php endforeach; ?>
            </select>
          </div>
        <?php else: ?>
          <div class="text-xs text-base-content/60">แสดงเฉพาะห้องที่ครูประจำชั้นรับผิดชอบ</div>
        <?php endif; ?>

        <?php
          // Thai month/year selector setup
          $ymParts   = explode('-', $ym);
          $ymCeYear  = (int)($ymParts[0] ?? date('Y'));
          $ymMonth   = (int)($ymParts[1] ?? date('n'));
          $crMonths  = ['มกราคม','กุมภาพันธ์','มีนาคม','เมษายน','พฤษภาคม','มิถุนายน','กรกฎาคม','สิงหาคม','กันยายน','ตุลาคม','พฤศจิกายน','ธันวาคม'];
          $crYears   = range((int)date('Y') - 2, (int)date('Y') + 1);
        ?>
        <div class="flex items-center gap-1">
          <select id="crMonthSel" onchange="crGoYm(this.value, document.getElementById('crYearSel').value)"
            class="rounded-xl border border-base-300 bg-base-100 px-3 py-2 text-sm outline-none focus:border-calm-500">
            <?php for ($m = 1; $m <= 12; $m++): ?>
              <option value="<?= str_pad((string)$m, 2, '0', STR_PAD_LEFT) ?>" <?= $m === $ymMonth ? 'selected' : '' ?>><?= e($crMonths[$m - 1]) ?></option>
            <?php endfor; ?>
          </select>
          <select id="crYearSel" onchange="crGoYm(document.getElementById('crMonthSel').value, this.value)"
            class="rounded-xl border border-base-300 bg-base-100 px-3 py-2 text-sm outline-none focus:border-calm-500">
            <?php foreach ($crYears as $ceY): ?>
              <option value="<?= $ceY ?>" <?= $ceY === $ymCeYear ? 'selected' : '' ?>><?= $ceY + 543 ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <script>
        function crGoYm(m, y) {
          location.href = '<?= e((string)$basePath) ?>?' + new URLSearchParams({
            year_id: '<?= (int)$yearId ?>', term: '<?= (int)$term ?>',
            class_level: '<?= e($selectedLevel) ?>', class_room: '<?= (int)$selectedRoom ?>',
            ym: y + '-' + m
          }).toString();
        }
        </script>

        <?php
          $printQs = http_build_query(array_filter([
            'year_id'     => $yearId,
            'term'        => $term,
            'class_level' => $selectedLevel,
            'class_room'  => $selectedRoom,
            'ym'          => $ym,
          ], static fn($v) => $v !== '' && $v !== 0));
          $printHref  = '/tracks/class_room_print'  . ($printQs !== '' ? '?' . $printQs : '');
          $exportHref = '/tracks/class_room_export' . ($printQs !== '' ? '?' . $printQs : '');
        ?>
        <a href="<?= e($printHref) ?>"
           class="inline-flex items-center gap-1.5 rounded-xl border border-base-300 bg-base-100 px-3 py-2 text-sm hover:bg-base-200">
          🖨️ ดู PDF
        </a>
        <a href="<?= e($exportHref) ?>"
           class="inline-flex items-center gap-1.5 rounded-xl border border-base-300 bg-base-100 px-3 py-2 text-sm hover:bg-base-200">
          📊 Export Excel
        </a>
      </div>
    </div>

    <div class="mt-4 overflow-hidden rounded-3xl border border-base-300 bg-base-100/80">
      <div class="overflow-auto">
        <table class="min-w-full bg-base-100 text-sm">
          <thead class="bg-base-200 text-left text-xs text-base-content/70">
            <tr>
              <th class="sticky left-0 z-[1] bg-base-200 px-4 py-3" rowspan="2">นักเรียน</th>
              <th class="bg-base-200 px-4 py-3" rowspan="2">มา</th>
              <th class="bg-base-200 px-4 py-3" rowspan="2">ขาด</th>
              <?php foreach ($dates as $d): ?>
                <?php
                  $ts = strtotime((string)$d);
                  $day = $ts !== false ? (int)date('j', $ts) : 0;
                  $month = $ts !== false ? (int)date('n', $ts) : 0;
                  $year = $ts !== false ? (int)date('Y', $ts) + 543 : 0;
                  $thaiMonths = [
                    1 => 'มกราคม',
                    2 => 'กุมภาพันธ์',
                    3 => 'มีนาคม',
                    4 => 'เมษายน',
                    5 => 'พฤษภาคม',
                    6 => 'มิถุนายน',
                    7 => 'กรกฎาคม',
                    8 => 'สิงหาคม',
                    9 => 'กันยายน',
                    10 => 'ตุลาคม',
                    11 => 'พฤศจิกายน',
                    12 => 'ธันวาคม',
                  ];
                  $dateLabel = ($day > 0 && $month > 0 && $year > 0) ? ($day . ' ' . ($thaiMonths[$month] ?? (string)$month) . ' ' . $year) : (string)$d;
                ?>
                <th class="bg-base-200 px-2 py-4 text-center" colspan="2" title="<?= e(thai_date_long((string)$d)) ?>">
                  <div class="mx-auto flex h-24 w-8 items-center justify-center select-none">
                    <div class="whitespace-nowrap text-[11px] font-medium text-base-content" style="transform: rotate(-90deg); transform-origin: center;">
                      <?= e($dateLabel) ?>
                    </div>
                  </div>
                </th>
              <?php endforeach; ?>
              <th class="bg-base-200 px-4 py-3" rowspan="2">ผลรายวิชา</th>
            </tr>
            <tr>
              <?php foreach ($dates as $_): ?>
                <th class="bg-base-200 px-2 py-3 text-center text-[11px]">เข้าเรียน</th>
                <th class="bg-base-200 px-2 py-3 text-center text-[11px]">ผลการเรียน</th>
              <?php endforeach; ?>
            </tr>
          </thead>
          <tbody class="divide-y divide-base-300">
            <?php foreach ($students as $st): ?>
              <?php
                $code = (string)($st['student_code'] ?? '');
                $name = trim((string)($st['first_name'] ?? '') . ' ' . (string)($st['last_name'] ?? ''));
                $no = (string)($st['number_in_room'] ?? '');
                $present = (int)($totals[$code]['present'] ?? 0);
                $absent = (int)($totals[$code]['absent'] ?? 0);

                $studentQs = http_build_query(array_filter([
                  'year_id' => $yearId,
                  'term' => $term,
                  'student_code' => $code,
                  'return_year_id' => $yearId,
                  'return_term' => $term,
                  'return_class_level' => $selectedLevel,
                  'return_class_room' => $selectedRoom,
                  'return_ym' => $ym,
                ], static fn($v) => $v !== '' && $v !== 0));
                $studentHref = '/tracks/class_room_student' . ($studentQs !== '' ? ('?' . $studentQs) : '');
              ?>
              <tr class="hover:bg-base-200">
                <td class="sticky left-0 bg-base-100 px-4 py-3">
                  <div class="flex flex-wrap items-center gap-2">
                    <div class="font-medium"><?= e(($no !== '' ? $no . '. ' : '') . ($name !== '' ? $name : $code)) ?></div>
                    <span class="rounded-full bg-pastel-sky/60 px-2.5 py-1 text-xs text-base-content/70 ring-1 ring-base-300"><?= e($code) ?></span>
                  </div>
                </td>
                <td class="px-4 py-3"><span class="inline-flex items-center rounded-full bg-emerald-50 px-2.5 py-1 text-xs text-emerald-900 ring-1 ring-emerald-200"><?= $present ?></span></td>
                <td class="px-4 py-3"><span class="inline-flex items-center rounded-full bg-red-50 px-2.5 py-1 text-xs text-red-900 ring-1 ring-red-200"><?= $absent ?></span></td>

                <?php foreach ($dates as $d): ?>
                  <?php
                    $attStatus = (string)($dailyMap[$code][$d]['attend'] ?? '');
                    $resStatus = (string)($dailyMap[$code][$d]['result'] ?? '');

                    $attCell = '—';
                    $attCls = 'bg-base-200 text-base-content/70 ring-1 ring-base-300';
                    if ($attStatus === 'present') { $attCell = 'มา'; $attCls = 'bg-emerald-50 text-emerald-900 ring-1 ring-emerald-200'; }
                    if ($attStatus === 'absent') { $attCell = 'ขาด'; $attCls = 'bg-red-50 text-red-900 ring-1 ring-red-200'; }

                    $resCell = '—';
                    $resCls = 'bg-base-200 text-base-content/70 ring-1 ring-base-300';
                    if ($resStatus === 'pass') { $resCell = 'ผ่าน'; $resCls = 'bg-emerald-50 text-emerald-900 ring-1 ring-emerald-200'; }
                    if ($resStatus === 'fail') { $resCell = 'ไม่ผ่าน'; $resCls = 'bg-red-50 text-red-900 ring-1 ring-red-200'; }
                    if ($resStatus === 'excellent') { $resCell = 'ยอดเยี่ยม'; $resCls = 'bg-amber-50 text-amber-900 ring-1 ring-amber-200'; }
                  ?>
                  <td class="px-2 py-3 text-center">
                    <span class="inline-flex min-w-[44px] justify-center rounded-full px-2 py-1 text-[11px] <?= $attCls ?>"><?= e($attCell) ?></span>
                  </td>
                  <td class="px-2 py-3 text-center">
                    <span class="inline-flex min-w-[54px] justify-center rounded-full px-2 py-1 text-[11px] <?= $resCls ?>"><?= e($resCell) ?></span>
                  </td>
                <?php endforeach; ?>

                <td class="px-4 py-3">
                  <a class="inline-flex items-center rounded-2xl border border-base-300 bg-base-100 px-3 py-2 text-xs hover:bg-base-200" href="<?= e($studentHref) ?>">ดูผล →</a>
                </td>
              </tr>
            <?php endforeach; ?>

            <?php if (empty($students)): ?>
              <tr>
                <td colspan="<?= 4 + (count($dates) * 2) ?>" class="px-4 py-10 text-center text-sm text-base-content/70">ไม่พบนักเรียนในห้องนี้</td>
              </tr>
            <?php endif; ?>
          </tbody>
        </table>
      </div>

      <div class=”border-t border-base-300 bg-base-100 px-4 py-3 text-xs text-base-content/60”>
        สถานะต่อวัน: เข้าเรียน “มา/ขาด/—“ (รวมทุกวิชาในวันนั้น) • ผลการเรียน “ยอดเยี่ยม/ผ่าน/ไม่ผ่าน/—“ (รวมทุกวิชาในวันนั้น)
      </div>
    </div>
  </section>
</div>
