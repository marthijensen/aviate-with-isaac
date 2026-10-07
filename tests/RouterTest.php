<?php

declare(strict_types=1);

// Run with: php tests/RouterTest.php

require_once dirname(__DIR__) . '/src/bootstrap.php';

$failures = 0;
$results = [];

// Results are printed at the end: in the CLI, any output marks headers as
// sent, which would stop the router from setting status codes.
function check(bool $condition, string $message): void
{
    global $failures, $results;
    $results[] = ($condition ? 'PASS ' : 'FAIL ') . $message;
    if (!$condition) {
        $failures++;
    }
}

/** Dispatches a request and returns [status, output]. */
function dispatch(Router $router, string $method, string $uri): array
{
    http_response_code(200);
    $_SERVER['REQUEST_URI'] = $uri;
    ob_start();
    $router->dispatch($method, $uri);
    return [http_response_code(), (string) ob_get_clean()];
}

$router = new Router();
$router->get('/dashboard', static function (): void { echo 'dashboard'; });
$router->post('/lessons', static function (): void { echo 'created'; });

[$status, $body] = dispatch($router, 'GET', '/dashboard');
check($status === 200 && $body === 'dashboard', 'GET /dashboard runs its handler');

[, $body] = dispatch($router, 'GET', '/dashboard/');
check($body === 'dashboard', 'trailing slash matches the same route');

[, $body] = dispatch($router, 'GET', '/dashboard?tab=today');
check($body === 'dashboard', 'query string is ignored when matching');

[$status, $body] = dispatch($router, 'HEAD', '/dashboard');
check($status === 200 && $body === 'dashboard', 'HEAD falls back to the GET handler');

[$status, $body] = dispatch($router, 'POST', '/lessons');
check($status === 200 && $body === 'created', 'POST route runs its handler');

[$status, $body] = dispatch($router, 'POST', '/dashboard');
check($status === 405, 'wrong method returns 405');

[$status, $body] = dispatch($router, 'GET', '/does-not-exist');
check($status === 404, 'unknown path returns 404');
check(str_contains($body, 'Page not found') && str_contains($body, '<title>Page Not Found | Aviate with Isaac</title>'), '404 page is rendered in the layout');

// The real app routes are registered.
$app = app_router();
[$status] = dispatch($app, 'GET', '/');
check($status === 302, 'GET / redirects');

echo implode(PHP_EOL, $results) . PHP_EOL;
echo PHP_EOL . ($failures === 0 ? 'All tests passed.' : "{$failures} test(s) failed.") . PHP_EOL;
exit($failures === 0 ? 0 : 1);
