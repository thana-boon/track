<?php
$yearTitle = $activeYear ? ((string)$activeYear['title'] . ' (' . (string)$activeYear['year_be'] . ')') : '—';
$recentLogs = is_array($recentLogs ?? null) ? $recentLogs : [];
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

      <a href="/tracks/students" class="rounded-3xl border border-black/5 bg-gradient-to-br from-ink-900 to-ink-800 p-5 text-white shadow-sm hover:opacity-95">
        <div class="text-xs text-white/70">📋 ไปหน้ารายชื่อ</div>
        <div class="mt-2 text-lg font-semibold">ค้นหา + กรองข้อมูล →</div>
        <p class="mt-2 text-xs text-white/70">เลือกปีการศึกษา • เลือกชั้น • ห้อง • เลขที่</p>
      </a>
    </div>

    <div class="mt-6">
      <div class="flex items-center justify-between">
        <h2 class="text-sm font-semibold text-ink-900">⚡ ลิงก์ด่วน</h2>
      </div>
      <div class="mt-3 grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
        <a class="rounded-2xl border border-black/5 bg-sand-100/70 px-4 py-3 text-sm hover:bg-black/5" href="/tracks/class_attendance_create">
          <div class="text-xs text-ink-800/60">📝 เช็คชื่อ</div>
          <div class="mt-1 font-semibold">สร้างรอบเรียน</div>
        </a>
        <a class="rounded-2xl border border-black/5 bg-pastel-mint/45 px-4 py-3 text-sm hover:bg-black/5" href="/tracks/register_track">
          <div class="text-xs text-ink-800/60">🧾 ลงทะเบียน</div>
          <div class="mt-1 font-semibold">ลงทะเบียนวิชา</div>
        </a>
        <a class="rounded-2xl border border-black/5 bg-pastel-sky/45 px-4 py-3 text-sm hover:bg-black/5" href="/tracks/users">
          <div class="text-xs text-ink-800/60">👤 ผู้ใช้</div>
          <div class="mt-1 font-semibold">จัดการผู้ใช้</div>
        </a>
        <a class="rounded-2xl border border-black/5 bg-pastel-lilac/40 px-4 py-3 text-sm hover:bg-black/5" href="/tracks/backup_restore">
          <div class="text-xs text-ink-800/60">💾 สำรองข้อมูล</div>
          <div class="mt-1 font-semibold">Backup/Restore</div>
        </a>
      </div>
    </div>
  </section>

  <section class="overflow-hidden rounded-3xl border border-black/5 bg-white/80 p-6 shadow-sm backdrop-blur">
    <div class="flex flex-col gap-1 sm:flex-row sm:items-center sm:justify-between">
      <div>
        <h2 class="text-base font-semibold tracking-tight">📜 กิจกรรมล่าสุด</h2>
        <p class="mt-1 text-sm text-ink-800/70">10 รายการล่าสุดจาก Activity log</p>
      </div>
      <a class="mt-2 inline-flex w-fit items-center rounded-2xl border border-black/10 bg-white px-4 py-2 text-sm hover:bg-black/5 sm:mt-0" href="/tracks/activity_logs">ดูทั้งหมด →</a>
    </div>

    <?php if (empty($recentLogs)): ?>
      <div class="mt-4 rounded-2xl border border-black/10 bg-sand-100/60 px-4 py-3 text-sm text-ink-800/70">ยังไม่มีข้อมูลกิจกรรม หรือยังไม่ได้เปิดใช้งาน activity log</div>
    <?php else: ?>
      <div class="mt-4 overflow-x-auto rounded-2xl border border-black/10 bg-white">
        <table class="min-w-full text-sm">
          <thead class="bg-sand-100/70 text-ink-800/80">
            <tr>
              <th class="px-3 py-2 text-left">เวลา</th>
              <th class="px-3 py-2 text-left">ผู้ใช้</th>
              <th class="px-3 py-2 text-left">เหตุการณ์</th>
              <th class="px-3 py-2 text-left">Route</th>
              <th class="px-3 py-2 text-left">รายละเอียด</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-black/5">
            <?php foreach ($recentLogs as $log): ?>
              <?php
                $createdAt = (string)($log['created_at'] ?? '');
                $username = (string)($log['username'] ?? '');
                $event = (string)($log['event'] ?? '');
                $route = (string)($log['route'] ?? '');
                $message = (string)($log['message'] ?? '');
              ?>
              <tr>
                <td class="whitespace-nowrap px-3 py-2 text-ink-800/70"><?= e($createdAt) ?></td>
                <td class="whitespace-nowrap px-3 py-2"><?= e($username !== '' ? $username : '-') ?></td>
                <td class="whitespace-nowrap px-3 py-2 font-mono text-xs sm:text-sm"><?= e($event) ?></td>
                <td class="whitespace-nowrap px-3 py-2 text-ink-800/70"><?= e($route) ?></td>
                <td class="max-w-[420px] truncate px-3 py-2 text-ink-800/70" title="<?= e($message) ?>"><?= e($message !== '' ? $message : '-') ?></td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    <?php endif; ?>
  </section>
</div>
