<?php
$settings = $settings ?? ['school_name' => '', 'logo_path' => ''];
$student = $student ?? null;
$transcriptRows = $transcriptRows ?? [];
$yearMap = is_array($yearMap ?? null) ? $yearMap : [];
$yearId = (int)($yearId ?? 0);
$yearText = (string)($yearText ?? ((string)$yearId));

$assetVer = trim((string)($settings['updated_at'] ?? ''));
$assetQs = $assetVer !== '' ? ('?v=' . rawurlencode($assetVer)) : '';

$schoolName = trim((string)($settings['school_name'] ?? ''));
$logoPath = trim((string)($settings['logo_path'] ?? ''));
$logoUrl = $logoPath !== '' ? ('/tracks/' . ltrim($logoPath, '/') . $assetQs) : '';

$advisorName = trim((string)(($advisorName ?? null) !== null ? $advisorName : ($settings['advisor_name'] ?? '')));
$registrarName = trim((string)($settings['registrar_name'] ?? ''));
$directorName = trim((string)($settings['director_name'] ?? ''));

$advisorSignPath = trim((string)($settings['advisor_sign_path'] ?? ''));
$registrarSignPath = trim((string)($settings['registrar_sign_path'] ?? ''));
$directorSignPath = trim((string)($settings['director_sign_path'] ?? ''));

$advisorSignUrl = $advisorSignPath !== '' ? ('/tracks/' . ltrim($advisorSignPath, '/') . $assetQs) : '';
$registrarSignUrl = $registrarSignPath !== '' ? ('/tracks/' . ltrim($registrarSignPath, '/') . $assetQs) : '';
$directorSignUrl = $directorSignPath !== '' ? ('/tracks/' . ltrim($directorSignPath, '/') . $assetQs) : '';

$studentName = $student ? ((string)$student['first_name'] . ' ' . (string)$student['last_name']) : '';
$studentCode = $student ? (string)$student['student_code'] : '';
$studentClass = $student ? ((string)$student['class_level'] . '/' . (string)$student['class_room']) : '';
$studentNo = $student ? (string)$student['number_in_room'] : '';

if (!function_exists('thai_date_long')) {
  function thai_date_long(string $ymd): string {
    $ymd = trim($ymd);
    if ($ymd === '') {
      $ymd = date('Y-m-d');
    }
    if (!preg_match('/^(\d{4})-(\d{2})-(\d{2})$/', $ymd, $m)) {
      // Fallback: return as-is
      return $ymd;
    }
    $y = (int)$m[1];
    $mo = (int)$m[2];
    $d = (int)$m[3];

    $thaiMonths = [
      1 => 'มกราคม',
      2 => 'กุมภาพันธ์',
      3 => 'มีนาคม',
      4 => 'เมษายน',
      5 => 'พฤษภาคม',
      6 => 'มิถุนายน',
      7 => 'กรกฎาคม',
      8 => 'สิงหาคม',
      9 => 'กันยายน',
      10 => 'ตุลาคม',
      11 => 'พฤศจิกายน',
      12 => 'ธันวาคม',
    ];

    $monthName = $thaiMonths[$mo] ?? (string)$mo;
    $beYear = $y + 543;
    return $d . ' ' . $monthName . ' ' . $beYear;
  }
}

$issuedDateRaw = trim((string)($settings['issued_date'] ?? ''));
$issuedDate = thai_date_long($issuedDateRaw);
?>

