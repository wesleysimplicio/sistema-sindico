<?php

declare(strict_types=1);

namespace App\Repositories;

final class IncidentCommentRepository extends BaseRepository
{
    protected string $table = 'incident_comments';

    public function add(int $incidentId, ?int $userId, string $body): int
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO incident_comments (incident_id, user_id, body)
             VALUES (:iid, :uid, :body)'
        );
        $stmt->execute([
            'iid'  => $incidentId,
            'uid'  => $userId,
            'body' => $body,
        ]);
        return (int) $this->pdo->lastInsertId();
    }

    public function listForIncident(int $incidentId): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT c.id, c.incident_id, c.user_id, c.body, c.created_at, u.name AS user_name
             FROM incident_comments c
             LEFT JOIN users u ON u.id = c.user_id
             WHERE c.incident_id = :iid
             ORDER BY c.id ASC'
        );
        $stmt->execute(['iid' => $incidentId]);
        return $stmt->fetchAll();
    }
}
