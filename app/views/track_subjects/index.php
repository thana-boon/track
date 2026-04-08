<?php
$csrf = csrf_token();
$editing = is_array($editing ?? null) ? $editing : null;
$groups = is_array($groups ?? null) ? $groups : [];
?>

<div class="grid gap-6">
  <section class="rounded-3xl border border-black/5 bg-white/80 p-5 shadow-sm backdrop-blur">
    <div class="flex flex-wrap items-end justify-between gap-3">
      <div>
        <h1 class="text-xl font-semibold tracking-tight">🧩 วิชา Track</h1>
        <p class="mt-1 text-sm text-ink-800/70">เพิ่ม/แก้ไข/ลบ วิชาที่ใช้ให้ลงทะเบียนในอนาคต</p>
      </div>

      <div class="flex flex-wrap items-center gap-2">
        <a class="rounded-2xl border border-black/10 bg-white px-4 py-2 text-sm hover:bg-black/5" href="/tracks/track-subjects?export=1&_csrf=<?= e($csrf) ?>">⬇️ Export CSV</a>
      </div>
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

    <div class="mt-4 grid gap-4 lg:grid-cols-2 lg:items-stretch">
      <div class="flex h-full flex-col rounded-3xl border border-black/5 bg-gradient-to-b from-sand-50 to-pastel-mint/25 p-5">
        <h2 class="text-sm font-semibold"><?= $editing ? '✏️ แก้ไขวิชา' : '➕ เพิ่มวิชา' ?></h2>
        <p class="mt-1 text-xs text-ink-800/60">ตัวอย่าง: การจัดการครัว, การสร้างโปรแกรม, คณิตขั้นสูง</p>

        <?php if (empty($groups)): ?>
          <div class="mt-3 rounded-2xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-900">
            ยังไม่มีกลุ่ม Track — กรุณาสร้างกลุ่มก่อนที่ <a class="underline" href="/tracks/track-groups">หน้า กลุ่ม Track</a>
          </div>
        <?php endif; ?>

        <form method="post" class="mt-4 space-y-3">
          <input type="hidden" name="_csrf" value="<?= e($csrf) ?>" />
          <input type="hidden" name="action" value="<?= $editing ? 'update' : 'create' ?>" />
          <?php if ($editing): ?>
            <input type="hidden" name="id" value="<?= (int)$editing['id'] ?>" />
          <?php endif; ?>

          <div>
            <label class="text-xs font-medium">กลุ่ม Track</label>
            <select name="group_id" class="mt-1 w-full rounded-2xl border border-black/10 bg-white px-4 py-2.5 text-sm outline-none focus:border-calm-500" <?= empty($groups) ? 'disabled' : '' ?>>
              <option value="">— เลือกกลุ่ม —</option>
              <?php $selectedGroupId = (int)($editing['group_id'] ?? 0); ?>
              <?php foreach ($groups as $g): ?>
                <?php $gid = (int)($g['id'] ?? 0); ?>
                <option value="<?= $gid ?>" <?= ($gid > 0 && $gid === $selectedGroupId) ? 'selected' : '' ?>><?= e((string)$g['title']) ?></option>
              <?php endforeach; ?>
            </select>
          </div>

          <div>
            <label class="text-xs font-medium">รหัสวิชา</label>
            <input name="subject_code" value="<?= e((string)($editing['subject_code'] ?? '')) ?>" class="mt-1 w-full rounded-2xl border border-black/10 bg-white px-4 py-2.5 text-sm outline-none focus:border-calm-500" placeholder="เช่น ENG101" />
          </div>

          <div>
            <label class="text-xs font-medium">ชื่อวิชา</label>
            <input name="title" value="<?= e((string)($editing['title'] ?? '')) ?>" class="mt-1 w-full rounded-2xl border border-black/10 bg-white px-4 py-2.5 text-sm outline-none focus:border-calm-500" placeholder="เช่น การสร้างโปรแกรม" />
          </div>

          <div>
            <label class="text-xs font-medium">รายละเอียด (ไม่บังคับ)</label>
            <textarea name="description" rows="3" class="mt-1 w-full rounded-2xl border border-black/10 bg-white px-4 py-2.5 text-sm outline-none focus:border-calm-500" placeholder="รายละเอียดสั้นๆ ของวิชา"><?= e((string)($editing['description'] ?? '')) ?></textarea>
          </div>

          <div class="flex items-center justify-between gap-3">
            <label class="inline-flex items-center gap-2 text-sm">
              <?php $checked = $editing ? ((int)($editing['is_active'] ?? 1) === 1) : true; ?>
              <input type="checkbox" name="is_active" value="1" <?= $checked ? 'checked' : '' ?> class="h-4 w-4 rounded border-black/20 text-calm-600 focus:ring-calm-500" />
              <span>เปิดใช้งาน</span>
            </label>

            <div class="flex items-center gap-2">
              <?php if ($editing): ?>
                <a class="rounded-2xl border border-black/10 bg-white px-3 py-2 text-xs hover:bg-black/5" href="/tracks/track-subjects">ยกเลิก</a>
              <?php endif; ?>
              <button class="rounded-2xl bg-calm-600 px-4 py-2 text-sm font-medium text-white shadow-sm hover:bg-calm-500">
                <?= $editing ? '💾 บันทึก' : '✅ เพิ่มวิชา' ?>
              </button>
            </div>
          </div>
        </form>
      </div>

      <div class="flex h-full flex-col rounded-3xl border border-black/5 bg-gradient-to-b from-sand-50 to-pastel-sky/25 p-5">
        <h2 class="text-sm font-semibold">📦 Import CSV</h2>
        <p class="mt-1 text-xs text-ink-800/60">คอลัมน์ใหม่: <span class="font-mono">subject_code,title,group_title,description,is_active</span> (รองรับไฟล์แบบเก่า <span class="font-mono">title,description,is_active</span> ด้วย)</p>

        <form method="post" enctype="multipart/form-data" class="mt-4 space-y-3">
          <input type="hidden" name="_csrf" value="<?= e($csrf) ?>" />
          <input type="hidden" name="action" value="import_csv" />

          <div>
            <label class="text-xs font-medium">ไฟล์ CSV</label>
            <input type="file" name="csv_file" accept=".csv,text/csv" class="mt-1 block w-full text-sm" />
            <p class="mt-1 text-xs text-ink-800/60">จำกัดขนาด 2MB</p>
          </div>

          <div>
            <label class="text-xs font-medium">โหมดนำเข้า</label>
            <select name="import_mode" class="mt-1 w-full rounded-2xl border border-black/10 bg-white px-4 py-2.5 text-sm outline-none focus:border-calm-500">
              <option value="upsert" selected>🔁 อัปเดตทับเมื่อชื่อวิชาซ้ำ (แนะนำ)</option>
              <option value="skip">⏭️ ข้ามเมื่อชื่อวิชาซ้ำ</option>
            </select>
          </div>

          <button class="rounded-2xl bg-ink-900 px-4 py-2.5 text-sm font-medium text-white shadow-sm hover:bg-ink-800">📤 นำเข้า CSV</button>

          <div class="rounded-2xl border border-black/5 bg-white/70 p-3 text-xs text-ink-800/60">
            เคล็ดลับ: ถ้าเปิดใน Excel แล้วตัวหนังสือเพี้ยน ให้บันทึกเป็น CSV UTF-8
          </div>
        </form>
      </div>
    </div>

    <div class="mt-4 overflow-hidden rounded-3xl border border-black/5">
      <table class="min-w-full bg-white text-sm">
        <thead class="bg-sand-100 text-left text-xs text-ink-800/70">
          <tr>
            <th class="px-4 py-3">🔤 รหัส</th>
            <th class="px-4 py-3">🧩 กลุ่ม</th>
            <th class="px-4 py-3">🧩 วิชา</th>
            <th class="px-4 py-3">สถานะ</th>
            <th class="px-4 py-3">อัปเดต</th>
            <th class="px-4 py-3">การทำงาน</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-black/5">
          <?php foreach (($subjects ?? []) as $s): ?>
            <tr class="hover:bg-sand-50">
              <td class="px-4 py-3 text-xs text-ink-800/80">
                <span class="rounded-xl border border-black/10 bg-white px-2.5 py-1">
                  <?= e((string)($s['subject_code'] ?? '-')) ?>
                </span>
              </td>
              <td class="px-4 py-3 text-xs text-ink-800/70">
                <?= e((string)($s['group_title'] ?? '-')) ?>
              </td>
              <td class="px-4 py-3">
                <div class="font-medium"><?= e((string)$s['title']) ?></div>
                <?php if (!empty($s['description'])): ?>
                  <div class="mt-1 line-clamp-2 text-xs text-ink-800/60"><?= e((string)$s['description']) ?></div>
                <?php endif; ?>
              </td>
              <td class="px-4 py-3">
                <?php if ((int)$s['is_active'] === 1): ?>
                  <span class="inline-flex items-center rounded-full bg-emerald-50 px-2.5 py-1 text-xs text-emerald-800 ring-1 ring-emerald-200">🟢 เปิด</span>
                <?php else: ?>
                  <span class="inline-flex items-center rounded-full bg-zinc-100 px-2.5 py-1 text-xs text-zinc-700 ring-1 ring-black/10">⚪ ปิด</span>
                <?php endif; ?>
              </td>
              <td class="px-4 py-3 text-xs text-ink-800/60">
                <?= e((string)($s['updated_at'] ?: $s['created_at'])) ?>
              </td>
              <td class="px-4 py-3">
                <div class="flex flex-wrap gap-2">
                  <a class="rounded-2xl border border-black/10 bg-white px-3 py-2 text-xs hover:bg-black/5" href="/tracks/track-subjects?edit=<?= (int)$s['id'] ?>">✏️ แก้ไข</a>
                  <form method="post" data-confirm="ยืนยันลบวิชานี้?">
                    <input type="hidden" name="_csrf" value="<?= e($csrf) ?>" />
                    <input type="hidden" name="action" value="delete" />
                    <input type="hidden" name="id" value="<?= (int)$s['id'] ?>" />
                    <button class="rounded-2xl border border-red-200 bg-red-50 px-3 py-2 text-xs text-red-700 hover:bg-red-100">🗑️ ลบ</button>
                  </form>
                </div>
              </td>
            </tr>
          <?php endforeach; ?>

          <?php if (empty($subjects)): ?>
            <tr>
              <td colspan="6" class="px-4 py-10 text-center text-sm text-ink-800/70">ยังไม่มีวิชา—ลองเพิ่มวิชาแรกได้เลย</td>
            </tr>
          <?php endif; ?>
        </tbody>
      </table>
    </div>

    <p class="mt-3 text-xs text-ink-800/60">ข้อมูลถูกเก็บใน <span class="font-mono">sukhon_track.track_subjects</span></p>
  </section>
</div>
