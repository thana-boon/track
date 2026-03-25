<?php
$setupMode = !(bool)($hasUsers ?? true);
?>

<section class="mx-auto max-w-xl overflow-hidden rounded-3xl border border-black/5 bg-white/80 p-6 shadow-sm backdrop-blur">
  <div class="flex items-start gap-3">
    <div class="grid h-11 w-11 place-items-center rounded-2xl bg-gradient-to-br from-pastel-sky to-pastel-lilac ring-1 ring-black/5">🔐</div>
    <div>
      <h1 class="text-xl font-semibold tracking-tight">เข้าสู่ระบบ</h1>
      <p class="mt-1 text-sm text-ink-800/70">ยินดีต้อนรับกลับมา—พร้อมลุยกันเลย</p>
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

  <form class="mt-6 space-y-4" method="post" action="/tracks/?route=login">
    <input type="hidden" name="_csrf" value="<?= e(csrf_token()) ?>" />
    <input type="hidden" name="action" value="login" />

    <div>
      <label class="text-sm font-medium">Username</label>
      <input name="username" class="mt-1 w-full rounded-xl border border-black/10 bg-white px-4 py-2.5 text-sm outline-none ring-0 focus:border-calm-500" autocomplete="username" />
    </div>

    <div>
      <label class="text-sm font-medium">Password</label>
      <input type="password" name="password" class="mt-1 w-full rounded-xl border border-black/10 bg-white px-4 py-2.5 text-sm outline-none focus:border-calm-500" autocomplete="current-password" />
    </div>

    <button class="inline-flex w-full items-center justify-center rounded-2xl bg-gradient-to-r from-ink-900 to-ink-800 px-4 py-2.5 text-sm font-medium text-white shadow-sm hover:opacity-95">
      ✨ เข้าสู่ระบบ
    </button>
  </form>

  <?php if ($setupMode): ?>
    <div class="mt-6 rounded-3xl border border-black/5 bg-gradient-to-b from-pastel-mint/40 to-sand-50 p-5">
      <h2 class="text-sm font-semibold">🧑‍🏫 สร้างผู้ใช้คนแรก</h2>
      <p class="mt-1 text-xs text-ink-800/70">ระบบยังไม่มีผู้ใช้—กรุณาสร้างผู้ใช้คนแรกเพื่อเริ่มใช้งาน</p>

      <form class="mt-4 space-y-3" method="post" action="/tracks/?route=login">
        <input type="hidden" name="_csrf" value="<?= e(csrf_token()) ?>" />
        <input type="hidden" name="action" value="setup" />

        <div>
          <label class="text-xs font-medium">Display name</label>
          <input name="displayname" class="mt-1 w-full rounded-xl border border-black/10 bg-white px-4 py-2.5 text-sm outline-none focus:border-calm-500" placeholder="เช่น ครูสมชาย" />
        </div>

        <div>
          <label class="text-xs font-medium">Username</label>
          <input name="username" class="mt-1 w-full rounded-xl border border-black/10 bg-white px-4 py-2.5 text-sm outline-none focus:border-calm-500" placeholder="เช่น admin" />
        </div>

        <div class="grid gap-3 sm:grid-cols-2">
          <div>
            <label class="text-xs font-medium">Password</label>
            <input type="password" name="password" class="mt-1 w-full rounded-xl border border-black/10 bg-white px-4 py-2.5 text-sm outline-none focus:border-calm-500" />
          </div>
          <div>
            <label class="text-xs font-medium">Confirm</label>
            <input type="password" name="confirm_password" class="mt-1 w-full rounded-xl border border-black/10 bg-white px-4 py-2.5 text-sm outline-none focus:border-calm-500" />
          </div>
        </div>

        <button class="inline-flex w-full items-center justify-center rounded-2xl bg-calm-600 px-4 py-2.5 text-sm font-medium text-white shadow-sm hover:bg-calm-500">
          ✅ สร้างและเข้าสู่ระบบ
        </button>
      </form>
    </div>
  <?php endif; ?>
</section>
