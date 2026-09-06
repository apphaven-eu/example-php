<?php

declare(strict_types=1);

/**
 * Turn a postgres:// URL into a PDO connection.
 *
 * AppHaven injects DATABASE_URL from ${service.db.url} as
 * postgres://user:pass@host:port/dbname. PDO wants a DSN instead, so the URL is
 * split with parse_url and reassembled. The generated password is percent
 * encoded in the URL, hence the rawurldecode.
 */
function db_connect(string $url): PDO
{
    $parts = parse_url($url);
    if ($parts === false || !isset($parts['host'])) {
        throw new RuntimeException('DATABASE_URL is not a valid URL');
    }

    $dsn = sprintf(
        'pgsql:host=%s;port=%d;dbname=%s',
        $parts['host'],
        $parts['port'] ?? 5432,
        ltrim($parts['path'] ?? '', '/')
    );

    $user = isset($parts['user']) ? rawurldecode($parts['user']) : '';
    $pass = isset($parts['pass']) ? rawurldecode($parts['pass']) : '';

    return new PDO($dsn, $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);
}

function db_from_env(): PDO
{
    $url = getenv('DATABASE_URL');
    if ($url === false || $url === '') {
        throw new RuntimeException('DATABASE_URL is not set');
    }

    return db_connect($url);
}

function db_migrate(PDO $pdo): void
{
    $pdo->exec(file_get_contents(__DIR__ . '/../schema.sql'));
}
