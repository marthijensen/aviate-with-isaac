<?php

declare(strict_types=1);

final class WriteDatabase
{
    public static function connect(): PDO
    {
        $host = Env::get('WRITE_DB_HOST');
        $port = Env::get('WRITE_DB_PORT', '5432');
        $database = Env::get('WRITE_DB_NAME', 'postgres');
        $username = Env::get('WRITE_DB_USER');
        $password = Env::get('WRITE_DB_PASSWORD');

        if ($host === '' || $username === '' || $password === '') {
            throw new RuntimeException(
                'Write database connection is not configured.'
            );
        }

        $dsn = sprintf(
            'pgsql:host=%s;port=%s;dbname=%s;sslmode=require;connect_timeout=10',
            $host,
            $port,
            $database
        );

        return new PDO($dsn, $username, $password, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]);
    }
}
