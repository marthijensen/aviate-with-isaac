<?php

function loadEnv($path)
{
    if (!file_exists($path)) {
        return;
    }

    $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

    foreach ($lines as $line) {
        $line = trim($line);

        if ($line === '' || str_starts_with($line, '#')) {
            continue;
        }

        if (!str_contains($line, '=')) {
            continue;
        }

        [$name, $value] = explode('=', $line, 2);

        $name = trim($name);
        $value = trim($value);
        $value = trim($value, "\"'");

        putenv($name . '=' . $value);
    }
}

loadEnv(__DIR__ . '/.env');


function supabaseRequest($endpoint)
{
    $supabaseUrl = getenv('SUPABASE_URL');
    $supabaseKey = getenv('SUPABASE_SECRET_KEY');

    if (!$supabaseUrl || !$supabaseKey) {
        throw new Exception(
            'Supabase environment variables are missing.'
        );
    }

    $url = rtrim($supabaseUrl, '/')
        . '/rest/v1/'
        . $endpoint;

    $curl = curl_init();

    curl_setopt_array($curl, [
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => [
            'apikey: ' . $supabaseKey,
            'Authorization: Bearer ' . $supabaseKey,
            'Content-Type: application/json'
        ]
    ]);

    $response = curl_exec($curl);

    if ($response === false) {
        $error = curl_error($curl);

        throw new Exception(
            'Supabase request failed: ' . $error
        );
    }

    $statusCode = curl_getinfo(
        $curl,
        CURLINFO_HTTP_CODE
    );

    if ($statusCode >= 400) {
        throw new Exception(
            'Supabase returned HTTP '
            . $statusCode
            . ': '
            . $response
        );
    }

    $decoded = json_decode($response, true);

    if (!is_array($decoded)) {
        return [];
    }

    return $decoded;
}