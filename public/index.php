<?php

declare(strict_types=1);

use App\App;
use App\Config;
use App\Database;
use App\Http\Request;
use App\Http\Response;

require __DIR__ . '/../vendor/autoload.php';

$config = Config::fromEnvironment();

try {
    $app = new App(Database::connect($config), $config);
    $response = $app->handle(Request::fromGlobals());
} catch (PDOException $error) {
    error_log((string) $error);
    $response = Response::json(['message' => 'The service is not available right now.'], 503);
}

$response->send();
