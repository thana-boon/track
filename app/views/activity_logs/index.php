<?php
$logs = $logs ?? [];
$filters = $filters ?? ['q' => '', 'event' => '', 'route_name' => '', 'method' => ''];
$options = $options ?? ['events' => [], 'routes' => []];
$pagination = $pagination ?? ['page' => 1, 'pageSize' => 50, 'total' => 0, 'totalPages' => 1, 'baseQuery' => ''];

$q = (string)($filters['q'] ?? '');
$event = (string)($filters['event'] ?? '');
$routeName = (string)($filters['route_name'] ?? '');
$method = (string)($filters['method'] ?? '');

$page = (int)($pagination['page'] ?? 1);
$pageSize = (int)($pagination['pageSize'] ?? 50);
$total = (int)($pagination['total'] ?? 0);
$totalPages = (int)($pagination['totalPages'] ?? 1);
$baseQuery = (string)($pagination['baseQuery'] ?? '');

$from = $total > 0 ? (($page - 1) * $pageSize + 1) : 0;
$to = $total > 0 ? min($total, $page * $pageSize) : 0;
?>

<div class="grid gap-6">
  <section class="rounded-3xl border border-black/5 bg-white/80 p-6 shadow-sm backdrop-blur">
    <div class="flex flex-wrap items-end justify-between gap-3">
      <div>
        <h1 class="text-xl font-semibold tracking-tight">📜 Activity Log</h1>
        <p class="mt-1 text-sm text-ink-800/70">บันทึกกิจกรรมสำคัญ เช่น login/logout, POST actions, session timeout, CSRF invalid</p>
      </div>
      <div class="rounded-2xl border border-black/5 bg-sand-100/70 px-4 py-3 text-sm">
        <div class="text-xs text-ink-800/60">รายการ</div>
        <div class="font-semibold"><?= $total > 0 ? e((string)$from . '-' . (string)$to . ' จาก ' . (string)$total) : '0' ?></div>
      </div>
    </div>

    <form class="mt-6 grid gap-3 rounded-3xl border border-black/5 bg-gradient-to-b from-sand-50 to-pastel-sky/20 p-5 md:grid-cols-6" method="get" action="/tracks/activity_logs">

      <div class="md:col-span-3">
        <label class="text-xs font-medium">ค้นหา</label>
        <input name="q" value="<?= e($q) ?>" placeholder="username / ip / route / event / message" class="mt-1 w-full rounded-2xl border border-black/10 bg-white px-4 py-2.5 text-sm outline-none focus:border-calm-500" />
      </div>

      <div>
        <label class="text-xs font-medium">Method</label>
        <select name="method" class="mt-1 w-full rounded-2xl border border-black/10 bg-white px-4 py-2.5 text-sm outline-none focus:border-calm-500">
          <option value="" <?= $method === '' ? 'selected' : '' ?>>ทั้งหมด</option>
          <option value="GET" <?= $method === 'GET' ? 'selected' : '' ?>>GET</option>
          <option value="POST" <?= $method === 'POST' ? 'selected' : '' ?>>POST</option>
        </select>
      </div>

      <div>
        <label class="text-xs font-medium">Event</label>
        <select name="event" class="mt-1 w-full rounded-2xl border border-black/10 bg-white px-4 py-2.5 text-sm outline-none focus:border-calm-500">
          <option value="" <?= $event === '' ? 'selected' : '' ?>>ทั้งหมด</option>
          <?php foreach (($options['events'] ?? []) as $row): ?>
            <?php $ev = (string)($row['event'] ?? ''); ?>
            <?php if ($ev === '') continue; ?>
            <option value="<?= e($ev) ?>" <?= $event === $ev ? 'selected' : '' ?>><?= e($ev) ?> (<?= (int)($row['c'] ?? 0) ?>)</option>
          <?php endforeach; ?>
        </select>
      </div>

      <div>
        <label class="text-xs font-medium">Route</label>
        <select name="route_name" class="mt-1 w-full rounded-2xl border border-black/10 bg-white px-4 py-2.5 text-sm outline-none focus:border-calm-500">
          <option value="" <?= $routeName === '' ? 'selected' : '' ?>>ทั้งหมด</option>
          <?php foreach (($options['routes'] ?? []) as $row): ?>
            <?php $rn = (string)($row['route'] ?? ''); ?>
            <?php if ($rn === '') continue; ?>
            <option value="<?= e($rn) ?>" <?= $routeName === $rn ? 'selected' : '' ?>><?= e($rn) ?> (<?= (int)($row['c'] ?? 0) ?>)</option>
          <?php endforeach; ?>
        </select>
      </div>

      <div class="md:col-span-6 flex flex-wrap items-center justify-between gap-2">
        <button class="rounded-2xl bg-ink-900 px-4 py-2.5 text-sm font-medium text-white shadow-sm hover:bg-ink-800">🔎 ค้นหา</button>
        <a class="rounded-2xl border border-black/10 bg-white px-4 py-2 text-sm hover:bg-black/5" href="/tracks/activity_logs">🧹 ล้างตัวกรอง</a>
      </div>
    </form>

    <div class="mt-6 overflow-auto rounded-3xl border border-black/5 bg-white">
      <table class="min-w-[1100px] bg-white text-sm">
        <thead class="bg-sand-100 text-left text-xs text-ink-800/70">
          <tr>
            <th class="px-4 py-3">เวลา</th>
            <th class="px-4 py-3">ผู้ใช้</th>
            <th class="px-4 py-3">IP</th>
            <th class="px-4 py-3">Method</th>
            <th class="px-4 py-3">Route</th>
            <th class="px-4 py-3">Event</th>
            <th class="px-4 py-3">Message</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-black/5">
          <?php foreach ($logs as $l): ?>
            <?php
              $u = trim((string)($l['username'] ?? ''));
              $r = trim((string)($l['role'] ?? ''));
              $userText = $u !== '' ? $u : '—';
              if ($r !== '') {
                $userText .= ' (' . $r . ')';
              }
              $methodText = (string)($l['method'] ?? '');
              $eventText = (string)($l['event'] ?? '');

              $badge = 'bg-sand-100/70 text-ink-900 ring-black/5';
              if ($eventText === 'login_success') $badge = 'bg-emerald-50 text-emerald-900 ring-emerald-200';
              if ($eventText === 'login_failed' || $eventText === 'login_blocked' || $eventText === 'csrf_invalid') $badge = 'bg-red-50 text-red-900 ring-red-200';
              if ($eventText === 'session_timeout') $badge = 'bg-amber-50 text-amber-900 ring-amber-200';
            ?>
            <tr class="hover:bg-sand-50">
              <td class="px-4 py-3 text-xs text-ink-800/70 whitespace-nowrap"><?= e((string)($l['created_at'] ?? '')) ?></td>
              <td class="px-4 py-3 font-medium whitespace-nowrap"><?= e($userText) ?></td>
              <td class="px-4 py-3 text-xs text-ink-800/70 whitespace-nowrap"><?= e((string)($l['ip'] ?? '')) ?></td>
              <td class="px-4 py-3 text-xs whitespace-nowrap"><?= e($methodText) ?></td>
              <td class="px-4 py-3 text-xs whitespace-nowrap"><?= e((string)($l['route'] ?? '')) ?></td>
              <td class="px-4 py-3">
                <span class="inline-flex items-center rounded-full px-3 py-1 text-xs font-medium ring-1 <?= e($badge) ?>"><?= e($eventText) ?></span>
              </td>
              <td class="px-4 py-3 text-xs text-ink-800/70"><?= e((string)($l['message'] ?? '')) ?></td>
            </tr>
          <?php endforeach; ?>

          <?php if (empty($logs)): ?>
            <tr>
              <td colspan="7" class="px-4 py-10 text-center text-sm text-ink-800/70">ไม่พบรายการ</td>
            </tr>
          <?php endif; ?>
        </tbody>
      </table>
    </div>

    <?php if ($totalPages > 1): ?>
      <div class="mt-4 flex flex-wrap items-center justify-between gap-2">
        <div class="text-xs text-ink-800/60">แสดง <?= e((string)$from) ?>-<?= e((string)$to) ?> จาก <?= e((string)$total) ?> รายการ</div>
        <div class="flex items-center gap-2">
          <?php
            $prev = max(1, $page - 1);
            $next = min($totalPages, $page + 1);

            $baseHref = '/tracks/activity_logs' . ($baseQuery !== '' ? ('?' . $baseQuery) : '');
            $prevHref = $baseHref . ($prev > 1 ? (($baseQuery !== '' ? '&' : '?') . 'page=' . $prev) : '');
            $nextHref = $baseHref . (($baseQuery !== '' ? '&' : '?') . 'page=' . $next);
          ?>
           <a class="rounded-xl border border-black/10 bg-white px-3 py-2 text-sm hover:bg-black/5 <?= $page <= 1 ? 'pointer-events-none opacity-50' : '' ?>" href="<?= e($prevHref) ?>">← ก่อนหน้า</a>
           <a class="rounded-xl border border-black/10 bg-white px-3 py-2 text-sm hover:bg-black/5 <?= $page >= $totalPages ? 'pointer-events-none opacity-50' : '' ?>" href="<?= e($nextHref) ?>">ถัดไป →</a>
         </div>
       </div>
     <?php endif; ?>
  </section>
</div>
