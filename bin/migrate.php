<?php

declare(strict_types=1);

use App\Config;
use App\Database;

require __DIR__ . '/../vendor/autoload.php';

$pdo = Database::connect(Config::fromEnvironment());
Database::migrate($pdo, __DIR__ . '/../database');

if (in_array('--seed', $argv, true)) {
    $pdo->exec((string) file_get_contents(__DIR__ . '/../database/seed.sql'));
}

echo "Database is ready.\n";
