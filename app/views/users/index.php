<?php
$csrf = csrf_token();
$filters = $filters ?? ['q' => '', 'role' => ''];
$pagination = $pagination ?? ['page' => 1, 'pageSize' => 20, 'total' => 0, 'totalPages' => 1, 'baseQuery' => ''];

$q = (string)($filters['q'] ?? '');
$roleFilter = (string)($filters['role'] ?? '');

$page = (int)($pagination['page'] ?? 1);
$pageSize = (int)($pagination['pageSize'] ?? 20);
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
        <h1 class="text-xl font-semibold tracking-tight">👤 จัดการผู้ใช้</h1>
        <p class="mt-1 text-sm text-ink-800/70">ผู้ใช้ถูกเก็บในฐานข้อมูล <span class="font-mono">sukhon_track.users</span></p>
      </div>

      <div class="rounded-2xl border border-black/5 bg-sand-100/70 px-4 py-3 text-sm">
        <div class="text-xs text-ink-800/60">รายการผู้ใช้</div>
        <div class="font-semibold"><?= $total > 0 ? e((string)$from . '-' . (string)$to . ' จาก ' . (string)$total) : '0' ?></div>
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

    <div class="mt-6 grid gap-4 lg:grid-cols-2">
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
          <div>
            <label class="text-xs font-medium">Role</label>
            <select name="role" class="mt-1 w-full rounded-xl border border-black/10 bg-white px-4 py-2.5 text-sm outline-none focus:border-calm-500">
              <option value="admin">admin</option>
              <option value="teacher" selected>teacher</option>
              <option value="student">student</option>
            </select>
          </div>
          <button class="inline-flex w-full items-center justify-center rounded-2xl bg-calm-600 px-4 py-2.5 text-sm font-medium text-white shadow-sm hover:bg-calm-500">
            ✅ เพิ่มผู้ใช้
          </button>
        </div>
      </form>

      <form method="post" enctype="multipart/form-data" class="rounded-3xl border border-black/5 bg-gradient-to-b from-sand-50 to-pastel-sky/20 p-5">
        <input type="hidden" name="_csrf" value="<?= e($csrf) ?>" />
        <input type="hidden" name="action" value="import_csv" />

        <h2 class="text-sm font-semibold">📥 นำเข้าผู้ใช้ (CSV)</h2>
        <p class="mt-1 text-xs text-ink-800/60">รูปแบบคอลัมน์: <span class="font-mono">username,displayname,password,role</span> (ต้องมี role ทุกแถว • มี header <span class="font-mono">username</span> ได้)</p>

        <div class="mt-3 space-y-3">
          <div>
            <label class="text-xs font-medium">ไฟล์ CSV</label>
            <input type="file" name="import_file" accept=".csv,text/csv" class="mt-1 w-full rounded-xl border border-black/10 bg-white px-4 py-2.5 text-sm outline-none focus:border-calm-500" />
            <div class="mt-1 text-[11px] text-ink-800/60">ตัวอย่างบรรทัด: <span class="font-mono">teacher01,ครูสมชาย,Pass1234,teacher</span></div>
          </div>

          <button class="inline-flex w-full items-center justify-center rounded-2xl bg-ink-900 px-4 py-2.5 text-sm font-medium text-white shadow-sm hover:bg-ink-800">⬆️ Import</button>
        </div>
      </form>
    </div>

    <form class="mt-6 grid gap-3 rounded-3xl border border-black/5 bg-gradient-to-b from-sand-50 to-pastel-mint/25 p-5 md:grid-cols-6" method="get" action="/tracks/users">

      <div class="md:col-span-3">
        <label class="text-xs font-medium">ค้นหา</label>
        <input name="q" value="<?= e($q) ?>" placeholder="ค้นหา username / display name" class="mt-1 w-full rounded-2xl border border-black/10 bg-white px-4 py-2.5 text-sm outline-none focus:border-calm-500" />
      </div>

      <div class="md:col-span-2">
        <label class="text-xs font-medium">Role</label>
        <select name="role" class="mt-1 w-full rounded-2xl border border-black/10 bg-white px-4 py-2.5 text-sm outline-none focus:border-calm-500">
          <option value="" <?= $roleFilter === '' ? 'selected' : '' ?>>ทั้งหมด</option>
          <option value="admin" <?= $roleFilter === 'admin' ? 'selected' : '' ?>>admin</option>
          <option value="teacher" <?= $roleFilter === 'teacher' ? 'selected' : '' ?>>teacher</option>
          <option value="student" <?= $roleFilter === 'student' ? 'selected' : '' ?>>student</option>
        </select>
      </div>

      <div class="md:col-span-1 flex items-end gap-2">
        <button class="inline-flex w-full items-center justify-center rounded-2xl bg-ink-900 px-4 py-2.5 text-sm font-medium text-white shadow-sm hover:bg-ink-800">🔎 ค้นหา</button>
      </div>

      <div class="md:col-span-6 flex flex-wrap items-center justify-between gap-2">
        <a class="rounded-2xl border border-black/10 bg-white px-4 py-2 text-sm hover:bg-black/5" href="/tracks/users">🧹 ล้างตัวกรอง</a>
        <?php if ($totalPages > 1): ?>
          <div class="text-xs text-ink-800/60">หน้า <?= (int)$page ?> / <?= (int)$totalPages ?> • แสดง <?= (int)$pageSize ?> รายการ/หน้า</div>
        <?php else: ?>
          <div class="text-xs text-ink-800/60">แสดง <?= (int)$pageSize ?> รายการ/หน้า</div>
        <?php endif; ?>
      </div>
    </form>

    <div class="mt-6 overflow-auto rounded-3xl border border-black/5 bg-white">
      <table class="min-w-[980px] bg-white text-sm">
        <thead class="bg-sand-100 text-left text-xs text-ink-800/70">
          <tr>
            <th class="px-4 py-3">Username</th>
            <th class="px-4 py-3">Display name</th>
            <th class="px-4 py-3">Role</th>
            <th class="px-4 py-3">Created</th>
            <th class="px-4 py-3">Actions</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-black/5">
          <?php foreach (($users ?? []) as $u): ?>
            <tr class="hover:bg-sand-50">
              <?php $formId = 'userUpdate' . (int)$u['id']; ?>
              <td class="px-4 py-3 font-medium whitespace-nowrap"><?= e((string)$u['username']) ?></td>
              <td class="px-4 py-3">
                <input form="<?= e($formId) ?>" name="displayname" value="<?= e((string)$u['displayname']) ?>" class="w-full min-w-[240px] rounded-xl border border-black/10 bg-white px-3 py-2 text-sm outline-none focus:border-calm-500" />
              </td>
              <td class="px-4 py-3">
                <?php $role = (string)($u['role'] ?? 'teacher'); ?>
                <form id="<?= e($formId) ?>" method="post" class="hidden">
                  <input type="hidden" name="_csrf" value="<?= e($csrf) ?>" />
                  <input type="hidden" name="action" value="update_user" />
                  <input type="hidden" name="id" value="<?= (int)$u['id'] ?>" />
                </form>

                <div class="flex items-center gap-2">
                  <select form="<?= e($formId) ?>" name="role" class="min-w-[110px] rounded-xl border border-black/10 bg-white px-3 py-2 text-xs outline-none focus:border-calm-500">
                    <option value="admin" <?= $role === 'admin' ? 'selected' : '' ?>>admin</option>
                    <option value="teacher" <?= $role === 'teacher' ? 'selected' : '' ?>>teacher</option>
                    <option value="student" <?= $role === 'student' ? 'selected' : '' ?>>student</option>
                  </select>
                  <button form="<?= e($formId) ?>" class="rounded-xl border border-black/10 bg-white px-3 py-2 text-xs hover:bg-black/5">บันทึก</button>
                </div>
              </td>
              <td class="px-4 py-3 text-xs text-ink-800/70 whitespace-nowrap"><?= e((string)$u['created_at']) ?></td>
              <td class="px-4 py-3">
                <div class="flex flex-wrap items-center gap-2">
                  <form method="post" class="flex items-center gap-2">
                    <input type="hidden" name="_csrf" value="<?= e($csrf) ?>" />
                    <input type="hidden" name="action" value="reset_password" />
                    <input type="hidden" name="id" value="<?= (int)$u['id'] ?>" />
                    <input type="password" name="new_password" placeholder="รหัสผ่านใหม่" class="w-40 rounded-xl border border-black/10 bg-white px-3 py-2 text-xs outline-none focus:border-calm-500" />
                    <input type="password" name="confirm_new_password" placeholder="ยืนยัน" class="w-28 rounded-xl border border-black/10 bg-white px-3 py-2 text-xs outline-none focus:border-calm-500" />
                    <button class="rounded-xl bg-ink-900 px-3 py-2 text-xs font-medium text-white hover:bg-ink-800">🔁 ตั้งใหม่</button>
                  </form>

                  <form method="post" data-confirm="ยืนยันลบผู้ใช้นี้?">
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
              <td colspan="5" class="px-4 py-8 text-center text-sm text-ink-800/70">ยังไม่มีผู้ใช้</td>
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

            $baseHref = '/tracks/users' . ($baseQuery !== '' ? ('?' . $baseQuery) : '');
            $prevHref = $baseHref . ($prev > 1 ? (($baseQuery !== '' ? '&' : '?') . 'page=' . $prev) : '');
            $nextHref = $baseHref . (($baseQuery !== '' ? '&' : '?') . 'page=' . $next);
          ?>

          <a class="rounded-xl border border-black/10 bg-white px-3 py-2 text-sm hover:bg-black/5 <?= $page <= 1 ? 'pointer-events-none opacity-50' : '' ?>" href="<?= e($prevHref) ?>">← ก่อนหน้า</a>
          <a class="rounded-xl border border-black/10 bg-white px-3 py-2 text-sm hover:bg-black/5 <?= $page >= $totalPages ? 'pointer-events-none opacity-50' : '' ?>" href="<?= e($nextHref) ?>">ถัดไป →</a>
        </div>
      </div>
    <?php endif; ?>

    <p class="mt-3 text-xs text-ink-800/60">หมายเหตุ: รหัสผ่านถูกเก็บแบบแฮช (password_hash)</p>
  </section>
</div>
