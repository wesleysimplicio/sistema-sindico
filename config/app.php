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
];
