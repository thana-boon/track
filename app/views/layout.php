<?php
$user = auth_user();
?>
<!doctype html>
<html lang="th" data-theme="light">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width,initial-scale=1" />
  <title><?= e((string)$title) ?></title>
  <link rel="icon" href="/tracks/favicon.ico" />
  <!-- daisyUI (prebuilt, no build step) + theme cupcake -->
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/daisyui@4.12.14/dist/full.min.css" />
  <script src="https://cdn.tailwindcss.com"></script>
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
  <script>
    tailwind.config = {
      theme: {
        extend: {
          colors: {
            // daisyUI semantic tokens — daisyUI v4 ใช้ค่า OKLCH ใน CSS variables (เปลี่ยน theme แล้วสีตามทันที)
            primary: 'oklch(var(--p) / <alpha-value>)',
            'primary-content': 'oklch(var(--pc) / <alpha-value>)',
            secondary: 'oklch(var(--s) / <alpha-value>)',
            'secondary-content': 'oklch(var(--sc) / <alpha-value>)',
            accent: 'oklch(var(--a) / <alpha-value>)',
            'accent-content': 'oklch(var(--ac) / <alpha-value>)',
            neutral: 'oklch(var(--n) / <alpha-value>)',
            'neutral-content': 'oklch(var(--nc) / <alpha-value>)',
            'base-100': 'oklch(var(--b1) / <alpha-value>)',
            'base-200': 'oklch(var(--b2) / <alpha-value>)',
            'base-300': 'oklch(var(--b3) / <alpha-value>)',
            'base-content': 'oklch(var(--bc) / <alpha-value>)',
            info: 'oklch(var(--in) / <alpha-value>)',
            success: 'oklch(var(--su) / <alpha-value>)',
            warning: 'oklch(var(--wa) / <alpha-value>)',
            error: 'oklch(var(--er) / <alpha-value>)',

            // พาเลตต์เสริมที่จูนให้เข้ากับ cupcake (ink/sand ส่วนใหญ่ถูกแทนด้วย semantic แล้ว — คงไว้กันพลาด)
            ink:    { 950: '#1f0f29', 900: '#291334', 800: '#3b2147', 700: '#4e2f5b', 100: '#f3eef6' },
            pastel: { mint: '#d9f3ee', sky: '#d4eef0', lilac: '#fbe7ef', peach: '#fbe2d4', lemon: '#fdeec9' },
            // calm = teal เข้มอ่านง่าย (ใช้คู่ text-white / เป็นสีตัวอักษรได้) เข้ากับ primary ของ cupcake
            calm:   { 700: '#1f6f6a', 600: '#2b7f79', 500: '#3b918a', 400: '#5aa39c', 100: '#e6f3f2', 50: '#eef7f6' },
            sand:   { 50: '#faf7f5', 100: '#efeae6', 200: '#e7e2df' },
            blush:  { 600: '#c24174', 50: '#fff1f2' }
          }
        }
      }
    }
  </script>

  <script>
    (function () {
      if (typeof Swal === 'undefined') return;

      function swalConfirm(message, title, okText) {
        return Swal.fire({
          title: title || 'ยืนยันการทำรายการ',
          text: message || '',
          icon: 'warning',
          showCancelButton: true,
          confirmButtonText: okText || 'ยืนยัน',
          cancelButtonText: 'ยกเลิก',
          reverseButtons: true
        }).then(function (r) {
          return !!(r && r.isConfirmed);
        });
      }

      // Expose for inline scripts (if needed)
      window.__swalConfirm = swalConfirm;

      // Confirm on form submit: <form data-confirm="..."></form>
      document.addEventListener('submit', function (e) {
        var form = e.target;
        if (!form || !(form instanceof HTMLFormElement)) return;
        if (form.dataset && form.dataset.confirmBypass === '1') {
          delete form.dataset.confirmBypass;
          return;
        }
        var msg = form.dataset ? form.dataset.confirm : '';
        if (!msg) return;

        e.preventDefault();

        var title = (form.dataset && form.dataset.confirmTitle) ? form.dataset.confirmTitle : 'ยืนยันการทำรายการ';
        var okText = (form.dataset && form.dataset.confirmOk) ? form.dataset.confirmOk : 'ยืนยัน';

        swalConfirm(msg, title, okText).then(function (ok) {
          if (!ok) return;
          // Use form.submit() to avoid re-triggering submit handlers.
          form.submit();
        });
      }, true);

      // Confirm on click (buttons/links): <a data-confirm="..." href="...">
      document.addEventListener('click', function (e) {
        if (!e.target || typeof e.target.closest !== 'function') return;

        var el = e.target.closest('[data-confirm]');
        if (!el) return;
        if (el instanceof HTMLFormElement) return;

        var tag = (el.tagName || '').toLowerCase();
        var isLink = tag === 'a' && !!el.getAttribute('href');
        var isButton = tag === 'button' || (tag === 'input' && (el.type === 'submit' || el.type === 'button'));
        if (!isLink && !isButton) return;

        var msg = el.getAttribute('data-confirm');
        if (!msg) return;

        e.preventDefault();

        var title = el.getAttribute('data-confirm-title') || 'ยืนยันการทำรายการ';
        var okText = el.getAttribute('data-confirm-ok') || 'ยืนยัน';

        swalConfirm(msg, title, okText).then(function (ok) {
          if (!ok) return;

          if (isLink) {
            window.location.href = el.getAttribute('href');
            return;
          }

          var form = el.form || el.closest('form');
          if (!form) return;

          if (typeof form.requestSubmit === 'function') {
            form.dataset.confirmBypass = '1';
            form.requestSubmit(el);
            return;
          }

          // Fallback: preserve submit button name/value
          var name = el.getAttribute('name');
          var value = el.getAttribute('value');
          var tmp = null;
          if (name) {
            tmp = document.createElement('input');
            tmp.type = 'hidden';
            tmp.name = name;
            tmp.value = value || '';
            form.appendChild(tmp);
          }
          form.submit();
          if (tmp) {
            form.removeChild(tmp);
          }
        });
      }, true);
    })();
  </script>
