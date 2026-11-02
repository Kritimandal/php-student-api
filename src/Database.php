<?php

declare(strict_types=1);

namespace App;

use PDO;

final class Database
{
    public static function connect(Config $config): PDO
    {
        $dsn = sprintf('mysql:host=%s;port=%d;dbname=%s;charset=utf8mb4', $config->dbHost, $config->dbPort, $config->dbName);

        return new PDO($dsn, $config->dbUser, $config->dbPassword, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]);
    }

    public static function migrate(PDO $pdo, string $databaseDir): void
    {
        $schema = (string) file_get_contents($databaseDir . '/schema.sql');
        foreach (array_filter(array_map('trim', explode(';', $schema))) as $statement) {
            $pdo->exec($statement);
        }

        foreach (glob($databaseDir . '/routines/*.sql') ?: [] as $file) {
            $name = basename($file, '.sql');
            $kind = str_starts_with($name, 'trg_') ? 'TRIGGER' : 'PROCEDURE';
            $pdo->exec("DROP {$kind} IF EXISTS `{$name}`");
            $pdo->exec((string) file_get_contents($file));
        }
    }
}
