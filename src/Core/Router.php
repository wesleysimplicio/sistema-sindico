<?php

declare(strict_types=1);

namespace App\Core;

final class Router
{
    /** @var array<int, array{method:string, regex:string, params:array<int,string>, handler:mixed, middleware:array<int,string>}> */
    private array $routes = [];

    /** @var array<int, string> */
    private array $groupMiddleware = [];

    public function get(string $path, mixed $handler, array $middleware = []): void
    {
        $this->add('GET', $path, $handler, $middleware);
    }

    public function post(string $path, mixed $handler, array $middleware = []): void
    {
        $this->add('POST', $path, $handler, $middleware);
    }

    public function put(string $path, mixed $handler, array $middleware = []): void
    {
        $this->add('PUT', $path, $handler, $middleware);
    }

    public function patch(string $path, mixed $handler, array $middleware = []): void
    {
        $this->add('PATCH', $path, $handler, $middleware);
    }

    public function delete(string $path, mixed $handler, array $middleware = []): void
    {
        $this->add('DELETE', $path, $handler, $middleware);
    }

    public function group(array $middleware, callable $callback): void
    {
        $previous = $this->groupMiddleware;
        $this->groupMiddleware = array_merge($previous, $middleware);
        $callback($this);
        $this->groupMiddleware = $previous;
    }

    private function add(string $method, string $path, mixed $handler, array $middleware): void
    {
        $path = $this->normalize($path);
        $params = [];
        $regex = preg_replace_callback('#\{([a-zA-Z_][a-zA-Z0-9_]*)\}#', function ($m) use (&$params) {
            $params[] = $m[1];
            return '([^/]+)';
        }, $path);

        $this->routes[] = [
            'method'     => $method,
            'regex'      => '#^' . $regex . '$#',
            'params'     => $params,
            'handler'    => $handler,
            'middleware' => array_merge($this->groupMiddleware, $middleware),
        ];
    }

    public function dispatch(string $method, string $uri): void
    {
        $path = $this->normalize(parse_url($uri, PHP_URL_PATH) ?: '/');

        foreach ($this->routes as $route) {
            if ($route['method'] !== $method) {
                continue;
            }
            if (preg_match($route['regex'], $path, $matches)) {
                array_shift($matches);
                $params = [];
                foreach ($route['params'] as $i => $name) {
                    $params[$name] = $matches[$i] ?? null;
                }

                foreach ($route['middleware'] as $mw) {
                    $instance = new $mw();
                    if (!$instance->handle($path)) {
                        return;
                    }
                }

                $this->invoke($route['handler'], $params);
                return;
            }
        }

        $this->notFound($path);
    }

    private function invoke(mixed $handler, array $params): void
    {
        if (is_array($handler) && count($handler) === 2) {
            [$class, $method] = $handler;
            $instance = new $class();
            $instance->$method($params);
            return;
        }
        if (is_callable($handler)) {
            $handler($params);
            return;
        }
        throw new \RuntimeException('Handler invalido');
    }

    private function notFound(string $path): void
    {
        if (str_starts_with($path, '/api')) {
            Response::error('Rota nao encontrada', 404, ['path' => $path]);
            return;
        }

        http_response_code(404);
        echo '<h1>404 - Pagina nao encontrada</h1>';
        echo '<p>Caminho: ' . htmlspecialchars($path) . '</p>';
    }

    private function normalize(string $path): string
    {
        $path = '/' . trim($path, '/');
        return $path === '' ? '/' : $path;
    }
}
