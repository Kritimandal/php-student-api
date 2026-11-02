<?php

declare(strict_types=1);

namespace App;

use App\Http\HttpException;
use App\Http\Request;
use App\Http\Response;
use App\Http\Router;
use App\Students\StudentController;
use App\Students\StudentRepository;
use App\Students\StudentValidator;
use PDO;
use Throwable;

final class App
{
    private Router $router;

    public function __construct(PDO $pdo, private readonly Config $config)
    {
        $students = new StudentController(new StudentRepository($pdo), new StudentValidator());

        $this->router = new Router();
        $this->router->add('GET', '/health', fn () => Response::json(['status' => 'ok']));
        $this->router->add('GET', '/students', [$students, 'index']);
        $this->router->add('POST', '/students', [$students, 'store']);
        $this->router->add('GET', '/students/{id}', [$students, 'show']);
        $this->router->add('PATCH', '/students/{id}', [$students, 'update']);
        $this->router->add('DELETE', '/students/{id}', [$students, 'destroy']);
        $this->router->add('GET', '/students/{id}/history', [$students, 'history']);
        $this->router->add('GET', '/reports/cities', [$students, 'citySummary']);
    }

    public function handle(Request $request): Response
    {
        if ($request->method === 'OPTIONS') {
            return Response::noContent()->withHeaders($this->corsHeaders() + ['Access-Control-Max-Age' => '600']);
        }

        try {
            $response = $this->router->dispatch($request);
        } catch (HttpException $error) {
            $body = ['message' => $error->getMessage()];
            if ($error->errors) {
                $body['errors'] = $error->errors;
            }
            $response = Response::json($body, $error->status)->withHeaders($error->headers);
        } catch (Throwable $error) {
            error_log((string) $error);
            $response = Response::json(['message' => 'Something went wrong. Please try again later.'], 500);
        }

        return $response->withHeaders($this->corsHeaders() + ['X-Content-Type-Options' => 'nosniff']);
    }

    private function corsHeaders(): array
    {
        return [
            'Access-Control-Allow-Origin' => $this->config->corsOrigin,
            'Access-Control-Allow-Methods' => 'GET, POST, PATCH, DELETE, OPTIONS',
            'Access-Control-Allow-Headers' => 'Content-Type',
        ];
    }
}
