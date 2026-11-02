<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Http\HttpException;
use App\Http\Request;
use App\Http\Response;
use App\Http\Router;
use App\Students\StudentRepository;
use PHPUnit\Framework\TestCase;

final class RouterTest extends TestCase
{
    private Router $router;

    protected function setUp(): void
    {
        $this->router = new Router();
        $this->router->add('GET', '/students/{id}', fn (Request $r, array $p) => Response::json($p));
        $this->router->add('DELETE', '/students/{id}', fn () => Response::noContent());
    }

    public function test_route_parameters_are_integers(): void
    {
        $this->assertSame(['id' => 42], $this->router->dispatch(Request::create('GET', '/students/42'))->body);
        $this->assertSame(['id' => 42], $this->router->dispatch(Request::create('GET', '/students/42/'))->body);
    }

    public function test_non_numeric_ids_do_not_match(): void
    {
        foreach (['/students/abc', '/students/0', '/students/1;DROP', '/students/-1'] as $path) {
            try {
                $this->router->dispatch(Request::create('GET', $path));
                $this->fail("{$path} should not match");
            } catch (HttpException $error) {
                $this->assertSame(404, $error->status);
            }
        }
    }

    public function test_wrong_method_returns_405_with_allow_header(): void
    {
        try {
            $this->router->dispatch(Request::create('PUT', '/students/1'));
            $this->fail('Expected 405');
        } catch (HttpException $error) {
            $this->assertSame(405, $error->status);
            $this->assertSame('GET, DELETE', $error->headers['Allow']);
        }
    }

    public function test_invalid_json_is_rejected(): void
    {
        $this->expectExceptionObject(new HttpException(400, 'Request body is not valid JSON.'));
        Request::create('POST', '/students', '{"name": ')->json();
    }

    public function test_like_wildcards_are_escaped(): void
    {
        $this->assertSame('50!% off!_now!!', StudentRepository::escapeLike('50% off_now!'));
    }
}
