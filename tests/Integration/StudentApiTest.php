<?php

declare(strict_types=1);

namespace Tests\Integration;

final class StudentApiTest extends ApiTestCase
{
    public function test_create_and_read_a_student(): void
    {
        $response = $this->send('POST', '/students', ['name' => 'Student One', 'age' => 20, 'city' => 'Kathmandu']);

        $this->assertSame(201, $response->status);
        $this->assertSame('/students/1', $response->headers['Location']);
        $this->assertSame(['id' => 1, 'name' => 'Student One', 'age' => 20, 'city' => 'Kathmandu'], array_slice($response->body['data'], 0, 4));

        $this->assertSame('Student One', $this->send('GET', '/students/1')->body['data']['name']);
    }

    public function test_invalid_student_returns_422_with_field_errors(): void
    {
        $response = $this->send('POST', '/students', ['name' => 'A', 'age' => 'twenty']);

        $this->assertSame(422, $response->status);
        $this->assertSame(['name', 'age', 'city'], array_keys($response->body['errors']));
    }

    public function test_sql_injection_is_stored_as_plain_text(): void
    {
        $name = "Robert'); DROP TABLE students; --";
        $this->createStudent($name);

        $this->assertSame($name, $this->send('GET', '/students/1')->body['data']['name']);
        $this->assertSame(1, $this->send('GET', '/students')->body['meta']['total']);
    }

    public function test_bulk_insert_creates_all_rows(): void
    {
        $response = $this->send('POST', '/students', [
            ['name' => 'Student One', 'age' => 20, 'city' => 'Kathmandu'],
            ['name' => 'Student Two', 'age' => 22, 'city' => 'Lalitpur'],
        ]);

        $this->assertSame(201, $response->status);
        $this->assertSame(2, $response->body['meta']['created']);
    }

    public function test_bulk_insert_with_one_bad_row_creates_nothing(): void
    {
        $response = $this->send('POST', '/students', [
            ['name' => 'Student One', 'age' => 20, 'city' => 'Kathmandu'],
            ['name' => 'Student Two', 'age' => 500, 'city' => 'Lalitpur'],
        ]);

        $this->assertSame(422, $response->status);
        $this->assertArrayHasKey('1.age', $response->body['errors']);
        $this->assertSame(0, $this->send('GET', '/students')->body['meta']['total']);
    }

    public function test_search_filters_and_pagination_use_stored_procedures(): void
    {
        $this->createStudent('Student One', 20, 'Kathmandu');
        $this->createStudent('Student Two', 22, 'Lalitpur');
        $this->createStudent('Student Three', 19, 'Kathmandu');

        $byCity = $this->send('GET', '/students?city=Kathmandu')->body;
        $this->assertSame(2, $byCity['meta']['total']);

        $search = $this->send('GET', '/students?search=lalit')->body;
        $this->assertSame(['Student Two'], array_column($search['data'], 'name'));

        $page = $this->send('GET', '/students?per_page=2&page=2')->body;
        $this->assertSame(['Student Three'], array_column($page['data'], 'name'));
        $this->assertSame(2, $page['meta']['last_page']);
    }

    public function test_percent_sign_in_search_is_not_a_wildcard(): void
    {
        $this->createStudent('Student One');

        $this->assertSame(0, $this->send('GET', '/students?search=%25')->body['meta']['total']);
    }

    public function test_bad_query_parameters_return_422(): void
    {
        $this->assertSame(422, $this->send('GET', '/students?per_page=1000')->status);
        $this->assertSame(422, $this->send('GET', '/students?page=abc')->status);
    }

    public function test_partial_update(): void
    {
        $this->createStudent('Student One', 20, 'Kathmandu');

        $response = $this->send('PATCH', '/students/1', ['city' => 'Pokhara']);

        $this->assertSame(200, $response->status);
        $this->assertSame('Pokhara', $response->body['data']['city']);
        $this->assertSame(20, $response->body['data']['age']);
    }

    public function test_delete_then_404(): void
    {
        $this->createStudent();

        $this->assertSame(204, $this->send('DELETE', '/students/1')->status);
        $this->assertSame(404, $this->send('GET', '/students/1')->status);
        $this->assertSame(404, $this->send('DELETE', '/students/1')->status);
    }

    public function test_triggers_record_full_history_even_after_delete(): void
    {
        $this->createStudent('Student One', 20, 'Kathmandu');
        $this->send('PATCH', '/students/1', ['age' => 21]);
        $this->send('DELETE', '/students/1');

        $history = $this->send('GET', '/students/1/history')->body['data'];

        $this->assertSame(['insert', 'update', 'delete'], array_column($history, 'action'));
        $this->assertSame(20, $history[1]['old']['age']);
        $this->assertSame(21, $history[1]['new']['age']);
        $this->assertNull($history[2]['new']);
    }

    public function test_city_summary_report(): void
    {
        $this->createStudent('Student One', 20, 'Kathmandu');
        $this->createStudent('Student Two', 24, 'Kathmandu');
        $this->createStudent('Student Three', 30, 'Pokhara');

        $this->assertSame([
            ['city' => 'Kathmandu', 'students' => 2, 'average_age' => 22.0, 'youngest' => 20, 'oldest' => 24],
            ['city' => 'Pokhara', 'students' => 1, 'average_age' => 30.0, 'youngest' => 30, 'oldest' => 30],
        ], $this->send('GET', '/reports/cities')->body['data']);
    }

    public function test_errors_are_json_with_cors_headers(): void
    {
        $response = $this->send('GET', '/nothing-here');

        $this->assertSame(404, $response->status);
        $this->assertSame(['message' => 'Not found.'], $response->body);
        $this->assertSame('https://example.test', $response->headers['Access-Control-Allow-Origin']);
        $this->assertSame('nosniff', $response->headers['X-Content-Type-Options']);
    }

    public function test_preflight_request(): void
    {
        $response = $this->send('OPTIONS', '/students');

        $this->assertSame(204, $response->status);
        $this->assertStringContainsString('PATCH', $response->headers['Access-Control-Allow-Methods']);
    }

    public function test_invalid_json_body_returns_400(): void
    {
        $response = $this->send('POST', '/students', '{"name":');

        $this->assertSame(400, $response->status);
        $this->assertSame('Request body is not valid JSON.', $response->body['message']);
    }
}
