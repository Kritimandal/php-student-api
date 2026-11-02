<?php

declare(strict_types=1);

namespace Tests\Integration;

use App\App;
use App\Config;
use App\Database;
use App\Http\Request;
use App\Http\Response;
use PDO;
use PHPUnit\Framework\TestCase;

abstract class ApiTestCase extends TestCase
{
    protected static ?PDO $pdo = null;

    protected App $app;

    public static function setUpBeforeClass(): void
    {
        if (self::$pdo === null) {
            self::$pdo = Database::connect(Config::fromEnvironment());
            Database::migrate(self::$pdo, dirname(__DIR__, 2) . '/database');
        }
    }

    protected function setUp(): void
    {
        self::$pdo->exec('DELETE FROM students');
        self::$pdo->exec('DELETE FROM student_audit');
        self::$pdo->exec('ALTER TABLE students AUTO_INCREMENT = 1');
        $this->app = new App(self::$pdo, new Config(corsOrigin: 'https://example.test'));
    }

    protected function send(string $method, string $uri, mixed $body = null): Response
    {
        return $this->app->handle(Request::create($method, $uri, $body));
    }

    protected function createStudent(string $name = 'Student One', int $age = 20, string $city = 'Kathmandu'): array
    {
        return $this->send('POST', '/students', compact('name', 'age', 'city'))->body['data'];
    }
}
