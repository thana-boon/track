<?php
$sessionsForDate = $sessionsForDate ?? [];
$sessionDatesInMonth = $sessionDatesInMonth ?? [];
$sessionDate = (string)($sessionDate ?? '');
$yearId = (int)($yearId ?? 0);
$term = (int)($term ?? 1);
$term = ($term === 2) ? 2 : 1;
$csrf = (string)($csrf ?? csrf_token());

$sessionDateDisplay = '';
if ($sessionDate !== '') {
  $ts = strtotime($sessionDate);
  if ($ts !== false) {
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
    $d = (int)date('j', $ts);
    $m = (int)date('n', $ts);
    $y = (int)date('Y', $ts) + 543;
    $sessionDateDisplay = $d . ' ' . ($thaiMonths[$m] ?? (string)$m) . ' ' . $y;
  }
}

?>

<div class="grid gap-6">
  <section class="rounded-3xl border border-black/5 bg-white/80 p-5 shadow-sm backdrop-blur">
    <div class="flex flex-wrap items-end justify-between gap-3">
      <div>
        <h1 class="text-xl font-semibold tracking-tight">📅 ตารางเรียน (ตามวัน)</h1>
        <p class="mt-1 text-sm text-ink-800/70">เลือกวัน แล้วดูว่าวันนั้นมีเรียนอะไรบ้าง • กดเข้าไปเช็คชื่อได้</p>
      </div>

      <div class="flex flex-wrap items-center gap-2">
        <?php $me = auth_user(); $role = (string)(is_array($me) ? ($me['role'] ?? 'teacher') : 'teacher'); ?>
        <?php if ($role === 'admin'): ?>
          <a class="rounded-2xl bg-calm-600 px-4 py-2.5 text-sm font-medium text-white shadow-sm hover:bg-calm-500" href="/tracks/class_attendance_create?year_id=<?= (int)$yearId ?>&term=<?= (int)$term ?>">➕ สร้างรอบเรียน</a>
        <?php endif; ?>
      </div>
    </div>

    <?php if (!empty($success)): ?>
      <div class="mt-4 rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-900"><?= e((string)$success) ?></div>
    <?php endif; ?>

    <?php if (!empty($error)): ?>
      <div class="mt-4 rounded-2xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-900"><?= e((string)$error) ?></div>
    <?php endif; ?>

    <div class="mt-4 overflow-hidden rounded-3xl border border-black/5 bg-white/80">
      <div class="flex flex-wrap items-center justify-between gap-2 bg-sand-100 px-4 py-3">
        <div class="flex items-center gap-2">
          <label class="text-xs font-medium text-ink-800/70">ปีการศึกษา</label>
          <select id="attYear" class="rounded-xl border border-black/10 bg-white px-3 py-2 text-sm outline-none focus:border-calm-500" onchange="(function(){var y=document.getElementById('attYear'); var t=document.getElementById('attTerm'); if(!y||!t) return; location.href='/tracks/class_attendance?year_id='+encodeURIComponent(y.value)+'&term='+encodeURIComponent(t.value);})()">
            <?php foreach (($years ?? []) as $y): ?>
              <?php
                $id = (int)($y['id'] ?? 0);
                $label = (string)($y['title'] ?? '') . ' (' . (string)($y['year_be'] ?? '') . ')';
                $active = ((int)($y['is_active'] ?? 0) === 1) ? ' • active' : '';
              ?>
              <option value="<?= $id ?>" <?= $id === $yearId ? 'selected' : '' ?>><?= e($label . $active) ?></option>
            <?php endforeach; ?>
          </select>

          <label class="text-xs font-medium text-ink-800/70">เทอม</label>
          <select id="attTerm" class="rounded-xl border border-black/10 bg-white px-3 py-2 text-sm outline-none focus:border-calm-500" onchange="(function(){var y=document.getElementById('attYear'); var t=document.getElementById('attTerm'); if(!y||!t) return; location.href='/tracks/class_attendance?year_id='+encodeURIComponent(y.value)+'&term='+encodeURIComponent(t.value);})()">
            <option value="1" <?= $term === 1 ? 'selected' : '' ?>>เทอม 1</option>
            <option value="2" <?= $term === 2 ? 'selected' : '' ?>>เทอม 2</option>
          </select>
        </div>

        <div class="flex items-center gap-2">
          <label class="text-xs font-medium text-ink-800/70">เลือกวัน</label>
          <input
            type="date"
            value="<?= e($sessionDate) ?>"
            class="rounded-xl border border-black/10 bg-white px-3 py-2 text-sm outline-none focus:border-calm-500"
            onchange="location.href='/tracks/class_attendance?year_id=<?= (int)$yearId ?>&term=<?= (int)$term ?>&session_date='+encodeURIComponent(this.value)"
          />
          <?php if ($sessionDateDisplay !== ''): ?>
            <span class="text-xs text-ink-800/60">(<?= e($sessionDateDisplay) ?>)</span>
          <?php endif; ?>
        </div>
      </div>

      <div class="border-t border-black/5 bg-white px-4 py-3">
        <div class="text-xs text-ink-800/60">วันในเดือนนี้ที่มีรอบเรียน:</div>
        <div class="mt-2 flex flex-wrap gap-2">
          <?php foreach ($sessionDatesInMonth as $d): ?>
            <?php
              $ts = strtotime((string)$d);
              $day = $ts !== false ? (int)date('j', $ts) : 0;
              $active = ((string)$d === (string)$sessionDate);
              $href = '/tracks/class_attendance?year_id=' . (int)$yearId . '&term=' . (int)$term . '&session_date=' . urlencode((string)$d);
            ?>
            <a class="inline-flex items-center rounded-full px-3 py-1 text-xs ring-1 ring-black/5 <?= $active ? 'bg-calm-600 text-white' : 'bg-sand-100 text-ink-900 hover:bg-black/5' ?>" href="<?= e($href) ?>"><?= $day > 0 ? (int)$day : e((string)$d) ?></a>
          <?php endforeach; ?>

          <?php if (empty($sessionDatesInMonth)): ?>
            <span class="text-xs text-ink-800/60">— ยังไม่มีรอบเรียนในเดือนนี้</span>
          <?php endif; ?>
        </div>
      </div>

      <div class="px-4 py-3 text-xs text-ink-800/60"><?= e(thai_date_long($sessionDate)) ?></div>

      <div class="overflow-auto">
        <table class="min-w-full bg-white text-sm">
          <thead class="bg-sand-50 text-left text-xs text-ink-800/70">
            <tr>
              <th class="px-4 py-3">วิชา</th>
              <th class="px-4 py-3">หมายเหตุ</th>
              <th class="px-4 py-3">จำนวน</th>
              <th class="px-4 py-3">ไปเช็คชื่อ</th>
              <?php if ($role === 'admin'): ?>
                <th class="px-4 py-3">จัดการ</th>
              <?php endif; ?>
            </tr>
          </thead>
          <tbody class="divide-y divide-black/5">
            <?php foreach ($sessionsForDate as $sess): ?>
              <?php
                $id = (int)($sess['id'] ?? 0);
                $subj = (string)($sess['subject_title'] ?? '');
                $note = trim((string)($sess['note'] ?? ''));
                $count = (int)($sess['student_count'] ?? 0);
                $href = '/tracks/class_attendance_view?id=' . $id
                  . '&term=' . (int)$term
                  . '&return_year=' . (int)$yearId
                  . '&return_date=' . urlencode((string)$sessionDate);

                $editHref = '/tracks/class_attendance_edit?id=' . $id
                  . '&term=' . (int)$term
                  . '&return_year=' . (int)$yearId
                  . '&return_date=' . urlencode((string)$sessionDate);
              ?>
              <tr class="hover:bg-sand-50">
                <td class="px-4 py-3">
                  <div class="font-medium"><?= e($subj) ?></div>
                </td>
                <td class="px-4 py-3 text-xs text-ink-800/60"><?= e($note !== '' ? $note : '—') ?></td>
                <td class="px-4 py-3">
                  <span class="inline-flex items-center rounded-full bg-pastel-sky/60 px-2.5 py-1 text-xs text-ink-800/70 ring-1 ring-black/5"><?= $count ?> คน</span>
                </td>
                <td class="px-4 py-3">
                  <a class="inline-flex items-center rounded-2xl border border-black/10 bg-white px-3 py-2 text-xs hover:bg-black/5" href="<?= e($href) ?>">เช็คชื่อ →</a>
                </td>
                <?php if ($role === 'admin'): ?>
                  <td class="px-4 py-3">
                    <div class="flex flex-wrap items-center gap-2">
                      <a class="inline-flex items-center rounded-2xl border border-black/10 bg-white px-3 py-2 text-xs hover:bg-black/5" href="<?= e($editHref) ?>">✏️ แก้ไข</a>
                      <form method="post" action="/tracks/class_attendance_delete" data-confirm="ต้องการลบรอบเรียนนี้ใช่ไหม? การเช็คชื่อ/ผลในรอบนี้จะหายไปด้วย">
                        <input type="hidden" name="_csrf" value="<?= e($csrf) ?>" />
                        <input type="hidden" name="session_id" value="<?= (int)$id ?>" />
                        <input type="hidden" name="term" value="<?= (int)$term ?>" />
                        <input type="hidden" name="return_year" value="<?= (int)$yearId ?>" />
                        <input type="hidden" name="return_date" value="<?= e((string)$sessionDate) ?>" />
                        <button class="inline-flex items-center rounded-2xl border border-red-200 bg-red-50 px-3 py-2 text-xs text-red-800 hover:bg-red-100">🗑️ ลบ</button>
                      </form>
                    </div>
                  </td>
                <?php endif; ?>
              </tr>
            <?php endforeach; ?>

            <?php if (empty($sessionsForDate)): ?>
              <tr>
                <td colspan="<?= $role === 'admin' ? 5 : 4 ?>" class="px-4 py-10 text-center text-sm text-ink-800/70">วันนี้ยังไม่มีรอบเรียน</td>
              </tr>
            <?php endif; ?>
          </tbody>
        </table>
      </div>

      <div class="border-t border-black/5 bg-white px-4 py-3 text-xs text-ink-800/60">
        เคล็ดลับ: สร้างรอบเรียนได้หลายรอบในวันเดียวกัน (คนละกลุ่มนักเรียน)
      </div>
    </div>
  </section>
</div>
