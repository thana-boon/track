<?php
$csrf = (string)($csrf ?? csrf_token());
$error = $error ?? null;
$success = $success ?? null;
$backups = is_array($backups ?? null) ? $backups : [];
?>

<div class="mx-auto max-w-4xl">
  <div class="flex flex-col gap-2 sm:flex-row sm:items-end sm:justify-between">
    <div>
      <h1 class="text-2xl font-semibold tracking-tight">Backup / Restore</h1>
      <p class="mt-1 text-sm text-ink-800/70">สำหรับผู้ดูแลระบบ: ดาวน์โหลดไฟล์ backup และกู้คืนข้อมูลจากไฟล์ .sql (เฉพาะไฟล์ที่สร้างจากระบบ)</p>
    </div>
  </div>

  <?php if (is_string($error) && $error !== ''): ?>
    <div class="mt-4 rounded-2xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">
      <?= e($error) ?>
    </div>
  <?php endif; ?>

  <?php if (is_string($success) && $success !== ''): ?>
    <div class="mt-4 rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-900">
      <?= e($success) ?>
    </div>
  <?php endif; ?>

  <div class="mt-6 grid grid-cols-1 gap-4 lg:grid-cols-2">

    <section class="rounded-3xl border border-black/10 bg-white/70 p-5 shadow-sm">
      <h2 class="text-lg font-semibold">💾 Backup (ดาวน์โหลด)</h2>
      <p class="mt-1 text-sm text-ink-800/70">กดเพื่อสร้างไฟล์ .sql และบันทึกไว้ที่เซิร์ฟเวอร์ จากนั้นสามารถกดดาวน์โหลดได้</p>

      <form class="mt-4" method="post" action="/tracks/backup_restore" autocomplete="off">
        <input type="hidden" name="_csrf" value="<?= e($csrf) ?>" />
        <input type="hidden" name="action" value="create_backup" />
        <button type="submit" class="inline-flex items-center justify-center rounded-2xl bg-ink-900 px-4 py-2 text-sm font-semibold text-white hover:bg-black">
          💾 สร้างไฟล์ Backup บนเซิร์ฟเวอร์
        </button>
      </form>

      <div class="mt-4">
        <div class="text-sm font-semibold">ไฟล์ Backup ที่บันทึกไว้</div>
        <?php if (empty($backups)): ?>
          <div class="mt-2 text-sm text-ink-800/70">ยังไม่มีไฟล์ backup</div>
        <?php else: ?>
          <div class="mt-2 overflow-hidden rounded-2xl border border-black/10 bg-white">
            <table class="w-full text-sm">
              <thead class="bg-sand-100/70 text-ink-800/80">
                <tr>
                  <th class="px-3 py-2 text-left">ไฟล์</th>
                  <th class="px-3 py-2 text-left">เวลา</th>
                  <th class="px-3 py-2 text-right">ขนาด</th>
                  <th class="px-3 py-2"></th>
                </tr>
              </thead>
              <tbody class="divide-y divide-black/5">
                <?php foreach ($backups as $b): ?>
                  <?php
                    $file = (string)($b['file'] ?? '');
                    $mtime = (int)($b['mtime'] ?? 0);
                    $size = (int)($b['size'] ?? 0);
                    $downloadUrl = '/tracks/backup_restore?download=' . rawurlencode($file) . '&_csrf=' . rawurlencode($csrf);
                  ?>
                  <tr>
                    <td class="px-3 py-2 font-mono text-xs sm:text-sm break-all"><?= e($file) ?></td>
                    <td class="px-3 py-2 text-ink-800/70"><?= $mtime > 0 ? e(date('Y-m-d H:i', $mtime)) : '-' ?></td>
                    <td class="px-3 py-2 text-right text-ink-800/70"><?= e(number_format(max(0, (int)round($size / 1024)))) ?> KB</td>
                    <td class="px-3 py-2">
                      <div class="flex flex-wrap justify-end gap-2">
                        <a class="rounded-xl border border-black/10 bg-white px-3 py-1.5 text-xs hover:bg-black/5" href="<?= e($downloadUrl) ?>">⬇️ ดาวน์โหลด</a>

                        <form method="post" action="/tracks/backup_restore" data-confirm="ยืนยันการกู้คืนข้อมูลจากไฟล์นี้? ข้อมูลเดิมอาจถูกเขียนทับ">
                          <input type="hidden" name="_csrf" value="<?= e($csrf) ?>" />
                          <input type="hidden" name="action" value="restore_saved" />
                          <input type="hidden" name="file" value="<?= e($file) ?>" />
                          <button type="submit" class="rounded-xl bg-blush-600 px-3 py-1.5 text-xs font-semibold text-white hover:bg-black">♻️ Restore</button>
                        </form>
                      </div>
                    </td>
                  </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        <?php endif; ?>
      </div>
    </section>

    <section class="rounded-3xl border border-black/10 bg-white/70 p-5 shadow-sm">
      <h2 class="text-lg font-semibold">♻️ Restore (กู้คืน)</h2>
      <p class="mt-1 text-sm text-ink-800/70">อัปโหลดไฟล์ .sql ที่ดาวน์โหลดจากระบบนี้เพื่อกู้คืนข้อมูล (ฐาน sukhon_track เท่านั้น)</p>

      <form class="mt-4 space-y-3" method="post" action="/tracks/backup_restore" enctype="multipart/form-data" data-confirm="ยืนยันการกู้คืนข้อมูลจากไฟล์ที่อัปโหลด? ข้อมูลเดิมอาจถูกเขียนทับ">
        <input type="hidden" name="_csrf" value="<?= e($csrf) ?>" />
        <input type="hidden" name="action" value="restore_upload" />

        <label class="block text-sm">
          <span class="text-ink-900">ไฟล์ .sql</span>
          <input type="file" name="sql_file" accept=".sql,text/plain,application/sql" class="mt-1 block w-full rounded-2xl border border-black/10 bg-white px-3 py-2 text-sm" />
        </label>

        <button type="submit" class="inline-flex items-center justify-center rounded-2xl bg-blush-600 px-4 py-2 text-sm font-semibold text-white hover:bg-black">
          ♻️ กู้คืนข้อมูล
        </button>

        <div class="rounded-2xl border border-black/10 bg-sand-100/60 px-3 py-2 text-xs text-ink-800/70">
          หมายเหตุ: ระบบจะปฏิเสธไฟล์ที่ไม่มี header <span class="font-mono">SUKHON_TRACK_BACKUP v1</span> และบล็อคคำสั่งอันตราย (เช่น DROP DATABASE, GRANT)
        </div>
      </form>
    </section>

  </div>
</div>
