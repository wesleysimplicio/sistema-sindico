<?php

declare(strict_types=1);

namespace App\Core;

final class Request
{
    public static function input(string $key, mixed $default = null): mixed
    {
        $body = self::body();
        if (array_key_exists($key, $body)) {
            return $body[$key];
        }
        if (array_key_exists($key, $_GET)) {
            return $_GET[$key];
        }
        return $default;
    }

    public static function all(): array
    {
        return array_merge($_GET, self::body());
    }

    public static function body(): array
    {
        static $cache;
        if ($cache !== null) {
            return $cache;
        }

        $contentType = $_SERVER['CONTENT_TYPE'] ?? '';
        if (str_contains($contentType, 'application/json')) {
            $raw = file_get_contents('php://input') ?: '';
            $data = json_decode($raw, true);
            return $cache = is_array($data) ? $data : [];
        }
        return $cache = $_POST;
    }

    public static function bearerToken(): ?string
    {
        $header = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
        if ($header === '' && function_exists('apache_request_headers')) {
            $headers = apache_request_headers() ?: [];
            $header = $headers['Authorization'] ?? $headers['authorization'] ?? '';
        }
        if (str_starts_with($header, 'Bearer ')) {
            return trim(substr($header, 7));
        }
        return null;
    }

    public static function isJson(): bool
    {
        return str_contains($_SERVER['CONTENT_TYPE'] ?? '', 'application/json');
    }

    public static function ip(): string
    {
        return $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    }
}
