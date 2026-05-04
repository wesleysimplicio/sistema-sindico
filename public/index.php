<?php

declare(strict_types=1);

$basePath = dirname(__DIR__);

require $basePath . '/src/Core/Env.php';
require $basePath . '/src/Core/Autoload.php';

$app = new App\Core\Application($basePath);
$app->boot();

$jwtSecret = (string) ($_ENV['JWT_SECRET'] ?? getenv('JWT_SECRET') ?: '');
if ($jwtSecret === '' || strlen($jwtSecret) < 32) {
    throw new RuntimeException('JWT_SECRET must be set and at least 32 chars');
}

$app->run();
