<?php

declare(strict_types=1);

/**
 * Minimal router: maps an HTTP method and path to a handler.
 */
final class Router
{
    /** @var array<string, array<string, callable>> path => method => handler */
    private array $routes = [];

    public function get(string $path, callable $handler): void
    {
        $this->routes[self::normalize($path)]['GET'] = $handler;
    }

    public function post(string $path, callable $handler): void
    {
        $this->routes[self::normalize($path)]['POST'] = $handler;
    }

    /**
     * Runs the matching handler. Sends 404 for unknown paths and 405 for
     * known paths requested with an unsupported method.
     */
    public function dispatch(string $method, string $uri): void
    {
        $path = self::normalize((string) parse_url($uri, PHP_URL_PATH));
        $method = strtoupper($method);
        $handlers = $this->routes[$path] ?? null;

        if ($handlers === null) {
            http_response_code(404);
            render('404', ['title' => 'Page Not Found']);
            return;
        }

        $handler = $handlers[$method] ?? ($method === 'HEAD' ? ($handlers['GET'] ?? null) : null);
        if ($handler === null) {
            http_response_code(405);
            header('Allow: ' . implode(', ', array_keys($handlers)));
            echo 'Method Not Allowed';
            return;
        }

        $handler();
    }

    private static function normalize(string $path): string
    {
        $path = '/' . trim($path, '/');
        return $path === '/' ? '/' : rtrim($path, '/');
    }
}
