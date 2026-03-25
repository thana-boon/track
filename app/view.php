<?php
declare(strict_types=1);

function render(string $view, array $data = []): string
{
    $viewFile = __DIR__ . '/views/' . $view . '.php';
    if (!is_file($viewFile)) {
        return 'View not found: ' . e($view);
    }

    $title = $data['title'] ?? APP_NAME;
    $content = '';

    ob_start();
    extract($data, EXTR_SKIP);
    require $viewFile;
    $content = ob_get_clean();

    ob_start();
    require __DIR__ . '/views/layout.php';
    return (string)ob_get_clean();
}

/** Render a view file without the main layout (useful for print pages / fragments). */
function render_plain(string $view, array $data = []): string
{
    $viewFile = __DIR__ . '/views/' . $view . '.php';
    if (!is_file($viewFile)) {
        return 'View not found: ' . e($view);
    }

    ob_start();
    extract($data, EXTR_SKIP);
    require $viewFile;
    return (string)ob_get_clean();
}
