<?php
$user = auth_user();
?>
<!doctype html>
<html lang="th">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width,initial-scale=1" />
  <title><?= e((string)$title) ?></title>
  <script src="https://cdn.tailwindcss.com"></script>
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
</head>
<body class="min-h-screen bg-gradient-to-b from-pastel-lilac/40 via-sand-50 to-pastel-mint/30 text-ink-900">

  <div aria-hidden="true" class="pointer-events-none fixed inset-0 overflow-hidden">
    <div class="absolute -top-24 -left-24 h-72 w-72 rounded-full bg-pastel-peach/40 blur-3xl"></div>
    <div class="absolute top-40 -right-24 h-80 w-80 rounded-full bg-pastel-sky/45 blur-3xl"></div>
    <div class="absolute bottom-0 left-1/3 h-64 w-64 rounded-full bg-pastel-lemon/35 blur-3xl"></div>
  </div>

  <header class="sticky top-0 z-10 border-b border-black/5 bg-white/75 backdrop-blur">
    <div class="mx-auto flex max-w-6xl items-center justify-between px-4 py-3">
      <a class="group flex items-center gap-2 font-semibold tracking-tight" href="/tracks/">
        <span class="grid h-9 w-9 place-items-center rounded-xl bg-gradient-to-br from-pastel-sky to-pastel-mint shadow-sm ring-1 ring-black/5">🏃</span>
        <span class="leading-none">
          <span class="block text-sm text-ink-800/70">Sukhon</span>
          <span class="block -mt-0.5 text-base text-ink-900">Track</span>
        </span>
      </a>

      <?php if ($user): ?>
        <nav class="flex flex-wrap items-center gap-2 text-sm">
          <div class="flex flex-wrap items-center gap-1 rounded-2xl border border-black/5 bg-sand-100/70 p-1">
            <a class="rounded-xl px-3 py-2 hover:bg-black/5" href="/tracks/?route=dashboard">🏠 Dashboard</a>
            <a class="rounded-xl px-3 py-2 hover:bg-black/5" href="/tracks/?route=students">📋 รายชื่อ</a>
          </div>

          <div class="flex flex-wrap items-center gap-1 rounded-2xl border border-black/5 bg-pastel-mint/45 p-1">
            <a class="rounded-xl px-3 py-2 hover:bg-black/5" href="/tracks/?route=register_track">🧾 ลงทะเบียน</a>
            <a class="rounded-xl px-3 py-2 hover:bg-black/5" href="/tracks/?route=track-subjects">🧩 วิชา</a>
          </div>

          <div class="flex flex-wrap items-center gap-1 rounded-2xl border border-black/5 bg-pastel-sky/45 p-1">
            <a class="rounded-xl px-3 py-2 hover:bg-black/5" href="/tracks/?route=student_track">🧒 ดูรายคน</a>
            <a class="rounded-xl px-3 py-2 hover:bg-black/5" href="/tracks/?route=report_statement">🖨️ ใบ Statement</a>
          </div>

          <div class="flex flex-wrap items-center gap-1 rounded-2xl border border-black/5 bg-pastel-lilac/40 p-1">
            <a class="rounded-xl px-3 py-2 hover:bg-black/5" href="/tracks/?route=users">👤 ผู้ใช้</a>
          </div>

          <div class="ml-auto flex items-center gap-2">
            <span class="hidden sm:inline text-ink-800/70"><?= e($user['displayname'] ?? $user['username']) ?></span>
            <a class="rounded-xl px-3 py-2 text-red-700 hover:bg-red-50" href="/tracks/?route=logout">🚪 ออกจากระบบ</a>
          </div>
        </nav>
      <?php else: ?>
        <nav class="text-sm">
          <a class="rounded-xl px-3 py-2 hover:bg-black/5" href="/tracks/?route=login">🔐 เข้าสู่ระบบ</a>
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
