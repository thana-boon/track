<?php
$student = $student ?? null;
$passed = $passed ?? [];
$failed = $failed ?? [];
$pending = $pending ?? [];
$returnHref = (string)($returnHref ?? '/tracks/class_room');

$code = is_array($student) ? (string)($student['student_code'] ?? '') : '';
$name = is_array($student) ? trim((string)($student['first_name'] ?? '') . ' ' . (string)($student['last_name'] ?? '')) : '';
$level = is_array($student) ? track_class_level_normalize((string)($student['class_level'] ?? '')) : '';
$room = is_array($student) ? (int)($student['class_room'] ?? 0) : 0;
$no = is_array($student) ? (string)($student['number_in_room'] ?? '') : '';

$titleLine = ($name !== '' ? $name : $code);
if ($no !== '') $titleLine = $no . '. ' . $titleLine;
?>

<div class="grid gap-6">
  <section class="rounded-3xl border border-black/5 bg-white/80 p-5 shadow-sm backdrop-blur">
    <div class="flex flex-wrap items-end justify-between gap-3">
      <div>
        <h1 class="text-xl font-semibold tracking-tight">📘 ผลรายวิชา: <?= e($titleLine) ?></h1>
        <p class="mt-1 text-sm text-ink-800/70"><?= e($level) ?>/<?= (int)$room ?> • รหัส <?= e($code) ?></p>
      </div>

      <div class="flex flex-wrap items-center gap-2">
        <a class="rounded-2xl border border-black/10 bg-white px-4 py-2.5 text-sm hover:bg-black/5" href="<?= e($returnHref) ?>">← กลับ</a>
      </div>
    </div>

    <div class="mt-4 grid gap-4 md:grid-cols-3">
      <div class="rounded-3xl border border-black/5 bg-white/80 p-4">
        <div class="text-sm font-semibold">🟢 ผ่าน</div>
        <div class="mt-2 space-y-2">
          <?php foreach ($passed as $r): ?>
            <div class="rounded-2xl border border-emerald-200 bg-emerald-50 px-3 py-2 text-sm">
              <div class="font-medium"><?= e((string)($r['subject_title'] ?? '')) ?></div>
              <div class="text-xs text-emerald-900/80">ล่าสุด: <?= e((string)($r['last_date'] ?? '')) ?></div>
            </div>
          <?php endforeach; ?>
          <?php if (empty($passed)): ?>
            <div class="text-sm text-ink-800/60">— ยังไม่มีวิชาที่ผ่าน</div>
          <?php endif; ?>
        </div>
      </div>

      <div class="rounded-3xl border border-black/5 bg-white/80 p-4">
        <div class="text-sm font-semibold">🔴 ไม่ผ่าน</div>
        <div class="mt-2 space-y-2">
          <?php foreach ($failed as $r): ?>
            <div class="rounded-2xl border border-red-200 bg-red-50 px-3 py-2 text-sm">
              <div class="font-medium"><?= e((string)($r['subject_title'] ?? '')) ?></div>
              <div class="text-xs text-red-900/80">ล่าสุด: <?= e((string)($r['last_date'] ?? '')) ?></div>
            </div>
          <?php endforeach; ?>
          <?php if (empty($failed)): ?>
            <div class="text-sm text-ink-800/60">— ไม่มีวิชาที่ไม่ผ่าน</div>
          <?php endif; ?>
        </div>
      </div>

      <div class="rounded-3xl border border-black/5 bg-white/80 p-4">
        <div class="text-sm font-semibold">⏳ รอดำเนินการ</div>
        <div class="mt-2 space-y-2">
          <?php foreach ($pending as $r): ?>
            <div class="rounded-2xl border border-black/10 bg-sand-50 px-3 py-2 text-sm">
              <div class="font-medium"><?= e((string)($r['subject_title'] ?? '')) ?></div>
              <div class="text-xs text-ink-800/60">ล่าสุด: <?= e((string)($r['last_date'] ?? '')) ?></div>
            </div>
          <?php endforeach; ?>
          <?php if (empty($pending)): ?>
            <div class="text-sm text-ink-800/60">— ไม่มีวิชาที่รอดำเนินการ</div>
          <?php endif; ?>
        </div>
      </div>
    </div>
  </section>
</div>
