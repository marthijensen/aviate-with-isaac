<?php

require_once __DIR__ . '/supabase.php';

try {
    $result = supabaseRequest('students?select=id&limit=1');

    echo "SUCCESS: PHP connected to Supabase!";
    echo "<br>";
    echo "The request completed successfully.";
} catch (Throwable $error) {
    echo "CONNECTION FAILED: ";
    echo htmlspecialchars($error->getMessage(), ENT_QUOTES, 'UTF-8');
}