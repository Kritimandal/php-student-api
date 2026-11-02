<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Http\HttpException;
use App\Students\StudentValidator;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class StudentValidatorTest extends TestCase
{
    private StudentValidator $validator;

    protected function setUp(): void
    {
        $this->validator = new StudentValidator();
    }

    private function errorsFor(callable $call): array
    {
        try {
            $call();
        } catch (HttpException $error) {
            $this->assertSame(422, $error->status);

            return $error->errors;
        }
        $this->fail('Expected a validation error.');
    }

    public function test_valid_student_is_cleaned(): void
    {
        $this->assertSame(
            ['name' => 'Student One', 'age' => 20, 'city' => 'Kathmandu'],
            $this->validator->validate(['name' => '  Student   One ', 'age' => 20, 'city' => 'Kathmandu']),
        );
    }

    public function test_missing_fields_are_required(): void
    {
        $this->assertSame(
            ['name', 'age', 'city'],
            array_keys($this->errorsFor(fn () => $this->validator->validate([]))),
        );
    }

    public static function invalidValues(): array
    {
        return [
            'age as string' => [['age' => '20'], 'age'],
            'age too high' => [['age' => 121], 'age'],
            'age as float' => [['age' => 20.5], 'age'],
            'name too short' => [['name' => 'A'], 'name'],
            'name too long' => [['name' => str_repeat('a', 101)], 'name'],
            'city not text' => [['city' => ['x']], 'city'],
            'unknown field' => [['admin' => true], 'admin'],
        ];
    }

    #[DataProvider('invalidValues')]
    public function test_invalid_values_are_rejected(array $override, string $field): void
    {
        $data = [...['name' => 'Student One', 'age' => 20, 'city' => 'Kathmandu'], ...$override];

        $this->assertArrayHasKey($field, $this->errorsFor(fn () => $this->validator->validate($data)));
    }

    public function test_partial_update_needs_at_least_one_field(): void
    {
        $this->assertArrayHasKey('body', $this->errorsFor(fn () => $this->validator->validate([], partial: true)));
        $this->assertSame(['age' => 30], $this->validator->validate(['age' => 30], partial: true));
    }

    public function test_body_must_be_an_object(): void
    {
        $this->assertArrayHasKey('body', $this->errorsFor(fn () => $this->validator->validate('text')));
        $this->assertArrayHasKey('body', $this->errorsFor(fn () => $this->validator->validate([1, 2])));
    }

    public function test_bulk_errors_point_to_the_row(): void
    {
        $errors = $this->errorsFor(fn () => $this->validator->validateMany([
            ['name' => 'Student One', 'age' => 20, 'city' => 'Kathmandu'],
            ['name' => 'Student Two', 'age' => 'old', 'city' => 'Lalitpur'],
        ]));

        $this->assertSame(['1.age'], array_keys($errors));
    }

    public function test_bulk_has_a_size_limit(): void
    {
        $row = ['name' => 'Student One', 'age' => 20, 'city' => 'Kathmandu'];

        $this->assertArrayHasKey(
            'students',
            $this->errorsFor(fn () => $this->validator->validateMany(array_fill(0, StudentValidator::MAX_BULK + 1, $row))),
        );
    }
}
