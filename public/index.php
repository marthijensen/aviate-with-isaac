<?php

declare(strict_types=1);

// Front controller: every request that isn't a real file goes through here.

// Let PHP's built-in server serve static files (CSS, images) directly.
if (PHP_SAPI === 'cli-server') {
    $file = __DIR__ . parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
    if (is_file($file)) {
        return false;
    }
}

// Bootstrap loads dependencies and application environment
require_once dirname(__DIR__) . '/src/bootstrap.php';

$uri = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);

app_router()->dispatch($_SERVER['REQUEST_METHOD'] ?? 'GET', $uri);