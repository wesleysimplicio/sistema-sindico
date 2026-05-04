<?php

declare(strict_types=1);

namespace App\Middleware;

use App\Core\Auth;
use App\Core\Jwt;
use App\Core\Request;
use App\Core\Response;
use App\Repositories\ApiTokenRepository;
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
        $secret = (string) ($_ENV['JWT_SECRET'] ?? getenv('JWT_SECRET') ?: '');
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

        $jti = isset($payload['jti']) ? (string) $payload['jti'] : null;
        if ($jti !== null && $jti !== '') {
            if (!(new ApiTokenRepository())->isActive($jti)) {
                Response::error('Sessao revogada.', 401);
                return false;
            }
            Auth::setJti($jti);
        }

        $user['condominium_id'] = $payload['cid'] ?? $user['condominium_id'];
        $user['role']           = $payload['role'] ?? $user['role'];
        $user['unit_id']        = $payload['uid']  ?? $user['unit_id'];
        Auth::setApiUser($user);
        return true;
    }
}
