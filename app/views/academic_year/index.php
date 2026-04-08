<?php
$csrf = csrf_token();
$years = $years ?? [];
$activeYear = $activeYear ?? null;
$activeTerm = (int)($activeTerm ?? 1);
$activeTerm = ($activeTerm === 2) ? 2 : 1;

$activeLabel = '';
if (is_array($activeYear)) {
  $activeLabel = (string)($activeYear['title'] ?? '') . ' (' . (string)($activeYear['year_be'] ?? '') . ')';
}
?>

<section class="rounded-3xl border border-black/5 bg-white/80 p-5 shadow-sm backdrop-blur">
  <div class="flex flex-wrap items-end justify-between gap-3">
    <div>
      <h1 class="text-xl font-semibold tracking-tight">📅 ตั้งค่าปีการศึกษา</h1>
      <p class="mt-1 text-sm text-ink-800/70">กำหนดว่า “ปีการศึกษาปัจจุบัน (active)” คือปีใด เพื่อให้หน้าอื่น ๆ ใช้เป็นค่าเริ่มต้น</p>
    </div>

  </div>

  <?php if (!empty($success)): ?>
    <div class="mt-4 rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-900"><?= e((string)$success) ?></div>
  <?php endif; ?>

  <?php if (!empty($error)): ?>
    <div class="mt-4 rounded-2xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-900"><?= e((string)$error) ?></div>
  <?php endif; ?>

  <div class="mt-4 grid gap-4 lg:grid-cols-2">
    <div class="rounded-3xl border border-black/5 bg-gradient-to-b from-sand-50 to-pastel-sky/20 p-4">
      <div class="text-sm font-semibold">✅ ปีการศึกษาปัจจุบัน</div>
      <div class="mt-2 rounded-2xl border border-black/10 bg-white px-4 py-3 text-sm">
        <?= $activeLabel !== '' ? e($activeLabel) : '—' ?>
      </div>

      <form class="mt-4 grid gap-3" method="post">
        <input type="hidden" name="_csrf" value="<?= e((string)$csrf) ?>" />
        <input type="hidden" name="action" value="set_active" />

        <div>
          <label class="text-xs font-medium">เลือกปีการศึกษาที่ต้องการให้ active</label>
          <select name="year_id" class="mt-1 w-full rounded-2xl border border-black/10 bg-white px-3 py-2.5 text-sm outline-none focus:border-calm-500">
            <?php foreach ($years as $y): ?>
              <?php
                $id = (int)($y['id'] ?? 0);
                $label = (string)($y['title'] ?? '') . ' (' . (string)($y['year_be'] ?? '') . ')';
                $isActive = ((int)($y['is_active'] ?? 0) === 1);
                $suffix = $isActive ? ' • active' : '';
              ?>
              <option value="<?= $id ?>" <?= $isActive ? 'selected' : '' ?>><?= e($label . $suffix) ?></option>
            <?php endforeach; ?>
          </select>
          <div class="mt-1 text-[11px] text-ink-800/60">ระบบจะตั้งให้มี active ได้เพียง 1 ปีในเวลาเดียวกัน</div>
        </div>

        <button class="rounded-2xl bg-calm-600 px-4 py-2.5 text-sm font-medium text-white shadow-sm hover:bg-calm-500">💾 ตั้งเป็นปีปัจจุบัน</button>
      </form>

      <div class="mt-6 border-t border-black/10 pt-4">
        <div class="text-sm font-semibold">🎯 เทอมปัจจุบัน (ระบบ Track)</div>
        <div class="mt-2 rounded-2xl border border-black/10 bg-white px-4 py-3 text-sm font-semibold">
          เทอม <?= (int)$activeTerm ?>
        </div>

        <form class="mt-4 grid gap-3" method="post">
          <input type="hidden" name="_csrf" value="<?= e((string)$csrf) ?>" />
          <input type="hidden" name="action" value="set_active_term" />

          <div>
            <label class="text-xs font-medium">เลือกเทอมที่ต้องการให้ active (Track)</label>
            <select name="term" class="mt-1 w-full rounded-2xl border border-black/10 bg-white px-3 py-2.5 text-sm outline-none focus:border-calm-500">
              <option value="1" <?= $activeTerm === 1 ? 'selected' : '' ?>>เทอม 1</option>
              <option value="2" <?= $activeTerm === 2 ? 'selected' : '' ?>>เทอม 2</option>
            </select>
            <div class="mt-1 text-[11px] text-ink-800/60">ค่าเทอมนี้จะถูกใช้เป็นค่าเริ่มต้นของหน้าต่าง ๆ ในระบบ Track (ถ้าไม่ได้ส่ง term มากับ URL)</div>
          </div>

          <button class="rounded-2xl bg-ink-900 px-4 py-2.5 text-sm font-medium text-white shadow-sm hover:bg-ink-800">💾 ตั้งเป็นเทอมปัจจุบัน</button>
        </form>
      </div>
    </div>

    <div class="overflow-hidden rounded-3xl border border-black/5 bg-white/80">
      <div class="flex items-center justify-between gap-2 bg-sand-100 px-4 py-3">
        <div class="text-sm font-semibold">🗂️ รายการปีการศึกษา</div>
        <div class="text-xs text-ink-800/60"><?= count($years) ?> รายการ</div>
      </div>

      <div class="overflow-auto">
        <table class="min-w-full text-sm">
          <thead class="bg-white">
            <tr class="text-left text-xs text-ink-800/70">
              <th class="px-4 py-3">ปี (พ.ศ.)</th>
              <th class="px-4 py-3">ชื่อ/คำอธิบาย</th>
              <th class="px-4 py-3">สถานะ</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-black/5">
            <?php foreach ($years as $y): ?>
              <?php
                $yearBe = (string)($y['year_be'] ?? '');
                $title = (string)($y['title'] ?? '');
                $isActive = ((int)($y['is_active'] ?? 0) === 1);
              ?>
              <tr class="hover:bg-sand-50">
                <td class="px-4 py-3 font-medium"><?= e($yearBe) ?></td>
                <td class="px-4 py-3"><?= e($title) ?></td>
                <td class="px-4 py-3">
                  <?php if ($isActive): ?>
                    <span class="inline-flex items-center rounded-full bg-pastel-mint/70 px-2.5 py-1 text-xs text-ink-900 ring-1 ring-black/5">active</span>
                  <?php else: ?>
                    <span class="inline-flex items-center rounded-full bg-sand-100 px-2.5 py-1 text-xs text-ink-800/70 ring-1 ring-black/5">—</span>
                  <?php endif; ?>
                </td>
              </tr>
            <?php endforeach; ?>

            <?php if (empty($years)): ?>
              <tr>
                <td class="px-4 py-10 text-center text-sm text-ink-800/70" colspan="3">ไม่พบข้อมูลในตาราง academic_years</td>
              </tr>
            <?php endif; ?>
          </tbody>
        </table>
      </div>

      <div class="border-t border-black/5 bg-white px-4 py-3 text-xs text-ink-800/60">
        แหล่งข้อมูล: <span class="font-mono">school_app.academic_years</span>
      </div>
    </div>
  </div>
</section>
