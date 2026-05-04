<?php

declare(strict_types=1);

namespace App\Controllers\Api;

use App\Core\Auth;
use App\Core\Jwt;
use App\Core\Request;
use App\Core\Response;
use App\Repositories\UserRepository;
use DateTimeImmutable;
use DateTimeZone;

final class AuthController
{
    /**
     * POST /api/auth/forgot-password
     *
     * Accepts { document } and issues a 6-digit recovery code.
     * Response is intentionally neutral (same body whether the document exists or not)
     * to avoid user-enumeration.
     * In development the plain code is logged via error_log(); in production only
     * the hash is stored.
     */
    public function forgotPassword(): void
    {
        $document = trim((string) Request::input('document', ''));

        if ($document === '') {
            Response::error('Campo document obrigatório.', 422);
            return;
        }

        $repo = new UserRepository();
        $user = $repo->findByDocument($document);

        if ($user !== null) {
            $code = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
            $hash = password_hash($code, PASSWORD_BCRYPT);
            $expiresAt = new DateTimeImmutable('+15 minutes', new DateTimeZone('UTC'));

            $repo->saveResetToken((int) $user['id'], $hash, $expiresAt);

            // Development-only logging — never expose the plain code in a response.
            if (getenv('APP_ENV') !== 'production') {
                error_log(sprintf(
                    '[forgot-password] document=%s user_id=%d code=%s expires_at=%s',
                    $document,
                    (int) $user['id'],
                    $code,
                    $expiresAt->format('Y-m-d H:i:s')
                ));
            }
        }

        // Neutral response — same shape regardless of whether the document matched.
        Response::json([
            'message' => 'Se o documento estiver cadastrado, um código de recuperação foi enviado.',
        ]);
    }

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

        $repo->touchLogin((int) $user['id']);

        $secret = (string) (getenv('JWT_SECRET') ?: 'change-me-in-prod');
        $token = Jwt::encode([
            'sub'  => (int) $user['id'],
            'role' => $user['role'],
            'cid'  => $user['condominium_id'] ?? null,
        ], $secret, 86400 * 7);

        Response::json([
            'token'      => $token,
            'expires_in' => 86400 * 7,
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
