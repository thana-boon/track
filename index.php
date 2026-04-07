<?php
declare(strict_types=1);

require __DIR__ . '/app/bootstrap.php';

$route = $_GET['route'] ?? '';
$route = is_string($route) ? trim($route, "/") : '';

$me = auth_user();
$role = is_array($me) ? (string)($me['role'] ?? 'teacher') : '';

// Role guard (minimal)
if ($me && $role !== 'admin') {
    if ($role === 'student') {
        $allowed = [
            '',
            'login',
            'logout',
            'student_results',
            'student-results',
        ];

        if (!in_array($route, $allowed, true)) {
            $route = 'student_results';
        }
        if ($route === '' || $route === 'dashboard') {
            $route = 'student_results';
        }
    } else {
        // teacher (and other non-admin roles)
        $allowed = [
            '',
            'login',
            'logout',
            'class_room',
            'class-room',
            'class_room_student',
            'class-room-student',
            'class_attendance',
            'class-attendance',
            'class_attendance_view',
            'class-attendance-view',
            'class_attendance_print',
            'class-attendance-print',
        ];

        if (!in_array($route, $allowed, true)) {
            $route = 'class_attendance';
        }
        if ($route === '' || $route === 'dashboard') {
            $route = 'class_attendance';
        }
    }
}

// Activity log: record all mutating requests (POST)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = '';
    if (isset($_POST['action']) && is_string($_POST['action'])) {
        $action = trim((string)$_POST['action']);
    }
    activity_log_write('post_action', [
        'route' => $route,
        'action' => $action,
        'path' => (string)($_SERVER['REQUEST_URI'] ?? ''),
    ], $action !== '' ? ('action=' . $action) : '');
}

switch ($route) {
    case '':
    case 'dashboard':
        require __DIR__ . '/app/routes/dashboard.php';
        break;
    case 'login':
        require __DIR__ . '/app/routes/login.php';
        break;
    case 'logout':
        require __DIR__ . '/app/routes/logout.php';
        break;
    case 'users':
        require __DIR__ . '/app/routes/users.php';
        break;
    case 'students':
        require __DIR__ . '/app/routes/students.php';
        break;
    case 'track-subjects':
        require __DIR__ . '/app/routes/track_subjects.php';
        break;
    case 'register_track':
    case 'register-track':
        require __DIR__ . '/app/routes/register_track.php';
        break;
    case 'student_track':
        require __DIR__ . '/app/routes/student_track.php';
        break;
    case 'report_statement':
        require __DIR__ . '/app/routes/report_statement.php';
        break;
    case 'academic_year':
    case 'academic-year':
        require __DIR__ . '/app/routes/academic_year.php';
        break;
    case 'class_advisors':
    case 'class-advisors':
        require __DIR__ . '/app/routes/class_advisors.php';
        break;
    case 'class_room':
    case 'class-room':
        require __DIR__ . '/app/routes/class_room.php';
        break;
    case 'class_room_student':
    case 'class-room-student':
        require __DIR__ . '/app/routes/class_room_student.php';
        break;
    case 'class_attendance':
    case 'class-attendance':
        require __DIR__ . '/app/routes/class_attendance.php';
        break;
    case 'class_attendance_create':
    case 'class-attendance-create':
        require __DIR__ . '/app/routes/class_attendance_create.php';
        break;
    case 'class_attendance_view':
    case 'class-attendance-view':
        require __DIR__ . '/app/routes/class_attendance_view.php';
        break;
    case 'class_attendance_edit':
    case 'class-attendance-edit':
        require __DIR__ . '/app/routes/class_attendance_edit.php';
        break;
    case 'class_attendance_delete':
    case 'class-attendance-delete':
        require __DIR__ . '/app/routes/class_attendance_delete.php';
        break;
    case 'class_attendance_print':
    case 'class-attendance-print':
        require __DIR__ . '/app/routes/class_attendance_print.php';
        break;
    case 'activity_logs':
    case 'activity-logs':
        require __DIR__ . '/app/routes/activity_logs.php';
        break;
    case 'student_results':
    case 'student-results':
        require __DIR__ . '/app/routes/student_results.php';
        break;
    case 'backup_restore':
    case 'backup-restore':
        require __DIR__ . '/app/routes/backup_restore.php';
        break;
    default:
        http_response_code(404);
        echo render('errors/404', [
            'title' => 'ไม่พบหน้า',
            'path' => $route,
        ]);
}
