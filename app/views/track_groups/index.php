<?php
$csrf = csrf_token();
$editing = is_array($editing ?? null) ? $editing : null;
?>

<div class="grid gap-6">
  <section class="rounded-3xl border border-base-300 bg-base-100/80 p-5 shadow-sm backdrop-blur">
    <div>
      <h1 class="text-xl font-semibold tracking-tight">🧩 กลุ่ม Track</h1>
      <p class="mt-1 text-sm text-base-content/70">สร้าง/แก้ไข/ลบ กลุ่ม เช่น วิศวะ, แพทย์ เพื่อใช้จัดหมวดวิชา</p>
    </div>

    <?php if (!empty($success)): ?>
      <div class="mt-4 rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-900">
        <?= e((string)$success) ?>
      </div>
    <?php endif; ?>

    <?php if (!empty($error)): ?>
      <div class="mt-4 rounded-2xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-900">
        <?= e((string)$error) ?>
      </div>
    <?php endif; ?>

    <div class="mt-4 grid gap-4">
      <div class="flex h-full flex-col rounded-3xl border border-base-300 bg-base-200 p-5">
        <h2 class="text-sm font-semibold"><?= $editing ? '✏️ แก้ไขกลุ่ม' : '➕ เพิ่มกลุ่ม' ?></h2>
        <p class="mt-1 text-xs text-base-content/60">ตัวอย่าง: วิศวะ, แพทย์, คอมพิวเตอร์</p>

        <form method="post" class="mt-4 space-y-3">
          <input type="hidden" name="_csrf" value="<?= e($csrf) ?>" />
          <input type="hidden" name="action" value="<?= $editing ? 'update' : 'create' ?>" />
          <?php if ($editing): ?>
            <input type="hidden" name="id" value="<?= (int)$editing['id'] ?>" />
          <?php endif; ?>

          <div>
            <label class="text-xs font-medium">ชื่อกลุ่ม</label>
            <input name="title" value="<?= e((string)($editing['title'] ?? '')) ?>" class="mt-1 w-full rounded-2xl border border-base-300 bg-base-100 px-4 py-2.5 text-sm outline-none focus:border-calm-500" placeholder="เช่น วิศวะ" />
          </div>

          <div class="flex items-center justify-between gap-3">
            <label class="inline-flex items-center gap-2 text-sm">
              <?php $checked = $editing ? ((int)($editing['is_active'] ?? 1) === 1) : true; ?>
              <input type="checkbox" name="is_active" value="1" <?= $checked ? 'checked' : '' ?> class="h-4 w-4 rounded border-base-300 text-calm-600 focus:ring-calm-500" />
              <span>เปิดใช้งาน</span>
            </label>

            <div class="flex items-center gap-2">
              <?php if ($editing): ?>
                <a class="rounded-2xl border border-base-300 bg-base-100 px-3 py-2 text-xs hover:bg-base-200" href="/tracks/track-groups">ยกเลิก</a>
              <?php endif; ?>
              <button class="rounded-2xl bg-neutral px-4 py-2 text-sm font-medium text-white shadow-sm hover:opacity-90">
                <?= $editing ? '💾 บันทึก' : '✅ เพิ่มกลุ่ม' ?>
              </button>
            </div>
          </div>
        </form>

        <div class="mt-4 rounded-2xl border border-base-300 bg-base-100/70 p-3 text-xs text-base-content/60">
          กลุ่มที่ถูกใช้ใน “วิชา Track” จะลบไม่ได้ เพื่อกันข้อมูลหลุด
        </div>
      </div>
    </div>

    <div class="mt-4 overflow-hidden rounded-3xl border border-base-300">
      <table class="min-w-full bg-base-100 text-sm">
        <thead class="bg-base-200 text-left text-xs text-base-content/70">
          <tr>
            <th class="px-4 py-3">🧩 กลุ่ม</th>
            <th class="px-4 py-3">สถานะ</th>
            <th class="px-4 py-3">อัปเดต</th>
            <th class="px-4 py-3">การทำงาน</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-base-300">
          <?php foreach (($groups ?? []) as $g): ?>
            <tr class="hover:bg-base-200">
              <td class="px-4 py-3">
                <div class="font-medium"><?= e((string)$g['title']) ?></div>
              </td>
              <td class="px-4 py-3">
                <?php if ((int)$g['is_active'] === 1): ?>
                  <span class="inline-flex items-center rounded-full bg-emerald-50 px-2.5 py-1 text-xs text-emerald-800 ring-1 ring-emerald-200">🟢 เปิด</span>
                <?php else: ?>
                  <span class="inline-flex items-center rounded-full bg-zinc-100 px-2.5 py-1 text-xs text-zinc-700 ring-1 ring-black/10">⚪ ปิด</span>
                <?php endif; ?>
              </td>
              <td class="px-4 py-3 text-xs text-base-content/60">
                <?= e((string)($g['updated_at'] ?: $g['created_at'])) ?>
              </td>
              <td class="px-4 py-3">
                <div class="flex flex-wrap gap-2">
                  <a class="rounded-2xl border border-base-300 bg-base-100 px-3 py-2 text-xs hover:bg-base-200" href="/tracks/track-groups?edit=<?= (int)$g['id'] ?>">✏️ แก้ไข</a>
                  <form method="post" data-confirm="ยืนยันลบกลุ่มนี้? (ถ้ามีวิชาใช้อยู่จะลบไม่ได้)">
                    <input type="hidden" name="_csrf" value="<?= e($csrf) ?>" />
                    <input type="hidden" name="action" value="delete" />
                    <input type="hidden" name="id" value="<?= (int)$g['id'] ?>" />
                    <button class="rounded-2xl border border-red-200 bg-red-50 px-3 py-2 text-xs text-red-700 hover:bg-red-100">🗑️ ลบ</button>
                  </form>
                </div>
              </td>
            </tr>
          <?php endforeach; ?>

          <?php if (empty($groups)): ?>
            <tr>
              <td colspan="4" class="px-4 py-10 text-center text-sm text-base-content/70">ยังไม่มีกลุ่ม—ลองเพิ่มกลุ่มแรกได้เลย</td>
            </tr>
          <?php endif; ?>
        </tbody>
      </table>
    </div>

    <p class="mt-3 text-xs text-base-content/60">ข้อมูลถูกเก็บใน <span class="font-mono">sukhon_track.track_groups</span></p>
  </section>
</div>
