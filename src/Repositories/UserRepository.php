<?php

declare(strict_types=1);

namespace App\Repositories;

final class UserRepository extends BaseRepository
{
    protected string $table = 'users';

    public function findByEmail(string $email): ?array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM users WHERE email = :email LIMIT 1');
        $stmt->execute(['email' => $email]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function listByCondominium(int $condominiumId, ?string $role = null): array
    {
        $sql = 'SELECT u.*, un.block, un.number AS unit_number
                FROM users u
                LEFT JOIN units un ON un.id = u.unit_id
                WHERE u.condominium_id = :cid';
        $params = ['cid' => $condominiumId];
        if ($role !== null) {
            $sql .= ' AND u.role = :role';
            $params['role'] = $role;
        }
        $sql .= ' ORDER BY u.name ASC';
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function touchLogin(int $id): void
    {
        $stmt = $this->pdo->prepare('UPDATE users SET last_login_at = NOW() WHERE id = :id');
        $stmt->execute(['id' => $id]);
    }

    public function findByDocument(string $document): ?array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM users WHERE document = :doc AND active = 1 LIMIT 1');
        $stmt->execute(['doc' => $document]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function createPasswordResetToken(int $userId, string $code, int $ttlSeconds = 600): void
    {
        // Invalidate any existing unused tokens for this user.
        $this->pdo->prepare(
            'UPDATE password_reset_tokens SET used_at = NOW() WHERE user_id = :uid AND used_at IS NULL'
        )->execute(['uid' => $userId]);

        $this->pdo->prepare(
            'INSERT INTO password_reset_tokens (user_id, code, expires_at)
             VALUES (:uid, :code, DATE_ADD(NOW(), INTERVAL :ttl SECOND))'
        )->execute(['uid' => $userId, 'code' => $code, 'ttl' => $ttlSeconds]);
    }

    public function findValidResetByCode(int $userId, string $code): ?array
    {
        $stmt = $this->pdo->prepare(
            'SELECT * FROM password_reset_tokens
             WHERE user_id = :uid AND code = :code AND used_at IS NULL AND expires_at > NOW()
             ORDER BY id DESC LIMIT 1'
        );
        $stmt->execute(['uid' => $userId, 'code' => $code]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function attachResetToken(int $tokenId, string $resetToken): void
    {
        $this->pdo->prepare(
            'UPDATE password_reset_tokens SET reset_token = :rt WHERE id = :id'
        )->execute(['rt' => $resetToken, 'id' => $tokenId]);
    }

    public function findValidResetByToken(string $resetToken): ?array
    {
        $stmt = $this->pdo->prepare(
            'SELECT * FROM password_reset_tokens
             WHERE reset_token = :rt AND used_at IS NULL AND expires_at > NOW()
             LIMIT 1'
        );
        $stmt->execute(['rt' => $resetToken]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function markResetTokenUsed(int $tokenId): void
    {
        $this->pdo->prepare(
            'UPDATE password_reset_tokens SET used_at = NOW() WHERE id = :id'
        )->execute(['id' => $tokenId]);
    }

    public function updatePassword(int $userId, string $passwordHash): void
    {
        $this->pdo->prepare(
            'UPDATE users SET password_hash = :hash, updated_at = NOW() WHERE id = :id'
        )->execute(['hash' => $passwordHash, 'id' => $userId]);
    }
}
