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

    public function findByDocument(string $document): ?array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM users WHERE document = :document AND active = 1 LIMIT 1');
        $stmt->execute(['document' => $document]);
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

    /**
     * Deletes all existing (unused) reset tokens for a user and inserts a new one.
     */
    public function saveResetToken(int $userId, string $tokenHash, \DateTimeImmutable $expiresAt): int
    {
        $this->deleteResetTokens($userId);

        $stmt = $this->pdo->prepare(
            'INSERT INTO password_reset_tokens (user_id, token_hash, expires_at)
             VALUES (:user_id, :token_hash, :expires_at)'
        );
        $stmt->execute([
            'user_id'    => $userId,
            'token_hash' => $tokenHash,
            'expires_at' => $expiresAt->format('Y-m-d H:i:s'),
        ]);
        return (int) $this->pdo->lastInsertId();
    }

    /**
     * Removes all password_reset_tokens rows for a user (cleanup before a new code).
     */
    public function deleteResetTokens(int $userId): void
    {
        $stmt = $this->pdo->prepare('DELETE FROM password_reset_tokens WHERE user_id = :user_id');
        $stmt->execute(['user_id' => $userId]);
    }
}
