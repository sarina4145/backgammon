<?php
declare(strict_types=1);

namespace App\Core;

final class Router
{
    private array $routes = [];

    public function post(string $path, callable $handler): void
    {
        $this->routes['POST'][$path] = $handler;
    }

    public function dispatch(string $method, string $path): void
    {
        $handler = $this->routes[$method][$path] ?? null;
        if (!$handler) Response::json(['success' => false, 'message' => 'مسیر پیدا نشد.'], 404);
        $handler();
    }
}
