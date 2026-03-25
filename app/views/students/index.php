<?php
$csrf = csrf_token(); // not used currently but kept for future actions
$filters = $filters ?? [];
?>
<div class="grid gap-6">
  <section class="rounded-3xl border border-black/5 bg-white/80 p-6 shadow-sm backdrop-blur">
    <div class="flex flex-wrap items-end justify-between gap-3">
      <div>
        <h1 class="text-xl font-semibold tracking-tight">📋 รายชื่อ (ม.4 - ม.6)</h1>
        <p class="mt-1 text-sm text-ink-800/70">เลือกปีการศึกษาและกรองข้อมูลได้แบบนุ่มๆ</p>
      </div>
      <a class="rounded-2xl border border-black/10 bg-white px-4 py-2 text-sm hover:bg-black/5" href="/tracks/?route=students">🧹 ล้างตัวกรอง</a>
    </div>

    <form class="mt-6 grid gap-3 rounded-3xl border border-black/5 bg-gradient-to-b from-sand-50 to-pastel-sky/25 p-4 md:grid-cols-6" method="get" action="/tracks/">
      <input type="hidden" name="route" value="students" />

      <div class="md:col-span-2">
        <label class="text-xs font-medium">ปีการศึกษา</label>
        <select name="year_id" class="mt-1 w-full rounded-xl border border-black/10 bg-white px-3 py-2.5 text-sm outline-none focus:border-calm-500">
          <?php foreach (($years ?? []) as $y): ?>
            <?php
              $id = (int)$y['id'];
              $label = (string)$y['title'] . ' (' . (string)$y['year_be'] . ')';
              $active = ((int)$y['is_active'] === 1) ? ' • active' : '';
            ?>
            <option value="<?= $id ?>" <?= ($id === (int)($yearId ?? 0)) ? 'selected' : '' ?>><?= e($label . $active) ?></option>
          <?php endforeach; ?>
        </select>
      </div>

      <div class="md:col-span-2">
        <label class="text-xs font-medium">ค้นหา</label>
        <input name="q" value="<?= e((string)($filters['q'] ?? '')) ?>" placeholder="เลขประจำตัว / ชื่อ / นามสกุล" class="mt-1 w-full rounded-xl border border-black/10 bg-white px-3 py-2.5 text-sm outline-none focus:border-calm-500" />
      </div>

      <div>
        <label class="text-xs font-medium">ชั้น</label>
        <select name="class_level" class="mt-1 w-full rounded-xl border border-black/10 bg-white px-3 py-2.5 text-sm outline-none focus:border-calm-500">
          <?php foreach (($classLevelOptions ?? []) as $val => $label): ?>
            <option value="<?= e((string)$val) ?>" <?= ((string)($filters['class_level'] ?? '') === (string)$val) ? 'selected' : '' ?>><?= e((string)$label) ?></option>
          <?php endforeach; ?>
        </select>
      </div>

      <div>
        <label class="text-xs font-medium">ห้อง</label>
        <input name="room" value="<?= e((string)($filters['room'] ?? '')) ?>" placeholder="เช่น 1" class="mt-1 w-full rounded-xl border border-black/10 bg-white px-3 py-2.5 text-sm outline-none focus:border-calm-500" />
      </div>

      <div>
        <label class="text-xs font-medium">เลขที่</label>
        <input name="number" value="<?= e((string)($filters['number'] ?? '')) ?>" placeholder="เช่น 12" class="mt-1 w-full rounded-xl border border-black/10 bg-white px-3 py-2.5 text-sm outline-none focus:border-calm-500" />
      </div>

      <div class="md:col-span-6 flex flex-wrap items-center gap-2">
        <button class="inline-flex items-center justify-center rounded-2xl bg-gradient-to-r from-ink-900 to-ink-800 px-4 py-2.5 text-sm font-medium text-white shadow-sm hover:opacity-95">🔎 กรองข้อมูล</button>
        <p class="text-xs text-ink-800/60">ถ้าข้อมูลเยอะ แนะนำใช้ค้นหา/กรองก่อน</p>
      </div>
    </form>

    <div class="mt-6 overflow-hidden rounded-3xl border border-black/5">
      <table class="min-w-full bg-white text-sm">
        <thead class="bg-sand-100 text-left text-xs text-ink-800/70">
          <tr>
            <th class="px-4 py-3">🆔 เลขประจำตัว</th>
            <th class="px-4 py-3">🏫 ชั้น</th>
            <th class="px-4 py-3">🚪 ห้อง</th>
            <th class="px-4 py-3">🔢 เลขที่</th>
            <th class="px-4 py-3">🙂 ชื่อจริง</th>
            <th class="px-4 py-3">🧡 นามสกุล</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-black/5">
          <?php foreach (($students ?? []) as $s): ?>
            <tr class="hover:bg-sand-50">
              <td class="px-4 py-3 font-medium"><?= e((string)$s['student_code']) ?></td>
              <td class="px-4 py-3"><?= e((string)$s['class_level']) ?></td>
              <td class="px-4 py-3"><?= e((string)$s['class_room']) ?></td>
              <td class="px-4 py-3"><?= e((string)$s['number_in_room']) ?></td>
              <td class="px-4 py-3"><?= e((string)$s['first_name']) ?></td>
              <td class="px-4 py-3"><?= e((string)$s['last_name']) ?></td>
            </tr>
          <?php endforeach; ?>

          <?php if (empty($students)): ?>
            <tr>
              <td colspan="6" class="px-4 py-10 text-center text-sm text-ink-800/70">ไม่พบข้อมูลตามเงื่อนไข</td>
            </tr>
          <?php endif; ?>
        </tbody>
      </table>
    </div>

    <div class="mt-3 text-xs text-ink-800/60">
      แหล่งข้อมูล: <span class="font-mono">school_app.academic_years</span> และ <span class="font-mono">school_app.students</span>
    </div>
  </section>
</div>
