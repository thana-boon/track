<?php
$settings    = is_array($settings ?? null) ? $settings : [];
$yearData    = is_array($yearData ?? null) ? $yearData : [];
$term        = (int)($term ?? 1);
$ymStart     = (string)($ymStart ?? date('Y-m'));
$ymEnd       = (string)($ymEnd ?? $ymStart);
$roomSpecs   = is_array($roomSpecs ?? null) ? $roomSpecs : [];
$columns     = is_array($columns ?? null) ? $columns : [];
$allStudents = is_array($allStudents ?? null) ? $allStudents : [];
$resultMap   = is_array($resultMap ?? null) ? $resultMap : [];
$isMultiRoom = (bool)($isMultiRoom ?? false);
$returnHref  = (string)($returnHref ?? '/tracks/class_room_print');

$thaiMonthsShort = [1=>'ม.ค.',2=>'ก.พ.',3=>'มี.ค.',4=>'เม.ย.',5=>'พ.ค.',6=>'มิ.ย.',7=>'ก.ค.',8=>'ส.ค.',9=>'ก.ย.',10=>'ต.ค.',11=>'พ.ย.',12=>'ธ.ค.'];
$thaiMonthsFull  = [1=>'มกราคม',2=>'กุมภาพันธ์',3=>'มีนาคม',4=>'เมษายน',5=>'พฤษภาคม',6=>'มิถุนายน',7=>'กรกฎาคม',8=>'สิงหาคม',9=>'กันยายน',10=>'ตุลาคม',11=>'พฤศจิกายน',12=>'ธันวาคม'];

// Period label
function prDateLabel(string $ym, array $monthsFull): string {
    $p = explode('-', $ym);
    $y = (int)($p[0] ?? date('Y'));
    $m = (int)($p[1] ?? date('n'));
    return ($monthsFull[$m] ?? '') . ' ' . ($y + 543);
}
$startLabel = prDateLabel($ymStart, $thaiMonthsFull);
$endLabel   = prDateLabel($ymEnd,   $thaiMonthsFull);
$periodLabel = ($ymStart === $ymEnd) ? $startLabel : ($startLabel . ' – ' . $endLabel);

// Rooms label
$roomsLabel = implode(', ', array_map(static fn($rs) => $rs['level'] . '/' . $rs['room'], $roomSpecs));

// Year label
$yearLabel = trim((string)($yearData['title'] ?? '') . ' (' . (string)($yearData['year_be'] ?? '') . ')');

// Logo / school
$schoolName = trim((string)($settings['school_name'] ?? ''));
$logoPath   = trim((string)($settings['logo_path'] ?? ''));
$assetQs    = trim((string)($settings['updated_at'] ?? '')) !== '' ? ('?v=' . rawurlencode((string)$settings['updated_at'])) : '';
$logoUrl    = $logoPath !== '' ? ('/tracks/' . ltrim($logoPath, '/') . $assetQs) : '';

// Print timestamp
$now       = time();
$printDate = (int)date('j', $now) . ' ' . ($thaiMonthsFull[(int)date('n', $now)] ?? '') . ' ' . ((int)date('Y', $now) + 543) . ' เวลา ' . date('H:i', $now) . ' น.';

