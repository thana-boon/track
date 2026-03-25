<?php
$csrf = csrf_token();
?>
<div class="grid gap-6">
  <section class="rounded-3xl border border-black/5 bg-white/80 p-6 shadow-sm backdrop-blur">
    <div class="flex flex-wrap items-end justify-between gap-3">
      <div>
        <h1 class="text-xl font-semibold tracking-tight">👤 จัดการผู้ใช้</h1>
        <p class="mt-1 text-sm text-ink-800/70">ผู้ใช้ถูกเก็บในฐานข้อมูล <span class="font-mono">sukhon_track.users</span></p>
      </div>
    </div>

    <?php if (!empty($success)): ?>
      <div class="mt-4 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-900">
        <?= e((string)$success) ?>
      </div>
    <?php endif; ?>

    <?php if (!empty($error)): ?>
      <div class="mt-4 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-900">
        <?= e((string)$error) ?>
      </div>
    <?php endif; ?>

    <div class="mt-6 grid gap-4 lg:grid-cols-3">
      <form method="post" class="rounded-3xl border border-black/5 bg-gradient-to-b from-sand-50 to-pastel-lilac/25 p-5">
        <input type="hidden" name="_csrf" value="<?= e($csrf) ?>" />
        <input type="hidden" name="action" value="create" />

        <h2 class="text-sm font-semibold">➕ เพิ่มผู้ใช้</h2>
        <div class="mt-3 space-y-3">
          <div>
            <label class="text-xs font-medium">Display name</label>
            <input name="displayname" class="mt-1 w-full rounded-xl border border-black/10 bg-white px-4 py-2.5 text-sm outline-none focus:border-calm-500" />
          </div>
          <div>
            <label class="text-xs font-medium">Username</label>
            <input name="username" class="mt-1 w-full rounded-xl border border-black/10 bg-white px-4 py-2.5 text-sm outline-none focus:border-calm-500" />
          </div>
          <div>
            <label class="text-xs font-medium">Password</label>
            <input type="password" name="password" class="mt-1 w-full rounded-xl border border-black/10 bg-white px-4 py-2.5 text-sm outline-none focus:border-calm-500" />
          </div>
          <div>
            <label class="text-xs font-medium">Confirm password</label>
            <input type="password" name="confirm_password" class="mt-1 w-full rounded-xl border border-black/10 bg-white px-4 py-2.5 text-sm outline-none focus:border-calm-500" />
          </div>
          <button class="inline-flex w-full items-center justify-center rounded-2xl bg-calm-600 px-4 py-2.5 text-sm font-medium text-white shadow-sm hover:bg-calm-500">
            ✅ เพิ่มผู้ใช้
          </button>
        </div>
      </form>

      <div class="lg:col-span-2">
        <div class="overflow-hidden rounded-3xl border border-black/5">
          <table class="min-w-full bg-white text-sm">
            <thead class="bg-sand-100 text-left text-xs text-ink-800/70">
              <tr>
                <th class="px-4 py-3">Username</th>
                <th class="px-4 py-3">Display name</th>
                <th class="px-4 py-3">Created</th>
                <th class="px-4 py-3">Actions</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-black/5">
              <?php foreach (($users ?? []) as $u): ?>
                <tr class="hover:bg-sand-50">
                  <td class="px-4 py-3 font-medium"><?= e((string)$u['username']) ?></td>
                  <td class="px-4 py-3">
                    <form method="post" class="flex items-center gap-2">
                      <input type="hidden" name="_csrf" value="<?= e($csrf) ?>" />
                      <input type="hidden" name="action" value="update_display" />
                      <input type="hidden" name="id" value="<?= (int)$u['id'] ?>" />
                      <input name="displayname" value="<?= e((string)$u['displayname']) ?>" class="w-full max-w-xs rounded-xl border border-black/10 bg-white px-3 py-2 text-sm outline-none focus:border-calm-500" />
                      <button class="rounded-xl border border-black/10 bg-white px-3 py-2 text-xs hover:bg-black/5">บันทึก</button>
                    </form>
                  </td>
                  <td class="px-4 py-3 text-xs text-ink-800/70"><?= e((string)$u['created_at']) ?></td>
                  <td class="px-4 py-3">
                    <div class="flex flex-wrap gap-2">
                      <form method="post" class="flex items-center gap-2">
                        <input type="hidden" name="_csrf" value="<?= e($csrf) ?>" />
                        <input type="hidden" name="action" value="reset_password" />
                        <input type="hidden" name="id" value="<?= (int)$u['id'] ?>" />
                        <input type="password" name="new_password" placeholder="รหัสผ่านใหม่" class="w-36 rounded-xl border border-black/10 bg-white px-3 py-2 text-xs outline-none focus:border-calm-500" />
                        <input type="password" name="confirm_new_password" placeholder="ยืนยัน" class="w-28 rounded-xl border border-black/10 bg-white px-3 py-2 text-xs outline-none focus:border-calm-500" />
                        <button class="rounded-xl bg-ink-900 px-3 py-2 text-xs font-medium text-white hover:bg-ink-800">🔁 ตั้งใหม่</button>
                      </form>

                      <form method="post" onsubmit="return confirm('ยืนยันลบผู้ใช้นี้?');">
                        <input type="hidden" name="_csrf" value="<?= e($csrf) ?>" />
                        <input type="hidden" name="action" value="delete" />
                        <input type="hidden" name="id" value="<?= (int)$u['id'] ?>" />
                        <button class="rounded-xl border border-red-200 bg-red-50 px-3 py-2 text-xs text-red-700 hover:bg-red-100">🗑️ ลบ</button>
                      </form>
                    </div>
                  </td>
                </tr>
              <?php endforeach; ?>

              <?php if (empty($users)): ?>
                <tr>
                  <td colspan="4" class="px-4 py-8 text-center text-sm text-ink-800/70">ยังไม่มีผู้ใช้</td>
                </tr>
              <?php endif; ?>
            </tbody>
          </table>
        </div>

        <p class="mt-3 text-xs text-ink-800/60">หมายเหตุ: รหัสผ่านถูกเก็บแบบแฮช (password_hash)</p>
      </div>
    </div>
  </section>
</div>
