<?php
$csrf = (string)($csrf ?? csrf_token());
$session = $session ?? null;
$subjects = $subjects ?? [];

$sessionId = is_array($session) ? (int)($session['id'] ?? 0) : 0;
$yearId = is_array($session) ? (int)($session['year_id'] ?? 0) : 0;

$subjectId = (int)($subjectId ?? (is_array($session) ? (int)($session['subject_id'] ?? 0) : 0));
$sessionDate = (string)($sessionDate ?? (is_array($session) ? (string)($session['session_date'] ?? '') : ''));
$note = (string)($note ?? (is_array($session) ? (string)($session['note'] ?? '') : ''));

$existingCodes = $existingCodes ?? [];
$roster = $roster ?? [];
$studentCodesText = (string)($studentCodesText ?? '');

$returnDate = (string)($returnDate ?? '');
$returnYear = (int)($returnYear ?? 0);

$backQs = http_build_query(array_filter([
  'year_id' => $returnYear > 0 ? $returnYear : $yearId,
  'session_date' => $returnDate !== '' ? $returnDate : $sessionDate,
], static fn($v) => $v !== '' && $v !== 0));
$backHref = '/tracks/class_attendance' . ($backQs !== '' ? ('?' . $backQs) : '');
?>

<div class="grid gap-6">
  <section class="rounded-3xl border border-black/5 bg-white/80 p-5 shadow-sm backdrop-blur">
    <div class="flex flex-wrap items-end justify-between gap-3">
      <div>
        <h1 class="text-xl font-semibold tracking-tight">✏️ แก้ไขรอบเรียน</h1>
        <p class="mt-1 text-sm text-ink-800/70">แก้ไขวิชา/วันที่/หมายเหตุ และรายชื่อนักเรียนในรอบเรียนนี้</p>
      </div>

      <div class="flex flex-wrap items-center gap-2">
        <a class="rounded-2xl border border-black/10 bg-white px-4 py-2.5 text-sm hover:bg-black/5" href="<?= e($backHref) ?>">← กลับตารางเรียน</a>
      </div>
    </div>

    <?php if (!empty($error)): ?>
      <div class="mt-4 rounded-2xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700"><?= e((string)$error) ?></div>
    <?php endif; ?>

    <?php if (!empty($success)): ?>
      <div class="mt-4 rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-900"><?= e((string)$success) ?></div>
    <?php endif; ?>

    <form class="mt-4 grid gap-4" method="post">
      <input type="hidden" name="_csrf" value="<?= e($csrf) ?>" />
      <input type="hidden" name="action" value="update_session" />

      <div class="grid gap-3 md:grid-cols-2">
        <div>
          <label class="text-xs font-medium">วิชา</label>
          <select name="subject_id" class="mt-1 w-full rounded-2xl border border-black/10 bg-white px-3 py-2.5 text-sm outline-none focus:border-calm-500">
            <?php foreach ($subjects as $s): ?>
              <?php
                $id = (int)($s['id'] ?? 0);
                $label = (string)($s['title'] ?? '');
                $suffix = ((int)($s['is_active'] ?? 1) === 1) ? '' : ' (ปิด)';
              ?>
              <option value="<?= $id ?>" <?= $id === $subjectId ? 'selected' : '' ?>><?= e($label . $suffix) ?></option>
            <?php endforeach; ?>
          </select>
        </div>

        <div>
          <label class="text-xs font-medium">วันที่เรียน</label>
          <input type="date" name="session_date" value="<?= e($sessionDate) ?>" class="mt-1 w-full rounded-2xl border border-black/10 bg-white px-3 py-2.5 text-sm outline-none focus:border-calm-500" />
        </div>
      </div>

      <div>
        <label class="text-xs font-medium">หมายเหตุ (ไม่บังคับ)</label>
        <input name="note" value="<?= e($note) ?>" placeholder="เช่น กลุ่มเช้า / กลุ่มบ่าย" class="mt-1 w-full rounded-2xl border border-black/10 bg-white px-3 py-2.5 text-sm outline-none focus:border-calm-500" />
      </div>

      <div class="grid gap-3 lg:grid-cols-3">
        <div class="lg:col-span-2 rounded-3xl border border-black/5 bg-white/70 p-3">
          <div class="flex flex-wrap items-center justify-between gap-2">
            <div class="text-xs font-semibold">👥 นักเรียนในรอบนี้</div>
            <div class="text-[11px] text-ink-800/60">ยกเลิกติ๊ก = นำออกจากรอบเรียน • มี <?= (int)count($existingCodes) ?> คน</div>
          </div>

          <div class="mt-3 max-h-[420px] overflow-auto rounded-2xl border border-black/5 bg-white">
            <ul class="divide-y divide-black/5">
              <?php if (!empty($roster)): ?>
                <?php foreach ($roster as $r): ?>
                  <?php
                    $code = (string)($r['student_code'] ?? '');
                    $name = trim((string)($r['first_name'] ?? '') . ' ' . (string)($r['last_name'] ?? ''));
                    $meta = trim((string)($r['class_level'] ?? '') . '/' . (string)($r['class_room'] ?? '') . ' เลขที่ ' . (string)($r['number_in_room'] ?? ''));
                  ?>
                  <li class="px-3 py-2 hover:bg-sand-50">
                    <label class="flex items-start gap-3">
                      <input class="mt-1 h-4 w-4 rounded border-black/20" type="checkbox" name="student_codes[]" value="<?= e($code) ?>" checked />
                      <span class="block flex-1">
                        <span class="flex flex-wrap items-center gap-2">
                          <span class="font-medium"><?= e($name !== '' ? $name : $code) ?></span>
                          <span class="rounded-full bg-pastel-sky/60 px-2.5 py-1 text-xs text-ink-800/70 ring-1 ring-black/5"><?= e($code) ?></span>
                        </span>
                        <?php if ($meta !== '/ เลขที่'): ?>
                          <span class="mt-1 block text-xs text-ink-800/60"><?= e($meta) ?></span>
                        <?php endif; ?>
                      </span>
                    </label>
                  </li>
                <?php endforeach; ?>
              <?php else: ?>
                <?php foreach ($existingCodes as $code): ?>
                  <li class="px-3 py-2 hover:bg-sand-50">
                    <label class="flex items-start gap-3">
                      <input class="mt-1 h-4 w-4 rounded border-black/20" type="checkbox" name="student_codes[]" value="<?= e((string)$code) ?>" checked />
                      <span class="block flex-1">
                        <span class="flex flex-wrap items-center gap-2">
                          <span class="font-medium"><?= e((string)$code) ?></span>
                          <span class="rounded-full bg-pastel-sky/60 px-2.5 py-1 text-xs text-ink-800/70 ring-1 ring-black/5">รหัส</span>
                        </span>
                      </span>
                    </label>
                  </li>
                <?php endforeach; ?>
              <?php endif; ?>

              <?php if (empty($existingCodes)): ?>
                <li class="px-4 py-10 text-center text-sm text-ink-800/70">ยังไม่มีนักเรียนในรอบนี้</li>
              <?php endif; ?>
            </ul>
          </div>
        </div>

        <div class="rounded-3xl border border-black/5 bg-gradient-to-b from-sand-50 to-pastel-sky/20 p-4">
          <div class="text-sm font-semibold">📥 เพิ่มรหัสนักเรียน</div>
          <p class="mt-1 text-xs text-ink-800/60">ใส่ 1 บรรทัดต่อ 1 คน (ใส่แค่รหัส หรือ ใส่ชื่อแล้วตามด้วยรหัสก็ได้)</p>

          <textarea
            name="student_codes_text"
            rows="10"
            placeholder="ตัวอย่าง:&#10;65001&#10;สมชาย ใจดี 65002&#10;65003"
            class="mt-3 w-full rounded-2xl border border-black/10 bg-white px-3 py-2.5 text-sm outline-none focus:border-calm-500"
          ><?= e($studentCodesText) ?></textarea>

          <div class="mt-4">
            <button class="w-full rounded-2xl bg-calm-600 px-4 py-2.5 text-sm font-medium text-white shadow-sm hover:bg-calm-500">💾 บันทึกการแก้ไข</button>
          </div>

          <div class="mt-3 text-[11px] text-ink-800/60">หมายเหตุ: ระบบจะรวมรหัสกับรายชื่อที่ติ๊กไว้ แล้วตัดซ้ำอัตโนมัติ</div>
        </div>
      </div>

      <div class="flex items-center justify-end gap-2">
        <button class="rounded-2xl bg-calm-600 px-4 py-2.5 text-sm font-medium text-white shadow-sm hover:bg-calm-500">💾 บันทึกการแก้ไข</button>
      </div>
    </form>
  </section>
</div>