</head>
<style>
  .nav-dd-open { animation: navDdIn 0.15s ease forwards; }
  @keyframes navDdIn {
    from { opacity: 0; transform: translateY(-5px); }
    to   { opacity: 1; transform: translateY(0); }
  }
</style>
<script>
  function navToggle(id) {
    var all = document.querySelectorAll('.nav-dd');
    var target = document.getElementById(id);
    var wasHidden = target ? target.classList.contains('hidden') : true;
    all.forEach(function(el) {
      el.classList.add('hidden');
      el.classList.remove('nav-dd-open');
    });
    if (wasHidden && target) {
      target.classList.remove('hidden');
      target.classList.add('nav-dd-open');
    }
  }
  document.addEventListener('click', function(e) {
    if (!e.target.closest('[data-dd]')) {
      document.querySelectorAll('.nav-dd').forEach(function(el) {
        el.classList.add('hidden');
        el.classList.remove('nav-dd-open');
      });
    }
  });
</script>
<style>
  .nav-dd { transform-origin: top center; }
  .nav-dd-open { animation: navDdIn 0.15s ease forwards; }
  @keyframes navDdIn {
    from { opacity: 0; transform: translateY(-6px) scaleY(0.95); }
    to   { opacity: 1; transform: translateY(0)  scaleY(1); }
  }
