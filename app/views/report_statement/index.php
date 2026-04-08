<?php
$csrf = csrf_token();
$filters = $filters ?? [];
$students = $students ?? [];
$settings = $settings ?? ['school_name' => '', 'logo_path' => ''];
$student = $student ?? null;

$yearId = (int)($yearId ?? 0);
$term = (int)($term ?? 1);
$term = ($term === 2) ? 2 : 1;
$selectedCode = (string)($filters['student_code'] ?? '');

$query = http_build_query(array_filter([
  'year_id' => $yearId,
  'term' => $term,
  'class_level' => (string)($filters['class_level'] ?? ''),
  'room' => (string)($filters['room'] ?? ''),
  'q' => (string)($filters['q'] ?? ''),
], static fn($v) => $v !== '' && $v !== 0));

$baseHref = '/tracks/report_statement' . ($query !== '' ? ('?' . $query) : '');
$previewHref = $selectedCode !== '' ? ($baseHref . ($query !== '' ? '&' : '?') . 'student_code=' . rawurlencode($selectedCode) . '#preview') : '';
$printHref = $selectedCode !== '' ? ($baseHref . ($query !== '' ? '&' : '?') . 'student_code=' . rawurlencode($selectedCode) . '&print=1') : '';
?>

<div class="grid gap-6">
  <style>
    /* Statement preview styling (matches print as close as possible) */
    .statement { font-family: "TH Sarabun New", "TH SarabunPSK", "Sarabun", "Noto Sans Thai", sans-serif; color: #111; }
    .a4 { width: 210mm; min-height: 297mm; }
    .statement { box-sizing: border-box; padding: 8mm; font-size: 16pt; line-height: 1.18; }
    .statement .header { display: grid; grid-template-columns: 52mm 1fr 52mm; gap: 7mm; align-items: center; }
    .statement .logo img { width: 22mm; height: 22mm; object-fit: contain; }
    .statement .logo-placeholder { width: 22mm; height: 22mm; display: grid; place-items: center; border: 1px dashed #999; font-size: 11pt; color: #666; }
    .statement .school-name { font-size: 21pt; font-weight: 700; text-align: center; }
    .statement .school-sub { font-size: 14pt; text-align: center; color: #444; margin-top: 0.8mm; }
    .statement .meta { font-size: 12pt; color: #222; text-align: right; }
    .statement .title { margin-top: 4mm; text-align: center; font-size: 19pt; font-weight: 700; }
    .statement .info { margin-top: 4mm; border: 1px solid #222; padding: 3mm; }
    .statement .info .row { display: grid; grid-template-columns: 1fr 1fr; gap: 4mm; }
    .statement .k { font-weight: 700; }
    .statement .section { margin-top: 4mm; }
    .statement .section-title { font-size: 16pt; font-weight: 700; margin-bottom: 1.5mm; }
    .statement .tbl { width: 100%; border-collapse: collapse; font-size: 12pt; }
    .statement .tbl th, .statement .tbl td { border: 1px solid #222; padding: 1.6mm 2mm; vertical-align: top; }
    .statement .tbl th { background: #f5f5f5; font-weight: 700; }
    .statement .tbl .no { width: 14mm; text-align: center; }
    .statement .tbl .date { width: 30mm; text-align: center; }
    .statement .tbl .year-row td { background: #eaeaea; font-weight: 700; }
    .statement .tbl .group-row td { background: #f7f7f7; font-weight: 700; text-align: center; }
    .statement .subj-title { font-weight: 700; }
    .statement .subj-desc { margin-top: 0.8mm; font-size: 11pt; color: #444; }
    .statement .footer { margin-top: 6mm; display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 6mm; }
    .statement .sig { text-align: center; font-size: 12pt; }
    .statement .sig .signbox { position: relative; height: 18mm; }
    .statement .sig .line { position: absolute; left: 0; right: 0; bottom: 0; height: 0; border-top: 1px solid #222; }
    .statement .sig .name { margin-top: 1mm; }
    .statement .sig .pos { margin-top: 0.4mm; }
    .statement .sign { position: absolute; left: 50%; transform: translateX(-50%); bottom: 1.8mm; height: 14mm; width: auto; object-fit: contain; }
    .statement .note { margin-top: 4mm; font-size: 11pt; color: #333; }
    .statement .empty { padding: 14mm; text-align: center; font-size: 16pt; color: #333; border: 1px dashed #999; margin-top: 10mm; }
  </style>
  <section class="rounded-3xl border border-black/5 bg-white/80 p-5 shadow-sm backdrop-blur">
    <div class="flex flex-wrap items-end justify-between gap-3">
      <div>
        <h1 class="text-xl font-semibold tracking-tight">🖨️ ใบ Transcript (A4)</h1>
        <p class="mt-1 text-sm text-ink-800/70">Preview ได้ในหน้านี้ • กดพิมพ์จะเปิดหน้าใหม่สำหรับสั่ง Print</p>
      </div>

      <div class="flex flex-wrap items-center gap-2">
        <a class="rounded-2xl border border-black/10 bg-white px-4 py-2.5 text-sm hover:bg-black/5 <?= $selectedCode === '' ? 'pointer-events-none opacity-50' : '' ?>" href="<?= e($previewHref) ?>">👀 Preview</a>
        <a target="_blank" rel="noopener" class="rounded-2xl bg-ink-900 px-4 py-2.5 text-sm font-medium text-white shadow-sm hover:opacity-95 <?= $selectedCode === '' ? 'pointer-events-none opacity-50' : '' ?>" href="<?= e($printHref) ?>">🖨️ พิมพ์</a>
      </div>
    </div>

    <?php if (!empty($success)): ?>
      <div class="mt-4 rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-900"><?= e((string)$success) ?></div>
    <?php endif; ?>

    <?php if (!empty($error)): ?>
      <div class="mt-4 rounded-2xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-900"><?= e((string)$error) ?></div>
    <?php endif; ?>

    <div class="mt-4 grid gap-4 lg:grid-cols-2">
      <div class="rounded-3xl border border-black/5 bg-gradient-to-b from-sand-50 to-pastel-lilac/20 p-4">
        <div class="text-sm font-semibold">🏫 ข้อมูลหัวรายงาน</div>
        <form class="mt-3 grid gap-3" method="post" enctype="multipart/form-data">
          <input type="hidden" name="_csrf" value="<?= e((string)$csrf) ?>" />
          <input type="hidden" name="action" value="save_settings" />

          <div>
            <label class="text-xs font-medium">ชื่อโรงเรียน</label>
            <input name="school_name" value="<?= e((string)($settings['school_name'] ?? '')) ?>" placeholder="เช่น โรงเรียนสุขล้ำวิทยา" class="mt-1 w-full rounded-2xl border border-black/10 bg-white px-3 py-2.5 text-sm outline-none focus:border-calm-500" />
          </div>

          <div>
            <label class="text-xs font-medium">วันที่ออกเอกสาร (ปรับได้)</label>
            <input type="date" name="issued_date" value="<?= e((string)($settings['issued_date'] ?? '')) ?>" class="mt-1 w-full rounded-2xl border border-black/10 bg-white px-3 py-2.5 text-sm outline-none focus:border-calm-500" />
            <div class="mt-1 text-[11px] text-ink-800/60">ถ้าเว้นว่าง ระบบจะใช้วันที่ปัจจุบัน</div>
          </div>

          <div>
            <label class="text-xs font-medium">Logo โรงเรียน (PNG/JPG/WEBP, ≤ 2MB)</label>
            <div class="mt-1 flex flex-wrap items-center gap-2">
              <input id="rsLogoInput" type="file" name="logo" accept="image/png,image/jpeg,image/webp" class="sr-only" />
              <label for="rsLogoInput" class="inline-flex cursor-pointer items-center justify-center rounded-2xl border border-black/10 bg-white px-3 py-2 text-sm hover:bg-black/5">📤 อัปโหลดโลโก้</label>
              <span id="rsLogoName" class="text-xs text-ink-800/60">ยังไม่ได้เลือกไฟล์</span>
            </div>
            <?php if (!empty($settings['logo_path'])): ?>
              <div class="mt-2 flex items-center gap-3 text-xs text-ink-800/60">
                <img class="h-10 w-10 rounded-xl border border-black/10 bg-white object-contain" src="/tracks/<?= e(ltrim((string)$settings['logo_path'], '/')) ?>?v=<?= e(rawurlencode((string)($settings['updated_at'] ?? ''))) ?>" alt="logo" />
                <span>โลโก้ปัจจุบัน: <?= e((string)$settings['logo_path']) ?></span>
              </div>
            <?php endif; ?>
          </div>

          <div class="grid gap-3 md:grid-cols-3">
            <div>
              <label class="text-xs font-medium">ชื่อ ผู้จัดทำ/ครูที่ปรึกษา</label>
              <input name="advisor_name" value="<?= e((string)($settings['advisor_name'] ?? '')) ?>" placeholder="เช่น นาย/นาง/นางสาว ..." class="mt-1 w-full rounded-2xl border border-black/10 bg-white px-3 py-2.5 text-sm outline-none focus:border-calm-500" />
              <label class="mt-2 block text-xs font-medium">รูปลายเซ็น (ครูที่ปรึกษา)</label>
              <input type="file" name="advisor_sign" accept="image/png,image/jpeg,image/webp" class="mt-1 w-full rounded-2xl border border-black/10 bg-white px-3 py-2.5 text-sm" />
            </div>
            <div>
              <label class="text-xs font-medium">ชื่อ งานทะเบียน/วัดผล</label>
              <input name="registrar_name" value="<?= e((string)($settings['registrar_name'] ?? '')) ?>" placeholder="เช่น นาย/นาง/นางสาว ..." class="mt-1 w-full rounded-2xl border border-black/10 bg-white px-3 py-2.5 text-sm outline-none focus:border-calm-500" />
              <label class="mt-2 block text-xs font-medium">รูปลายเซ็น (ทะเบียน/วัดผล)</label>
              <input type="file" name="registrar_sign" accept="image/png,image/jpeg,image/webp" class="mt-1 w-full rounded-2xl border border-black/10 bg-white px-3 py-2.5 text-sm" />
            </div>
            <div>
              <label class="text-xs font-medium">ชื่อ ผู้อำนวยการ</label>
              <input name="director_name" value="<?= e((string)($settings['director_name'] ?? '')) ?>" placeholder="เช่น นาย/นาง/นางสาว ..." class="mt-1 w-full rounded-2xl border border-black/10 bg-white px-3 py-2.5 text-sm outline-none focus:border-calm-500" />
              <label class="mt-2 block text-xs font-medium">รูปลายเซ็น (ผู้อำนวยการ)</label>
              <input type="file" name="director_sign" accept="image/png,image/jpeg,image/webp" class="mt-1 w-full rounded-2xl border border-black/10 bg-white px-3 py-2.5 text-sm" />
            </div>
          </div>

          <button class="rounded-2xl bg-calm-600 px-4 py-2.5 text-sm font-medium text-white shadow-sm hover:bg-calm-500">💾 บันทึกหัวรายงาน</button>
        </form>
      </div>

      <form class="rounded-3xl border border-black/5 bg-gradient-to-b from-sand-50 to-pastel-sky/20 p-4" method="get" action="/tracks/report_statement">
        <div class="text-sm font-semibold">🔎 เลือกนักเรียน</div>

        <div class="mt-3 grid gap-3 md:grid-cols-6">
          <div class="md:col-span-2">
            <label class="text-xs font-medium">ปีการศึกษา</label>
            <select name="year_id" class="mt-1 w-full rounded-2xl border border-black/10 bg-white px-3 py-2.5 text-sm outline-none focus:border-calm-500">
              <?php foreach (($years ?? []) as $y): ?>
                <?php
                  $id = (int)$y['id'];
                  $label = (string)$y['title'] . ' (' . (string)$y['year_be'] . ')';
                  $active = ((int)$y['is_active'] === 1) ? ' • active' : '';
                ?>
                <option value="<?= $id ?>" <?= ($id === (int)$yearId) ? 'selected' : '' ?>><?= e($label . $active) ?></option>
              <?php endforeach; ?>
            </select>
          </div>

          <div>
            <label class="text-xs font-medium">เทอม</label>
            <select name="term" class="mt-1 w-full rounded-2xl border border-black/10 bg-white px-3 py-2.5 text-sm outline-none focus:border-calm-500">
              <option value="1" <?= $term === 1 ? 'selected' : '' ?>>เทอม 1</option>
              <option value="2" <?= $term === 2 ? 'selected' : '' ?>>เทอม 2</option>
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
            <input name="q" value="<?= e((string)($filters['q'] ?? '')) ?>" placeholder="เลขประจำตัว / ชื่อ / นามสกุล" class="mt-1 w-full rounded-2xl border border-black/10 bg-white px-3 py-2.5 text-sm outline-none focus:border-calm-500" />
          </div>

          <div class="md:col-span-6 flex flex-wrap items-center gap-2">
            <button class="inline-flex items-center justify-center rounded-2xl bg-gradient-to-r from-ink-900 to-ink-800 px-4 py-2.5 text-sm font-medium text-white shadow-sm hover:opacity-95">🔎 ค้นหา/โหลดรายชื่อ</button>
            <a class="rounded-2xl border border-black/10 bg-white px-4 py-2.5 text-sm hover:bg-black/5" href="/tracks/report_statement">🧹 ล้างตัวกรอง</a>
          </div>
        </div>

        <div class="mt-4 overflow-hidden rounded-3xl border border-black/5 bg-white/80">
          <div class="flex items-center justify-between gap-2 bg-sand-100 px-4 py-3">
            <div class="text-sm font-semibold">👥 รายชื่อเด็ก (คลิกเพื่อเลือก)</div>
            <div class="text-xs text-ink-800/60">แสดงสูงสุด 500</div>
          </div>
          <div class="max-h-[420px] overflow-auto">
            <ul class="divide-y divide-black/5">
              <?php foreach ($students as $s): ?>
                <?php
                  $code = (string)$s['student_code'];
                  $name = (string)$s['first_name'] . ' ' . (string)$s['last_name'];
                  $meta = (string)$s['class_level'] . '/' . (string)$s['class_room'] . ' เลขที่ ' . (string)$s['number_in_room'];
                  $href = $baseHref . ($query !== '' ? '&' : '?') . 'student_code=' . rawurlencode($code);
                  $active = ($selectedCode !== '' && $selectedCode === $code);
                ?>
                <li>
                  <div class="flex items-start gap-3 px-4 py-3 hover:bg-sand-50 <?= $active ? 'bg-pastel-mint/40' : '' ?>">
                    <input
                      class="rs-student-check mt-1 h-4 w-4 rounded border-black/20 text-ink-900"
                      type="checkbox"
                      form="rsBulkForm"
                      name="student_codes[]"
                      value="<?= e($code) ?>"
                    />
                    <a class="block flex-1" href="<?= e($href) ?>">
                      <div class="flex flex-wrap items-center gap-2">
                        <div class="font-medium"><?= e($name) ?></div>
                        <span class="rounded-full bg-pastel-sky/60 px-2.5 py-1 text-xs text-ink-800/70 ring-1 ring-black/5"><?= e($code) ?></span>
                      </div>
                      <div class="mt-1 text-xs text-ink-800/60"><?= e($meta) ?></div>
                    </a>
                    <a class="mt-0.5 shrink-0 rounded-xl border border-black/10 bg-white px-2.5 py-1.5 text-xs hover:bg-black/5" href="<?= e($href) ?>#preview">Preview</a>
                  </div>
                </li>
              <?php endforeach; ?>

              <?php if (empty($students)): ?>
                <li class="px-4 py-10 text-center text-sm text-ink-800/70">ไม่พบรายชื่อนักเรียนตามตัวกรอง</li>
              <?php endif; ?>
            </ul>
          </div>
        </div>
      </form>
    </div>

    <div class="mt-4 rounded-3xl border border-black/5 bg-white/80 p-4">
      <div class="flex flex-wrap items-end justify-between gap-2">
        <div>
          <div class="text-sm font-semibold">🖨️ พิมพ์หลายคน/ทั้งชั้น</div>
          <div class="mt-0.5 text-xs text-ink-800/60">เลือกเด็กจากรายการด้านบน (หรือพิมพ์ทั้งหมดตามตัวกรอง) แล้วกดพิมพ์</div>
        </div>
        <div class="flex flex-wrap items-center gap-2 text-xs">
          <button id="rsSelectAll" type="button" class="rounded-2xl border border-black/10 bg-white px-3 py-2 hover:bg-black/5">เลือกทั้งหมดที่แสดง</button>
          <button id="rsClearAll" type="button" class="rounded-2xl border border-black/10 bg-white px-3 py-2 hover:bg-black/5">เอาติ๊กออกทั้งหมด</button>
        </div>
      </div>

      <form id="rsBulkForm" class="mt-3 flex flex-wrap items-center gap-2" method="post" target="_blank" action="<?= e($baseHref) ?>">
        <input type="hidden" name="_csrf" value="<?= e((string)$csrf) ?>" />
        <input type="hidden" name="action" value="print_bulk" />

        <input type="hidden" name="print_scope" value="selected" />
        <button class="rounded-2xl bg-ink-900 px-4 py-2.5 text-sm font-medium text-white hover:opacity-95">🖨️ พิมพ์เฉพาะที่เลือก</button>

        <button name="print_scope" value="all_filtered" class="rounded-2xl border border-black/10 bg-white px-4 py-2.5 text-sm hover:bg-black/5" data-confirm="พิมพ์ทั้งหมดตามตัวกรอง (ทั้งชั้น/ห้อง) ใช่ไหม?">พิมพ์ทั้งหมดตามตัวกรอง</button>

        <span class="text-xs text-ink-800/60">(จะเปิดหน้าใหม่ แล้วกด Print ได้)</span>

        <div class="w-full"></div>
        <div class="w-full rounded-2xl border border-black/5 bg-sand-50 px-3 py-2 text-xs text-ink-800/70">
          เคล็ดลับ: เลือก “ชั้น” + “ห้อง” ด้านบน แล้วกด “พิมพ์ทั้งหมดตามตัวกรอง” = ปริ้นทั้งห้องได้เลย
        </div>

      </form>
    </div>

    <div id="preview" class="mt-6">
      <div class="flex flex-wrap items-end justify-between gap-2">
        <div>
          <div class="text-sm font-semibold">👀 Preview ใบ Transcript</div>
          <div class="mt-0.5 text-xs text-ink-800/60">ถ้าต้องการพิมพ์จริง แนะนำกดปุ่ม “พิมพ์” ด้านบน (จะเปิดหน้าใหม่)</div>
        </div>
        <?php if ($selectedCode !== ''): ?>
          <a target="_blank" rel="noopener" class="rounded-2xl bg-ink-900 px-4 py-2 text-xs font-medium text-white hover:opacity-95" href="<?= e($printHref) ?>">🖨️ พิมพ์หน้านี้</a>
        <?php endif; ?>
      </div>

      <div class="mt-3 overflow-auto rounded-3xl border border-black/5 bg-gradient-to-b from-sand-50 to-white p-4">
        <div class="mx-auto w-[210mm] origin-top scale-[0.88] rounded-xl bg-white shadow-md ring-1 ring-black/5">
          <?php
            // reuse the print statement markup
            require __DIR__ . '/_statement.php';
          ?>
        </div>
      </div>
    </div>
  </section>
</div>

<script>
  (function () {
    const logoInput = document.getElementById('rsLogoInput');
    const logoName = document.getElementById('rsLogoName');
    if (logoInput && logoName) {
      logoInput.addEventListener('change', () => {
        const f = logoInput.files && logoInput.files[0] ? logoInput.files[0] : null;
        logoName.textContent = f ? f.name : 'ยังไม่ได้เลือกไฟล์';
      });
    }

    const selectAllBtn = document.getElementById('rsSelectAll');
    const clearAllBtn = document.getElementById('rsClearAll');
    const checks = Array.from(document.querySelectorAll('.rs-student-check'));
    if (selectAllBtn) {
      selectAllBtn.addEventListener('click', () => {
        checks.forEach(c => { c.checked = true; });
      });
    }
    if (clearAllBtn) {
      clearAllBtn.addEventListener('click', () => {
        checks.forEach(c => { c.checked = false; });
      });
    }
  })();
</script>
