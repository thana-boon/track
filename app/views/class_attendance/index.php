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
  <section class="card border border-base-300 bg-base-100/80 p-5 shadow-sm backdrop-blur">
    <div class="flex flex-wrap items-end justify-between gap-3">
      <div>
        <h1 class="text-xl font-semibold tracking-tight">📅 ตารางเรียน (ตามวัน)</h1>
        <p class="mt-1 text-sm text-base-content/70">เลือกวัน แล้วดูว่าวันนั้นมีเรียนอะไรบ้าง • กดเข้าไปเช็คชื่อได้</p>
      </div>

      <div class="flex flex-wrap items-center gap-2">
        <?php $me = auth_user(); $role = (string)(is_array($me) ? ($me['role'] ?? 'teacher') : 'teacher'); ?>
        <?php if ($role === 'admin'): ?>
          <a class="btn btn-neutral rounded-2xl" href="/tracks/class_attendance_create?year_id=<?= (int)$yearId ?>&term=<?= (int)$term ?>">➕ สร้างรอบเรียน</a>
        <?php endif; ?>
      </div>
    </div>

    <?php if (!empty($success)): ?>
      <div role="alert" class="alert alert-success mt-4 text-sm"><span><?= e((string)$success) ?></span></div>
    <?php endif; ?>

    <?php if (!empty($error)): ?>
      <div role="alert" class="alert alert-error mt-4 text-sm"><span><?= e((string)$error) ?></span></div>
    <?php endif; ?>

    <div class="mt-4 overflow-hidden rounded-2xl border border-base-300 bg-base-100">
      <div class="flex flex-wrap items-center justify-between gap-2 bg-base-200 px-4 py-3">
        <div class="flex flex-wrap items-center gap-2">
          <label class="text-xs font-medium text-base-content/70">ปีการศึกษา</label>
          <select id="attYear" class="select select-bordered select-sm" onchange="(function(){var y=document.getElementById('attYear'); var t=document.getElementById('attTerm'); if(!y||!t) return; location.href='/tracks/class_attendance?year_id='+encodeURIComponent(y.value)+'&term='+encodeURIComponent(t.value);})()">
            <?php foreach (($years ?? []) as $y): ?>
              <?php
                $id = (int)($y['id'] ?? 0);
                $label = (string)($y['title'] ?? '') . ' (' . (string)($y['year_be'] ?? '') . ')';
                $active = ((int)($y['is_active'] ?? 0) === 1) ? ' • active' : '';
              ?>
              <option value="<?= $id ?>" <?= $id === $yearId ? 'selected' : '' ?>><?= e($label . $active) ?></option>
            <?php endforeach; ?>
          </select>

          <label class="text-xs font-medium text-base-content/70">เทอม</label>
          <select id="attTerm" class="select select-bordered select-sm" onchange="(function(){var y=document.getElementById('attYear'); var t=document.getElementById('attTerm'); if(!y||!t) return; location.href='/tracks/class_attendance?year_id='+encodeURIComponent(y.value)+'&term='+encodeURIComponent(t.value);})()">
            <option value="1" <?= $term === 1 ? 'selected' : '' ?>>เทอม 1</option>
            <option value="2" <?= $term === 2 ? 'selected' : '' ?>>เทอม 2</option>
          </select>
        </div>

        <div class="flex flex-wrap items-center gap-2">
          <label class="text-xs font-medium text-base-content/70">เดือน</label>
          <?php
            $curYear  = (int)substr($sessionDate, 0, 4);
            $curMonth = (int)substr($sessionDate, 5, 2);
            $thaiMonthNames = [
              1=>'มกราคม',2=>'กุมภาพันธ์',3=>'มีนาคม',4=>'เมษายน',
              5=>'พฤษภาคม',6=>'มิถุนายน',7=>'กรกฎาคม',8=>'สิงหาคม',
              9=>'กันยายน',10=>'ตุลาคม',11=>'พฤศจิกายน',12=>'ธันวาคม',
            ];
          ?>
          <select id="attMonth" class="select select-bordered select-sm"
            onchange="(function(){
              var m=document.getElementById('attMonth');
              var yr=document.getElementById('attMonthYear');
              if(!m||!yr) return;
              var mm=m.value.padStart(2,'0');
              location.href='/tracks/class_attendance?year_id=<?= (int)$yearId ?>&term=<?= (int)$term ?>&session_date='+encodeURIComponent(yr.value+'-'+mm+'-01');
            })()">
            <?php foreach ($thaiMonthNames as $mn => $mLabel): ?>
              <option value="<?= $mn ?>" <?= $mn === $curMonth ? 'selected' : '' ?>><?= $mLabel ?></option>
            <?php endforeach; ?>
          </select>
          <select id="attMonthYear" class="select select-bordered select-sm"
            onchange="(function(){
              var m=document.getElementById('attMonth');
              var yr=document.getElementById('attMonthYear');
              if(!m||!yr) return;
              var mm=m.value.padStart(2,'0');
              location.href='/tracks/class_attendance?year_id=<?= (int)$yearId ?>&term=<?= (int)$term ?>&session_date='+encodeURIComponent(yr.value+'-'+mm+'-01');
            })()">
            <?php foreach (range($curYear - 1, $curYear + 1) as $yr): ?>
              <option value="<?= $yr ?>" <?= $yr === $curYear ? 'selected' : '' ?>><?= $yr + 543 ?></option>
            <?php endforeach; ?>
          </select>
        </div>
      </div>

      <div class="border-t border-base-300 bg-base-100 px-4 py-3">
        <div class="text-xs text-base-content/60">วันในเดือนนี้ที่มีรอบเรียน:</div>
        <div class="mt-2 flex flex-wrap gap-2">
          <?php foreach ($sessionDatesInMonth as $d): ?>
            <?php
              $ts = strtotime((string)$d);
              $day = $ts !== false ? (int)date('j', $ts) : 0;
              $active = ((string)$d === (string)$sessionDate);
              $href = '/tracks/class_attendance?year_id=' . (int)$yearId . '&term=' . (int)$term . '&session_date=' . urlencode((string)$d);
            ?>
            <a class="inline-flex items-center rounded-full px-3 py-1 text-xs ring-1 ring-base-300 <?= $active ? 'bg-neutral text-neutral-content' : 'bg-base-200 text-base-content hover:bg-base-300' ?>" href="<?= e($href) ?>"><?= $day > 0 ? (int)$day : e((string)$d) ?></a>
          <?php endforeach; ?>

          <?php if (empty($sessionDatesInMonth)): ?>
            <span class="text-xs text-base-content/60">— ยังไม่มีรอบเรียนในเดือนนี้</span>
          <?php endif; ?>
        </div>
      </div>

      <div class="px-4 py-3 text-xs text-base-content/60"><?= e(thai_date_long($sessionDate)) ?></div>

      <div class="divide-y divide-base-300 bg-base-100">
            <?php foreach ($sessionsForDate as $sess): ?>
              <?php
                $id = (int)($sess['id'] ?? 0);
                $subj = (string)($sess['subject_title'] ?? '');
                $groupTitle = trim((string)($sess['group_title'] ?? ''));
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
              <div class="px-4 py-4 transition hover:bg-base-200">
                <div class="flex flex-wrap items-start justify-between gap-3">
                  <div class="min-w-0 flex-1">
                    <div class="font-medium text-sm"><?= e($subj) ?></div>
                    <?php if ($groupTitle !== ''): ?>
                      <div class="mt-1">
                        <span class="badge badge-sm border-none bg-primary/15 text-base-content/80">📂 <?= e($groupTitle) ?></span>
                      </div>
                    <?php endif; ?>
                    <div class="mt-1.5 flex flex-wrap items-center gap-2">
                      <span class="badge badge-sm border-none bg-secondary/15 text-base-content/80"><?= $count ?> คน</span>
                      <?php if ($note !== ''): ?>
                        <span class="text-xs text-base-content/60"><?= e($note) ?></span>
                      <?php endif; ?>
                    </div>
                  </div>
                  <div class="flex w-full flex-wrap items-center gap-2 md:w-auto">
                    <a class="btn btn-neutral flex-1 rounded-2xl md:flex-none" href="<?= e($href) ?>">✅ เช็คชื่อ</a>
                    <?php if ($role === 'admin'): ?>
                      <a class="btn btn-ghost rounded-2xl border border-base-300" href="<?= e($editHref) ?>">✏️ แก้ไข</a>
                      <form method="post" action="/tracks/class_attendance_delete" data-confirm="ต้องการลบรอบเรียนนี้ใช่ไหม? การเช็คชื่อ/ผลในรอบนี้จะหายไปด้วย">
                        <input type="hidden" name="_csrf" value="<?= e($csrf) ?>" />
                        <input type="hidden" name="session_id" value="<?= (int)$id ?>" />
                        <input type="hidden" name="term" value="<?= (int)$term ?>" />
                        <input type="hidden" name="return_year" value="<?= (int)$yearId ?>" />
                        <input type="hidden" name="return_date" value="<?= e((string)$sessionDate) ?>" />
                        <button class="btn btn-outline btn-error rounded-2xl">🗑️ ลบ</button>
                      </form>
                    <?php endif; ?>
                  </div>
                </div>
              </div>
            <?php endforeach; ?>

            <?php if (empty($sessionsForDate)): ?>
              <div class="px-4 py-10 text-center text-sm text-base-content/70">วันนี้ยังไม่มีรอบเรียน</div>
            <?php endif; ?>
      </div>

      <div class="border-t border-base-300 bg-base-100 px-4 py-3 text-xs text-base-content/60">
        เคล็ดลับ: สร้างรอบเรียนได้หลายรอบในวันเดียวกัน (คนละกลุ่มนักเรียน)
      </div>
    </div>
  </section>
</div>
