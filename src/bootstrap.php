<?php

declare(strict_types=1);

require_once __DIR__ . '/Env.php';
require_once __DIR__ . '/ReadOnlyDatabase.php';
require_once __DIR__ . '/WriteDatabase.php';
require_once __DIR__ . '/SupabaseClient.php';
require_once __DIR__ . '/UpcomingLessons.php';
require_once __DIR__ . '/Appointments.php';
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

/**
 * Database writes are only allowed during explicitly enabled local testing,
 * from the local machine. Sends a 403 and returns false otherwise.
 */
function require_local_writes(string $message): bool
{
    $remoteAddress = $_SERVER['REMOTE_ADDR'] ?? '';

    if (
        Env::get('AVIATE_LOCAL_WRITES', '0') !== '1'
        || !in_array($remoteAddress, ['127.0.0.1', '::1'], true)
    ) {
        http_response_code(403);
        echo $message;
        return false;
    }

    return true;
}

/** Returns the session's CSRF token for a form, creating it if needed. */
function csrf_token(string $form): string
{
    if (session_status() !== PHP_SESSION_ACTIVE) {
        session_start();
    }

    if (empty($_SESSION[$form])) {
        $_SESSION[$form] = bin2hex(random_bytes(32));
    }

    return $_SESSION[$form];
}

/**
 * Checks the posted csrf_token against the session's token for a form.
 * Sends a 403 and returns false if it doesn't match.
 */
function require_csrf(string $form): bool
{
    if (session_status() !== PHP_SESSION_ACTIVE) {
        session_start();
    }

    $submitted = $_POST['csrf_token'] ?? '';
    $expected = $_SESSION[$form] ?? '';

    if (!is_string($submitted) || !is_string($expected) || $expected === '' || !hash_equals($expected, $submitted)) {
        http_response_code(403);
        echo 'Invalid form token. Refresh the page and try again.';
        return false;
    }

    return true;
}

/** Reads a positive integer ID from a request array, or null. */
function positive_id(array $source, string $key): ?int
{
    $value = $source[$key] ?? null;

    return is_string($value) && ctype_digit($value) && (int) $value > 0 ? (int) $value : null;
}

function supabase(): SupabaseClient
{
    return new SupabaseClient(Env::get('SUPABASE_URL'), Env::get('SUPABASE_SECRET_KEY'));
}
