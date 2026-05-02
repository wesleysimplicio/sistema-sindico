<?php

declare(strict_types=1);

$basePath = dirname(__DIR__);

require $basePath . '/src/Core/Env.php';
require $basePath . '/src/Core/Autoload.php';

$app = new App\Core\Application($basePath);
$app->boot();
$app->run();
