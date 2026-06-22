<?php
$setupMode = !(bool)($hasUsers ?? true);
?>

<section class="mx-auto grid min-h-[78vh] max-w-md place-items-center px-2">
  <div class="card w-full border border-base-300 bg-base-100 shadow-sm">
    <div class="card-body p-6 sm:p-8">

      <!-- โลโก้ + ชื่อเว็บ -->
      <div class="flex flex-col items-center text-center">
        <div class="text-4xl">🏃</div>
        <div class="mt-1 leading-tight">
          <div class="text-sm text-base-content/60">Sukhon</div>
          <div class="text-lg font-semibold tracking-tight">Track</div>
        </div>
      </div>

      <h1 class="mt-4 text-center text-xl font-semibold tracking-tight">เข้าสู่ระบบ</h1>

      <?php if (!empty($success)): ?>
        <div role="alert" class="alert alert-success mt-4 text-sm"><span><?= e((string)$success) ?></span></div>
      <?php endif; ?>

      <?php if (!empty($error)): ?>
        <div role="alert" class="alert alert-error mt-4 text-sm"><span><?= e((string)$error) ?></span></div>
      <?php endif; ?>

      <form class="mt-4 space-y-4" method="post" action="/tracks/login">
        <input type="hidden" name="_csrf" value="<?= e(csrf_token()) ?>" />
        <input type="hidden" name="action" value="login" />

        <div class="form-control">
          <label class="label py-1"><span class="label-text font-medium">Username</span></label>
          <label class="input input-bordered flex items-center gap-2 focus-within:input-primary">
            <span class="opacity-50">👤</span>
            <input type="text" name="username" autofocus autocomplete="username" class="grow" placeholder="เช่น admin / teacher01 / 66001234" />
          </label>
        </div>

        <div class="form-control">
          <label class="label py-1"><span class="label-text font-medium">Password</span></label>
          <label class="input input-bordered flex items-center gap-2 focus-within:input-primary">
            <span class="opacity-50">🔑</span>
            <input id="login-password" type="password" name="password" autocomplete="current-password" class="grow" placeholder="••••••••" />
            <button type="button" onclick="loginTogglePw('login-password', this)" class="opacity-50 transition hover:opacity-100" aria-label="แสดง/ซ่อนรหัสผ่าน">👁️</button>
          </label>
        </div>

        <button class="btn btn-neutral group w-full">
          <span>เข้าสู่ระบบ</span>
          <span class="transition-transform group-hover:translate-x-0.5">→</span>
        </button>
      </form>

      <?php if ($setupMode): ?>
        <div class="card mt-6 border border-base-300 bg-base-200">
          <div class="card-body p-5">
            <h2 class="flex items-center gap-2 text-sm font-semibold">🧑‍🏫 สร้างผู้ใช้คนแรก (Admin)</h2>
            <p class="text-xs text-base-content/65">ระบบยังไม่มีผู้ใช้ — สร้างผู้ใช้คนแรกเพื่อเริ่มต้นจัดการข้อมูล</p>

            <form class="mt-2 space-y-3" method="post" action="/tracks/login">
              <input type="hidden" name="_csrf" value="<?= e(csrf_token()) ?>" />
              <input type="hidden" name="action" value="setup" />

              <div class="form-control">
                <label class="label py-1"><span class="label-text text-xs font-medium">Display name</span></label>
                <input name="displayname" class="input input-sm input-bordered" placeholder="เช่น ผู้ดูแลระบบ" />
              </div>

              <div class="form-control">
                <label class="label py-1"><span class="label-text text-xs font-medium">Username</span></label>
                <input name="username" class="input input-sm input-bordered" placeholder="เช่น admin" />
              </div>

              <div class="grid gap-3 sm:grid-cols-2">
                <div class="form-control">
                  <label class="label py-1"><span class="label-text text-xs font-medium">Password</span></label>
                  <input type="password" name="password" class="input input-sm input-bordered" />
                </div>
                <div class="form-control">
                  <label class="label py-1"><span class="label-text text-xs font-medium">Confirm</span></label>
                  <input type="password" name="confirm_password" class="input input-sm input-bordered" />
                </div>
              </div>

              <button class="btn btn-neutral btn-sm w-full">✅ สร้างและเข้าสู่ระบบ</button>
            </form>
          </div>
        </div>
      <?php endif; ?>

    </div>
  </div>
</section>

<script>
  function loginTogglePw(id, btn) {
    var el = document.getElementById(id);
    if (!el) return;
    var show = el.type === 'password';
    el.type = show ? 'text' : 'password';
    btn.textContent = show ? '🙈' : '👁️';
  }
</script>
