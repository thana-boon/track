<?php
declare(strict_types=1);

require_auth();

$me = auth_user();
$role = is_array($me) ? (string)($me['role'] ?? 'teacher') : '';
if ($role !== 'admin') {
    redirect('dashboard');
}

activity_logs_table_ensure();

$q = trim(query_string('q'));
$event = trim(query_string('event'));
$routeFilter = trim(query_string('route_name'));
$method = strtoupper(trim(query_string('method')));

$page = (int)(query_string('page') !== '' ? query_string('page') : '1');
$page = max(1, $page);
$pageSize = 50;

if (!in_array($method, ['', 'GET', 'POST'], true)) {
    $method = '';
}

$where = [];
$params = [];

if ($q !== '') {
    $where[] = '(username LIKE ? OR ip LIKE ? OR route LIKE ? OR event LIKE ? OR message LIKE ? OR context_json LIKE ?)';
    $like = '%' . $q . '%';
    $params[] = $like;
    $params[] = $like;
    $params[] = $like;
    $params[] = $like;
    $params[] = $like;
    $params[] = $like;
}

if ($event !== '') {
    $where[] = 'event = ?';
    $params[] = $event;
}

if ($routeFilter !== '') {
    $where[] = 'route = ?';
    $params[] = $routeFilter;
}

if ($method !== '') {
    $where[] = 'method = ?';
    $params[] = $method;
}

$sqlWhere = count($where) > 0 ? (' WHERE ' . implode(' AND ', $where)) : '';

$pdo = db_app();
$stmtCount = $pdo->prepare('SELECT COUNT(*) FROM activity_logs' . $sqlWhere);
$stmtCount->execute($params);
$total = (int)$stmtCount->fetchColumn();

$totalPages = (int)max(1, (int)ceil($total / $pageSize));
if ($page > $totalPages) {
    $page = $totalPages;
}
$offset = ($page - 1) * $pageSize;

$stmt = $pdo->prepare(
    'SELECT id, created_at, user_id, username, role, ip, method, route, event, message '
    . 'FROM activity_logs '
    . $sqlWhere . ' '
    . 'ORDER BY id DESC '
    . 'LIMIT ' . (int)$pageSize . ' OFFSET ' . (int)$offset
);
$stmt->execute($params);
$logs = $stmt->fetchAll();

$events = $pdo->query('SELECT event, COUNT(*) AS c FROM activity_logs GROUP BY event ORDER BY c DESC, event ASC LIMIT 50')->fetchAll();
$routes = $pdo->query('SELECT route, COUNT(*) AS c FROM activity_logs WHERE route <> "" GROUP BY route ORDER BY c DESC, route ASC LIMIT 50')->fetchAll();

$baseQuery = http_build_query(array_filter([
    'q' => $q !== '' ? $q : null,
    'event' => $event !== '' ? $event : null,
    'route_name' => $routeFilter !== '' ? $routeFilter : null,
    'method' => $method !== '' ? $method : null,
], static fn($v) => $v !== null && $v !== ''));

echo render('activity_logs/index', [
    'title' => 'Activity Log',
    'logs' => $logs,
    'filters' => [
        'q' => $q,
        'event' => $event,
        'route_name' => $routeFilter,
        'method' => $method,
    ],
    'options' => [
        'events' => $events,
        'routes' => $routes,
    ],
    'pagination' => [
        'page' => $page,
        'pageSize' => $pageSize,
        'total' => $total,
        'totalPages' => $totalPages,
        'baseQuery' => $baseQuery,
    ],
]);