<div class="statement a4">
  <div class="header">
    <div class="logo">
      <?php if ($logoUrl !== ''): ?>
        <img src="<?= e($logoUrl) ?>" alt="โลโก้โรงเรียน" />
      <?php else: ?>
        <div class="logo-placeholder">LOGO</div>
      <?php endif; ?>
    </div>
    <div class="school">
      <div class="school-name"><?= e($schoolName !== '' ? $schoolName : 'ชื่อโรงเรียน') ?></div>
      <div class="school-sub">ใบรับรองผลการเรียนรู้ (Transcript)</div>
    </div>
    <div class="meta">
      <div>เลขที่เอกสาร: __________</div>
      <div>วันที่ออกเอกสาร: <?= e($issuedDate) ?></div>
    </div>
  </div>

  <div class="title">ใบแสดงรายการวิชาที่ผ่านการเรียน (Track Transcript)</div>

  <?php if (!$student): ?>
    <div class="empty">กรุณาเลือกนักเรียนเพื่อแสดงใบ Transcript</div>
  <?php else: ?>
    <div class="info">
      <div class="row">
        <div><span class="k">ชื่อนักเรียน:</span> <span class="v"><?= e($studentName) ?></span></div>
        <div><span class="k">รหัสนักเรียน:</span> <span class="v"><?= e($studentCode) ?></span></div>
      </div>
      <div class="row">
        <div><span class="k">ชั้น/ห้อง:</span> <span class="v"><?= e($studentClass) ?></span></div>
        <div><span class="k">เลขที่:</span> <span class="v"><?= e($studentNo) ?></span></div>
      </div>
    </div>

    <div class="section">
      <div class="section-title">รายการวิชา (ผ่าน/รอผล)</div>

      <?php if (empty($transcriptRows)): ?>
        <div class="empty">ไม่พบข้อมูลวิชา</div>
      <?php else: ?>
        <?php
          // Group: year_id+term -> group_title -> rows
          $byYearTerm = [];
          foreach ($transcriptRows as $r) {
            $yid = (int)($r['year_id'] ?? 0);
            $t = (int)($r['term'] ?? 1);
            $t = ($t === 2) ? 2 : 1;
            $k = $yid . '-' . $t;

            $gTitle = trim((string)($r['group_title'] ?? ''));
            if ($gTitle === '') {
              $gTitle = '-';
            }

            if (!isset($byYearTerm[$k])) {
              $byYearTerm[$k] = ['year_id' => $yid, 'term' => $t, 'groups' => []];
            }
            if (!isset($byYearTerm[$k]['groups'][$gTitle])) {
              $byYearTerm[$k]['groups'][$gTitle] = [];
            }
            $byYearTerm[$k]['groups'][$gTitle][] = $r;
          }

          // Sort keys by year then term
          uksort($byYearTerm, static function ($a, $b) {
            [$ya, $ta] = array_map('intval', explode('-', (string)$a));
            [$yb, $tb] = array_map('intval', explode('-', (string)$b));
            if ($ya === $yb) return $ta <=> $tb;
            return $ya <=> $yb;
          });
        ?>
        <table class="tbl">
          <thead>
            <tr>
              <th class="no">ลำดับ</th>
              <th>วิชา</th>
              <th class="date">ผลการเรียน</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($byYearTerm as $block): ?>
              <?php
                $yid = (int)($block['year_id'] ?? 0);
                $t = (int)($block['term'] ?? 1);
                $yRow = $yearMap[$yid] ?? null;
                $yearBe = '';
                if (is_array($yRow)) {
                  $yearBe = trim((string)($yRow['year_be'] ?? ''));
                  if ($yearBe === '') {
                    $yearBe = trim((string)($yRow['title'] ?? ''));
                  }
                }
                $yearLabel = $yearBe !== '' ? ($t . '/' . $yearBe) : ($t . '/' . (string)$yid);
              ?>
              <tr class="year-row">
                <td colspan="3">ปีการศึกษา <?= e($yearLabel) ?></td>
              </tr>

              <?php foreach (($block['groups'] ?? []) as $gTitle => $rows): ?>
                <tr class="group-row">
                  <td colspan="3"><?= e((string)$gTitle) ?></td>
                </tr>

                <?php $n = 1; ?>
                <?php foreach ($rows as $r): ?>
                  <?php
                    $code = trim((string)($r['subject_code'] ?? ''));
                    $title = trim((string)($r['subject_title'] ?? ''));
                    $desc = trim((string)($r['subject_description'] ?? ''));
                    $line = $code !== '' ? ($code . ' ' . $title) : $title;
                  ?>
                  <?php
                    $status = (string)($r['status'] ?? 'pass');
                    $statusLabel = $status === 'pass' ? 'ผ่าน' : 'รอผล';
                  ?>
                  <tr>
                    <td class="no"><?= (int)$n ?></td>
                    <td>
                      <div class="subj-title"><?= e($line !== '' ? $line : '-') ?></div>
                      <?php if ($desc !== ''): ?>
                        <div class="subj-desc"><?= e($desc) ?></div>
                      <?php endif; ?>
                    </td>
                    <td class="date"><?= e($statusLabel) ?></td>
                  </tr>
                  <?php $n++; ?>
                <?php endforeach; ?>
              <?php endforeach; ?>
            <?php endforeach; ?>
          </tbody>
        </table>
      <?php endif; ?>
    </div>

    <div class="footer">
      <div class="sig">
        <div class="signbox">
          <?php if ($advisorSignUrl !== ''): ?>
            <img class="sign" src="<?= e($advisorSignUrl) ?>" alt="ลายเซ็น" />
          <?php endif; ?>
          <div class="line"></div>
        </div>
        <div class="name"><?= $advisorName !== '' ? '(' . e($advisorName) . ')' : '(.................................)' ?></div>
        <div class="pos">ผู้จัดทำ/ครูที่ปรึกษา</div>
      </div>
      <div class="sig">
        <div class="signbox">
          <?php if ($registrarSignUrl !== ''): ?>
            <img class="sign" src="<?= e($registrarSignUrl) ?>" alt="ลายเซ็น" />
          <?php endif; ?>
          <div class="line"></div>
        </div>
        <div class="name"><?= $registrarName !== '' ? '(' . e($registrarName) . ')' : '(.................................)' ?></div>
        <div class="pos">งานทะเบียน/วัดผล</div>
      </div>
      <div class="sig">
        <div class="signbox">
          <?php if ($directorSignUrl !== ''): ?>
            <img class="sign" src="<?= e($directorSignUrl) ?>" alt="ลายเซ็น" />
          <?php endif; ?>
          <div class="line"></div>
        </div>
        <div class="name"><?= $directorName !== '' ? '(' . e($directorName) . ')' : '(.................................)' ?></div>
        <div class="pos">ผู้อำนวยการ</div>
      </div>
    </div>

    <div class="note">
    </div>
  <?php endif; ?>
</div>
