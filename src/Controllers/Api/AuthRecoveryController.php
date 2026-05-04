<?php

declare(strict_types=1);

namespace App\Controllers\Api;

use App\Core\Request;
use App\Core\Response;
use App\Repositories\PasswordHistoryRepository;
use App\Repositories\PasswordResetRepository;
use App\Repositories\UserRepository;

final class AuthRecoveryController
{
    private const NEUTRAL_MESSAGE = 'Se a conta existir, um codigo foi enviado.';
    private const CODE_TTL_SECONDS  = 15 * 60;
    private const TOKEN_TTL_SECONDS = 10 * 60;
    private const MIN_PASSWORD_LEN  = 8;

    public function forgotPassword(): void
    {
        $document = Request::input('document');
        $email    = Request::input('email');
        $document = is_string($document) ? $document : null;
        $email    = is_string($email) ? $email : null;

        if (($document === null || trim($document) === '') && ($email === null || trim($email) === '')) {
            Response::error('Informe document ou email.', 422);
            return;
        }

        $user = (new UserRepository())->findByDocumentOrEmail($document, $email);
        if ($user === null) {
            // Neutral response to prevent enumeration.
            Response::json(['message' => self::NEUTRAL_MESSAGE]);
            return;
        }

        $code = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
        $codeHash = password_hash($code, PASSWORD_DEFAULT);

        $resets = new PasswordResetRepository();
        $resets->invalidatePendingForUser((int) $user['id']);
        $resets->createForUser((int) $user['id'], $codeHash, self::CODE_TTL_SECONDS);

        // Dev-only logging; no email sender wired in this sprint.
        error_log("[forgot-password] user_id={$user['id']} code={$code}");

        Response::json(['message' => self::NEUTRAL_MESSAGE]);
    }

    public function verifyCode(): void
    {
        $identifier = (string) Request::input('document_or_email', '');
        $code = (string) Request::input('code', '');
        $identifier = trim($identifier);
        $code = trim($code);

        if ($identifier === '' || $code === '') {
            Response::error('document_or_email e code sao obrigatorios.', 422);
            return;
        }

        $userRepo = new UserRepository();
        $user = $userRepo->findByDocumentOrEmail($identifier, $identifier);
        if ($user === null) {
            Response::error('Codigo invalido ou expirado.', 400);
            return;
        }

        $resets = new PasswordResetRepository();
        $row = $resets->findActiveByUser((int) $user['id']);
        if ($row === null || !password_verify($code, (string) $row['code_hash'])) {
            Response::error('Codigo invalido ou expirado.', 400);
            return;
        }

        $resetToken = bin2hex(random_bytes(32));
        $resetTokenHash = hash('sha256', $resetToken);
        $resets->attachResetToken((int) $row['id'], $resetTokenHash, self::TOKEN_TTL_SECONDS);

        Response::json([
            'reset_token' => $resetToken,
            'expires_in'  => self::TOKEN_TTL_SECONDS,
        ]);
    }

    public function resetPassword(): void
    {
        $resetToken = (string) Request::input('reset_token', '');
        $newPassword = (string) Request::input('new_password', '');
        $resetToken = trim($resetToken);

        if ($resetToken === '' || $newPassword === '') {
            Response::error('reset_token e new_password sao obrigatorios.', 422);
            return;
        }
        if (strlen($newPassword) < self::MIN_PASSWORD_LEN) {
            Response::error('Senha deve ter ao menos ' . self::MIN_PASSWORD_LEN . ' caracteres.', 422);
            return;
        }

        $resets = new PasswordResetRepository();
        $tokenHash = hash('sha256', $resetToken);
        $row = $resets->findByResetTokenHash($tokenHash);
        if ($row === null) {
            Response::error('Token invalido ou expirado.', 400);
            return;
        }

        $userId = (int) $row['user_id'];
        $userRepo = new UserRepository();
        $user = $userRepo->find($userId);
        if ($user === null) {
            Response::error('Token invalido ou expirado.', 400);
            return;
        }

        $history = new PasswordHistoryRepository();
        if ($history->matchesAnyRecent($userId, $newPassword, 5)) {
            Response::error('Nao reutilize uma das ultimas 5 senhas.', 422);
            return;
        }

        $oldHash = (string) $user['password_hash'];
        $newHash = password_hash($newPassword, PASSWORD_DEFAULT);

        $userRepo->setPassword($userId, $newHash);
        if ($oldHash !== '') {
            $history->append($userId, $oldHash);
        }
        $resets->markUsed((int) $row['id']);

        Response::json(['message' => 'Senha atualizada.']);
    }
}
