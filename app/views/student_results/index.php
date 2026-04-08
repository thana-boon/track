<?php
$student = $student ?? null;
$subjects = $subjects ?? [];
$summary = $summary ?? ['assigned_total' => 0, 'passed_total' => 0, 'passed_titles' => [], 'missing_titles' => []];
$years = is_array($years ?? null) ? $years : [];

$yearId = (int)($yearId ?? 0);
$term = (int)($term ?? 1);
$term = ($term === 2) ? 2 : 1;
$yearText = (string)($yearText ?? ((string)$yearId));
$studentCode = (string)($studentCode ?? '');

$name = $student ? ((string)$student['first_name'] . ' ' . (string)$student['last_name']) : '';
$classText = $student ? ((string)$student['class_level'] . '/' . (string)$student['class_room']) : '';
$noText = $student ? (string)$student['number_in_room'] : '';
?>

<div class="grid gap-6">
  <section class="rounded-3xl border border-black/5 bg-white/80 p-5 shadow-sm backdrop-blur">
    <div class="flex flex-wrap items-start justify-between gap-3">
      <div>
        <h1 class="text-xl font-semibold tracking-tight">📘 ผลการเรียนของฉัน</h1>
        <p class="mt-1 text-sm text-ink-800/70">แสดงเฉพาะข้อมูลของบัญชีที่ล็อกอิน (username = รหัสนักเรียน)</p>
      </div>

      <form class="rounded-2xl border border-black/5 bg-sand-100/70 px-4 py-3 text-sm" method="get" action="/tracks/student_results">
        <div class="grid gap-2">
          <div>
            <div class="text-xs font-medium text-ink-800/70">ปีการศึกษา</div>
            <select name="year_id" class="mt-1 w-full rounded-xl border border-black/10 bg-white px-3 py-2 text-sm outline-none focus:border-calm-500" onchange="this.form.submit()">
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

          <div>
            <div class="text-xs font-medium text-ink-800/70">เทอม</div>
            <select name="term" class="mt-1 w-full rounded-xl border border-black/10 bg-white px-3 py-2 text-sm outline-none focus:border-calm-500" onchange="this.form.submit()">
              <option value="1" <?= $term === 1 ? 'selected' : '' ?>>เทอม 1</option>
              <option value="2" <?= $term === 2 ? 'selected' : '' ?>>เทอม 2</option>
            </select>
          </div>
        </div>
      </form>
    </div>

    <?php if (!empty($error)): ?>
      <div class="mt-4 rounded-2xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-900"><?= e((string)$error) ?></div>
    <?php endif; ?>

    <div class="mt-4 grid gap-3 md:grid-cols-3">
      <div class="rounded-3xl border border-black/5 bg-gradient-to-b from-sand-50 to-pastel-sky/20 p-4">
        <div class="text-xs text-ink-800/60">นักเรียน</div>
        <div class="mt-1 text-base font-semibold"><?= e($name !== '' ? $name : '—') ?></div>
        <div class="mt-1 text-xs text-ink-800/60"><?= e($studentCode !== '' ? $studentCode : '—') ?><?= $classText !== '' ? ' • ' . e($classText) : '' ?><?= $noText !== '' ? ' • เลขที่ ' . e($noText) : '' ?></div>
      </div>

      <div class="rounded-3xl border border-black/5 bg-gradient-to-b from-sand-50 to-pastel-mint/25 p-4">
        <div class="text-xs text-ink-800/60">สรุปการผ่าน</div>
        <div class="mt-1 text-2xl font-semibold"><?= (int)($summary['passed_total'] ?? 0) ?> / <?= (int)($summary['assigned_total'] ?? 0) ?></div>
        <div class="mt-1 text-xs text-ink-800/60">ผ่านแล้ว / วิชาที่ถูกกำหนดทั้งหมด</div>
      </div>

      <div class="rounded-3xl border border-black/5 bg-gradient-to-b from-sand-50 to-pastel-lilac/25 p-4">
        <div class="text-xs text-ink-800/60">ใบทรานสคริป</div>
        <?php $assignedTotal = (int)($summary['assigned_total'] ?? 0); ?>
        <?php $passedTotal = (int)($summary['passed_total'] ?? 0); ?>
        <?php $canGraduate = $assignedTotal > 0 && $passedTotal >= $assignedTotal; ?>

        <?php if ($assignedTotal === 0): ?>
          <div class="mt-1 text-base font-semibold text-ink-900">ยังไม่มีข้อมูล</div>
          <div class="mt-1 text-xs text-ink-800/60">ยังไม่มีวิชาที่ถูกกำหนดในระบบ</div>
        <?php elseif ($canGraduate): ?>
          <div class="mt-1 text-base font-semibold text-emerald-700">ผ่านครบตามที่ถูกกำหนด ✅</div>
          <div class="mt-1 text-xs text-ink-800/60">(สถานะจากข้อมูลที่มีในระบบ)</div>
        <?php else: ?>
          <div class="mt-1 text-base font-semibold text-red-700">ยังจบไม่ได้ ❌</div>
          <div class="mt-1 text-xs text-ink-800/60">ยังมีวิชาที่ไม่ผ่าน/รอดำเนินการ</div>
        <?php endif; ?>
      </div>
    </div>

    <div class="mt-4 grid gap-4 lg:grid-cols-2">
      <div class="rounded-3xl border border-black/5 bg-white/75 overflow-hidden">
        <div class="bg-sand-100 px-4 py-3">
          <div class="text-sm font-semibold">✅ วิชาที่ผ่านแล้ว</div>
          <div class="mt-0.5 text-xs text-ink-800/60">รวม <?= (int)($summary['passed_total'] ?? 0) ?> วิชา</div>
        </div>
        <div class="p-4">
          <?php $passed = (array)($summary['passed_titles'] ?? []); ?>
          <?php if (empty($passed)): ?>
            <div class="text-sm text-ink-800/70">ยังไม่มีวิชาที่ผ่าน</div>
          <?php else: ?>
            <ul class="grid gap-2 sm:grid-cols-2">
              <?php foreach ($passed as $t): ?>
                <li class="rounded-2xl border border-emerald-200 bg-emerald-50 px-3 py-2 text-sm text-emerald-900"><?= e((string)$t) ?></li>
              <?php endforeach; ?>
            </ul>
          <?php endif; ?>
        </div>
      </div>

      <div class="rounded-3xl border border-black/5 bg-white/75 overflow-hidden">
        <div class="bg-sand-100 px-4 py-3">
          <div class="text-sm font-semibold">🧾 วิชาที่ถูกกำหนด (ทั้งหมด)</div>
          <div class="mt-0.5 text-xs text-ink-800/60">แสดงสถานะเข้าเรียน/ผ่าน-ไม่ผ่านจากการเช็คชื่อ</div>
        </div>

        <div class="overflow-auto">
          <table class="min-w-full bg-white text-sm">
            <thead class="bg-sand-50 text-left text-xs text-ink-800/70">
              <tr>
                <th class="px-4 py-3">วิชา</th>
                <th class="px-4 py-3">เข้าเรียน</th>
                <th class="px-4 py-3">ผล</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-black/5">
              <?php foreach ($subjects as $row): ?>
                <tr class="hover:bg-sand-50">
                  <td class="px-4 py-3">
                    <div class="font-medium"><?= e((string)($row['title'] ?? '')) ?></div>
                    <?php if (!empty($row['description'])): ?>
                      <div class="mt-1 text-xs text-ink-800/60"><?= e((string)$row['description']) ?></div>
                    <?php endif; ?>
                    <?php if (!empty($row['last_session_date'])): ?>
                      <div class="mt-1 text-[11px] text-ink-800/55">ล่าสุดเรียน: <?= e((string)$row['last_session_date']) ?><?= !empty($row['checked_at']) ? ' • เช็คชื่อ: ' . e((string)$row['checked_at']) : '' ?></div>
                    <?php endif; ?>
                  </td>
                  <td class="px-4 py-3 text-xs text-ink-800/70"><?= e((string)($row['attend_label'] ?? '')) ?></td>
                  <td class="px-4 py-3">
                    <span class="inline-flex items-center rounded-full px-3 py-1 text-xs font-medium ring-1 <?= e((string)($row['result_tone'] ?? 'bg-sand-100/70 text-ink-900 ring-black/5')) ?>"><?= e((string)($row['result_label'] ?? '')) ?></span>
                  </td>
                </tr>
              <?php endforeach; ?>

              <?php if (empty($subjects)): ?>
                <tr>
                  <td colspan="3" class="px-4 py-10 text-center text-sm text-ink-800/70">ยังไม่มีวิชาที่ถูกกำหนดในระบบ</td>
                </tr>
              <?php endif; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>

    <?php if ((int)($summary['assigned_total'] ?? 0) > 0 && !$canGraduate): ?>
      <div class="mt-4 rounded-3xl border border-red-200 bg-red-50 p-4">
        <div class="text-sm font-semibold text-red-900">📄 ใบทรานสคริป: ยังจบไม่ได้</div>
        <div class="mt-1 text-xs text-red-900/80">วิชาที่ยังไม่ผ่าน/รอดำเนินการ: <?= e(implode(', ', (array)($summary['missing_titles'] ?? []))) ?></div>
      </div>
    <?php endif; ?>

  </section>
</div>
