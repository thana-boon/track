<?php
$setupMode = !(bool)($hasUsers ?? true);
?>

<section class="mx-auto grid min-h-[70vh] max-w-4xl place-items-center">
  <div class="w-full overflow-hidden rounded-3xl border border-black/5 bg-white/80 shadow-sm backdrop-blur">
    <div class="grid gap-0 md:grid-cols-2">

      <div class="p-6 md:p-8">
        <div class="flex items-start gap-3">
          <div class="grid h-11 w-11 place-items-center rounded-2xl bg-gradient-to-br from-pastel-sky to-pastel-lilac ring-1 ring-black/5">🔐</div>
          <div>
            <h1 class="text-xl font-semibold tracking-tight">เข้าสู่ระบบ</h1>
            <p class="mt-1 text-sm text-ink-800/70">เลือกบัญชีที่ได้รับจากโรงเรียน แล้วเข้าใช้งานตามสิทธิ์</p>
          </div>
        </div>

        <?php if (!empty($success)): ?>
          <div class="mt-4 rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-900"><?= e((string)$success) ?></div>
        <?php endif; ?>

        <?php if (!empty($error)): ?>
          <div class="mt-4 rounded-2xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-900"><?= e((string)$error) ?></div>
        <?php endif; ?>

        <form class="mt-6 space-y-4" method="post" action="/tracks/login">
          <input type="hidden" name="_csrf" value="<?= e(csrf_token()) ?>" />
          <input type="hidden" name="action" value="login" />

          <div>
            <label class="text-sm font-medium">Username</label>
            <input name="username" class="mt-1 w-full rounded-2xl border border-black/10 bg-white px-4 py-2.5 text-sm outline-none ring-0 focus:border-calm-500" autocomplete="username" placeholder="เช่น admin / teacher01 / 66001234" />
            <div class="mt-1 text-xs text-ink-800/60">หมายเหตุ: บัญชีนักเรียนแนะนำให้ใช้ Username = รหัสนักเรียน</div>
          </div>

          <div>
            <label class="text-sm font-medium">Password</label>
            <input type="password" name="password" class="mt-1 w-full rounded-2xl border border-black/10 bg-white px-4 py-2.5 text-sm outline-none focus:border-calm-500" autocomplete="current-password" />
          </div>

          <button class="inline-flex w-full items-center justify-center rounded-2xl bg-gradient-to-r from-ink-900 to-ink-800 px-4 py-2.5 text-sm font-medium text-white shadow-sm hover:opacity-95">
            ✨ เข้าสู่ระบบ
          </button>
        </form>

        <?php if ($setupMode): ?>
          <div class="mt-6 rounded-3xl border border-black/5 bg-gradient-to-b from-pastel-mint/40 to-sand-50 p-5">
            <h2 class="text-sm font-semibold">🧑‍🏫 สร้างผู้ใช้คนแรก (Admin)</h2>
            <p class="mt-1 text-xs text-ink-800/70">ระบบยังไม่มีผู้ใช้—สร้างผู้ใช้คนแรกเพื่อเริ่มต้นจัดการข้อมูล</p>

            <form class="mt-4 space-y-3" method="post" action="/tracks/login">
              <input type="hidden" name="_csrf" value="<?= e(csrf_token()) ?>" />
              <input type="hidden" name="action" value="setup" />

              <div>
                <label class="text-xs font-medium">Display name</label>
                <input name="displayname" class="mt-1 w-full rounded-2xl border border-black/10 bg-white px-4 py-2.5 text-sm outline-none focus:border-calm-500" placeholder="เช่น ผู้ดูแลระบบ" />
              </div>

              <div>
                <label class="text-xs font-medium">Username</label>
                <input name="username" class="mt-1 w-full rounded-2xl border border-black/10 bg-white px-4 py-2.5 text-sm outline-none focus:border-calm-500" placeholder="เช่น admin" />
              </div>

              <div class="grid gap-3 sm:grid-cols-2">
                <div>
                  <label class="text-xs font-medium">Password</label>
                  <input type="password" name="password" class="mt-1 w-full rounded-2xl border border-black/10 bg-white px-4 py-2.5 text-sm outline-none focus:border-calm-500" />
                </div>
                <div>
                  <label class="text-xs font-medium">Confirm</label>
                  <input type="password" name="confirm_password" class="mt-1 w-full rounded-2xl border border-black/10 bg-white px-4 py-2.5 text-sm outline-none focus:border-calm-500" />
                </div>
              </div>

              <button class="inline-flex w-full items-center justify-center rounded-2xl bg-calm-600 px-4 py-2.5 text-sm font-medium text-white shadow-sm hover:bg-calm-500">
                ✅ สร้างและเข้าสู่ระบบ
              </button>
            </form>
          </div>
        <?php endif; ?>
      </div>

      <div class="border-t border-black/5 bg-gradient-to-b from-sand-50 to-pastel-sky/25 p-6 md:border-t-0 md:border-l md:p-8">
        <div class="text-sm font-semibold">👤 เข้าใช้งานตามสิทธิ์ (3 Roles)</div>
        <p class="mt-1 text-xs text-ink-800/60">เมื่อเข้าสู่ระบบแล้ว ระบบจะพาไปหน้าที่ตรงกับ role โดยอัตโนมัติ</p>

        <div class="mt-4 grid gap-3">
          <div class="rounded-3xl border border-black/5 bg-white/70 p-4">
            <div class="flex items-center justify-between gap-3">
              <div class="font-semibold">🛠️ Admin</div>
              <span class="rounded-full bg-pastel-lilac/60 px-2.5 py-1 text-xs text-ink-800/70 ring-1 ring-black/5">จัดการระบบ</span>
            </div>
            <div class="mt-2 text-xs text-ink-800/60">จัดการผู้ใช้ • ปีการศึกษา • วิชา/ลงทะเบียน • รายงาน</div>
          </div>

          <div class="rounded-3xl border border-black/5 bg-white/70 p-4">
            <div class="flex items-center justify-between gap-3">
              <div class="font-semibold">📝 Teacher</div>
              <span class="rounded-full bg-pastel-mint/60 px-2.5 py-1 text-xs text-ink-800/70 ring-1 ring-black/5">เช็คชื่อ</span>
            </div>
            <div class="mt-2 text-xs text-ink-800/60">ตารางเรียนตามวัน • เช็คชื่อ • ดูนักเรียนประจำชั้น (ถ้ามีการกำหนด)</div>
          </div>

          <div class="rounded-3xl border border-black/5 bg-white/70 p-4">
            <div class="flex items-center justify-between gap-3">
              <div class="font-semibold">📘 Student</div>
              <span class="rounded-full bg-pastel-sky/60 px-2.5 py-1 text-xs text-ink-800/70 ring-1 ring-black/5">ผลการเรียน</span>
            </div>
            <div class="mt-2 text-xs text-ink-800/60">ดูวิชาที่ถูกกำหนด • เข้าเรียน/ผ่าน-ไม่ผ่าน • สรุปวิชาที่ผ่าน</div>
            <div class="mt-2 text-[11px] text-ink-800/55">แนะนำ: ตั้ง Username = รหัสนักเรียน เพื่ออ้างอิงข้อมูลได้ตรง</div>
          </div>
        </div>
      </div>

    </div>
  </div>
</section>
