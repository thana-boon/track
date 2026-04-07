<?php
declare(strict_types=1);

require_auth();
track_class_tables_ensure();
track_subjects_table_ensure();

$me = auth_user();
$role = is_array($me) ? (string)($me['role'] ?? 'teacher') : 'teacher';
if ($role !== 'admin') {
    redirect('class_attendance');
}

$sessionId = (int)query_string('id');
$returnDate = (string)query_string('return_date');
$returnYear = (int)query_string('return_year');

$session = track_class_session_get($sessionId);
if (!$session) {
    http_response_code(404);
    echo render('errors/404', [
        'title' => 'ไม่พบรอบเรียน',
        'path' => 'class_attendance_edit',
    ]);
    exit;
}

$yearId = (int)($session['year_id'] ?? 0);
$subjects = track_subjects_all();

$roster = track_class_roster($sessionId);
$existingCodes = [];
foreach ($roster as $r) {
    $c = trim((string)($r['student_code'] ?? ''));
    if ($c !== '') $existingCodes[] = $c;
}
$existingCodes = array_values(array_unique($existingCodes));

$error = null;
$success = flash_get('success');

$formSubjectId = (int)($session['subject_id'] ?? 0);
$formSessionDate = (string)($session['session_date'] ?? '');
$formNote = (string)($session['note'] ?? '');
$formStudentCodesText = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();

    $action = input_string('action');

    try {
        if ($action === 'update_session') {
            $subjectIdPost = (int)input_string('subject_id');
            $sessionDatePost = input_string('session_date');
            $notePost = input_string('note');

            $formSubjectId = $subjectIdPost;
            $formSessionDate = $sessionDatePost;
            $formNote = $notePost;

            $codes = $_POST['student_codes'] ?? [];
            if (!is_array($codes)) {
                $codes = [];
            }

            $codesText = input_string('student_codes_text');
            $formStudentCodesText = $codesText;
            if ($codesText !== '') {
                $lines = preg_split('/\R/u', $codesText) ?: [];
                foreach ($lines as $line) {
                    $line = trim((string)$line);
                    if ($line === '') continue;

                    if (preg_match_all('/\d{3,}/u', $line, $m) && !empty($m[0])) {
                        $codes[] = (string)end($m[0]);
                    }
                }
            }

            // If admin didn't touch roster inputs, keep existing roster.
            $codesNorm = [];
            foreach ($codes as $c) {
                $c = trim((string)$c);
                if ($c === '') continue;
                if (!isset($codesNorm[$c])) $codesNorm[$c] = true;
            }
            $codes = array_keys($codesNorm);
            if (count($codes) === 0) {
                $codes = $existingCodes;
            }

            track_class_session_update($sessionId, $subjectIdPost, $sessionDatePost, $notePost, $codes);
            flash_set('success', 'แก้ไขรอบเรียนแล้ว ✅');

            $backQs = http_build_query(array_filter([
                'year_id' => $returnYear > 0 ? $returnYear : $yearId,
                'session_date' => $returnDate !== '' ? $returnDate : $sessionDatePost,
            ], static fn($v) => $v !== '' && $v !== 0));

            header('Location: /tracks/class_attendance' . ($backQs !== '' ? ('?' . $backQs) : ''));
            exit;
        }

        throw new RuntimeException('คำสั่งไม่ถูกต้อง');
    } catch (Throwable $e) {
        $error = $e instanceof RuntimeException ? $e->getMessage() : 'เกิดข้อผิดพลาด';
    }
}

$csrf = csrf_token();

echo render('class_attendance/edit', [
    'title' => 'แก้ไขรอบเรียน',
    'csrf' => $csrf,
    'session' => $session,
    'subjects' => $subjects,
    'subjectId' => $formSubjectId,
    'sessionDate' => $formSessionDate,
    'note' => $formNote,
    'roster' => $roster,
    'existingCodes' => $existingCodes,
    'studentCodesText' => $formStudentCodesText,
    'returnDate' => $returnDate,
    'returnYear' => $returnYear,
    'error' => $error,
    'success' => $success,
]);
