<?php
$settings = $settings ?? ['school_name' => '', 'logo_path' => ''];
$student = $student ?? null;
$studentRegs = $studentRegs ?? [];
$yearId = (int)($yearId ?? 0);
$yearText = (string)($yearText ?? ((string)$yearId));

$schoolName = trim((string)($settings['school_name'] ?? ''));
$logoPath = trim((string)($settings['logo_path'] ?? ''));
$logoUrl = $logoPath !== '' ? ('/tracks/' . ltrim($logoPath, '/')) : '';

$advisorName = trim((string)($settings['advisor_name'] ?? ''));
$registrarName = trim((string)($settings['registrar_name'] ?? ''));
$directorName = trim((string)($settings['director_name'] ?? ''));

$advisorSignPath = trim((string)($settings['advisor_sign_path'] ?? ''));
$registrarSignPath = trim((string)($settings['registrar_sign_path'] ?? ''));
$directorSignPath = trim((string)($settings['director_sign_path'] ?? ''));

$advisorSignUrl = $advisorSignPath !== '' ? ('/tracks/' . ltrim($advisorSignPath, '/')) : '';
$registrarSignUrl = $registrarSignPath !== '' ? ('/tracks/' . ltrim($registrarSignPath, '/')) : '';
$directorSignUrl = $directorSignPath !== '' ? ('/tracks/' . ltrim($directorSignPath, '/')) : '';

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
      <div class="school-sub">ใบรับรองผลการเรียนรู้ (Statement)</div>
    </div>
    <div class="meta">
      <div>เลขที่เอกสาร: __________</div>
      <div>วันที่ออกเอกสาร: <?= e($issuedDate) ?></div>
    </div>
  </div>

  <div class="title">ใบแสดงรายการวิชาที่ผ่านการเรียน (Track Statement)</div>

  <?php if (!$student): ?>
    <div class="empty">กรุณาเลือกนักเรียนเพื่อแสดงใบ Statement</div>
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
      <div class="row">
        <div><span class="k">ปีการศึกษา:</span> <span class="v"><?= e($yearText) ?></span></div>
        <div></div>
      </div>
    </div>

    <div class="section">
      <div class="section-title">รายการวิชาที่ลงทะเบียน</div>

      <?php if (empty($studentRegs)): ?>
        <div class="empty">ไม่พบรายการลงทะเบียน</div>
      <?php else: ?>
        <table class="tbl">
          <thead>
            <tr>
              <th class="no">ลำดับ</th>
              <th>วิชา</th>
              <th class="date">ผลการเรียน</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($studentRegs as $i => $r): ?>
              <tr>
                <td class="no"><?= (int)($i + 1) ?></td>
                <td>
                  <div class="subj-title"><?= e((string)$r['subject_title']) ?></div>
                  <?php if (!empty($r['subject_description'])): ?>
                    <div class="subj-desc"><?= e((string)$r['subject_description']) ?></div>
                  <?php endif; ?>
                </td>
                <td class="date">ผ่าน</td>
              </tr>
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
      เอกสารฉบับนี้จัดทำเพื่อใช้เป็นหลักฐานแสดงรายการวิชาที่นักเรียนได้ลงทะเบียนไว้ในระบบ (Track)
    </div>
  <?php endif; ?>
</div>
