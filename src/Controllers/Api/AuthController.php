<?php

declare(strict_types=1);

namespace App\Controllers\Api;

use App\Core\Auth;
use App\Core\Jwt;
use App\Core\Request;
use App\Core\Response;
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

    public function forgotPassword(): void
    {
        $document = trim((string) Request::input('document', ''));
        if ($document === '') {
            Response::error('O campo documento (CPF/CNPJ) é obrigatório.', 422);
            return;
        }

        $repo = new UserRepository();
        $user = $repo->findByDocument($document);

        // Always respond 200 to avoid user enumeration.
        if ($user !== null) {
            $code = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
            $repo->createPasswordResetToken((int) $user['id'], $code, 600);

            // In dev/prod without an SMS/email provider, log the code so it is testable.
            error_log(sprintf(
                '[password-reset] user_id=%d document=%s code=%s',
                (int) $user['id'],
                $document,
                $code
            ));
        }

        Response::json(['message' => 'Se o documento estiver cadastrado, um código será enviado.']);
    }

    public function verifyCode(): void
    {
        $document = trim((string) Request::input('document', ''));
        $code     = trim((string) Request::input('code', ''));

        if ($document === '' || $code === '') {
            Response::error('Documento e código são obrigatórios.', 422);
            return;
        }

        $repo = new UserRepository();
        $user = $repo->findByDocument($document);
        if ($user === null) {
            Response::error('Código inválido ou expirado.', 400);
            return;
        }

        $row = $repo->findValidResetByCode((int) $user['id'], $code);
        if ($row === null) {
            Response::error('Código inválido ou expirado.', 400);
            return;
        }

        $resetToken = bin2hex(random_bytes(32));
        $repo->attachResetToken((int) $row['id'], $resetToken);

        Response::json(['reset_token' => $resetToken]);
    }

    public function resetPassword(): void
    {
        $resetToken  = trim((string) Request::input('reset_token', ''));
        $newPassword = (string) Request::input('new_password', '');

        if ($resetToken === '' || $newPassword === '') {
            Response::error('Token e nova senha são obrigatórios.', 422);
            return;
        }

        $error = self::validatePassword($newPassword);
        if ($error !== null) {
            Response::error($error, 422);
            return;
        }

        $repo = new UserRepository();
        $row  = $repo->findValidResetByToken($resetToken);
        if ($row === null) {
            Response::error('Token inválido ou expirado.', 400);
            return;
        }

        $hash = password_hash($newPassword, PASSWORD_BCRYPT);
        $repo->updatePassword((int) $row['user_id'], $hash);
        $repo->markResetTokenUsed((int) $row['id']);

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

    /**
     * Validate password strength.
     * Rules: minimum 8 characters, at least one letter and one digit.
     * Returns an error message string or null on success.
     */
    public static function validatePassword(string $password): ?string
    {
        if (strlen($password) < 8) {
            return 'A senha deve ter no mínimo 8 caracteres.';
        }
        if (!preg_match('/[A-Za-z]/', $password)) {
            return 'A senha deve conter pelo menos uma letra.';
        }
        if (!preg_match('/[0-9]/', $password)) {
            return 'A senha deve conter pelo menos um número.';
        }
        return null;
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
