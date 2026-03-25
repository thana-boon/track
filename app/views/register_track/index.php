<?php
$csrf = csrf_token();
$filters = $filters ?? [];
$subjects = $subjects ?? [];
$students = $students ?? [];
$regs = $regs ?? [];

$activeSubjects = array_values(array_filter($subjects, static fn($s) => (int)($s['is_active'] ?? 0) === 1));

$filterBaseQuery = http_build_query(array_filter([
  'route' => 'register_track',
  'year_id' => (int)($yearId ?? 0),
  'class_level' => (string)($filters['class_level'] ?? ''),
  'room' => (string)($filters['room'] ?? ''),
  'q' => (string)($filters['q'] ?? ''),
], static fn($v) => $v !== '' && $v !== 0));

// The regs table partial expects $filterQuery to be the base query (without page).
$filterQuery = $filterBaseQuery;
?>

<div class="grid gap-6">
  <section class="rounded-3xl border border-black/5 bg-white/80 p-5 shadow-sm backdrop-blur">
    <div class="flex flex-wrap items-end justify-between gap-3">
      <div>
        <h1 class="text-xl font-semibold tracking-tight">🧾 ลงทะเบียน Track</h1>
        <p class="mt-1 text-sm text-ink-800/70">ออกแบบให้ “ลงวิชาเดียวกันหลายคน” ได้เร็ว: เลือกเด็กหลายคน → เลือกวิชา → กดลงทะเบียน</p>
      </div>

      <div class="text-xs text-ink-800/60">
        เคล็ดลับ: ลากการ์ดวิชาไปวางบนชื่อนักเรียนได้
      </div>
    </div>

    <?php if (!empty($success)): ?>
      <div class="mt-4 rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-900"><?= e((string)$success) ?></div>
    <?php endif; ?>

    <?php if (!empty($error)): ?>
      <div class="mt-4 rounded-2xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-900"><?= e((string)$error) ?></div>
    <?php endif; ?>

    <form class="mt-4 grid gap-3 rounded-3xl border border-black/5 bg-gradient-to-b from-sand-50 to-pastel-lilac/20 p-4 md:grid-cols-6" method="get" action="/tracks/">
      <input type="hidden" name="route" value="register_track" />

      <div class="md:col-span-2">
        <label class="text-xs font-medium">ปีการศึกษา</label>
        <select name="year_id" class="mt-1 w-full rounded-2xl border border-black/10 bg-white px-3 py-2.5 text-sm outline-none focus:border-calm-500">
          <?php foreach (($years ?? []) as $y): ?>
            <?php
              $id = (int)$y['id'];
              $label = (string)$y['title'] . ' (' . (string)$y['year_be'] . ')';
              $active = ((int)$y['is_active'] === 1) ? ' • active' : '';
            ?>
            <option value="<?= $id ?>" <?= ($id === (int)($yearId ?? 0)) ? 'selected' : '' ?>><?= e($label . $active) ?></option>
          <?php endforeach; ?>
        </select>
      </div>

      <div>
        <label class="text-xs font-medium">ชั้น</label>
        <select name="class_level" class="mt-1 w-full rounded-2xl border border-black/10 bg-white px-3 py-2.5 text-sm outline-none focus:border-calm-500">
          <?php foreach (($classLevelOptions ?? []) as $val => $label): ?>
            <option value="<?= e((string)$val) ?>" <?= ((string)($filters['class_level'] ?? '') === (string)$val) ? 'selected' : '' ?>><?= e((string)$label) ?></option>
          <?php endforeach; ?>
        </select>
      </div>

      <div>
        <label class="text-xs font-medium">ห้อง</label>
        <input name="room" value="<?= e((string)($filters['room'] ?? '')) ?>" placeholder="เช่น 1" class="mt-1 w-full rounded-2xl border border-black/10 bg-white px-3 py-2.5 text-sm outline-none focus:border-calm-500" />
      </div>

      <div class="md:col-span-2">
        <label class="text-xs font-medium">ค้นหา</label>
        <input name="q" value="<?= e((string)($filters['q'] ?? '')) ?>" placeholder="เลขประจำตัว / ชื่อ / นามสกุล / วิชา" class="mt-1 w-full rounded-2xl border border-black/10 bg-white px-3 py-2.5 text-sm outline-none focus:border-calm-500" />
      </div>

      <div class="md:col-span-6 flex flex-wrap items-center gap-2">
        <button class="inline-flex items-center justify-center rounded-2xl bg-gradient-to-r from-ink-900 to-ink-800 px-4 py-2.5 text-sm font-medium text-white shadow-sm hover:opacity-95">🔎 โหลดรายชื่อ</button>
        <a class="rounded-2xl border border-black/10 bg-white px-4 py-2.5 text-sm hover:bg-black/5" href="/tracks/?route=register_track">🧹 ล้างตัวกรอง</a>
      </div>
    </form>

    <div class="mt-4 grid gap-4 lg:grid-cols-2 lg:items-stretch">
      <form id="bulkForm" class="flex h-full flex-col rounded-3xl border border-black/5 bg-gradient-to-b from-sand-50 to-pastel-mint/25 p-5" method="post" action="/tracks/?<?= e((string)$filterBaseQuery) ?>">
        <input type="hidden" name="_csrf" value="<?= e($csrf) ?>" />

        <div class="flex flex-wrap items-start justify-between gap-3">
          <div>
            <div class="flex flex-wrap items-center gap-3">
              <h2 class="text-sm font-semibold">👧🧒 เลือกนักเรียน</h2>
              <label class="inline-flex items-center gap-2 text-xs">
                <input id="checkAll" type="checkbox" class="h-4 w-4 rounded border-black/20 text-calm-600 focus:ring-calm-500" />
                เลือกทั้งหมด
              </label>
              <button id="clearAll" type="button" class="rounded-2xl border border-black/10 bg-white px-3 py-1.5 text-xs hover:bg-black/5">🚫 เอาติ๊กออกทั้งหมด</button>
            </div>
            <p class="mt-1 text-xs text-ink-800/60">เลือก 1 คน = ลงรายบุคคล • เลือกหลายคน = ลงกลุ่ม</p>
          </div>
        </div>

        <div class="mt-4 grid gap-3 sm:grid-cols-2">
          <div class="sm:col-span-2">
            <label class="text-xs font-medium">วิชาที่จะลงทะเบียน</label>
            <select id="subjectSelect" name="subject_id" class="mt-1 w-full rounded-2xl border border-black/10 bg-white px-4 py-2.5 text-sm outline-none focus:border-calm-500">
              <option value="">— เลือกวิชา —</option>
              <?php foreach ($activeSubjects as $subj): ?>
                <option value="<?= (int)$subj['id'] ?>"><?= e((string)$subj['title']) ?></option>
              <?php endforeach; ?>
            </select>
          </div>

          <button name="action" value="bulk_assign" class="rounded-2xl bg-calm-600 px-4 py-2.5 text-sm font-medium text-white shadow-sm hover:bg-calm-500">✅ ลงทะเบียนให้ที่เลือก</button>
          <button name="action" value="bulk_unassign" class="rounded-2xl border border-red-200 bg-red-50 px-4 py-2.5 text-sm font-medium text-red-700 hover:bg-red-100">🗑️ ลบวิชานี้ออกจากที่เลือก</button>
        </div>

        <div class="mt-4 flex-1 overflow-hidden rounded-3xl border border-black/5 bg-white/80">
          <div class="max-h-[420px] overflow-auto">
            <ul class="divide-y divide-black/5">
              <?php foreach ($students as $s): ?>
                <?php
                  $code = (string)$s['student_code'];
                  $name = (string)$s['first_name'] . ' ' . (string)$s['last_name'];
                  $meta = (string)$s['class_level'] . '/' . (string)$s['class_room'] . ' เลขที่ ' . (string)$s['number_in_room'];
                ?>
                <li class="student-row flex items-center gap-3 px-4 py-3 hover:bg-sand-50" data-student="<?= e($code) ?>">
                  <input class="student-check h-4 w-4 rounded border-black/20 text-calm-600 focus:ring-calm-500" type="checkbox" name="student_codes[]" value="<?= e($code) ?>" />
                  <div class="min-w-0 flex-1">
                    <div class="flex flex-wrap items-center gap-2">
                      <span class="font-medium truncate"><?= e($name) ?></span>
                      <span class="rounded-full bg-pastel-sky/60 px-2.5 py-1 text-xs text-ink-800/70 ring-1 ring-black/5"><?= e($code) ?></span>
                    </div>
                    <div class="mt-1 text-xs text-ink-800/60"><?= e($meta) ?></div>
                  </div>
                  <span class="rounded-2xl border border-black/10 bg-white px-2.5 py-1 text-xs text-ink-800/60">วางวิชาที่นี่ ↘</span>
                </li>
              <?php endforeach; ?>

              <?php if (empty($students)): ?>
                <li class="px-4 py-10 text-center text-sm text-ink-800/70">ไม่พบรายชื่อนักเรียนตามตัวกรอง</li>
              <?php endif; ?>
            </ul>
          </div>
        </div>

        <p class="mt-3 text-xs text-ink-800/60">หมายเหตุ: ลงทะเบียนจะไม่ซ้ำ (กันซ้ำให้อัตโนมัติ)</p>
      </form>

      <div class="flex h-full flex-col rounded-3xl border border-black/5 bg-gradient-to-b from-sand-50 to-pastel-sky/25 p-5">
        <div class="flex items-start justify-between gap-3">
          <div>
            <h2 class="text-sm font-semibold">🧩 วิชา (ลากได้)</h2>
            <p class="mt-1 text-xs text-ink-800/60">ลากการ์ดวิชาไปวางที่ชื่อนักเรียนเพื่อเพิ่มรายบุคคลแบบเร็วๆ</p>
          </div>
          <a class="rounded-2xl border border-black/10 bg-white px-3 py-2 text-xs hover:bg-black/5" href="/tracks/?route=track-subjects">จัดการวิชา</a>
        </div>

        <div class="mt-4 grid gap-3 sm:grid-cols-2">
          <?php foreach ($activeSubjects as $subj): ?>
            <div draggable="true" class="subject-card cursor-grab rounded-3xl border border-black/5 bg-white/85 p-4 shadow-sm hover:bg-white" data-subject="<?= (int)$subj['id'] ?>">
              <div class="text-sm font-semibold"><?= e((string)$subj['title']) ?></div>
              <?php if (!empty($subj['description'])): ?>
                <div class="mt-1 line-clamp-2 text-xs text-ink-800/60"><?= e((string)$subj['description']) ?></div>
              <?php endif; ?>
              <div class="mt-3 text-xs text-ink-800/55">ลากไปวาง หรือเลือกจาก dropdown</div>
            </div>
          <?php endforeach; ?>

          <?php if (empty($activeSubjects)): ?>
            <div class="sm:col-span-2 rounded-3xl border border-black/5 bg-white/70 p-5 text-sm text-ink-800/70">
              ยังไม่มีวิชาที่เปิดใช้งาน ไปเพิ่มที่หน้า “วิชา Track” ก่อนครับ
            </div>
          <?php endif; ?>
        </div>

        <div class="mt-4 rounded-3xl border border-black/5 bg-white/70 p-4 text-xs text-ink-800/60">
          ถ้าลากไม่ได้ (มือถือ) ให้เลือกนักเรียนด้วย checkbox แล้วกด “ลงทะเบียนให้ที่เลือก” แทน
        </div>
      </div>
    </div>

    <?php require __DIR__ . '/_regs_table.php'; ?>
  </section>
