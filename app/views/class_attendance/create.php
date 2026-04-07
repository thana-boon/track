<?php
$csrf = (string)($csrf ?? csrf_token());
$filters = $filters ?? [];
$students = $students ?? [];

$yearId = (int)($yearId ?? 0);
$subjectId = (int)($subjectId ?? 0);

$basePath = '/tracks/class_attendance_create';
$baseQuery = http_build_query(array_filter([
  'year_id' => $yearId,
  'subject_id' => $subjectId,
  'class_level' => (string)($filters['class_level'] ?? ''),
  'room' => (string)($filters['room'] ?? ''),
  'q' => (string)($filters['q'] ?? ''),
], static fn($v) => $v !== '' && $v !== 0));
?>

<div class="grid gap-6">
  <section class="rounded-3xl border border-black/5 bg-white/80 p-5 shadow-sm backdrop-blur">
    <div class="flex flex-wrap items-end justify-between gap-3">
      <div>
        <h1 class="text-xl font-semibold tracking-tight">➕ สร้างรอบเรียน</h1>
        <p class="mt-1 text-sm text-ink-800/70">เลือกวิชา/วันที่/รายชื่อนักเรียน แล้วสร้างรอบเรียนเพื่อไปเช็คชื่อ</p>
      </div>

      <div class="flex flex-wrap items-center gap-2">
        <a class="rounded-2xl border border-black/10 bg-white px-4 py-2.5 text-sm hover:bg-black/5" href="/tracks/class_attendance?year_id=<?= (int)$yearId ?>">← ตารางเรียนตามวัน</a>
      </div>
    </div>

    <?php if (!empty($error)): ?>
      <div class="mt-4 rounded-2xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
        <?= e((string)$error) ?>
      </div>
    <?php endif; ?>

    <?php if (!empty($success)): ?>
      <div class="mt-4 rounded-2xl border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-700">
        <?= e((string)$success) ?>
      </div>
    <?php endif; ?>

    <form class="mt-4 grid gap-4" method="post">
      <input type="hidden" name="_csrf" value="<?= e((string)$csrf) ?>" />
      <input type="hidden" name="action" value="create_session" />

      <div class="grid gap-3 md:grid-cols-2">
        <div>
          <label class="text-xs font-medium">ปีการศึกษา</label>
          <select name="year_id" class="mt-1 w-full rounded-2xl border border-black/10 bg-white px-3 py-2.5 text-sm outline-none focus:border-calm-500" onchange="location.href='/tracks/class_attendance_create?year_id='+encodeURIComponent(this.value)+'&subject_id='+encodeURIComponent(this.form.subject_id.value)">
            <?php foreach (($years ?? []) as $y): ?>
              <?php
                $id = (int)($y['id'] ?? 0);
                $label = (string)($y['title'] ?? '') . ' (' . (string)($y['year_be'] ?? '') . ')';
                $active = ((int)($y['is_active'] ?? 0) === 1) ? ' • active' : '';
              ?>
              <option value="<?= $id ?>" <?= $id === $yearId ? 'selected' : '' ?>><?= e($label . $active) ?></option>
            <?php endforeach; ?>
          </select>
        </div>

        <div>
          <label class="text-xs font-medium">วิชา</label>
          <select name="subject_id" class="mt-1 w-full rounded-2xl border border-black/10 bg-white px-3 py-2.5 text-sm outline-none focus:border-calm-500" onchange="location.href='/tracks/class_attendance_create?year_id=<?= (int)$yearId ?>&subject_id='+encodeURIComponent(this.value)">
            <?php foreach (($subjects ?? []) as $s): ?>
              <?php
                $id = (int)($s['id'] ?? 0);
                $label = (string)($s['title'] ?? '');
                $suffix = ((int)($s['is_active'] ?? 1) === 1) ? '' : ' (ปิด)';
              ?>
              <option value="<?= $id ?>" <?= $id === $subjectId ? 'selected' : '' ?>><?= e($label . $suffix) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
      </div>

      <div class="grid gap-3 md:grid-cols-2">
        <div>
          <label class="text-xs font-medium">วันที่เรียน</label>
          <input type="date" name="session_date" value="<?= e((string)($sessionDate ?? '')) ?>" class="mt-1 w-full rounded-2xl border border-black/10 bg-white px-3 py-2.5 text-sm outline-none focus:border-calm-500" />
          <div class="mt-1 text-[11px] text-ink-800/60">เว้นว่างได้ (จะเป็นวันนี้อัตโนมัติ)</div>
        </div>

        <div>
          <label class="text-xs font-medium">หมายเหตุ (ไม่บังคับ)</label>
          <input name="note" value="<?= e((string)($note ?? '')) ?>" placeholder="เช่น กลุ่มเช้า / กลุ่มบ่าย" class="mt-1 w-full rounded-2xl border border-black/10 bg-white px-3 py-2.5 text-sm outline-none focus:border-calm-500" />
        </div>
      </div>

      <div class="grid gap-3 lg:grid-cols-3">
        <div class="lg:col-span-2 rounded-3xl border border-black/5 bg-white/70 p-3">
          <div class="flex flex-wrap items-center justify-between gap-2">
            <div class="text-xs font-semibold">👥 เลือกนักเรียน</div>
            <div class="flex flex-wrap items-center gap-2">
              <button type="button" class="rounded-2xl border border-black/10 bg-white px-3 py-2 text-xs hover:bg-black/5" onclick="(function(){document.querySelectorAll('input[name=\'student_codes[]\']').forEach(function(el){el.checked=true;});})();">เลือกทั้งหมด</button>
              <button type="button" class="rounded-2xl border border-black/10 bg-white px-3 py-2 text-xs hover:bg-black/5" onclick="(function(){document.querySelectorAll('input[name=\'student_codes[]\']').forEach(function(el){el.checked=false;});})();">เอาออกทั้งหมด</button>
              <div class="text-[11px] text-ink-800/60">ติ๊กได้หลายคน • แสดงสูงสุด 800</div>
            </div>
          </div>

          <div class="mt-3 grid gap-3 md:grid-cols-6">
            <div class="md:col-span-2">
              <label class="text-xs font-medium">ชั้น</label>
              <select class="mt-1 w-full rounded-2xl border border-black/10 bg-white px-3 py-2.5 text-sm outline-none focus:border-calm-500" onchange="location.href='<?= e((string)$basePath) ?>?<?= e((string)$baseQuery) ?>&class_level='+encodeURIComponent(this.value)">
                <?php foreach (($classLevelOptions ?? []) as $val => $label): ?>
                  <option value="<?= e((string)$val) ?>" <?= ((string)($filters['class_level'] ?? '') === (string)$val) ? 'selected' : '' ?>><?= e((string)$label) ?></option>
                <?php endforeach; ?>
              </select>
            </div>

            <div class="md:col-span-2">
              <label class="text-xs font-medium">ห้อง</label>
              <input value="<?= e((string)($filters['room'] ?? '')) ?>" placeholder="เช่น 1" class="mt-1 w-full rounded-2xl border border-black/10 bg-white px-3 py-2.5 text-sm outline-none focus:border-calm-500" onkeydown="if(event.key==='Enter'){event.preventDefault();location.href='<?= e((string)$basePath) ?>?<?= e((string)$baseQuery) ?>&room='+encodeURIComponent(this.value)}" />
            </div>

            <div class="md:col-span-2">
              <label class="text-xs font-medium">ค้นหา</label>
              <input value="<?= e((string)($filters['q'] ?? '')) ?>" placeholder="เลขประจำตัว / ชื่อ / นามสกุล" class="mt-1 w-full rounded-2xl border border-black/10 bg-white px-3 py-2.5 text-sm outline-none focus:border-calm-500" onkeydown="if(event.key==='Enter'){event.preventDefault();location.href='<?= e((string)$basePath) ?>?<?= e((string)$baseQuery) ?>&q='+encodeURIComponent(this.value)}" />
            </div>
          </div>

          <div class="mt-3 max-h-[420px] overflow-auto rounded-2xl border border-black/5 bg-white">
            <ul class="divide-y divide-black/5">
              <?php foreach ($students as $st): ?>
                <?php
                  $code = (string)($st['student_code'] ?? '');
                  $name = (string)($st['first_name'] ?? '') . ' ' . (string)($st['last_name'] ?? '');
                  $meta = (string)($st['class_level'] ?? '') . '/' . (string)($st['class_room'] ?? '') . ' เลขที่ ' . (string)($st['number_in_room'] ?? '');
                ?>
                <li class="px-3 py-2 hover:bg-sand-50">
                  <label class="flex items-start gap-3">
                    <input class="mt-1 h-4 w-4 rounded border-black/20" type="checkbox" name="student_codes[]" value="<?= e($code) ?>" />
                    <span class="block flex-1">
                      <span class="flex flex-wrap items-center gap-2">
                        <span class="font-medium"><?= e($name) ?></span>
                        <span class="rounded-full bg-pastel-sky/60 px-2.5 py-1 text-xs text-ink-800/70 ring-1 ring-black/5"><?= e($code) ?></span>
                      </span>
                      <span class="mt-1 block text-xs text-ink-800/60"><?= e($meta) ?></span>
                    </span>
                  </label>
                </li>
              <?php endforeach; ?>

              <?php if (empty($students)): ?>
                <li class="px-4 py-10 text-center text-sm text-ink-800/70">ไม่พบรายชื่อนักเรียนตามตัวกรอง</li>
              <?php endif; ?>
            </ul>
          </div>
        </div>

        <div class="rounded-3xl border border-black/5 bg-gradient-to-b from-sand-50 to-pastel-sky/20 p-4">
          <div class="text-sm font-semibold">📥 Import รหัสนักเรียน</div>
          <p class="mt-1 text-xs text-ink-800/60">ใส่ 1 บรรทัดต่อ 1 คน (ใส่แค่รหัส หรือ ใส่ชื่อแล้วตามด้วยรหัสก็ได้)</p>

          <textarea
            name="student_codes_text"
            rows="10"
            placeholder="ตัวอย่าง:&#10;65001&#10;สมชาย ใจดี 65002&#10;65003"
            class="mt-3 w-full rounded-2xl border border-black/10 bg-white px-3 py-2.5 text-sm outline-none focus:border-calm-500"
          ><?= e((string)($studentCodesText ?? '')) ?></textarea>

          <div class="mt-4">
            <button class="w-full rounded-2xl bg-calm-600 px-4 py-2.5 text-sm font-medium text-white shadow-sm hover:bg-calm-500">✅ สร้างรอบเรียน</button>
          </div>

          <div class="mt-3 text-[11px] text-ink-800/60">หมายเหตุ: ถ้าเลือก “ติ๊กชื่อ” และ “วางรหัส” พร้อมกัน ระบบจะรวมให้ แล้วตัดซ้ำอัตโนมัติ</div>
        </div>
      </div>
    </form>
  </section>
</div>
