<?php
$session = $session ?? null;
$roster = $roster ?? [];
$extraCols = (int)($extraCols ?? 6);
$extraCols = max(5, min(10, $extraCols));

$subj = is_array($session) ? (string)($session['subject_title'] ?? '') : '';
$date = is_array($session) ? (string)($session['session_date'] ?? '') : '';
$note = is_array($session) ? trim((string)($session['note'] ?? '')) : '';
$yearId = is_array($session) ? (int)($session['year_id'] ?? 0) : 0;

$line2 = thai_date_long($date);
if ($note !== '') {
  $line2 .= ' • ' . $note;
}

// Compute A4-friendly columns.
$blankHeaders = [];
for ($i = 1; $i <= $extraCols; $i++) {
  $blankHeaders[] = ' '; // blank header
}
?>
<!doctype html>
<html lang="th">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width,initial-scale=1" />
  <title><?= e((string)($title ?? 'ใบเช็คชื่อ')) ?></title>
  <link rel="icon" href="/tracks/favicon.ico" />
  <style>
    @page { size: A4; margin: 10mm; }
    body { font-family: "TH Sarabun New", "Sarabun", "Noto Sans Thai", sans-serif; color: #111; }
    .wrap { max-width: 210mm; margin: 0 auto; }
    .head { display: flex; align-items: flex-start; justify-content: space-between; gap: 12mm; }
    h1 { margin: 0; font-size: 20pt; }
    .meta { margin-top: 2mm; font-size: 14pt; color: #333; }
    .right { text-align: right; font-size: 12pt; color: #333; }
    .tbl { width: 100%; border-collapse: collapse; margin-top: 6mm; font-size: 12pt; }
    .tbl th, .tbl td { border: 1px solid #222; padding: 2.2mm 2.4mm; vertical-align: middle; }
    .tbl th { background: #f5f5f5; font-weight: 700; }
    .no { width: 10mm; text-align: center; }
    .code { width: 20mm; }
    .class { width: 18mm; text-align: center; }
    .room { width: 14mm; text-align: center; }
    .num { width: 14mm; text-align: center; }
    .name { width: 55mm; }
    .blank { width: 12mm; }

    .hint { margin-top: 4mm; font-size: 11pt; color: #333; }

    .no-print { margin-top: 6mm; font-size: 12pt; }

    @media print {
      .no-print { display: none; }
    }
  </style>
</head>
<body>
  <div class="wrap">
    <div class="head">
      <div>
        <h1>ใบเช็คชื่อ</h1>
        <div class="meta">วิชา: <strong><?= e($subj) ?></strong></div>
        <div class="meta">วันที่เรียน: <?= e($line2) ?></div>
      </div>
      <div class="right">
        <div>ปีการศึกษา: <?= e((string)$yearId) ?></div>
        <div>พิมพ์เมื่อ: <?= e(date('Y-m-d H:i')) ?></div>
      </div>
    </div>

    <table class="tbl">
      <thead>
        <tr>
          <th class="no">#</th>
          <th class="code">รหัส</th>
          <th class="name">ชื่อ-สกุล</th>
          <th class="class">ชั้น</th>
          <th class="room">ห้อง</th>
          <th class="num">เลขที่</th>
          <?php foreach ($blankHeaders as $_): ?>
            <th class="blank">&nbsp;</th>
          <?php endforeach; ?>
        </tr>
      </thead>
      <tbody>
        <?php $i = 0; foreach ($roster as $r): $i++; ?>
          <?php
            $code = (string)($r['student_code'] ?? '');
            $name = trim((string)($r['first_name'] ?? '') . ' ' . (string)($r['last_name'] ?? ''));
            $cl = (string)($r['class_level'] ?? '');
            $rm = (string)($r['class_room'] ?? '');
            $no = (string)($r['number_in_room'] ?? '');
          ?>
          <tr>
            <td class="no"><?= $i ?></td>
            <td class="code"><?= e($code) ?></td>
            <td class="name"><?= e($name !== '' ? $name : $code) ?></td>
            <td class="class"><?= e($cl) ?></td>
            <td class="room"><?= e($rm) ?></td>
            <td class="num"><?= e($no) ?></td>
            <?php for ($c = 1; $c <= $extraCols; $c++): ?>
              <td class="blank">&nbsp;</td>
            <?php endfor; ?>
          </tr>
        <?php endforeach; ?>

        <?php if (empty($roster)): ?>
          <tr>
            <td colspan="<?= 6 + $extraCols ?>" style="text-align:center; padding: 12mm;">ไม่มีรายชื่อนักเรียน</td>
          </tr>
        <?php endif; ?>
      </tbody>
    </table>

    <div class="hint">ช่องว่างด้านขวาไว้สำหรับเช็คชื่อ/หมายเหตุ (<?= (int)$extraCols ?> ช่อง)</div>

    <div class="no-print">
      <button onclick="window.print()">พิมพ์</button>
      <a href="/tracks/class_attendance_view?id=<?= (int)($session['id'] ?? 0) ?>">กลับไปหน้าเช็คชื่อ</a>
      <span style="color:#666; margin-left: 8px;">ปรับจำนวนช่อง: เปิดลิงก์ด้วยพารามิเตอร์ <code>?cols=5..10</code></span>
    </div>
  </div>
</body>
</html>
