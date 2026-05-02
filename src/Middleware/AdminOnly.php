<?php

declare(strict_types=1);

namespace App\Middleware;

use App\Core\Auth;

final class AdminOnly
{
    public function handle(string $path): bool
    {
        if (!Auth::check()) {
            header('Location: /login');
            return false;
        }
        $role = Auth::role();
        if (!in_array($role, ['admin', 'sindico'], true)) {
            http_response_code(403);
            echo '<h1>403 - Acesso negado</h1>';
            return false;
        }
        return true;
    }
}