// Fixed columns count (before date/subj columns)
$fixedCols = $isMultiRoom ? 4 : 3; // #, name, [room], code
$totalCols = $fixedCols + count($columns);
?>
<!doctype html>
<html lang="th">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width,initial-scale=1" />
  <title><?= e((string)($title ?? 'รายงานการเข้าเรียน')) ?></title>
  <link rel="icon" href="/tracks/favicon.ico" />
  <style>
    @page { size: A4 portrait; margin: 10mm 8mm; }
    * { box-sizing: border-box; margin: 0; padding: 0; -webkit-print-color-adjust: exact; print-color-adjust: exact; color-adjust: exact; }
    body {
      font-family: "TH Sarabun New", "Sarabun", "Noto Sans Thai", sans-serif;
      font-size: 10pt; color: #111; background: #fff;
    }
    .wrap { width: 194mm; margin: 0 auto; }

    /* Header */
    .head { display: flex; align-items: flex-start; gap: 3mm; padding-bottom: 3mm; border-bottom: 0.8pt solid #333; margin-bottom: 3mm; }
    .logo { width: 15mm; height: 15mm; object-fit: contain; flex-shrink: 0; }
    .logo-ph { width: 15mm; height: 15mm; border: 0.5pt solid #ccc; border-radius: 50%; flex-shrink: 0; }
    .head-center { flex: 1; text-align: center; }
    .school-name  { font-size: 13pt; font-weight: 700; line-height: 1.2; }
    .report-title { font-size: 11.5pt; font-weight: 700; margin-top: 0.5mm; }
    .report-sub   { font-size: 9pt; color: #333; margin-top: 0.5mm; }
    .head-right   { text-align: right; font-size: 7.5pt; color: #555; flex-shrink: 0; white-space: nowrap; }
    .head-right div { line-height: 1.6; }

    /* Table */
    .tbl { width: 100%; border-collapse: collapse; font-size: 8pt; }
    .tbl th {
      background: #e6e6e6;
      border: 0.4pt solid #888;
      text-align: center;
      font-weight: 700;
      vertical-align: bottom;
      padding: 1mm;
      line-height: 1.1;
    }
    .tbl td {
      border: 0.4pt solid #bbb;
      padding: 0.8mm 1mm;
      vertical-align: middle;
      text-align: center;
      line-height: 1.2;
    }
    .tbl tbody tr:nth-child(even) td      { background: #f8f8f8; }
    .tbl tbody tr:nth-child(even) td.ns   { background: #c8c8c8; }

    /* Fixed columns */
    .col-no   { width: 7mm; }
    .col-name { width: 52mm; text-align: left !important; }
    .col-room { width: 12mm; }
    .col-code { width: 16mm; font-size: 7.5pt; }

    /* Column header: date on top, subject below */
    .col-hdr {
      padding: 1.5mm 1mm !important;
      vertical-align: middle !important;
      width: 18mm;
      max-width: 18mm;
      line-height: 1.3;
    }
    .col-hdr-date { font-size: 7.5pt; font-weight: 700; white-space: nowrap; }
    .col-hdr-subj { font-size: 6pt; font-weight: 500; color: #222; word-break: break-all; white-space: normal; margin-top: 0.5mm; }

    /* No-session */
    .ns { background: #d0d0d0 !important; }

    /* Result badges */
    .res { display: inline-block; border-radius: 8pt; padding: 0 3pt; line-height: 1.5; font-size: 7.5pt; }
    .r-excellent { background: #fef3c7; color: #92400e; }
    .r-pass      { background: #d1fae5; color: #065f46; }
    .r-fail      { background: #fee2e2; color: #991b1b; }
    .r-pending   { color: #aaa; font-size: 7pt; }

    /* Room separator row */
    .room-sep td {
      background: #2b7f79 !important;
      color: #fff;
      font-weight: 700;
      font-size: 8.5pt;
      padding: 1mm 2mm;
      text-align: left;
    }

    /* Legend */
    .legend { margin-top: 2mm; font-size: 7.5pt; color: #555; }
    .ns-box { display: inline-block; width: 8pt; height: 8pt; background: #d0d0d0; vertical-align: middle; border: 0.3pt solid #999; margin-right: 1pt; }

    /* Signature */
    .sign-area { margin-top: 8mm; display: flex; justify-content: flex-end; }
    .sign-box  { text-align: center; width: 65mm; }
    .sign-line { border-top: 0.5pt solid #333; padding-top: 1mm; font-size: 9pt; margin-top: 14mm; }

    /* Screen only */
    .no-print { margin-top: 8mm; padding-top: 4mm; border-top: 1pt dashed #ccc; display: flex; gap: 8px; align-items: center; flex-wrap: wrap; }
    @media print { .no-print { display: none !important; } }
  </style>
</head>
<body>
<div class="wrap">

  <!-- Header -->
  <div class="head">
    <?php if ($logoUrl !== ''): ?>
      <img src="<?= e($logoUrl) ?>" class="logo" alt="" />
    <?php else: ?>
      <div class="logo-ph"></div>
    <?php endif; ?>

    <div class="head-center">
      <?php if ($schoolName !== ''): ?><div class="school-name"><?= e($schoolName) ?></div><?php endif; ?>
      <div class="report-title">รายงานการเข้าเรียน ประจำเดือน<?= $ymStart !== $ymEnd ? 'ช่วง' : '' ?> <?= e($periodLabel) ?></div>
      <div class="report-sub">
        ชั้น <?= e($roomsLabel) ?> &nbsp;•&nbsp; ปีการศึกษา <?= e($yearLabel) ?> เทอม <?= $term ?>
      </div>
    </div>

    <div class="head-right">
      <div>นักเรียน: <?= count($allStudents) ?> คน</div>
      <div>พิมพ์: <?= e($printDate) ?></div>
    </div>
  </div>

  <?php if (empty($columns)): ?>
    <p style="text-align:center; padding:15mm; color:#666;">ไม่พบข้อมูลรอบเรียนในช่วงเวลาที่เลือก</p>
  <?php else: ?>

  <!-- Table -->
  <table class="tbl">
    <thead>
      <tr>
        <th class="col-no">#</th>
        <th class="col-name" style="text-align:left;">ชื่อ-สกุล</th>
        <?php if ($isMultiRoom): ?>
          <th class="col-room">ห้อง</th>
        <?php endif; ?>
        <th class="col-code">รหัส</th>

        <?php foreach ($columns as $col): ?>
          <?php
            $d         = (string)($col['session_date'] ?? '');
            $ts        = strtotime($d);
            $day       = $ts !== false ? (int)date('j', $ts) : 0;
            $mo        = $ts !== false ? (int)date('n', $ts) : 0;
            $yr        = $ts !== false ? (int)date('Y', $ts) + 543 : 0;
            $dlbl      = $day . ' ' . ($thaiMonthsShort[$mo] ?? '') . ' ' . $yr;
            $subj      = (string)($col['subject_title'] ?? '');
            $subjShort = mb_strlen($subj) > 14 ? mb_substr($subj, 0, 13) . '…' : $subj;
          ?>
          <th class="col-hdr">
            <div class="col-hdr-date"><?= e($dlbl) ?></div>
            <div class="col-hdr-subj" title="<?= e($subj) ?>"><?= e($subjShort) ?></div>
          </th>
        <?php endforeach; ?>
      </tr>
    </thead>

    <tbody>
      <?php
        $prevRoomKey = null;
        $roomRowNum  = 0;
        $globalNum   = 0;
        foreach ($allStudents as $st):
          $code     = (string)($st['student_code'] ?? '');
          $name     = trim((string)($st['first_name'] ?? '') . ' ' . (string)($st['last_name'] ?? ''));
          $no       = (string)($st['number_in_room'] ?? '');
          $roomKey  = (string)($st['room_key'] ?? '');
          $globalNum++;

          if ($isMultiRoom && $roomKey !== $prevRoomKey):
            $prevRoomKey = $roomKey;
            $roomRowNum  = 0;
      ?>
        <tr class="room-sep">
          <td colspan="<?= $totalCols ?>">ห้อง <?= e($roomKey) ?></td>
        </tr>
      <?php endif; $roomRowNum++; ?>

      <tr>
        <td class="col-no"><?= e($no !== '' ? $no : (string)$roomRowNum) ?></td>
        <td class="col-name"><?= e($name !== '' ? $name : $code) ?></td>
        <?php if ($isMultiRoom): ?><td class="col-room"><?= e($roomKey) ?></td><?php endif; ?>
        <td class="col-code"><?= e($code) ?></td>

        <?php foreach ($columns as $col): ?>
          <?php
            $colKey = (string)($col['session_date'] ?? '') . '_' . (int)($col['subject_id'] ?? 0);
            $result = $resultMap[$code][$colKey] ?? null;
          ?>
          <?php if ($result === null): ?>
            <td class="ns"></td>
          <?php else: ?>
            <td>
              <?php if ($result === 'excellent'): ?>
                <span class="res r-excellent">ยอดเยี่ยม</span>
              <?php elseif ($result === 'pass'): ?>
                <span class="res r-pass">ผ่าน</span>
              <?php elseif ($result === 'fail'): ?>
                <span class="res r-fail">ไม่ผ่าน</span>
              <?php else: ?>
                <span class="res r-pending">รอประเมิน</span>
              <?php endif; ?>
            </td>
          <?php endif; ?>
        <?php endforeach; ?>
      </tr>

      <?php endforeach; ?>

      <?php if (empty($allStudents)): ?>
        <tr><td colspan="<?= $totalCols ?>" style="text-align:center; padding:8mm; color:#666;">ไม่พบนักเรียน</td></tr>
      <?php endif; ?>
    </tbody>
  </table>

  <div class="legend">
    <span class="ns-box"></span> ไม่มีคาบเรียน &nbsp;|&nbsp;
    <span class="res r-excellent">ยอดเยี่ยม</span>
    <span class="res r-pass">ผ่าน</span>
    <span class="res r-fail">ไม่ผ่าน</span>
    <span class="res r-pending">รอประเมิน</span>
  </div>

  <div class="sign-area">
    <div class="sign-box">
      <div class="sign-line">ลงชื่อ ................................................ ครูประจำชั้น</div>
    </div>
  </div>

  <?php endif; ?>

  <div class="no-print">
    <button onclick="window.print()" style="padding:6px 18px; border-radius:8px; background:#2b7f79; color:#fff; border:none; cursor:pointer; font-size:13px; font-family:inherit;">🖨️ พิมพ์ / บันทึก PDF</button>
    <a href="<?= e($returnHref) ?>" style="font-size:13px; color:#444; text-decoration:none;">← เลือกห้องใหม่</a>
    <span style="font-size:11px; color:#999;">แนะนำ Chrome → พิมพ์ → บันทึกเป็น PDF</span>
  </div>
</div>
<script>window.addEventListener('load', function(){ setTimeout(function(){ window.print(); }, 400); });</script>
</body>
</html>