</div>

<script>
  (function () {
    const checkAll = document.getElementById('checkAll');
    const clearAll = document.getElementById('clearAll');
    const checks = Array.from(document.querySelectorAll('.student-check'));
    if (checkAll) {
      checkAll.addEventListener('change', () => {
        checks.forEach(cb => { cb.checked = checkAll.checked; });
      });
    }

    if (clearAll) {
      clearAll.addEventListener('click', () => {
        checks.forEach(cb => { cb.checked = false; });
        if (checkAll) checkAll.checked = false;
      });
    }

    // Keep the select-all checkbox in sync when user toggles individual rows
    checks.forEach(cb => {
      cb.addEventListener('change', () => {
        if (!checkAll) return;
        checkAll.checked = checks.length > 0 && checks.every(x => x.checked);
      });
    });

    // Drag & drop subjects onto student rows
    let draggedSubjectId = null;

    async function refreshRegs() {
      const container = document.getElementById('regsContainer');
      if (!container) return;
      const currentPage = parseInt(container.dataset.page || '1', 10) || 1;
      const url = '/tracks/?<?= e((string)$filterBaseQuery) ?>' + (currentPage > 1 ? ('&page=' + currentPage) : '') + '&fragment=regs';
      try {
        const res = await fetch(url, { method: 'GET' });
        const html = await res.text();
        // Replace entire regs container (includes header/table)
        container.outerHTML = html;
      } catch (_) {
        // ignore
      }
    }

    document.addEventListener('click', async (e) => {
      const a = e.target && e.target.closest ? e.target.closest('.regs-page-link') : null;
      if (!a) return;
      // Prefer AJAX if possible
      const container = document.getElementById('regsContainer');
      if (!container) return;
      e.preventDefault();
      const page = parseInt(a.dataset.page || '1', 10) || 1;
      container.dataset.page = String(page);
      await refreshRegs();
      // Smooth scroll to table top
      const newContainer = document.getElementById('regsContainer');
      if (newContainer) newContainer.scrollIntoView({ behavior: 'smooth', block: 'start' });
    });
    document.querySelectorAll('.subject-card').forEach(card => {
      card.addEventListener('dragstart', (e) => {
        draggedSubjectId = card.dataset.subject;
        e.dataTransfer.effectAllowed = 'copy';
      });
    });

    document.querySelectorAll('.student-row').forEach(row => {
      row.addEventListener('dragover', (e) => {
        if (!draggedSubjectId) return;
        e.preventDefault();
        row.classList.add('ring-2', 'ring-calm-500/40');
      });
      row.addEventListener('dragleave', () => {
        row.classList.remove('ring-2', 'ring-calm-500/40');
      });
      row.addEventListener('drop', async (e) => {
        e.preventDefault();
        row.classList.remove('ring-2', 'ring-calm-500/40');
        const studentCode = row.dataset.student;
        const subjectId = draggedSubjectId;
        if (!studentCode || !subjectId) return;

        const fd = new FormData();
        fd.append('_csrf', '<?= e($csrf) ?>');
        fd.append('action', 'assign_ajax');
        fd.append('student_code', studentCode);
        fd.append('subject_id', subjectId);

        try {
          const res = await fetch('/tracks/?<?= e((string)$filterBaseQuery) ?>', { method: 'POST', body: fd });
          const json = await res.json();
          if (!json.ok) throw new Error(json.error || 'error');
          // Quick visual feedback
          row.classList.add('bg-emerald-50');
          setTimeout(() => row.classList.remove('bg-emerald-50'), 700);
          refreshRegs();
        } catch (err) {
          alert('ไม่สามารถลงทะเบียนได้: ' + (err && err.message ? err.message : 'เกิดข้อผิดพลาด'));
        }
      });
    });
  })();
</script>
