<?php

declare(strict_types=1);

namespace App\Controllers\Api;

use App\Core\Auth;
use App\Core\Request;
use App\Core\Response;
use App\Core\Totp;
use App\Repositories\ApiTokenRepository;
use App\Repositories\UserRepository;

final class SecurityController
{
    public function status(): void
    {
        $u = Auth::user();
        if ($u === null) {
            Response::error('Nao autenticado.', 401);
            return;
        }
        Response::json([
            'twofa_enabled' => (int) ($u['twofa_enabled'] ?? 0) === 1,
            'has_secret'    => !empty($u['totp_secret']),
        ]);
    }

    public function setup2fa(): void
    {
        $u = Auth::user();
        if ($u === null) {
            Response::error('Nao autenticado.', 401);
            return;
        }
        $secret = Totp::generateSecret();
        $issuer = (string) ($_ENV['APP_NAME'] ?? getenv('APP_NAME') ?: 'Sistema Sindico');
        $url    = Totp::otpauthUrl($secret, (string) $u['email'], $issuer);

        (new UserRepository())->update((int) $u['id'], [
            'totp_secret'   => $secret,
            'twofa_enabled' => 0,
        ]);

        Response::json([
            'secret'      => $secret,
            'otpauth_url' => $url,
            'issuer'      => $issuer,
        ]);
    }

    public function enable2fa(): void
    {
        $u = Auth::user();
        if ($u === null) {
            Response::error('Nao autenticado.', 401);
            return;
        }
        $code = trim((string) Request::input('code', ''));
        if ($code === '') {
            Response::error('Codigo obrigatorio.', 422);
            return;
        }
        if (empty($u['totp_secret'])) {
            Response::error('Configure o 2FA primeiro.', 422, [], 'twofa_not_setup');
            return;
        }
        if (!Totp::verify((string) $u['totp_secret'], $code)) {
            Response::error('Codigo invalido.', 401, [], 'twofa_invalid');
            return;
        }
        (new UserRepository())->update((int) $u['id'], ['twofa_enabled' => 1]);
        Response::json(['twofa_enabled' => true]);
    }

    public function disable2fa(): void
    {
        $u = Auth::user();
        if ($u === null) {
            Response::error('Nao autenticado.', 401);
            return;
        }
        $code = trim((string) Request::input('code', ''));
        if ((int) ($u['twofa_enabled'] ?? 0) === 1) {
            if ($code === '' || !Totp::verify((string) ($u['totp_secret'] ?? ''), $code)) {
                Response::error('Codigo invalido.', 401, [], 'twofa_invalid');
                return;
            }
        }
        (new UserRepository())->update((int) $u['id'], [
            'twofa_enabled' => 0,
            'totp_secret'   => null,
        ]);
        Response::json(['twofa_enabled' => false]);
    }

    public function listSessions(): void
    {
        $uid = Auth::id();
        if ($uid === null) {
            Response::error('Nao autenticado.', 401);
            return;
        }
        $items = (new ApiTokenRepository())->listForUser($uid, Auth::jti());
        Response::json($items, 200, ['count' => count($items)]);
    }

    public function revokeSession(array $params): void
    {
        $uid = Auth::id();
        if ($uid === null) {
            Response::error('Nao autenticado.', 401);
            return;
        }
        $id = (int) ($params['id'] ?? 0);
        $ok = (new ApiTokenRepository())->revoke($id, $uid);
        if (!$ok) {
            Response::error('Sessao nao encontrada.', 404);
            return;
        }
        Response::json(['revoked' => true]);
    }
}
