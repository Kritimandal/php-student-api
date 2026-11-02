<?php

declare(strict_types=1);

namespace App;

final class Config
{
    public function __construct(
        public readonly string $dbHost = '127.0.0.1',
        public readonly int $dbPort = 3306,
        public readonly string $dbName = 'student_api',
        public readonly string $dbUser = 'root',
        public readonly string $dbPassword = '',
        public readonly string $corsOrigin = '*',
    ) {
    }

    public static function fromEnvironment(): self
    {
        $env = static fn (string $key, string $default): string => (string) (getenv($key) !== false ? getenv($key) : $default);

        return new self(
            dbHost: $env('DB_HOST', '127.0.0.1'),
            dbPort: (int) $env('DB_PORT', '3306'),
            dbName: $env('DB_NAME', 'student_api'),
            dbUser: $env('DB_USER', 'root'),
            dbPassword: $env('DB_PASSWORD', ''),
            corsOrigin: $env('CORS_ORIGIN', '*'),
        );
    }
}
