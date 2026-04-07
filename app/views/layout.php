<?php
$user = auth_user();
?>
<!doctype html>
<html lang="th">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width,initial-scale=1" />
  <title><?= e((string)$title) ?></title>
  <link rel="icon" href="/tracks/favicon.ico" />
  <script src="https://cdn.tailwindcss.com"></script>
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
  <script>
    tailwind.config = {
      theme: {
        extend: {
          colors: {
            ink: {
              950: '#0b1020',
              900: '#11172a',
              800: '#1b2542',
              700: '#273156',
              100: '#eef2ff'
            },
            pastel: {
              mint: '#d7f7ef',
              sky: '#dbeafe',
              lilac: '#ede9fe',
              peach: '#ffe4e6',
              lemon: '#fef9c3'
            },
            calm: {
              700: '#1f6f6a',
              600: '#2b7f79',
              500: '#3b918a',
              100: '#e6f3f2'
            },
            sand: {
              50: '#fcfbf8',
              100: '#f6f3eb',
              200: '#efe7d8'
            },
            blush: {
              600: '#c24174',
              50: '#fff1f2'
            }
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
<body class="min-h-screen bg-gradient-to-b from-pastel-lilac/40 via-sand-50 to-pastel-mint/30 text-ink-900">

  <div aria-hidden="true" class="pointer-events-none fixed inset-0 overflow-hidden">
    <div class="absolute -top-24 -left-24 h-72 w-72 rounded-full bg-pastel-peach/40 blur-3xl"></div>
    <div class="absolute top-40 -right-24 h-80 w-80 rounded-full bg-pastel-sky/45 blur-3xl"></div>
    <div class="absolute bottom-0 left-1/3 h-64 w-64 rounded-full bg-pastel-lemon/35 blur-3xl"></div>
  </div>

  <header class="sticky top-0 z-10 border-b border-black/5 bg-white/75 backdrop-blur">
    <div class="mx-auto flex max-w-6xl flex-col gap-3 px-4 py-3 md:flex-row md:items-center md:justify-between">
      <a class="group flex items-center gap-2 font-semibold tracking-tight" href="/tracks/">
        <span class="grid h-9 w-9 place-items-center rounded-xl bg-gradient-to-br from-pastel-sky to-pastel-mint shadow-sm ring-1 ring-black/5">🏃</span>
        <span class="leading-none">
          <span class="block text-sm text-ink-800/70">Sukhon</span>
          <span class="block -mt-0.5 text-base text-ink-900">Track</span>
        </span>
      </a>

      <?php if ($user): ?>
  		<?php $role = (string)($user['role'] ?? 'teacher'); ?>
        <div class="flex w-full flex-col gap-2 md:w-auto md:flex-row md:items-center md:gap-3">
          <nav class="-mx-2 overflow-x-auto px-2 text-sm md:mx-0 md:overflow-visible md:px-0">
            <div class="flex min-w-max flex-nowrap items-center gap-2">
  			  <?php if ($role === 'admin'): ?>

              <div class="relative shrink-0 group">
                <button type="button" class="rounded-xl border border-black/5 bg-sand-100/70 px-3 py-2 hover:bg-black/5 focus:outline-none">📋 ข้อมูล</button>
                <div class="absolute left-0 top-full z-20 mt-1 w-56 overflow-hidden rounded-2xl border border-black/10 bg-white shadow-sm opacity-0 pointer-events-none transition-opacity duration-150 delay-300 group-hover:delay-0 group-hover:opacity-100 group-hover:pointer-events-auto group-focus-within:delay-0 group-focus-within:opacity-100 group-focus-within:pointer-events-auto">
                  <a class="block px-3 py-2 text-sm hover:bg-black/5" href="/tracks/students">📋 รายชื่อ</a>
                  <a class="block px-3 py-2 text-sm hover:bg-black/5" href="/tracks/student_track">🧒 ดูรายคน</a>
                  <a class="block px-3 py-2 text-sm hover:bg-black/5" href="/tracks/academic_year">📅 ปีการศึกษา</a>
                  <a class="block px-3 py-2 text-sm hover:bg-black/5" href="/tracks/class_advisors">👩‍🏫 ครูประจำชั้น</a>
                </div>
              </div>

              <div class="relative shrink-0 group">
                <button type="button" class="rounded-xl border border-black/5 bg-pastel-mint/45 px-3 py-2 hover:bg-black/5 focus:outline-none">🧩 วิชา/ลงทะเบียน</button>
                <div class="absolute left-0 top-full z-20 mt-1 w-56 overflow-hidden rounded-2xl border border-black/10 bg-white shadow-sm opacity-0 pointer-events-none transition-opacity duration-150 delay-300 group-hover:delay-0 group-hover:opacity-100 group-hover:pointer-events-auto group-focus-within:delay-0 group-focus-within:opacity-100 group-focus-within:pointer-events-auto">
                  <a class="block px-3 py-2 text-sm hover:bg-black/5" href="/tracks/track-subjects">🧩 วิชา</a>
                  <a class="block px-3 py-2 text-sm hover:bg-black/5" href="/tracks/register_track">🧾 ลงทะเบียน</a>
                </div>
              </div>

              <div class="relative shrink-0 group">
                <button type="button" class="rounded-xl border border-black/5 bg-pastel-sky/45 px-3 py-2 hover:bg-black/5 focus:outline-none">📝 เช็คชื่อ</button>
                <div class="absolute left-0 top-full z-20 mt-1 w-56 overflow-hidden rounded-2xl border border-black/10 bg-white shadow-sm opacity-0 pointer-events-none transition-opacity duration-150 delay-300 group-hover:delay-0 group-hover:opacity-100 group-hover:pointer-events-auto group-focus-within:delay-0 group-focus-within:opacity-100 group-focus-within:pointer-events-auto">
                  <a class="block px-3 py-2 text-sm hover:bg-black/5" href="/tracks/class_attendance_create">➕ สร้างรอบเรียน</a>
                  <a class="block px-3 py-2 text-sm hover:bg-black/5" href="/tracks/class_attendance">📅 ตารางเรียนตามวัน</a>
                  <a class="block px-3 py-2 text-sm hover:bg-black/5" href="/tracks/class_room">🏫 ดูนักเรียนรายห้อง</a>
                </div>
              </div>

              <div class="relative shrink-0 group">
                <button type="button" class="rounded-xl border border-black/5 bg-pastel-lilac/40 px-3 py-2 hover:bg-black/5 focus:outline-none">🖨️ รายงาน</button>
                <div class="absolute left-0 top-full z-20 mt-1 w-56 overflow-hidden rounded-2xl border border-black/10 bg-white shadow-sm opacity-0 pointer-events-none transition-opacity duration-150 delay-300 group-hover:delay-0 group-hover:opacity-100 group-hover:pointer-events-auto group-focus-within:delay-0 group-focus-within:opacity-100 group-focus-within:pointer-events-auto">
                  <a class="block px-3 py-2 text-sm hover:bg-black/5" href="/tracks/report_statement">🖨️ ใบ Statement</a>
                  <a class="block px-3 py-2 text-sm hover:bg-black/5" href="/tracks/activity_logs">📜 Activity log</a>
                  <a class="block px-3 py-2 text-sm hover:bg-black/5" href="/tracks/backup_restore">💾 Backup/Restore</a>
                </div>
              </div>

              <div class="relative shrink-0 group">
                <button type="button" class="rounded-xl border border-black/5 bg-pastel-lilac/40 px-3 py-2 hover:bg-black/5 focus:outline-none">👤 ผู้ใช้</button>
                <div class="absolute left-0 top-full z-20 mt-1 w-56 overflow-hidden rounded-2xl border border-black/10 bg-white shadow-sm opacity-0 pointer-events-none transition-opacity duration-150 delay-300 group-hover:delay-0 group-hover:opacity-100 group-hover:pointer-events-auto group-focus-within:delay-0 group-focus-within:opacity-100 group-focus-within:pointer-events-auto">
                  <a class="block px-3 py-2 text-sm hover:bg-black/5" href="/tracks/users">👤 ผู้ใช้</a>
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
        <a class="shrink-0 rounded-xl border border-black/5 bg-pastel-sky/45 px-3 py-2 hover:bg-black/5" href="/tracks/student_results">📘 ผลการเรียนของฉัน</a>
      <?php else: ?>
        <a class="shrink-0 rounded-xl border border-black/5 bg-pastel-sky/45 px-3 py-2 hover:bg-black/5" href="/tracks/class_attendance">📅 ตารางเรียนตามวัน</a>
        <?php if ($showRoom): ?>
          <a class="shrink-0 rounded-xl border border-black/5 bg-sand-100/70 px-3 py-2 hover:bg-black/5" href="/tracks/class_room">🏫 นักเรียนประจำชั้น</a>
        <?php endif; ?>
      <?php endif; ?>
			  <?php endif; ?>
            </div>
          </nav>

          <div class="flex items-center justify-between gap-2 md:justify-end">
            <span class="truncate text-sm text-ink-800/70 md:max-w-[220px]"><?= e($user['displayname'] ?? $user['username']) ?></span>
            <a class="shrink-0 rounded-xl px-3 py-2 text-sm text-red-700 hover:bg-red-50" href="/tracks/logout">🚪 ออกจากระบบ</a>
          </div>
        </div>
      <?php else: ?>
        <nav class="text-sm">
          <a class="rounded-xl px-3 py-2 hover:bg-black/5" href="/tracks/login">🔐 เข้าสู่ระบบ</a>
        </nav>
      <?php endif; ?>
    </div>
  </header>

  <main class="mx-auto max-w-7xl px-4 py-6">
    <?= $content ?>
  </main>

  <footer class="border-t border-black/5 bg-white/55">
    <div class="mx-auto max-w-6xl px-4 py-6 text-xs text-ink-800/60">
      Powred by IT Sukhon • <?= date('Y-m-d H:i') ?>
    </div>
  </footer>

</body>
</html>
