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


function supabaseRequest($endpoint, $method = 'GET', $body = null)
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

    $options = [
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_CUSTOMREQUEST => $method,
        CURLOPT_HTTPHEADER => [
            'apikey: ' . $supabaseKey,
            'Authorization: Bearer ' . $supabaseKey,
            'Content-Type: application/json',
            // Ask Supabase to send back the saved record.
            'Prefer: return=representation'
        ]
    ];

    // Send data along with POST/PATCH requests.
    if ($body !== null) {
        $options[CURLOPT_POSTFIELDS] = json_encode($body);
    }

    curl_setopt_array($curl, $options);

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

feature/student-list-navigation


main
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

function supabaseWrite($endpoint, array $payload)
{
    $supabaseUrl = getenv('SUPABASE_URL');
    $supabaseKey = getenv('SUPABASE_SECRET_KEY');

    if (!$supabaseUrl || !$supabaseKey) {
        throw new Exception('Supabase environment variables are missing.');
    }

    $url = rtrim($supabaseUrl, '/') . '/rest/v1/' . $endpoint;
    $curl = curl_init();

    curl_setopt_array($curl, [
        CURLOPT_URL => $url,
        CURLOPT_POST => true,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POSTFIELDS => json_encode($payload),
        CURLOPT_HTTPHEADER => [
            'apikey: ' . $supabaseKey,
            'Authorization: Bearer ' . $supabaseKey,
            'Content-Type: application/json',
            'Prefer: return=representation',
        ],
    ]);

    $response = curl_exec($curl);

    if ($response === false) {
        throw new Exception('Supabase request failed: ' . curl_error($curl));
    }

    $statusCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);

    if ($statusCode >= 400) {
        throw new Exception('Supabase returned HTTP ' . $statusCode . ': ' . $response);
    }

    $decoded = json_decode($response, true);

    return is_array($decoded) ? $decoded : [];
}