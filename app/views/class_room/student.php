<?php
$student = $student ?? null;
$excellent = $excellent ?? [];
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
  <section class="rounded-3xl border border-base-300 bg-base-100/80 p-5 shadow-sm backdrop-blur">
    <div class="flex flex-wrap items-end justify-between gap-3">
      <div>
        <h1 class="text-xl font-semibold tracking-tight">📘 ผลรายวิชา: <?= e($titleLine) ?></h1>
        <p class="mt-1 text-sm text-base-content/70"><?= e($level) ?>/<?= (int)$room ?> • รหัส <?= e($code) ?></p>
      </div>

      <div class="flex flex-wrap items-center gap-2">
        <a class="rounded-2xl border border-base-300 bg-base-100 px-4 py-2.5 text-sm hover:bg-base-200" href="<?= e($returnHref) ?>">← กลับ</a>
      </div>
    </div>

    <div class="mt-4 grid gap-4 md:grid-cols-4">
      <div class="rounded-3xl border border-base-300 bg-base-100/80 p-4">
        <div class="text-sm font-semibold">⭐ ยอดเยี่ยม</div>
        <div class="mt-2 space-y-2">
          <?php foreach ($excellent as $r): ?>
            <div class="rounded-2xl border border-amber-200 bg-amber-50 px-3 py-2 text-sm">
              <div class="font-medium"><?= e((string)($r['subject_title'] ?? '')) ?></div>
              <div class="text-xs text-amber-900/80">ล่าสุด: <?= e((string)($r['last_date'] ?? '')) ?></div>
            </div>
          <?php endforeach; ?>
          <?php if (empty($excellent)): ?>
            <div class="text-sm text-base-content/60">— ยังไม่มีวิชาที่ยอดเยี่ยม</div>
          <?php endif; ?>
        </div>
      </div>

      <div class="rounded-3xl border border-base-300 bg-base-100/80 p-4">
        <div class="text-sm font-semibold">🟢 ผ่าน</div>
        <div class="mt-2 space-y-2">
          <?php foreach ($passed as $r): ?>
            <div class="rounded-2xl border border-emerald-200 bg-emerald-50 px-3 py-2 text-sm">
              <div class="font-medium"><?= e((string)($r['subject_title'] ?? '')) ?></div>
              <div class="text-xs text-emerald-900/80">ล่าสุด: <?= e((string)($r['last_date'] ?? '')) ?></div>
            </div>
          <?php endforeach; ?>
          <?php if (empty($passed)): ?>
            <div class="text-sm text-base-content/60">— ยังไม่มีวิชาที่ผ่าน</div>
          <?php endif; ?>
        </div>
      </div>

      <div class="rounded-3xl border border-base-300 bg-base-100/80 p-4">
        <div class="text-sm font-semibold">🔴 ไม่ผ่าน</div>
        <div class="mt-2 space-y-2">
          <?php foreach ($failed as $r): ?>
            <div class="rounded-2xl border border-red-200 bg-red-50 px-3 py-2 text-sm">
              <div class="font-medium"><?= e((string)($r['subject_title'] ?? '')) ?></div>
              <div class="text-xs text-red-900/80">ล่าสุด: <?= e((string)($r['last_date'] ?? '')) ?></div>
            </div>
          <?php endforeach; ?>
          <?php if (empty($failed)): ?>
            <div class="text-sm text-base-content/60">— ไม่มีวิชาที่ไม่ผ่าน</div>
          <?php endif; ?>
        </div>
      </div>

      <div class="rounded-3xl border border-base-300 bg-base-100/80 p-4">
        <div class="text-sm font-semibold">⏳ รอดำเนินการ</div>
        <div class="mt-2 space-y-2">
          <?php foreach ($pending as $r): ?>
            <div class="rounded-2xl border border-base-300 bg-base-200 px-3 py-2 text-sm">
              <div class="font-medium"><?= e((string)($r['subject_title'] ?? '')) ?></div>
              <div class="text-xs text-base-content/60">ล่าสุด: <?= e((string)($r['last_date'] ?? '')) ?></div>
            </div>
          <?php endforeach; ?>
          <?php if (empty($pending)): ?>
            <div class="text-sm text-base-content/60">— ไม่มีวิชาที่รอดำเนินการ</div>
          <?php endif; ?>
        </div>
      </div>
    </div>
  </section>
</div>
