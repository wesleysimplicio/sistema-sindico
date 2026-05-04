<?php

declare(strict_types=1);

namespace App\Repositories;

final class PasswordHistoryRepository extends BaseRepository
{
    protected string $table = 'password_history';

    /**
     * @return array<int, array{id:int,user_id:int,password_hash:string,created_at:string}>
     */
    public function recentForUser(int $userId, int $limit = 5): array
    {
        $limit = max(1, min(50, $limit));
        $stmt = $this->pdo->prepare(
            'SELECT * FROM password_history
             WHERE user_id = :uid
             ORDER BY id DESC
             LIMIT ' . $limit
        );
        $stmt->execute(['uid' => $userId]);
        return $stmt->fetchAll();
    }

    public function append(int $userId, string $passwordHash): int
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO password_history (user_id, password_hash) VALUES (:uid, :hash)'
        );
        $stmt->execute(['uid' => $userId, 'hash' => $passwordHash]);
        return (int) $this->pdo->lastInsertId();
    }

    public function matchesAnyRecent(int $userId, string $plaintext, int $window = 5): bool
    {
        foreach ($this->recentForUser($userId, $window) as $row) {
            if (password_verify($plaintext, (string) $row['password_hash'])) {
                return true;
            }
        }
        return false;
    }
}
