<?php

declare(strict_types=1);

namespace App\Middleware;

use App\Core\Auth;
use App\Core\Jwt;
use App\Core\Request;
use App\Core\Response;
use App\Repositories\UserRepository;

final class ApiAuth
{
    public function handle(string $path): bool
    {
        $token = Request::bearerToken();
        if ($token === null) {
            Response::error('Token nao informado', 401);
            return false;
        }
        $secret = (string) (getenv('JWT_SECRET') ?: 'change-me-in-prod');
        $payload = Jwt::decode($token, $secret);
        if ($payload === null || !isset($payload['sub'])) {
            Response::error('Token invalido ou expirado', 401);
            return false;
        }
        $user = (new UserRepository())->find((int) $payload['sub']);
        if ($user === null) {
            Response::error('Usuario nao encontrado', 401);
            return false;
        }
        Auth::setApiUser($user);
        return true;
    }
}
