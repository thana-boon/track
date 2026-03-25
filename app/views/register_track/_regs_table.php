<?php
// Expected variables: $regs, $csrf, $filterQuery
$regs = $regs ?? [];
$regsPage = (int)($regsPage ?? 1);
$regsPageSize = (int)($regsPageSize ?? 50);
$regsTotalCount = (int)($regsTotalCount ?? ($regsTotal ?? 0));
$regsPage = max(1, $regsPage);
$regsPageSize = max(1, $regsPageSize);
$totalPages = (int)max(1, (int)ceil(max(0, $regsTotalCount) / $regsPageSize));
if ($regsPage > $totalPages) {
  $regsPage = $totalPages;
}
?>

<div class="mt-4 overflow-hidden rounded-3xl border border-black/5" id="regsContainer" data-page="<?= (int)$regsPage ?>" data-pages="<?= (int)$totalPages ?>" data-total="<?= (int)$regsTotalCount ?>">
  <div class="flex flex-wrap items-center justify-between gap-2 bg-sand-100 px-4 py-3">
    <div>
      <div class="text-sm font-semibold">📌 รายการที่ลงทะเบียนแล้ว (ตามตัวกรอง)</div>
      <div class="mt-0.5 text-xs text-ink-800/60">ทั้งหมด <?= (int)$regsTotalCount ?> รายการ • หน้า <?= (int)$regsPage ?>/<?= (int)$totalPages ?> • แสดง <?= (int)$regsPageSize ?> ต่อหน้า</div>
    </div>

    <div class="flex items-center gap-2 text-xs">
      <?php
        $base = $filterQuery;
        $prevPage = max(1, $regsPage - 1);
        $nextPage = min($totalPages, $regsPage + 1);
        $prevHref = '/tracks/?' . $base . ($prevPage > 1 ? '&page=' . $prevPage : '');
        $nextHref = '/tracks/?' . $base . ($nextPage > 1 ? '&page=' . $nextPage : '');
        $postAction = '/tracks/?' . $base . ($regsPage > 1 ? '&page=' . $regsPage : '');
      ?>
      <a class="regs-page-link rounded-2xl border border-black/10 bg-white px-3 py-2 hover:bg-black/5 <?= ($regsPage <= 1) ? 'pointer-events-none opacity-50' : '' ?>" data-page="<?= (int)$prevPage ?>" href="<?= e($prevHref) ?>">⬅ ก่อนหน้า</a>
      <a class="regs-page-link rounded-2xl border border-black/10 bg-white px-3 py-2 hover:bg-black/5 <?= ($regsPage >= $totalPages) ? 'pointer-events-none opacity-50' : '' ?>" data-page="<?= (int)$nextPage ?>" href="<?= e($nextHref) ?>">ถัดไป ➡</a>
    </div>
  </div>

  <div class="overflow-auto">
    <table class="min-w-full bg-white text-sm">
      <thead class="bg-sand-50 text-left text-xs text-ink-800/70">
        <tr>
          <th class="px-4 py-3">นักเรียน</th>
          <th class="px-4 py-3">วิชา</th>
          <th class="px-4 py-3">วันที่ลง</th>
          <th class="px-4 py-3">จัดการ</th>
        </tr>
      </thead>
      <tbody class="divide-y divide-black/5">
        <?php foreach ($regs as $r): ?>
          <tr class="hover:bg-sand-50">
            <td class="px-4 py-3">
              <div class="font-medium">
                <a class="hover:underline" href="/tracks/?route=student_track&year_id=<?= (int)$r['year_id'] ?>&student_code=<?= e(rawurlencode((string)$r['student_code'])) ?>"><?= e((string)$r['first_name'] . ' ' . (string)$r['last_name']) ?></a>
              </div>
              <div class="mt-1 text-xs text-ink-800/60"><?= e((string)$r['student_code']) ?> • <?= e((string)$r['class_level']) ?>/<?= e((string)$r['class_room']) ?> เลขที่ <?= e((string)$r['number_in_room']) ?></div>
            </td>
            <td class="px-4 py-3">
              <span class="inline-flex items-center rounded-full bg-pastel-lilac/70 px-2.5 py-1 text-xs text-ink-800 ring-1 ring-black/5">🧩 <?= e((string)$r['subject_title']) ?></span>
            </td>
            <td class="px-4 py-3 text-xs text-ink-800/60"><?= e((string)$r['created_at']) ?></td>
            <td class="px-4 py-3">
              <form method="post" action="<?= e((string)$postAction) ?>" onsubmit="return confirm('ยืนยันลบรายการนี้?');">
                <input type="hidden" name="_csrf" value="<?= e((string)$csrf) ?>" />
                <input type="hidden" name="action" value="delete_one" />
                <input type="hidden" name="id" value="<?= (int)$r['id'] ?>" />
                <button class="rounded-2xl border border-red-200 bg-red-50 px-3 py-2 text-xs text-red-700 hover:bg-red-100">🗑️ ลบ</button>
              </form>
            </td>
          </tr>
        <?php endforeach; ?>

        <?php if (empty($regs)): ?>
          <tr>
            <td colspan="4" class="px-4 py-10 text-center text-sm text-ink-800/70">ยังไม่มีรายการตามตัวกรอง</td>
          </tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>
