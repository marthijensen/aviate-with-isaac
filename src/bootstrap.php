<?php

declare(strict_types=1);

require_once __DIR__ . '/Env.php';
require_once __DIR__ . '/SupabaseClient.php';
require_once __DIR__ . '/UpcomingLessons.php';
require_once __DIR__ . '/Router.php';
require_once __DIR__ . '/routes.php';

Env::load(dirname(__DIR__) . '/.env');

function e(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function app_timezone(): DateTimeZone
{
    try {
        return new DateTimeZone(Env::get('APP_TIMEZONE', 'UTC'));
    } catch (Exception) {
        return new DateTimeZone('UTC');
    }
}

/**
 * Renders views/{view}.php inside the shared layout.
 *
 * @param array<string, mixed> $data Variables made available to the view
 */
function render(string $view, array $data = []): void
{
    $viewsDir = dirname(__DIR__) . '/views';

    $content = (static function (string $__file, array $__data): string {
        extract($__data);
        ob_start();
        require $__file;
        return (string) ob_get_clean();
    })("{$viewsDir}/{$view}.php", $data);

    (static function (string $__file, array $__data): void {
        extract($__data);
        require $__file;
    })("{$viewsDir}/layout.php", [
        'title' => (string) ($data['title'] ?? 'Aviate with Isaac'),
        'content' => $content,
        'currentPath' => rtrim((string) parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH), '/') ?: '/',
    ]);
}

function redirect(string $path, int $status = 302): void
{
    header('Location: ' . $path, true, $status);
}

function supabase(): SupabaseClient
{
    return new SupabaseClient(Env::get('SUPABASE_URL'), Env::get('SUPABASE_SECRET_KEY'));
}
