<?php
declare(strict_types=1);

require __DIR__ . '/app/bootstrap.php';

$route = $_GET['route'] ?? '';
$route = is_string($route) ? trim($route, "/") : '';

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
    default:
        http_response_code(404);
        echo render('errors/404', [
            'title' => 'ไม่พบหน้า',
            'path' => $route,
        ]);
}
