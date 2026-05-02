<?php

declare(strict_types=1);

namespace App\Core;

final class View
{
    public static function render(string $template, array $data = []): void
    {
        $basePath = dirname(__DIR__, 2);
        $file = $basePath . '/templates/' . ltrim($template, '/') . '.php';

        if (!is_file($file)) {
            http_response_code(500);
            echo "Template nao encontrado: $template";
            return;
        }

        $content = (function () use ($file, $data) {
            extract($data, EXTR_SKIP);
            ob_start();
            require $file;
            return (string) ob_get_clean();
        })();

        $title = $data['title'] ?? 'Sistema Sindico';
        $layoutFile = $basePath . '/templates/layouts/app.php';
        if (is_file($layoutFile)) {
            require $layoutFile;
            return;
        }

        echo $content;
    }
}
