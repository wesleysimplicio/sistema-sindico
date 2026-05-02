<?php

declare(strict_types=1);

namespace App\Core;

final class Application
{
    public string $basePath;
    public array $config = [];
    public Router $router;

    public function __construct(string $basePath)
    {
        $this->basePath = rtrim($basePath, DIRECTORY_SEPARATOR);
        $this->router = new Router();
    }

    public function boot(): void
    {
        Env::load($this->basePath . '/.env');
        $this->config = require $this->basePath . '/config/app.php';

        if ($this->config['debug'] ?? false) {
            error_reporting(E_ALL);
            ini_set('display_errors', '1');
        } else {
            ini_set('display_errors', '0');
        }

        $router = $this->router;
        $config = $this->config;

        require $this->basePath . '/routes/web.php';
        require $this->basePath . '/routes/api.php';
    }

    public function run(): void
    {
        $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
        $uri    = $_SERVER['REQUEST_URI']    ?? '/';
        $this->router->dispatch($method, $uri);
    }
}
