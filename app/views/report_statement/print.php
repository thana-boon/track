<?php
// Plain print view (no layout) for A4 printing.
?>
<!doctype html>
<html lang="th">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width,initial-scale=1" />
  <title><?= e((string)($title ?? 'ใบ Statement')) ?></title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Sarabun:wght@400;600;700&display=swap" rel="stylesheet">
  <style>
    /* Prefer TH Sarabun if installed locally, fallback to Sarabun */
    :root { --font: "TH Sarabun New", "TH SarabunPSK", "Sarabun", "Noto Sans Thai", sans-serif; }

    html, body { height: 100%; }
    body {
      margin: 0;
      font-family: var(--font);
      color: #111;
      background: #f4f4f5;
    }

    .toolbar {
      position: sticky;
      top: 0;
      z-index: 5;
      display: flex;
      gap: 8px;
      align-items: center;
      justify-content: center;
      padding: 10px;
      background: rgba(255,255,255,0.95);
      border-bottom: 1px solid rgba(0,0,0,0.08);
      backdrop-filter: blur(8px);
    }

    .btn {
      font-family: var(--font);
      font-size: 16px;
      padding: 10px 14px;
      border-radius: 12px;
      border: 1px solid rgba(0,0,0,0.12);
      background: #fff;
      cursor: pointer;
    }

    .btn.primary { background: #111827; color: #fff; border-color: #111827; }

    .wrap { padding: 18px 12px 40px; }

    /* A4 */
    .a4 {
      width: 210mm;
      min-height: 297mm;
      background: white;
      box-shadow: 0 10px 30px rgba(0,0,0,0.10);
      margin: 0 auto 18px;
    }

    .statement {
      box-sizing: border-box;
      padding: 12mm;
      font-size: 18pt;
      line-height: 1.25;
    }

    .header { display: grid; grid-template-columns: 26mm 1fr 58mm; gap: 10mm; align-items: center; }
    .logo img { width: 26mm; height: 26mm; object-fit: contain; }
    .logo-placeholder { width: 26mm; height: 26mm; display: grid; place-items: center; border: 1px dashed #999; font-size: 12pt; color: #666; }

    .school-name { font-size: 24pt; font-weight: 700; text-align: center; }
    .school-sub { font-size: 16pt; text-align: center; color: #444; margin-top: 1mm; }

    .meta { font-size: 14pt; color: #222; text-align: right; }

    .title { margin-top: 6mm; text-align: center; font-size: 22pt; font-weight: 700; }

    .info { margin-top: 6mm; border: 1px solid #222; padding: 4mm; }
    .info .row { display: grid; grid-template-columns: 1fr 1fr; gap: 6mm; }
    .k { font-weight: 700; }

    .section { margin-top: 6mm; }
    .section-title { font-size: 18pt; font-weight: 700; margin-bottom: 2mm; }

    .tbl { width: 100%; border-collapse: collapse; font-size: 14pt; }
    .tbl th, .tbl td { border: 1px solid #222; padding: 2.2mm 2.4mm; vertical-align: top; }
    .tbl th { background: #f5f5f5; font-weight: 700; }
    .tbl .no { width: 14mm; text-align: center; }
    .tbl .date { width: 30mm; text-align: center; }
    .subj-title { font-weight: 700; }
    .subj-desc { margin-top: 1mm; font-size: 12pt; color: #444; }

    .footer { margin-top: 10mm; display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 10mm; }
    .sig { text-align: center; font-size: 14pt; }
    .sig .signbox { position: relative; height: 22mm; }
    .sig .line { position: absolute; left: 0; right: 0; bottom: 0; height: 0; border-top: 1px solid #222; }
    .sig .name { margin-top: 1.5mm; }
    .sig .pos { margin-top: 0.5mm; }

    .note { margin-top: 6mm; font-size: 12pt; color: #333; }
    .empty { padding: 14mm; text-align: center; font-size: 16pt; color: #333; border: 1px dashed #999; margin-top: 10mm; }

    .sign { position: absolute; left: 50%; transform: translateX(-50%); bottom: 2mm; height: 18mm; width: auto; object-fit: contain; }
    .page-break { page-break-after: always; break-after: page; }

    @page { size: A4; margin: 0; }
    @media print {
      body { background: #fff; }
      .toolbar { display: none !important; }
      .wrap { padding: 0; }
      .a4 { box-shadow: none; margin: 0; }
    }
  </style>
</head>
<body>
  <div class="toolbar">
    <button class="btn primary" onclick="window.print()">พิมพ์ / Print</button>
    <button class="btn" onclick="window.close()">ปิดหน้าต่าง</button>
  </div>

  <div class="wrap">
    <?php
      $printStudents = $printStudents ?? null;
      if (is_array($printStudents) && count($printStudents) > 0) {
        foreach ($printStudents as $idx => $item) {
          $student = $item['student'] ?? null;
          $studentRegs = $item['regs'] ?? [];
          require __DIR__ . '/_statement.php';
          if ($idx < count($printStudents) - 1) {
            echo '<div class="page-break"></div>';
          }
        }
      } else {
        require __DIR__ . '/_statement.php';
      }
    ?>
  </div>
</body>
</html>
