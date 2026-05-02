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

    public static function error(string $message, int $status = 400, array $details = []): void
    {
        http_response_code($status);
        header('Content-Type: application/json; charset=utf-8');

        echo json_encode([
            'success' => false,
            'error'   => [
                'message' => $message,
                'details' => $details,
            ],
            'meta'    => ['timestamp' => gmdate('c')],
        ], JSON_UNESCAPED_UNICODE);
    }
}
