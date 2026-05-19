<?php

$env = static function (string $key, mixed $default = null): mixed {
    $value = getenv($key);
    return ($value === false || $value === '') ? $default : $value;
};

return [
    'name'  => $env('APP_NAME', 'Sistema Sindico'),
    'env'   => $env('APP_ENV', 'local'),
    'debug' => filter_var($env('APP_DEBUG', 'false'), FILTER_VALIDATE_BOOLEAN),
    'url'   => $env('APP_URL', 'http://localhost:8000'),

    'db' => [
        'host'     => $env('DB_HOST', '127.0.0.1'),
        'port'     => (int) $env('DB_PORT', 3306),
        'database' => $env('DB_DATABASE', 'sistema_sindico'),
        'username' => $env('DB_USERNAME', 'root'),
        'password' => $env('DB_PASSWORD', ''),
        'charset'  => 'utf8mb4',
    ],

    'mail' => [
        'driver' => strtolower((string) $env('MAIL_DRIVER', 'log')),
        'from' => $env('MAIL_FROM', 'no-reply@sindico.local'),
        'from_name' => $env('MAIL_FROM_NAME', 'Sistema Sindico'),
        'api_key' => $env('MAIL_API_KEY', ''),
        'api_base_url' => $env('MAIL_API_BASE_URL', 'https://api.resend.com'),
        'timeout_seconds' => (int) $env('MAIL_TIMEOUT_SECONDS', 10),
    ],

    'rate_limit' => [
        'driver'       => strtolower((string) $env('RATE_LIMIT_DRIVER', 'mysql')),
        'redis_url'    => $env('REDIS_URL', 'redis://redis:6379/0'),
        'redis_prefix' => 'sistema-sindico:rate-limit',
    ],
];
