<?php
$csrf = csrf_token();
$filters = $filters ?? [];
$students = $students ?? [];
$student = $student ?? null;
$studentRegs = $studentRegs ?? [];

$yearId = (int)($yearId ?? 0);
$selectedCode = (string)($filters['student_code'] ?? '');

$basePath = '/tracks/student_track';
$baseQuery = http_build_query(array_filter([
  'year_id' => $yearId,
  'class_level' => (string)($filters['class_level'] ?? ''),
  'room' => (string)($filters['room'] ?? ''),
  'q' => (string)($filters['q'] ?? ''),
], static fn($v) => $v !== '' && $v !== 0));
?>

<div class="grid gap-6">
  <section class="rounded-3xl border border-black/5 bg-white/80 p-5 shadow-sm backdrop-blur">
    <div class="flex flex-wrap items-end justify-between gap-3">
      <div>
        <h1 class="text-xl font-semibold tracking-tight">🧒 ดูข้อมูล Track รายคน</h1>
        <p class="mt-1 text-sm text-ink-800/70">เลือกเด็ก 1 คน แล้วดูว่า “ลงทะเบียนวิชาอะไรไปบ้าง”</p>
      </div>
      <a class="rounded-2xl border border-black/10 bg-white px-4 py-2.5 text-sm hover:bg-black/5" href="/tracks/register_track?year_id=<?= (int)$yearId ?><?= $selectedCode !== '' ? '&q=' . e(rawurlencode($selectedCode)) : '' ?>">🧾 ไปหน้าลงทะเบียน</a>
    </div>

    <?php if (!empty($success)): ?>
      <div class="mt-4 rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-900"><?= e((string)$success) ?></div>
    <?php endif; ?>

    <?php if (!empty($error)): ?>
      <div class="mt-4 rounded-2xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-900"><?= e((string)$error) ?></div>
    <?php endif; ?>

    <form class="mt-4 grid gap-3 rounded-3xl border border-black/5 bg-gradient-to-b from-sand-50 to-pastel-sky/20 p-4 md:grid-cols-6" method="get" action="<?= e((string)$basePath) ?>">

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
        <button class="inline-flex items-center justify-center rounded-2xl bg-gradient-to-r from-ink-900 to-ink-800 px-4 py-2.5 text-sm font-medium text-white shadow-sm hover:opacity-95">🔎 ค้นหาเด็ก</button>
        <a class="rounded-2xl border border-black/10 bg-white px-4 py-2.5 text-sm hover:bg-black/5" href="/tracks/student_track">🧹 ล้างตัวกรอง</a>
      </div>
    </form>

    <div class="mt-4 grid gap-4 lg:grid-cols-2 lg:items-start">
      <div class="rounded-3xl border border-black/5 bg-white/75 overflow-hidden">
        <div class="flex items-center justify-between gap-2 bg-sand-100 px-4 py-3">
          <div class="text-sm font-semibold">👥 รายชื่อเด็ก (เลือกได้)</div>
          <div class="text-xs text-ink-800/60">แสดงสูงสุด 500</div>
        </div>
        <div class="max-h-[520px] overflow-auto">
          <ul class="divide-y divide-black/5">
            <?php foreach ($students as $s): ?>
              <?php
                $code = (string)$s['student_code'];
                $name = (string)$s['first_name'] . ' ' . (string)$s['last_name'];
                $meta = (string)$s['class_level'] . '/' . (string)$s['class_room'] . ' เลขที่ ' . (string)$s['number_in_room'];
                $href = $basePath . ($baseQuery !== '' ? ('?' . $baseQuery . '&') : '?') . 'student_code=' . rawurlencode($code);
                $active = ($selectedCode !== '' && $selectedCode === $code);
              ?>
              <li>
                <a class="block px-4 py-3 hover:bg-sand-50 <?= $active ? 'bg-pastel-mint/40' : '' ?>" href="<?= e($href) ?>">
                  <div class="flex flex-wrap items-center gap-2">
                    <div class="font-medium"><?= e($name) ?></div>
                    <span class="rounded-full bg-pastel-sky/60 px-2.5 py-1 text-xs text-ink-800/70 ring-1 ring-black/5"><?= e($code) ?></span>
                  </div>
                  <div class="mt-1 text-xs text-ink-800/60"><?= e($meta) ?></div>
                </a>
              </li>
            <?php endforeach; ?>

            <?php if (empty($students)): ?>
              <li class="px-4 py-10 text-center text-sm text-ink-800/70">ไม่พบรายชื่อนักเรียนตามตัวกรอง</li>
            <?php endif; ?>
          </ul>
        </div>
      </div>

      <div class="rounded-3xl border border-black/5 bg-white/75 overflow-hidden">
        <div class="bg-sand-100 px-4 py-3">
          <div class="text-sm font-semibold">🧾 รายการที่ลงทะเบียน</div>
          <div class="mt-0.5 text-xs text-ink-800/60">เลือกเด็กจากฝั่งซ้ายเพื่อแสดงรายละเอียด</div>
        </div>

        <?php if (!$student): ?>
          <div class="px-4 py-10 text-center text-sm text-ink-800/70">ยังไม่ได้เลือกเด็ก</div>
        <?php else: ?>
          <div class="px-4 py-4">
            <div class="flex flex-wrap items-start justify-between gap-3">
              <div>
                <div class="text-base font-semibold"><?= e((string)$student['first_name'] . ' ' . (string)$student['last_name']) ?></div>
                <div class="mt-1 text-xs text-ink-800/60"><?= e((string)$student['student_code']) ?> • <?= e((string)$student['class_level']) ?>/<?= e((string)$student['class_room']) ?> เลขที่ <?= e((string)$student['number_in_room']) ?></div>
              </div>
              <div class="flex flex-wrap items-center gap-2">
                <a class="rounded-2xl border border-black/10 bg-white px-3 py-2 text-xs hover:bg-black/5" href="/tracks/register_track?year_id=<?= (int)$yearId ?>&q=<?= e(rawurlencode((string)$student['student_code'])) ?>">🧾 ไปแก้ไข/ลงทะเบียนเพิ่ม</a>
                <a class="rounded-2xl bg-ink-900 px-3 py-2 text-xs font-medium text-white hover:opacity-95" href="/tracks/report_statement?year_id=<?= (int)$yearId ?>&student_code=<?= e(rawurlencode((string)$student['student_code'])) ?>#preview">🖨️ ใบ Statement</a>
              </div>
            </div>
          </div>

          <div class="overflow-auto">
            <table class="min-w-full bg-white text-sm">
              <thead class="bg-sand-50 text-left text-xs text-ink-800/70">
                <tr>
                  <th class="px-4 py-3">วิชา</th>
                  <th class="px-4 py-3">วันที่ลง</th>
                  <th class="px-4 py-3">จัดการ</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-black/5">
                <?php foreach ($studentRegs as $r): ?>
                  <tr class="hover:bg-sand-50">
                    <td class="px-4 py-3">
                      <div class="font-medium"><?= e((string)$r['subject_title']) ?></div>
                      <?php if (!empty($r['subject_description'])): ?>
                        <div class="mt-1 text-xs text-ink-800/60"><?= e((string)$r['subject_description']) ?></div>
                      <?php endif; ?>
                    </td>
                    <td class="px-4 py-3 text-xs text-ink-800/60"><?= e((string)$r['created_at']) ?></td>
                    <td class="px-4 py-3">
                      <form method="post" action="<?= e((string)$basePath) ?>?<?= e((string)$baseQuery) ?>&student_code=<?= e(rawurlencode((string)$student['student_code'])) ?>" data-confirm="ยืนยันลบรายการนี้?">
                        <input type="hidden" name="_csrf" value="<?= e((string)$csrf) ?>" />
                        <input type="hidden" name="action" value="delete_one" />
                        <input type="hidden" name="id" value="<?= (int)$r['id'] ?>" />
                        <button class="rounded-2xl border border-red-200 bg-red-50 px-3 py-2 text-xs text-red-700 hover:bg-red-100">🗑️ ลบ</button>
                      </form>
                    </td>
                  </tr>
                <?php endforeach; ?>

                <?php if (empty($studentRegs)): ?>
                  <tr>
                    <td colspan="3" class="px-4 py-10 text-center text-sm text-ink-800/70">ยังไม่มีรายการลงทะเบียน</td>
                  </tr>
                <?php endif; ?>
              </tbody>
            </table>
          </div>
        <?php endif; ?>
      </div>
    </div>
  </section>
</div>
