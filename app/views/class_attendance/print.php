<?php
$session = $session ?? null;
$roster = $roster ?? [];
$extraCols = (int)($extraCols ?? 5);
$extraCols = max(3, min(8, $extraCols));

$subj = is_array($session) ? (string)($session['subject_title'] ?? '') : '';
$date = is_array($session) ? (string)($session['session_date'] ?? '') : '';
$note = is_array($session) ? trim((string)($session['note'] ?? '')) : '';
$yearId = is_array($session) ? (int)($session['year_id'] ?? 0) : 0;
$groupTitle = is_array($session) ? trim((string)($session['group_title'] ?? '')) : '';

$line2 = thai_date_long($date);
if ($note !== '') {
  $line2 .= ' • ' . $note;
}

// Thai print timestamp
$thaiMonthNames = [
  1=>'มกราคม',2=>'กุมภาพันธ์',3=>'มีนาคม',4=>'เมษายน',
  5=>'พฤษภาคม',6=>'มิถุนายน',7=>'กรกฎาคม',8=>'สิงหาคม',
  9=>'กันยายน',10=>'ตุลาคม',11=>'พฤศจิกายน',12=>'ธันวาคม',
];
$now = time();
$printDateThai = (int)date('j', $now) . ' ' . ($thaiMonthNames[(int)date('n', $now)] ?? '') . ' ' . ((int)date('Y', $now) + 543) . ' เวลา ' . date('H:i', $now) . ' น.';
?>
<!doctype html>
<html lang="th">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width,initial-scale=1" />
  <title><?= e((string)($title ?? 'ใบเช็คชื่อ')) ?></title>
  <link rel="icon" href="/tracks/favicon.ico" />
  <style>
    @page { size: A4 portrait; margin: 8mm; }
    * { box-sizing: border-box; }
    body {
      font-family: "TH Sarabun New", "Sarabun", "Noto Sans Thai", sans-serif;
      font-size: 11pt;
      line-height: 1.2;
      color: #111;
      margin: 0;
      padding: 0;
    }
    .wrap { width: 194mm; margin: 0 auto; }

    /* ── Header ── */
    .head { display: flex; align-items: flex-start; justify-content: space-between; gap: 6mm; margin-bottom: 3mm; }
    h1 { margin: 0 0 1mm; font-size: 15pt; line-height: 1.1; }
    .meta { font-size: 10pt; color: #222; margin: 0.5mm 0; }
    .right { text-align: right; font-size: 9pt; color: #444; white-space: nowrap; }

    /* ── Table ── */
    .tbl { width: 100%; border-collapse: collapse; table-layout: fixed; font-size: 9pt; }
    .tbl thead th {
      background: #f0f0f0;
      font-weight: 700;
      font-size: 9pt;
      border: 0.4pt solid #333;
      padding: 0.8mm 1mm;
      text-align: center;
      white-space: nowrap;
    }
    .tbl tbody td {
      border: 0.4pt solid #555;
      padding: 0.45mm 1mm;
      vertical-align: middle;
      height: 4mm;
    }
    .tbl tbody tr:nth-child(even) td { background: #fafafa; }

    /* ── Column widths (194mm total) ── */
    .col-no   { width: 7mm;  text-align: center; }
    .col-code { width: 17mm; }
    .col-name { width: 58mm; white-space: nowrap; overflow: hidden; text-overflow: clip; }
    .col-cl   { width: 11mm; text-align: center; }
    .col-rm   { width: 10mm; text-align: center; }
    .col-num  { width: 10mm; text-align: center; }
    /* remaining width shared between blank cols */
    .col-blank { text-align: center; }

    .hint { margin-top: 2mm; font-size: 8.5pt; color: #555; }

    .no-print { margin-top: 6mm; font-size: 12pt; display: flex; gap: 8px; align-items: center; flex-wrap: wrap; }
    @media print { .no-print { display: none !important; } }
  </style>
</head>
<body>
  <div class="wrap">
    <div class="head">
      <div>
        <h1>ใบเช็คชื่อ</h1>
        <div class="meta">วิชา: <strong><?= e($subj) ?></strong><?= $groupTitle !== '' ? ' &nbsp;|&nbsp; กลุ่ม: <strong>' . e($groupTitle) . '</strong>' : '' ?></div>
        <div class="meta">วันที่: <?= e($line2) ?></div>
      </div>
      <div class="right">
        <div>ปีการศึกษา: <?= e((string)$yearId) ?></div>
        <div>พิมพ์เมื่อ: <?= e($printDateThai) ?></div>
        <div>รายชื่อ: <?= count($roster) ?> คน</div>
      </div>
    </div>

    <?php
      // Compute blank col width dynamically
      $fixedMm = 7 + 17 + 58 + 11 + 10 + 10; // 113mm
      $blankWidth = round((194 - $fixedMm) / max(1, $extraCols), 1);
    ?>

    <table class="tbl">
      <colgroup>
        <col class="col-no" />
        <col class="col-code" />
        <col class="col-name" />
        <col class="col-cl" />
        <col class="col-rm" />
        <col class="col-num" />
        <?php for ($i = 0; $i < $extraCols; $i++): ?>
          <col style="width:<?= $blankWidth ?>mm;" />
        <?php endfor; ?>
      </colgroup>
      <thead>
        <tr>
          <th class="col-no">#</th>
          <th class="col-code">รหัส</th>
          <th class="col-name" style="text-align:left;">ชื่อ-สกุล</th>
          <th class="col-cl">ชั้น</th>
          <th class="col-rm">ห้อง</th>
          <th class="col-num">เลขที่</th>
          <?php for ($i = 1; $i <= $extraCols; $i++): ?>
            <th class="col-blank"><?= $i ?></th>
          <?php endfor; ?>
        </tr>
      </thead>
      <tbody>
        <?php $i = 0; foreach ($roster as $r): $i++; ?>
          <?php
            $code = (string)($r['student_code'] ?? '');
            $name = trim((string)($r['first_name'] ?? '') . ' ' . (string)($r['last_name'] ?? ''));
            $cl   = (string)($r['class_level'] ?? '');
            $rm   = (string)($r['class_room'] ?? '');
            $no   = (string)($r['number_in_room'] ?? '');
          ?>
          <tr>
            <td class="col-no"><?= $i ?></td>
            <td class="col-code"><?= e($code) ?></td>
            <td class="col-name"><?= e($name !== '' ? $name : $code) ?></td>
            <td class="col-cl"><?= e($cl) ?></td>
            <td class="col-rm"><?= e($rm) ?></td>
            <td class="col-num"><?= e($no) ?></td>
            <?php for ($c = 0; $c < $extraCols; $c++): ?>
              <td class="col-blank">&nbsp;</td>
            <?php endfor; ?>
          </tr>
        <?php endforeach; ?>

        <?php if (empty($roster)): ?>
          <tr>
            <td colspan="<?= 6 + $extraCols ?>" style="text-align:center; padding: 8mm;">ไม่มีรายชื่อนักเรียน</td>
          </tr>
        <?php endif; ?>
      </tbody>
    </table>

    <div class="hint">ช่องหมายเลขด้านขวา <?= (int)$extraCols ?> ช่อง ไว้สำหรับเช็คชื่อ/หมายเหตุ</div>

    <div class="no-print">
      <button onclick="window.print()" style="padding:6px 16px; border-radius:8px; background:#2b7f79; color:#fff; border:none; cursor:pointer; font-size:13px;">🖨️ พิมพ์</button>
      <a href="/tracks/class_attendance_view?id=<?= (int)($session['id'] ?? 0) ?>" style="font-size:13px;">← กลับหน้าเช็คชื่อ</a>
      <span style="color:#888; font-size:12px;">ปรับช่องเช็ค: เพิ่ม <code>?cols=3..8</code> ใน URL</span>
    </div>
  </div>
</body>
</html>
