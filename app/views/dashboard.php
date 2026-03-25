<?php
$yearTitle = $activeYear ? ((string)$activeYear['title'] . ' (' . (string)$activeYear['year_be'] . ')') : '—';
?>
<div class="grid gap-6">
  <section class="overflow-hidden rounded-3xl border border-black/5 bg-white/80 p-6 shadow-sm backdrop-blur">
    <div class="flex items-start justify-between gap-4">
      <div>
        <h1 class="text-xl font-semibold tracking-tight">🏠 Dashboard</h1>
        <p class="mt-1 text-sm text-ink-800/70">ปีการศึกษาปัจจุบัน: <span class="font-medium text-ink-900"><?= e($yearTitle) ?></span></p>
      </div>
    </div>

    <div class="mt-6 grid gap-4 sm:grid-cols-2">
      <div class="rounded-3xl border border-black/5 bg-gradient-to-b from-white to-pastel-mint/30 p-5">
        <div class="text-xs text-ink-800/60">🎓 จำนวนนักเรียน (ม.4 - ม.6)</div>
        <div class="mt-2 text-3xl font-semibold tracking-tight"><?= number_format((int)($counts['students_m456'] ?? 0)) ?></div>
        <p class="mt-2 text-xs text-ink-800/60">ดูรายละเอียดในหน้ารายชื่อเพื่อกรอง/ค้นหา</p>
      </div>

      <a href="/tracks/?route=students" class="rounded-3xl border border-black/5 bg-gradient-to-br from-ink-900 to-ink-800 p-5 text-white shadow-sm hover:opacity-95">
        <div class="text-xs text-white/70">📋 ไปหน้ารายชื่อ</div>
        <div class="mt-2 text-lg font-semibold">ค้นหา + กรองข้อมูล →</div>
        <p class="mt-2 text-xs text-white/70">เลือกปีการศึกษา • เลือกชั้น • ห้อง • เลขที่</p>
      </a>
    </div>
  </section>
</div>
