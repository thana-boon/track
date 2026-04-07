<?php
$years = $years ?? [];
$yearId = (int)($yearId ?? 0);
$classes = $classes ?? [];
$teachers = $teachers ?? [];
$advisorMap = $advisorMap ?? [];
$csrf = (string)($csrf ?? csrf_token());
?>

<div class="grid gap-6">
  <section class="rounded-3xl border border-black/5 bg-white/80 p-5 shadow-sm backdrop-blur">
    <div class="flex flex-wrap items-end justify-between gap-3">
      <div>
        <h1 class="text-xl font-semibold tracking-tight">👩‍🏫 กำหนดครูประจำชั้น</h1>
        <p class="mt-1 text-sm text-ink-800/70">รายชื่อชั้นเรียนดึงจากฐานข้อมูลนักเรียน • เลือกครูจากผู้ใช้ที่ role = teacher</p>
      </div>

      <div class="flex items-center gap-2">
        <label class="text-xs font-medium text-ink-800/70">ปีการศึกษา</label>
        <select class="rounded-xl border border-black/10 bg-white px-3 py-2 text-sm outline-none focus:border-calm-500" onchange="location.href='/tracks/class_advisors?year_id='+encodeURIComponent(this.value)">
          <?php foreach ($years as $y): ?>
            <?php
              $id = (int)($y['id'] ?? 0);
              $label = (string)($y['title'] ?? '') . ' (' . (string)($y['year_be'] ?? '') . ')';
              $active = ((int)($y['is_active'] ?? 0) === 1) ? ' • active' : '';
            ?>
            <option value="<?= $id ?>" <?= $id === $yearId ? 'selected' : '' ?>><?= e($label . $active) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
    </div>

    <?php if (!empty($success)): ?>
      <div class="mt-4 rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-900"><?= e((string)$success) ?></div>
    <?php endif; ?>

    <?php if (!empty($error)): ?>
      <div class="mt-4 rounded-2xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-900"><?= e((string)$error) ?></div>
    <?php endif; ?>

    <div class="mt-4 overflow-hidden rounded-3xl border border-black/5 bg-white/80">
      <div class="overflow-auto">
        <table class="min-w-full bg-white text-sm">
          <thead class="bg-sand-50 text-left text-xs text-ink-800/70">
            <tr>
              <th class="px-4 py-3">ชั้นเรียน</th>
              <th class="px-4 py-3">จำนวนนักเรียน</th>
              <th class="px-4 py-3">ครูประจำชั้น</th>
              <th class="px-4 py-3">บันทึก</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-black/5">
            <?php foreach ($classes as $c): ?>
              <?php
                $level = (string)($c['class_level'] ?? '');
                $room = (int)($c['class_room'] ?? 0);
                $count = (int)($c['student_count'] ?? 0);
                $key = $level . '|' . (string)$room;
                $advisor = $advisorMap[$key] ?? null;
                $selectedTeacherId = is_array($advisor) ? (int)($advisor['teacher_user_id'] ?? 0) : 0;
              ?>
              <tr class="hover:bg-sand-50">
                <td class="px-4 py-3">
                  <div class="font-medium"><?= e($level) ?>/<?= (int)$room ?></div>
                </td>
                <td class="px-4 py-3">
                  <span class="inline-flex items-center rounded-full bg-pastel-sky/60 px-2.5 py-1 text-xs text-ink-800/70 ring-1 ring-black/5"><?= $count ?> คน</span>
                </td>
                <td class="px-4 py-3">
                  <form class="flex items-center gap-2" method="post">
                    <input type="hidden" name="_csrf" value="<?= e($csrf) ?>" />
                    <input type="hidden" name="action" value="set_advisor" />
                    <input type="hidden" name="year_id" value="<?= (int)$yearId ?>" />
                    <input type="hidden" name="class_level" value="<?= e($level) ?>" />
                    <input type="hidden" name="class_room" value="<?= (int)$room ?>" />

                    <select name="teacher_user_id" class="min-w-[240px] rounded-xl border border-black/10 bg-white px-3 py-2 text-sm outline-none focus:border-calm-500">
                      <option value="0" <?= $selectedTeacherId === 0 ? 'selected' : '' ?>>— ยังไม่กำหนด —</option>
                      <?php foreach ($teachers as $t): ?>
                        <?php
                          $tid = (int)($t['id'] ?? 0);
                          $dn = (string)($t['displayname'] ?? '');
                          $un = (string)($t['username'] ?? '');
                          $label = trim($dn !== '' ? $dn : $un);
                          if ($dn !== '' && $un !== '') $label .= ' (' . $un . ')';
                        ?>
                        <option value="<?= $tid ?>" <?= $tid === $selectedTeacherId ? 'selected' : '' ?>><?= e($label) ?></option>
                      <?php endforeach; ?>
                    </select>

                    <button class="rounded-2xl bg-calm-600 px-4 py-2 text-sm font-medium text-white shadow-sm hover:bg-calm-500">💾 บันทึก</button>
                  </form>
                </td>
                <td class="px-4 py-3 text-xs text-ink-800/60">
                  <?php if (is_array($advisor) && $selectedTeacherId > 0): ?>
                    กำหนดแล้ว
                  <?php else: ?>
                    —
                  <?php endif; ?>
                </td>
              </tr>
            <?php endforeach; ?>

            <?php if (empty($classes)): ?>
              <tr>
                <td colspan="4" class="px-4 py-10 text-center text-sm text-ink-800/70">ยังไม่พบข้อมูลชั้นเรียนในปีการศึกษานี้</td>
              </tr>
            <?php endif; ?>
          </tbody>
        </table>
      </div>

      <?php if (empty($teachers)): ?>
        <div class="border-t border-black/5 bg-white px-4 py-3 text-xs text-ink-800/60">
          หมายเหตุ: ยังไม่มีผู้ใช้ที่ role = teacher • ไปที่หน้า “ผู้ใช้” เพื่อสร้างครูก่อน
        </div>
      <?php endif; ?>
    </div>
  </section>
</div>
