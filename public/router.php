<?php
declare(strict_types=1);

$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH) ?: '/';
if ($path !== '/' && !str_starts_with($path, '/api/')) {
    $file = __DIR__ . $path;
    if (is_file($file)) return false;
}
if ($path === '/') {
    header('Location: /game.html');
    exit;
}
if (str_starts_with($path, '/api/')) {
    $_GET['route'] = substr($path, 4);
    require __DIR__ . '/index.php';
    return true;
}
http_response_code(404);
echo 'Not Found';
