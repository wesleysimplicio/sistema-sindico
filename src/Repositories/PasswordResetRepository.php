<?php

declare(strict_types=1);

namespace App\Repositories;

final class PasswordResetRepository extends BaseRepository
{
    protected string $table = 'password_resets';

    public function invalidatePendingForUser(int $userId): void
    {
        $stmt = $this->pdo->prepare(
            'UPDATE password_resets
             SET used_at = NOW()
             WHERE user_id = :uid AND used_at IS NULL'
        );
        $stmt->execute(['uid' => $userId]);
    }

    public function createForUser(int $userId, string $codeHash, int $ttlSeconds): int
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO password_resets (user_id, code_hash, expires_at)
             VALUES (:uid, :hash, DATE_ADD(NOW(), INTERVAL :ttl SECOND))'
        );
        $stmt->execute([
            'uid'  => $userId,
            'hash' => $codeHash,
            'ttl'  => $ttlSeconds,
        ]);
        return (int) $this->pdo->lastInsertId();
    }

    public function findActiveByUser(int $userId): ?array
    {
        $stmt = $this->pdo->prepare(
            'SELECT * FROM password_resets
             WHERE user_id = :uid
               AND used_at IS NULL
               AND expires_at > NOW()
             ORDER BY id DESC
             LIMIT 1'
        );
        $stmt->execute(['uid' => $userId]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function attachResetToken(int $id, string $tokenHash, int $ttlSeconds): void
    {
        $stmt = $this->pdo->prepare(
            'UPDATE password_resets
             SET reset_token_hash = :hash,
                 expires_at = DATE_ADD(NOW(), INTERVAL :ttl SECOND)
             WHERE id = :id'
        );
        $stmt->execute([
            'id'   => $id,
            'hash' => $tokenHash,
            'ttl'  => $ttlSeconds,
        ]);
    }

    public function findByResetTokenHash(string $tokenHash): ?array
    {
        $stmt = $this->pdo->prepare(
            'SELECT * FROM password_resets
             WHERE reset_token_hash = :hash
               AND used_at IS NULL
               AND expires_at > NOW()
             ORDER BY id DESC
             LIMIT 1'
        );
        $stmt->execute(['hash' => $tokenHash]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function markUsed(int $id): void
    {
        $stmt = $this->pdo->prepare(
            'UPDATE password_resets SET used_at = NOW() WHERE id = :id'
        );
        $stmt->execute(['id' => $id]);
    }
}
