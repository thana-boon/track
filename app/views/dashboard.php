<?php
$yearTitle = $activeYear ? ((string)$activeYear['title'] . ' (' . (string)$activeYear['year_be'] . ')') : '—';
$recentLogs = is_array($recentLogs ?? null) ? $recentLogs : [];
?>
<div class="grid gap-6">
  <section class="card border border-base-300 bg-base-100/80 p-6 shadow-sm backdrop-blur">
    <div class="flex items-start justify-between gap-4">
      <div>
        <h1 class="text-xl font-semibold tracking-tight">🏠 Dashboard</h1>
        <p class="mt-1 text-sm text-base-content/70">ปีการศึกษาปัจจุบัน: <span class="font-medium text-base-content"><?= e($yearTitle) ?></span></p>
      </div>
    </div>

    <div class="mt-6 grid gap-4 sm:grid-cols-2">
      <div class="rounded-2xl border border-base-300 bg-base-100 p-5">
        <div class="text-xs text-base-content/60">🎓 จำนวนนักเรียน (ม.4 - ม.6)</div>
        <div class="mt-2 text-3xl font-semibold tracking-tight"><?= number_format((int)($counts['students_m456'] ?? 0)) ?></div>
        <p class="mt-2 text-xs text-base-content/60">ดูรายละเอียดในหน้ารายชื่อเพื่อกรอง/ค้นหา</p>
      </div>

      <a href="/tracks/students" class="rounded-2xl border-none bg-neutral p-5 text-neutral-content shadow-sm transition hover:opacity-90">
        <div class="text-xs text-neutral-content/70">📋 ไปหน้ารายชื่อ</div>
        <div class="mt-2 text-lg font-semibold">ค้นหา + กรองข้อมูล →</div>
        <p class="mt-2 text-xs text-neutral-content/70">เลือกปีการศึกษา • เลือกชั้น • ห้อง • เลขที่</p>
      </a>
    </div>

    <div class="mt-6">
      <div class="flex items-center justify-between">
        <h2 class="text-sm font-semibold text-base-content">⚡ ลิงก์ด่วน</h2>
      </div>
      <div class="mt-3 grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
        <a class="rounded-2xl border border-base-300 bg-base-100 px-4 py-3 text-sm transition hover:bg-base-200" href="/tracks/class_attendance_create">
          <div class="text-xs text-base-content/60">📝 เช็คชื่อ</div>
          <div class="mt-1 font-semibold">สร้างรอบเรียน</div>
        </a>
        <a class="rounded-2xl border border-base-300 bg-base-100 px-4 py-3 text-sm transition hover:bg-base-200" href="/tracks/register_track">
          <div class="text-xs text-base-content/60">🧾 ลงทะเบียน</div>
          <div class="mt-1 font-semibold">ลงทะเบียนวิชา</div>
        </a>
        <a class="rounded-2xl border border-base-300 bg-base-100 px-4 py-3 text-sm transition hover:bg-base-200" href="/tracks/users">
          <div class="text-xs text-base-content/60">👤 ผู้ใช้</div>
          <div class="mt-1 font-semibold">จัดการผู้ใช้</div>
        </a>
        <a class="rounded-2xl border border-base-300 bg-base-100 px-4 py-3 text-sm transition hover:bg-base-200" href="/tracks/backup_restore">
          <div class="text-xs text-base-content/60">💾 สำรองข้อมูล</div>
          <div class="mt-1 font-semibold">Backup/Restore</div>
        </a>
      </div>
    </div>
  </section>

  <section class="card border border-base-300 bg-base-100/80 p-6 shadow-sm backdrop-blur">
    <div class="flex flex-col gap-1 sm:flex-row sm:items-center sm:justify-between">
      <div>
        <h2 class="text-base font-semibold tracking-tight">📜 กิจกรรมล่าสุด</h2>
        <p class="mt-1 text-sm text-base-content/70">10 รายการล่าสุดจาก Activity log</p>
      </div>
      <a class="btn btn-sm btn-ghost mt-2 w-fit rounded-2xl border border-base-300 font-normal sm:mt-0" href="/tracks/activity_logs">ดูทั้งหมด →</a>
    </div>

    <?php if (empty($recentLogs)): ?>
      <div class="mt-4 rounded-2xl border border-base-300 bg-base-200 px-4 py-3 text-sm text-base-content/70">ยังไม่มีข้อมูลกิจกรรม หรือยังไม่ได้เปิดใช้งาน activity log</div>
    <?php else: ?>
      <div class="mt-4 overflow-x-auto rounded-2xl border border-base-300">
        <table class="table table-sm">
          <thead class="bg-base-200 text-base-content/80">
            <tr>
              <th>เวลา</th>
              <th>ผู้ใช้</th>
              <th>เหตุการณ์</th>
              <th>Route</th>
              <th>รายละเอียด</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($recentLogs as $log): ?>
              <?php
                $createdAt = (string)($log['created_at'] ?? '');
                $username = (string)($log['username'] ?? '');
                $event = (string)($log['event'] ?? '');
                $route = (string)($log['route'] ?? '');
                $message = (string)($log['message'] ?? '');
              ?>
              <tr>
                <td class="whitespace-nowrap text-base-content/70"><?= e($createdAt) ?></td>
                <td class="whitespace-nowrap"><?= e($username !== '' ? $username : '-') ?></td>
                <td class="whitespace-nowrap font-mono text-xs sm:text-sm"><?= e($event) ?></td>
                <td class="whitespace-nowrap text-base-content/70"><?= e($route) ?></td>
                <td class="max-w-[420px] truncate text-base-content/70" title="<?= e($message) ?>"><?= e($message !== '' ? $message : '-') ?></td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    <?php endif; ?>
  </section>
</div>
