<?php

declare(strict_types=1);

namespace App\Http;

use JsonException;

final class Request
{
    public function __construct(
        public readonly string $method,
        public readonly string $path,
        public readonly array $query = [],
        private readonly string $rawBody = '',
    ) {
    }

    public static function fromGlobals(): self
    {
        $path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';

        return new self(
            strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET'),
            $path,
            $_GET,
            (string) file_get_contents('php://input'),
        );
    }

    public static function create(string $method, string $uri, mixed $body = null): self
    {
        parse_str((string) parse_url($uri, PHP_URL_QUERY), $query);
        $raw = is_string($body) ? $body : ($body === null ? '' : json_encode($body, JSON_THROW_ON_ERROR));

        return new self(strtoupper($method), parse_url($uri, PHP_URL_PATH) ?: '/', $query, $raw);
    }

    public function json(): mixed
    {
        if (trim($this->rawBody) === '') {
            throw new HttpException(400, 'Request body must be JSON.');
        }

        try {
            return json_decode($this->rawBody, true, 32, JSON_THROW_ON_ERROR);
        } catch (JsonException) {
            throw new HttpException(400, 'Request body is not valid JSON.');
        }
    }

    public function queryString(string $key): ?string
    {
        $value = $this->query[$key] ?? null;

        return is_string($value) && trim($value) !== '' ? trim($value) : null;
    }

    public function queryInt(string $key, int $default, int $min, int $max): int
    {
        $value = $this->query[$key] ?? null;
        if ($value === null || $value === '') {
            return $default;
        }
        if (!is_string($value) || !ctype_digit($value) || (int) $value < $min || (int) $value > $max) {
            throw new HttpException(422, 'Invalid query parameter.', [$key => ["Must be a whole number from {$min} to {$max}."]]);
        }

        return (int) $value;
    }
}
