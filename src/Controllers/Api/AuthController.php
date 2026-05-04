<?php

declare(strict_types=1);

namespace App\Controllers\Api;

use App\Core\Auth;
use App\Core\Jwt;
use App\Core\Request;
use App\Core\Response;
use App\Repositories\PasswordResetRepository;
use App\Repositories\UserRepository;

final class AuthController
{
    /** Code TTL in seconds (15 minutes). */
    private const CODE_TTL = 900;

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

    /**
     * POST /api/auth/forgot-password  { document }
     * Generates a 6-digit recovery code, stores its hash, and "sends" it
     * (logged to error_log in dev — replace with SMS/email in production).
     */
    public function forgotPassword(): void
    {
        $document = trim((string) Request::input('document', ''));

        if ($document === '') {
            Response::error('Campo document obrigatorio.', 422);
            return;
        }

        $userRepo = new UserRepository();
        $user = $userRepo->findByDocument($document);

        // Always return success to avoid user enumeration
        if ($user === null || !(bool) $user['active']) {
            Response::json(['message' => 'Se o documento estiver cadastrado, um codigo sera enviado.']);
            return;
        }

        $code = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
        $codeHash = hash('sha256', $code);
        $expiresAt = date('Y-m-d H:i:s', time() + self::CODE_TTL);

        $resetRepo = new PasswordResetRepository();
        $resetRepo->createForUser((int) $user['id'], $codeHash, $expiresAt);

        // In production replace this with an SMS / e-mail dispatch
        error_log(sprintf('[forgot-password] user_id=%d code=%s', (int) $user['id'], $code));

        Response::json(['message' => 'Se o documento estiver cadastrado, um codigo sera enviado.']);
    }

    /**
     * POST /api/auth/verify-code  { document, code }
     * Validates the 6-digit code and, if correct, issues a one-time reset_token.
     */
    public function verifyCode(): void
    {
        $document = trim((string) Request::input('document', ''));
        $code     = trim((string) Request::input('code', ''));

        if ($document === '' || $code === '') {
            Response::error('Campos document e code sao obrigatorios.', 422);
            return;
        }

        $userRepo = new UserRepository();
        $user = $userRepo->findByDocument($document);

        if ($user === null || !(bool) $user['active']) {
            Response::error('Codigo invalido ou expirado.', 422);
            return;
        }

        $resetRepo = new PasswordResetRepository();
        $record = $resetRepo->findPendingForUser((int) $user['id']);

        if ($record === null || !hash_equals((string) $record['code_hash'], hash('sha256', $code))) {
            Response::error('Codigo invalido ou expirado.', 422);
            return;
        }

        // Generate a secure single-use reset token
        $resetToken = bin2hex(random_bytes(32));
        $resetTokenHash = hash('sha256', $resetToken);

        $resetRepo->markVerified((int) $record['id'], $resetTokenHash);

        Response::json(['reset_token' => $resetToken]);
    }

    /**
     * POST /api/auth/reset-password  { reset_token, new_password }
     * Uses the reset_token to set a new password and records history.
     */
    public function resetPassword(): void
    {
        $resetToken  = trim((string) Request::input('reset_token', ''));
        $newPassword = (string) Request::input('new_password', '');

        if ($resetToken === '' || $newPassword === '') {
            Response::error('Campos reset_token e new_password sao obrigatorios.', 422);
            return;
        }

        if (strlen($newPassword) < 8) {
            Response::error('A senha deve ter no minimo 8 caracteres.', 422);
            return;
        }

        $resetRepo = new PasswordResetRepository();
        $record = $resetRepo->findByResetToken(hash('sha256', $resetToken));

        if ($record === null) {
            Response::error('Token invalido ou expirado.', 422);
            return;
        }

        $userRepo = new UserRepository();
        $user = $userRepo->find((int) $record['user_id']);

        if ($user === null) {
            Response::error('Token invalido ou expirado.', 422);
            return;
        }

        // Save the current (old) hash to history before overwriting
        $userRepo->pushPasswordHistory((int) $user['id'], (string) $user['password_hash']);

        $userRepo->updatePassword((int) $user['id'], password_hash($newPassword, PASSWORD_BCRYPT));

        $resetRepo->markUsed((int) $record['id']);

        Response::json(['message' => 'Senha alterada com sucesso.']);
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

