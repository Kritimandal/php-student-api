<?php

declare(strict_types=1);

namespace App\Students;

use App\Http\HttpException;

final class StudentValidator
{
    public const MAX_BULK = 100;

    private const FIELDS = ['name', 'age', 'city'];

    public function validate(mixed $data, bool $partial = false): array
    {
        $errors = $this->errorsFor($data, $partial);
        if ($errors) {
            throw new HttpException(422, 'The given data was invalid.', $errors);
        }

        return $this->clean($data);
    }

    public function validateMany(mixed $items): array
    {
        if (!is_array($items) || !array_is_list($items) || $items === []) {
            throw new HttpException(422, 'The given data was invalid.', ['students' => ['Must be a non-empty list.']]);
        }
        if (count($items) > self::MAX_BULK) {
            throw new HttpException(422, 'The given data was invalid.', ['students' => ['At most ' . self::MAX_BULK . ' students can be added at once.']]);
        }

        $errors = [];
        foreach ($items as $index => $item) {
            foreach ($this->errorsFor($item, false) as $field => $messages) {
                $errors["{$index}.{$field}"] = $messages;
            }
        }
        if ($errors) {
            throw new HttpException(422, 'The given data was invalid.', $errors);
        }

        return array_map(fn (array $item) => $this->clean($item), $items);
    }

    private function errorsFor(mixed $data, bool $partial): array
    {
        if (!is_array($data) || ($data !== [] && array_is_list($data))) {
            return ['body' => ['Must be a JSON object.']];
        }

        $errors = [];
        foreach (array_diff(array_keys($data), self::FIELDS) as $unknown) {
            $errors[$unknown] = ['Unknown field.'];
        }

        foreach (self::FIELDS as $field) {
            if (!array_key_exists($field, $data)) {
                if (!$partial) {
                    $errors[$field] = ['This field is required.'];
                }
                continue;
            }
            $message = $field === 'age' ? $this->ageError($data[$field]) : $this->textError($data[$field]);
            if ($message !== null) {
                $errors[$field] = [$message];
            }
        }

        if ($partial && !$errors && !array_intersect(array_keys($data), self::FIELDS)) {
            $errors['body'] = ['Send at least one of: ' . implode(', ', self::FIELDS) . '.'];
        }

        return $errors;
    }

    private function textError(mixed $value): ?string
    {
        if (!is_string($value)) {
            return 'Must be text.';
        }
        $length = mb_strlen(trim($value));

        return $length < 2 || $length > 100 ? 'Must be between 2 and 100 characters.' : null;
    }

    private function ageError(mixed $value): ?string
    {
        if (!is_int($value)) {
            return 'Must be a whole number.';
        }

        return $value < 3 || $value > 120 ? 'Must be between 3 and 120.' : null;
    }

    private function clean(array $data): array
    {
        $clean = array_intersect_key($data, array_flip(self::FIELDS));
        foreach (['name', 'city'] as $field) {
            if (isset($clean[$field])) {
                $clean[$field] = preg_replace('/\s+/u', ' ', trim($clean[$field]));
            }
        }

        return $clean;
    }
}
