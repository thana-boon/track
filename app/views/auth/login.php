<?php
$setupMode = !(bool)($hasUsers ?? true);
?>

<style>
  @keyframes loginFadeUp {
    from { opacity: 0; transform: translateY(14px); }
    to   { opacity: 1; transform: translateY(0); }
  }
  .login-anim { animation: loginFadeUp 0.5s cubic-bezier(0.22, 1, 0.36, 1) both; }
  .login-anim-2 { animation: loginFadeUp 0.5s cubic-bezier(0.22, 1, 0.36, 1) 0.08s both; }
  @keyframes loginFloat {
    0%, 100% { transform: translateY(0); }
    50%      { transform: translateY(-8px); }
  }
  .login-float { animation: loginFloat 6s ease-in-out infinite; }
</style>

<section class="mx-auto grid min-h-[78vh] max-w-5xl place-items-center px-2">
  <div class="login-anim w-full overflow-hidden rounded-[2rem] border border-black/5 bg-white/80 shadow-xl shadow-ink-900/5 ring-1 ring-white/40 backdrop-blur-md">
    <div class="grid gap-0 md:grid-cols-[1.05fr,1fr]">

      <!-- ซ้าย: ฟอร์มเข้าสู่ระบบ -->
      <div class="p-6 sm:p-8 md:p-10">
        <div class="flex items-center gap-3">
          <div class="login-float grid h-12 w-12 place-items-center rounded-2xl bg-gradient-to-br from-pastel-sky via-pastel-lilac to-pastel-mint text-2xl shadow-sm ring-1 ring-black/5">🔐</div>
          <div>
            <h1 class="text-2xl font-semibold tracking-tight">เข้าสู่ระบบ</h1>
            <p class="mt-0.5 text-sm text-ink-800/60">ยินดีต้อนรับกลับมา 👋 เลือกบัญชีที่ได้รับจากโรงเรียน</p>
          </div>
        </div>

        <?php if (!empty($success)): ?>
          <div class="mt-5 flex items-start gap-2 rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-900">
            <span class="mt-0.5">✅</span><span><?= e((string)$success) ?></span>
          </div>
        <?php endif; ?>

        <?php if (!empty($error)): ?>
          <div class="mt-5 flex items-start gap-2 rounded-2xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-900">
            <span class="mt-0.5">⚠️</span><span><?= e((string)$error) ?></span>
          </div>
        <?php endif; ?>

        <form class="mt-7 space-y-5" method="post" action="/tracks/login">
          <input type="hidden" name="_csrf" value="<?= e(csrf_token()) ?>" />
          <input type="hidden" name="action" value="login" />

          <div>
            <label class="mb-1.5 block text-sm font-medium text-ink-800">Username</label>
            <div class="group relative">
              <span class="pointer-events-none absolute inset-y-0 left-0 grid w-11 place-items-center text-ink-800/40">👤</span>
              <input name="username" autofocus autocomplete="username"
                class="w-full rounded-2xl border border-black/10 bg-white/90 py-3 pl-11 pr-4 text-sm shadow-sm outline-none transition focus:border-calm-500 focus:ring-4 focus:ring-calm-500/15"
                placeholder="เช่น admin / teacher01 / 66001234" />
            </div>
            <p class="mt-1.5 text-xs text-ink-800/55">บัญชีนักเรียนแนะนำให้ใช้ Username = รหัสนักเรียน</p>
          </div>

          <div>
            <label class="mb-1.5 block text-sm font-medium text-ink-800">Password</label>
            <div class="group relative">
              <span class="pointer-events-none absolute inset-y-0 left-0 grid w-11 place-items-center text-ink-800/40">🔑</span>
              <input id="login-password" type="password" name="password" autocomplete="current-password"
                class="w-full rounded-2xl border border-black/10 bg-white/90 py-3 pl-11 pr-12 text-sm shadow-sm outline-none transition focus:border-calm-500 focus:ring-4 focus:ring-calm-500/15"
                placeholder="••••••••" />
              <button type="button" onclick="loginTogglePw('login-password', this)"
                class="absolute inset-y-0 right-0 grid w-12 place-items-center text-ink-800/40 transition hover:text-ink-800/70" aria-label="แสดง/ซ่อนรหัสผ่าน">👁️</button>
            </div>
          </div>

          <button class="group inline-flex w-full items-center justify-center gap-2 rounded-2xl bg-gradient-to-r from-ink-900 to-ink-700 px-4 py-3 text-sm font-semibold text-white shadow-lg shadow-ink-900/20 transition hover:-translate-y-0.5 hover:shadow-xl hover:shadow-ink-900/25 active:translate-y-0">
            <span>เข้าสู่ระบบ</span>
            <span class="transition-transform group-hover:translate-x-0.5">→</span>
          </button>
        </form>

        <?php if ($setupMode): ?>
          <div class="mt-7 rounded-3xl border border-calm-500/20 bg-gradient-to-b from-pastel-mint/40 to-sand-50 p-5 shadow-sm">
            <h2 class="flex items-center gap-2 text-sm font-semibold">🧑‍🏫 สร้างผู้ใช้คนแรก (Admin)</h2>
            <p class="mt-1 text-xs text-ink-800/65">ระบบยังไม่มีผู้ใช้ — สร้างผู้ใช้คนแรกเพื่อเริ่มต้นจัดการข้อมูล</p>

            <form class="mt-4 space-y-3" method="post" action="/tracks/login">
              <input type="hidden" name="_csrf" value="<?= e(csrf_token()) ?>" />
              <input type="hidden" name="action" value="setup" />

              <div>
                <label class="mb-1 block text-xs font-medium text-ink-800">Display name</label>
                <input name="displayname" class="w-full rounded-2xl border border-black/10 bg-white px-4 py-2.5 text-sm shadow-sm outline-none transition focus:border-calm-500 focus:ring-4 focus:ring-calm-500/15" placeholder="เช่น ผู้ดูแลระบบ" />
              </div>

              <div>
                <label class="mb-1 block text-xs font-medium text-ink-800">Username</label>
                <input name="username" class="w-full rounded-2xl border border-black/10 bg-white px-4 py-2.5 text-sm shadow-sm outline-none transition focus:border-calm-500 focus:ring-4 focus:ring-calm-500/15" placeholder="เช่น admin" />
              </div>

              <div class="grid gap-3 sm:grid-cols-2">
                <div>
                  <label class="mb-1 block text-xs font-medium text-ink-800">Password</label>
                  <input type="password" name="password" class="w-full rounded-2xl border border-black/10 bg-white px-4 py-2.5 text-sm shadow-sm outline-none transition focus:border-calm-500 focus:ring-4 focus:ring-calm-500/15" />
                </div>
                <div>
                  <label class="mb-1 block text-xs font-medium text-ink-800">Confirm</label>
                  <input type="password" name="confirm_password" class="w-full rounded-2xl border border-black/10 bg-white px-4 py-2.5 text-sm shadow-sm outline-none transition focus:border-calm-500 focus:ring-4 focus:ring-calm-500/15" />
                </div>
              </div>

              <button class="inline-flex w-full items-center justify-center gap-2 rounded-2xl bg-calm-600 px-4 py-2.5 text-sm font-semibold text-white shadow-md shadow-calm-600/25 transition hover:-translate-y-0.5 hover:bg-calm-500 active:translate-y-0">
                ✅ สร้างและเข้าสู่ระบบ
              </button>
            </form>
          </div>
        <?php endif; ?>
      </div>

      <!-- ขวา: hero + ข้อมูล role -->
      <div class="login-anim-2 relative overflow-hidden border-t border-black/5 bg-gradient-to-br from-ink-900 via-ink-800 to-calm-700 p-6 text-white sm:p-8 md:border-l md:border-t-0 md:p-10">
        <div aria-hidden="true" class="pointer-events-none absolute inset-0">
          <div class="absolute -top-16 -right-10 h-56 w-56 rounded-full bg-pastel-sky/20 blur-3xl"></div>
          <div class="absolute bottom-0 -left-12 h-52 w-52 rounded-full bg-calm-500/30 blur-3xl"></div>
        </div>

        <div class="relative">
          <div class="inline-flex items-center gap-2 rounded-full bg-white/10 px-3 py-1 text-xs ring-1 ring-white/15">🏃 Sukhon Track</div>
          <h2 class="mt-4 text-xl font-semibold leading-snug">เข้าใช้งานตามสิทธิ์<br />ของแต่ละบทบาท</h2>
          <p class="mt-2 text-sm text-white/65">เมื่อเข้าสู่ระบบแล้ว ระบบจะพาไปยังหน้าที่ตรงกับ role ของคุณโดยอัตโนมัติ</p>

          <div class="mt-6 grid gap-3">
            <div class="rounded-2xl bg-white/10 p-4 ring-1 ring-white/10 backdrop-blur transition hover:bg-white/15">
              <div class="flex items-center justify-between gap-3">
                <div class="flex items-center gap-2 font-semibold">🛠️ Admin</div>
                <span class="rounded-full bg-pastel-lilac/25 px-2.5 py-0.5 text-xs text-white/85 ring-1 ring-white/15">จัดการระบบ</span>
              </div>
              <div class="mt-1.5 text-xs text-white/60">จัดการผู้ใช้ • ปีการศึกษา • วิชา/ลงทะเบียน • รายงาน</div>
            </div>

            <div class="rounded-2xl bg-white/10 p-4 ring-1 ring-white/10 backdrop-blur transition hover:bg-white/15">
              <div class="flex items-center justify-between gap-3">
                <div class="flex items-center gap-2 font-semibold">📝 Teacher</div>
                <span class="rounded-full bg-pastel-mint/25 px-2.5 py-0.5 text-xs text-white/85 ring-1 ring-white/15">เช็คชื่อ</span>
              </div>
              <div class="mt-1.5 text-xs text-white/60">ตารางเรียนตามวัน • เช็คชื่อ • ดูนักเรียนประจำชั้น</div>
            </div>

            <div class="rounded-2xl bg-white/10 p-4 ring-1 ring-white/10 backdrop-blur transition hover:bg-white/15">
              <div class="flex items-center justify-between gap-3">
                <div class="flex items-center gap-2 font-semibold">📘 Student</div>
                <span class="rounded-full bg-pastel-sky/25 px-2.5 py-0.5 text-xs text-white/85 ring-1 ring-white/15">ผลการเรียน</span>
              </div>
              <div class="mt-1.5 text-xs text-white/60">ดูวิชาที่ถูกกำหนด • เข้าเรียน/ผ่าน-ไม่ผ่าน • สรุปวิชาที่ผ่าน</div>
              <div class="mt-1.5 text-[11px] text-white/45">แนะนำ: ตั้ง Username = รหัสนักเรียน เพื่ออ้างอิงข้อมูลได้ตรง</div>
            </div>
          </div>
        </div>
      </div>

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
