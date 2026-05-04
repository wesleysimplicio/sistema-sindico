<?php

declare(strict_types=1);

namespace App\Repositories;

final class PasswordResetRepository extends BaseRepository
{
    protected string $table = 'password_resets';

    /**
     * Invalidate all pending (unused) reset records for a user, then create a
     * new one with the hashed 6-digit code.
     */
    public function createForUser(int $userId, string $codeHash, string $expiresAt): int
    {
        // Invalidate previous pending codes for this user
        $stmt = $this->pdo->prepare(
            'UPDATE password_resets SET used_at = NOW()
             WHERE user_id = :uid AND used_at IS NULL'
        );
        $stmt->execute(['uid' => $userId]);

        return $this->create([
            'user_id'    => $userId,
            'code_hash'  => $codeHash,
            'expires_at' => $expiresAt,
        ]);
    }

    /**
     * Find the most recent valid (unexpired, unused, no reset_token yet) reset
     * record for a user.
     */
    public function findPendingForUser(int $userId): ?array
    {
        $stmt = $this->pdo->prepare(
            'SELECT * FROM password_resets
             WHERE user_id = :uid
               AND used_at IS NULL
               AND reset_token_hash IS NULL
               AND expires_at > NOW()
             ORDER BY created_at DESC
             LIMIT 1'
        );
        $stmt->execute(['uid' => $userId]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    /**
     * Stamp the record with the hashed reset_token after code verification.
     */
    public function markVerified(int $id, string $resetTokenHash): void
    {
        $stmt = $this->pdo->prepare(
            'UPDATE password_resets SET reset_token_hash = :rth WHERE id = :id'
        );
        $stmt->execute(['rth' => $resetTokenHash, 'id' => $id]);
    }

    /**
     * Find a valid (unexpired, unused, verified) record by its reset_token hash.
     */
    public function findByResetToken(string $resetTokenHash): ?array
    {
        $stmt = $this->pdo->prepare(
            'SELECT * FROM password_resets
             WHERE reset_token_hash = :rth
               AND used_at IS NULL
               AND expires_at > NOW()
             LIMIT 1'
        );
        $stmt->execute(['rth' => $resetTokenHash]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    /**
     * Mark a reset record as consumed so it cannot be reused.
     */
    public function markUsed(int $id): void
    {
        $stmt = $this->pdo->prepare(
            'UPDATE password_resets SET used_at = NOW() WHERE id = :id'
        );
        $stmt->execute(['id' => $id]);
    }
}
