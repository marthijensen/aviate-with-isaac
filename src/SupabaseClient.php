<?php

declare(strict_types=1);

/**
 * Small wrapper around the Supabase REST API (PostgREST).
 *
 * Runs server-side only. The secret key must never be sent to the browser.
 */
final class SupabaseClient
{
    public function __construct(
        private readonly string $url,
        private readonly string $apiKey,
    ) {
        if ($url === '' || $apiKey === '') {
            throw new RuntimeException('Supabase is not configured. Set SUPABASE_URL and SUPABASE_SECRET_KEY in .env.');
        }
    }

    /**
     * @param array<string, string> $query PostgREST query parameters
     * @return list<array<string, mixed>>
     */
    public function select(string $table, array $query): array
    {
        $endpoint = rtrim($this->url, '/') . '/rest/v1/' . rawurlencode($table) . '?' . http_build_query($query, '', '&', PHP_QUERY_RFC3986);

        $curl = curl_init($endpoint);
        curl_setopt_array($curl, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 10,
            CURLOPT_HTTPHEADER => [
                'apikey: ' . $this->apiKey,
                'Accept: application/json',
            ],
        ]);

        $body = curl_exec($curl);
        $status = curl_getinfo($curl, CURLINFO_RESPONSE_CODE);
        $error = curl_error($curl);
        curl_close($curl);

        if ($body === false) {
            throw new RuntimeException('Could not reach Supabase: ' . $error);
        }
        if ($status >= 400) {
            throw new RuntimeException("Supabase request failed ({$status}): {$body}");
        }

        $rows = json_decode($body, true);
        if (!is_array($rows)) {
            throw new RuntimeException('Unexpected response from Supabase.');
        }

        return $rows;
    }
}
