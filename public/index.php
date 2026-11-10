<?php
declare(strict_types=1);

require dirname(__DIR__) . '/app/autoload.php';

use App\Core\Response;

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Content-Type');
header('Access-Control-Allow-Methods: POST, OPTIONS');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

$path = $_GET['route'] ?? '';
if ($path === '') {
    $uriPath = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH) ?: '/';
    $path = preg_replace('#^.*/api#', '', $uriPath) ?: '';
}
$path = '/' . trim($path, '/');

$router = require dirname(__DIR__) . '/routes/api.php';
$router->dispatch($_SERVER['REQUEST_METHOD'], $path);
