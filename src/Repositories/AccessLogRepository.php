<?php

declare(strict_types=1);

namespace App\Repositories;

final class AccessLogRepository extends BaseRepository
{
    protected string $table = 'access_logs';

    private const DIRECTIONS = ['in', 'out'];
    private const RESULTS    = ['granted', 'denied'];

    public function record(
        int $condominiumId,
        ?int $userId,
        ?int $visitorId,
        ?int $unitId,
        string $direction,
        string $result = 'granted',
        ?string $reason = null,
        ?string $photoUrl = null
    ): int {
        if (!in_array($direction, self::DIRECTIONS, true)) {
            throw new \InvalidArgumentException('Invalid access direction.');
        }
        if (!in_array($result, self::RESULTS, true)) {
            throw new \InvalidArgumentException('Invalid access result.');
        }
        $stmt = $this->pdo->prepare(
            'INSERT INTO access_logs (condominium_id, user_id, visitor_id, unit_id, direction, result, reason, photo_url, occurred_at)
             VALUES (:cid, :uid, :vid, :unit, :dir, :res, :reason, :photo, NOW())'
        );
        $stmt->execute([
            'cid'    => $condominiumId,
            'uid'    => $userId,
            'vid'    => $visitorId,
            'unit'   => $unitId,
            'dir'    => $direction,
            'res'    => $result,
            'reason' => $reason,
            'photo'  => $photoUrl,
        ]);
        return (int) $this->pdo->lastInsertId();
    }

    public function listByCondominium(int $condominiumId, int $limit = 100): array
    {
        $limit = max(1, min(500, $limit));
        $stmt = $this->pdo->prepare(
            'SELECT a.id, a.condominium_id, a.user_id, a.visitor_id, a.unit_id,
                    a.direction, a.result, a.reason, a.photo_url, a.occurred_at,
                    v.name AS visitor_name, u.name AS user_name
             FROM access_logs a
             LEFT JOIN visitors v ON v.id = a.visitor_id
             LEFT JOIN users u ON u.id = a.user_id
             WHERE a.condominium_id = :cid
             ORDER BY a.id DESC
             LIMIT ' . $limit
        );
        $stmt->execute(['cid' => $condominiumId]);
        return $stmt->fetchAll();
    }
}
