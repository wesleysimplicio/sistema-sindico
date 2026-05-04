<?php

declare(strict_types=1);

namespace App\Repositories;

final class PasswordHistoryRepository extends BaseRepository
{
    protected string $table = 'password_history';

    public function append(int $userId, string $passwordHash): int
    {
        return $this->create([
            'user_id'       => $userId,
            'password_hash' => $passwordHash,
        ]);
    }

    public function listByUser(int $userId, int $limit = 10): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT * FROM password_history WHERE user_id = :uid ORDER BY created_at DESC LIMIT ' . (int) $limit
        );
        $stmt->execute(['uid' => $userId]);
        return $stmt->fetchAll();
    }
}
