<?php

declare(strict_types=1);

namespace App\Middleware;

use App\Core\Auth;

final class WebAuth
{
    public function handle(string $path): bool
    {
        if (!Auth::check()) {
            header('Location: /login');
            return false;
        }
        return true;
    }
}
