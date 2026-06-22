<?php
$csrf = (string)($csrf ?? csrf_token());
$editing = is_array($editing ?? null) ? $editing : null;
$groups = is_array($groups ?? null) ? $groups : [];
?>

<div class="grid gap-6">
  <section class="rounded-3xl border border-base-300 bg-base-100/80 p-5 shadow-sm backdrop-blur">
    <div class="flex flex-wrap items-end justify-between gap-3">
      <div>
        <h1 class="text-xl font-semibold tracking-tight">👥 กลุ่มเรียน</h1>
        <p class="mt-1 text-sm text-base-content/70">สร้างกลุ่มนักเรียนที่ใช้บ่อย เพื่อเลือกเพิ่มลงรอบเรียนได้ทีเดียว</p>
      </div>
      <a class="rounded-2xl border border-base-300 bg-base-100 px-4 py-2.5 text-sm hover:bg-base-200" href="/tracks/class_attendance_create">➕ ไปสร้างรอบเรียน</a>
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

    <div class="mt-4 grid gap-4 lg:grid-cols-5">
      <!-- ─── Form panel ─── -->
      <div class="lg:col-span-2 flex flex-col rounded-3xl border border-base-300 bg-base-200 p-5">
        <h2 class="text-sm font-semibold"><?= $editing ? '✏️ แก้ไขกลุ่มเรียน' : '➕ สร้างกลุ่มเรียนใหม่' ?></h2>
        <p class="mt-1 text-xs text-base-content/60">ใส่รหัสนักเรียน 1 บรรทัดต่อ 1 คน (ใส่แค่รหัส หรือ ชื่อตามด้วยรหัสก็ได้)</p>

        <form method="post" class="mt-4 flex flex-col gap-3">
          <input type="hidden" name="_csrf" value="<?= e($csrf) ?>" />
          <input type="hidden" name="action" value="<?= $editing ? 'update' : 'create' ?>" />
          <?php if ($editing): ?>
            <input type="hidden" name="id" value="<?= (int)$editing['id'] ?>" />
          <?php endif; ?>

          <div>
            <label class="text-xs font-medium">ชื่อกลุ่ม <span class="text-red-500">*</span></label>
            <input
              name="title"
              value="<?= e((string)($editing['title'] ?? '')) ?>"
              placeholder="เช่น กลุ่มที่ 1 / กลุ่มเช้า / ม.5/2"
              class="mt-1 w-full rounded-2xl border border-base-300 bg-base-100 px-3 py-2.5 text-sm outline-none focus:border-calm-500"
              required
            />
          </div>

          <div>
            <label class="text-xs font-medium">รหัสนักเรียน</label>
            <textarea
              name="student_codes_text"
              rows="12"
              placeholder="ตัวอย่าง:&#10;65001&#10;สมชาย ใจดี 65002&#10;65003"
              class="mt-1 w-full rounded-2xl border border-base-300 bg-base-100 px-3 py-2.5 font-mono text-sm outline-none focus:border-calm-500"
            ><?= e((string)($editing['student_codes'] ?? '')) ?></textarea>
            <p class="mt-1 text-[11px] text-base-content/60">รองรับ "65001" หรือ "ชื่อ นามสกุล 65001" — ระบบจะดึงตัวเลขท้ายเป็นรหัส</p>
          </div>

          <div class="flex items-center justify-end gap-2">
            <?php if ($editing): ?>
              <a class="rounded-2xl border border-base-300 bg-base-100 px-4 py-2.5 text-sm hover:bg-base-200" href="/tracks/class-groups">ยกเลิก</a>
            <?php endif; ?>
            <button class="rounded-2xl bg-neutral px-5 py-2.5 text-sm font-medium text-white shadow-sm hover:opacity-90">
              <?= $editing ? '💾 บันทึก' : '✅ สร้างกลุ่ม' ?>
            </button>
          </div>
        </form>
      </div>

      <!-- ─── Groups list ─── -->
      <div class="lg:col-span-3">
        <?php if (empty($groups)): ?>
          <div class="rounded-3xl border border-dashed border-base-300 bg-base-100/60 py-16 text-center text-sm text-base-content/60">
            ยังไม่มีกลุ่มเรียน — สร้างกลุ่มแรกได้เลย
          </div>
        <?php else: ?>
          <div class="grid gap-3">
            <?php foreach ($groups as $g): ?>
              <?php
                $gid    = (int)$g['id'];
                $gtitle = (string)$g['title'];
                $gcodes = class_group_parse_codes((string)($g['student_codes'] ?? ''));
                $gcount = count($gcodes);
                $preview = implode(', ', array_slice($gcodes, 0, 8));
                if ($gcount > 8) $preview .= ' …';
              ?>
              <div class="rounded-3xl border border-base-300 bg-base-100/80 p-4 shadow-sm">
                <div class="flex flex-wrap items-start justify-between gap-2">
                  <div class="min-w-0">
                    <div class="flex items-center gap-2 flex-wrap">
                      <span class="font-semibold"><?= e($gtitle) ?></span>
                      <span class="rounded-full bg-pastel-sky/60 px-2.5 py-0.5 text-xs text-base-content ring-1 ring-base-300"><?= $gcount ?> คน</span>
                    </div>
                    <?php if ($preview !== ''): ?>
                      <p class="mt-1 text-xs text-base-content/60 break-all"><?= e($preview) ?></p>
                    <?php endif; ?>
                    <p class="mt-1 text-[11px] text-base-content/40">
                      <?= e((string)($g['updated_at'] ?: $g['created_at'])) ?>
                    </p>
                  </div>
                  <div class="flex shrink-0 items-center gap-2">
                    <a
                      class="rounded-2xl border border-base-300 bg-base-100 px-3 py-2 text-xs hover:bg-base-200"
                      href="/tracks/class-groups?edit=<?= $gid ?>"
                    >✏️ แก้ไข</a>
                    <form
                      method="post"
                      data-confirm="ยืนยันลบกลุ่ม «<?= e($gtitle) ?>»?"
                      data-confirm-ok="ลบ"
                    >
                      <input type="hidden" name="_csrf" value="<?= e($csrf) ?>" />
                      <input type="hidden" name="action" value="delete" />
                      <input type="hidden" name="id" value="<?= $gid ?>" />
                      <button class="rounded-2xl border border-red-200 bg-red-50 px-3 py-2 text-xs text-red-700 hover:bg-red-100">🗑️ ลบ</button>
                    </form>
                    <a
                      class="rounded-2xl border border-calm-600/30 bg-calm-100 px-3 py-2 text-xs text-calm-700 hover:bg-calm-100/70"
                      href="/tracks/class_attendance_create?use_group=<?= $gid ?>"
                      title="นำกลุ่มนี้ไปสร้างรอบเรียน"
                    >➕ สร้างรอบ</a>
                  </div>
                </div>
              </div>
            <?php endforeach; ?>
          </div>
        <?php endif; ?>
      </div>
    </div>
  </section>
</div>
