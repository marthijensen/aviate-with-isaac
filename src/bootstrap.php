<?php

declare(strict_types=1);

require_once __DIR__ . '/Env.php';
require_once __DIR__ . '/SupabaseClient.php';
require_once __DIR__ . '/UpcomingLessons.php';

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

function supabase(): SupabaseClient
{
    return new SupabaseClient(Env::get('SUPABASE_URL'), Env::get('SUPABASE_SECRET_KEY'));
}
