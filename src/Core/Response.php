<?php

declare(strict_types=1);

namespace App\Core;

final class Response
{
    public static function json(mixed $data, int $status = 200, array $meta = []): void
    {
        http_response_code($status);
        header('Content-Type: application/json; charset=utf-8');
        header('X-Content-Type-Options: nosniff');

        $envelope = [
            'success' => $status >= 200 && $status < 400,
            'data'    => $data,
            'meta'    => array_merge([
                'timestamp' => gmdate('c'),
                'version'   => trim((string) @file_get_contents(dirname(__DIR__, 2) . '/VERSION')),
            ], $meta),
        ];

        echo json_encode($envelope, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }

    public static function error(string $message, int $status = 400, array $details = [], ?string $code = null): void
    {
        http_response_code($status);
        header('Content-Type: application/json; charset=utf-8');
        header('X-Content-Type-Options: nosniff');

        $code ??= self::codeForStatus($status);

        echo json_encode([
            'success' => false,
            'error'   => [
                'code'    => $code,
                'message' => $message,
                'details' => $details,
            ],
            'meta'    => ['timestamp' => gmdate('c')],
        ], JSON_UNESCAPED_UNICODE);
    }

    private static function codeForStatus(int $status): string
    {
        return match (true) {
            $status === 400 => 'bad_request',
            $status === 401 => 'unauthenticated',
            $status === 403 => 'forbidden',
            $status === 404 => 'not_found',
            $status === 409 => 'conflict',
            $status === 422 => 'validation_failed',
            $status === 429 => 'rate_limited',
            $status === 502 => 'upstream_error',
            $status === 503 => 'service_unavailable',
            $status >= 500  => 'internal_error',
            default         => 'error',
        };
    }
}
