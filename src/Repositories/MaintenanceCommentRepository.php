<?php

declare(strict_types=1);

namespace App\Repositories;

final class MaintenanceCommentRepository extends BaseRepository
{
    protected string $table = 'maintenance_comments';

    public function listForRequest(int $requestId): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT c.id, c.request_id, c.user_id, c.body, c.created_at, u.name AS author_name
             FROM maintenance_comments c
             LEFT JOIN users u ON u.id = c.user_id
             WHERE c.request_id = :rid
             ORDER BY c.id ASC'
        );
        $stmt->execute(['rid' => $requestId]);
        return $stmt->fetchAll();
    }

    public function add(int $requestId, ?int $userId, string $body): int
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO maintenance_comments (request_id, user_id, body)
             VALUES (:rid, :uid, :body)'
        );
        $stmt->execute(['rid' => $requestId, 'uid' => $userId, 'body' => $body]);
        return (int) $this->pdo->lastInsertId();
    }
}
