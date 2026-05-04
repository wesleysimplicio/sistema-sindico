<?php

declare(strict_types=1);

namespace App\Controllers\Api;

use App\Core\Auth;
use App\Core\Jwt;
use App\Core\Request;
use App\Core\Response;
use App\Core\Totp;
use App\Repositories\ApiTokenRepository;
use App\Repositories\UserRepository;

final class AuthController
{
    public function login(): void
    {
        $email = (string) Request::input('email', '');
        $password = (string) Request::input('password', '');

        if ($email === '' || $password === '') {
            Response::error('Email e senha obrigatorios.', 422);
            return;
        }

        $repo = new UserRepository();
        $user = $repo->findByEmail($email);
        if ($user === null || !password_verify($password, (string) $user['password_hash'])) {
            Response::error('Credenciais invalidas.', 401);
            return;
        }

        if ((int) ($user['twofa_enabled'] ?? 0) === 1 && !empty($user['totp_secret'])) {
            $code = trim((string) Request::input('code', ''));
            if ($code === '') {
                Response::json([
                    'twofa_required' => true,
                    'message'        => 'Informe o codigo do app autenticador.',
                ]);
                return;
            }
            if (!Totp::verify((string) $user['totp_secret'], $code)) {
                Response::error('Codigo 2FA invalido.', 401, [], 'twofa_invalid');
                return;
            }
        }

        $repo->touchLogin((int) $user['id']);

        $secret = (string) ($_ENV['JWT_SECRET'] ?? getenv('JWT_SECRET') ?: '');
        $ttl    = 86400 * 7;
        $jti    = bin2hex(random_bytes(16));
        $token  = Jwt::encode([
            'sub'  => (int) $user['id'],
            'role' => $user['role'],
            'cid'  => $user['condominium_id'] ?? null,
            'jti'  => $jti,
        ], $secret, $ttl);

        $device = trim((string) Request::input('device', ''));
        $ip     = Request::ip();
        $ua     = isset($_SERVER['HTTP_USER_AGENT']) ? (string) $_SERVER['HTTP_USER_AGENT'] : null;
        (new ApiTokenRepository())->claim(
            (int) $user['id'],
            $jti,
            $device !== '' ? substr($device, 0, 100) : null,
            $ip,
            $ua,
            $ttl
        );

        Response::json([
            'token'      => $token,
            'expires_in' => $ttl,
            'user'       => self::publicUser($user),
        ]);
    }

    public function me(): void
    {
        $user = Auth::user();
        if ($user === null) {
            Response::error('Nao autenticado.', 401);
            return;
        }
        Response::json(['user' => self::publicUser($user)]);
    }

    public function logout(): void
    {
        $jti = Auth::jti();
        $uid = Auth::id();
        if ($jti !== null && $uid !== null) {
            (new ApiTokenRepository())->revokeByJti($jti, $uid);
        }
        Response::json(['logged_out' => true]);
    }

    private static function publicUser(array $u): array
    {
        return [
            'id'             => (int) $u['id'],
            'name'           => $u['name'],
            'email'          => $u['email'],
            'role'           => $u['role'],
            'condominium_id' => isset($u['condominium_id']) ? (int) $u['condominium_id'] : null,
            'unit_id'        => isset($u['unit_id']) ? (int) $u['unit_id'] : null,
            'phone'          => $u['phone'] ?? null,
            'avatar_url'     => $u['avatar_url'] ?? null,
        ];
    }
}