</style>
<body class="min-h-screen bg-base-200 text-base-content">

  <header class="sticky top-0 z-10 border-b border-base-300 bg-base-100/80 backdrop-blur">
    <div class="mx-auto flex max-w-6xl flex-col gap-3 px-4 py-3 md:flex-row md:items-center md:justify-between">
      <a class="group flex items-center gap-2 font-semibold tracking-tight" href="/tracks/">
        <span class="grid h-9 w-9 place-items-center text-2xl">🏃</span>
        <span class="leading-none">
          <span class="block text-sm text-base-content/60">Sukhon</span>
          <span class="block -mt-0.5 text-base text-base-content">Track</span>
        </span>
      </a>

      <?php if ($user): ?>
  		<?php $role = (string)($user['role'] ?? 'teacher'); ?>
        <div class="flex w-full flex-col gap-2 md:w-auto md:flex-row md:items-center md:gap-3">
          <nav class="-mx-2 overflow-x-auto px-2 text-sm md:mx-0 md:overflow-visible md:px-0">
            <div class="flex min-w-max flex-nowrap items-center gap-2">
  			  <?php if ($role === 'admin'): ?>

              <div class="relative shrink-0" data-dd="nav-data">
                <button type="button" onclick="navToggle('nav-data')" class="btn btn-sm rounded-xl border-base-300 bg-base-100 font-normal shadow-sm hover:bg-base-300">📋 ข้อมูล</button>
                <div id="nav-data" class="nav-dd hidden absolute left-0 top-full z-20 mt-1 w-56 overflow-hidden rounded-2xl border border-base-300 bg-base-100 shadow-lg">
                  <a class="block px-3 py-2 text-sm hover:bg-base-200" href="/tracks/students">📋 รายชื่อ</a>
                  <a class="block px-3 py-2 text-sm hover:bg-base-200" href="/tracks/academic_year">📅 ปีการศึกษา</a>
                  <a class="block px-3 py-2 text-sm hover:bg-base-200" href="/tracks/class_advisors">👩‍🏫 ครูประจำชั้น</a>
                </div>
              </div>

              <div class="relative shrink-0" data-dd="nav-track">
                <button type="button" onclick="navToggle('nav-track')" class="btn btn-sm rounded-xl border-none bg-primary/15 font-normal shadow-sm hover:bg-primary/25">🧩 วิชา/ลงทะเบียน</button>
                <div id="nav-track" class="nav-dd hidden absolute left-0 top-full z-20 mt-1 w-56 overflow-hidden rounded-2xl border border-base-300 bg-base-100 shadow-lg">
                  <a class="block px-3 py-2 text-sm hover:bg-base-200" href="/tracks/track-groups">🧩 กลุ่ม Track</a>
                  <a class="block px-3 py-2 text-sm hover:bg-base-200" href="/tracks/track-subjects">🧩 วิชา</a>
                  <a class="block px-3 py-2 text-sm hover:bg-base-200" href="/tracks/register_track">🧾 ลงทะเบียน</a>
                  <a class="block px-3 py-2 text-sm hover:bg-base-200" href="/tracks/student_manage">🧒 จัดการรายคน</a>
                </div>
              </div>

              <div class="relative shrink-0" data-dd="nav-att">
                <button type="button" onclick="navToggle('nav-att')" class="btn btn-sm rounded-xl border-none bg-secondary/15 font-normal shadow-sm hover:bg-secondary/25">📝 เช็คชื่อ</button>
                <div id="nav-att" class="nav-dd hidden absolute left-0 top-full z-20 mt-1 w-56 overflow-hidden rounded-2xl border border-base-300 bg-base-100 shadow-lg">
                  <a class="block px-3 py-2 text-sm hover:bg-base-200" href="/tracks/class_attendance_create">➕ สร้างรอบเรียน</a>
                  <a class="block px-3 py-2 text-sm hover:bg-base-200" href="/tracks/class-groups">👥 กลุ่มเรียน</a>
                  <a class="block px-3 py-2 text-sm hover:bg-base-200" href="/tracks/class_attendance">📅 ตารางเรียนตามวัน</a>
                  <a class="block px-3 py-2 text-sm hover:bg-base-200" href="/tracks/class_room">🏫 ดูนักเรียนรายห้อง</a>
                </div>
              </div>

              <div class="relative shrink-0" data-dd="nav-report">
                <button type="button" onclick="navToggle('nav-report')" class="btn btn-sm rounded-xl border-none bg-accent/15 font-normal shadow-sm hover:bg-accent/25">🖨️ รายงาน</button>
                <div id="nav-report" class="nav-dd hidden absolute left-0 top-full z-20 mt-1 w-56 overflow-hidden rounded-2xl border border-base-300 bg-base-100 shadow-lg">
                  <a class="block px-3 py-2 text-sm hover:bg-base-200" href="/tracks/report_statement">🖨️ ใบ Transcript</a>
                  <a class="block px-3 py-2 text-sm hover:bg-base-200" href="/tracks/activity_logs">📜 Activity log</a>
                  <a class="block px-3 py-2 text-sm hover:bg-base-200" href="/tracks/backup_restore">💾 Backup/Restore</a>
                </div>
              </div>

              <div class="relative shrink-0" data-dd="nav-users">
                <button type="button" onclick="navToggle('nav-users')" class="btn btn-sm rounded-xl border-base-300 bg-base-100 font-normal shadow-sm hover:bg-base-300">👤 ผู้ใช้</button>
                <div id="nav-users" class="nav-dd hidden absolute left-0 top-full z-20 mt-1 w-56 overflow-hidden rounded-2xl border border-base-300 bg-base-100 shadow-lg">
                  <a class="block px-3 py-2 text-sm hover:bg-base-200" href="/tracks/users">👤 ผู้ใช้</a>
                </div>
              </div>
			  <?php else: ?>
      <?php
        $showRoom = false;
        if ($role === 'teacher') {
        try {
          track_class_advisors_table_ensure();
          $pdoSchool = db_school();
          $activeYearId = (int)($pdoSchool->query('SELECT id FROM academic_years WHERE is_active = 1 ORDER BY id DESC LIMIT 1')->fetchColumn() ?: 0);
          if ($activeYearId > 0) {
          $pdoApp = db_app();
          $stmt = $pdoApp->prepare('SELECT 1 FROM track_class_advisors WHERE year_id = ? AND teacher_user_id = ? LIMIT 1');
          $stmt->execute([$activeYearId, (int)($user['id'] ?? 0)]);
          $showRoom = (bool)$stmt->fetchColumn();
          }
        } catch (Throwable $e) {
          $showRoom = false;
        }
        }
      ?>

      <?php if ($role === 'student'): ?>
        <a class="btn btn-sm shrink-0 rounded-xl border-none bg-secondary/15 font-normal shadow-sm hover:bg-secondary/25" href="/tracks/student_results">📘 ผลการเรียนของฉัน</a>
      <?php else: ?>
        <a class="btn btn-sm shrink-0 rounded-xl border-none bg-secondary/15 font-normal shadow-sm hover:bg-secondary/25" href="/tracks/class_attendance">📅 ตารางเรียนตามวัน</a>
        <?php if ($showRoom): ?>
          <a class="btn btn-sm shrink-0 rounded-xl border-base-300 bg-base-100 font-normal shadow-sm hover:bg-base-300" href="/tracks/class_room">🏫 นักเรียนประจำชั้น</a>
        <?php endif; ?>
      <?php endif; ?>
			  <?php endif; ?>
            </div>
          </nav>

          <div class="flex items-center justify-between gap-2 md:justify-end">
            <span class="truncate text-sm text-base-content/70 md:max-w-[220px]"><?= e($user['displayname'] ?? $user['username']) ?></span>
            <a class="btn btn-sm btn-ghost shrink-0 rounded-xl font-normal text-error hover:bg-error/10" href="/tracks/logout">🚪 ออกจากระบบ</a>
          </div>
        </div>
      <?php else: ?>
        <nav class="text-sm">
          <a class="btn btn-sm btn-ghost rounded-xl font-normal" href="/tracks/login">🔐 เข้าสู่ระบบ</a>
        </nav>
      <?php endif; ?>
    </div>
  </header>

  <main class="mx-auto max-w-7xl px-4 py-6">
    <?= $content ?>
  </main>

  <footer class="border-t border-base-300 bg-base-100/60">
    <div class="mx-auto max-w-6xl px-4 py-6 text-xs text-base-content/60">
      Powred by IT Sukhon • <?= date('Y-m-d H:i') ?>
    </div>
  </footer>

</body>
</html>
