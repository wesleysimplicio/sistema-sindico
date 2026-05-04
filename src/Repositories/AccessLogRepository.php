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
             LIMIT :lim'
        );
        $stmt->bindValue('cid', $condominiumId, \PDO::PARAM_INT);
        $stmt->bindValue('lim', $limit, \PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function listWithFilters(int $condominiumId, array $filters = []): array
    {
        $page  = max(1, (int) ($filters['page'] ?? 1));
        $limit = max(1, min(200, (int) ($filters['limit'] ?? 50)));
        $offset = ($page - 1) * $limit;

        $sql = 'SELECT a.id, a.condominium_id, a.user_id, a.visitor_id, a.unit_id,
                       a.direction, a.result, a.reason, a.photo_url, a.occurred_at,
                       v.name AS visitor_name, u.name AS user_name,
                       un.block, un.number AS unit_number
                FROM access_logs a
                LEFT JOIN visitors v ON v.id = a.visitor_id
                LEFT JOIN users u    ON u.id = a.user_id
                LEFT JOIN units un   ON un.id = a.unit_id
                WHERE a.condominium_id = :cid';
        $params = ['cid' => $condominiumId];

        if (!empty($filters['from'])) {
            $sql .= ' AND a.occurred_at >= :from';
            $params['from'] = $filters['from'];
        }
        if (!empty($filters['to'])) {
            $sql .= ' AND a.occurred_at <= :to';
            $params['to'] = $filters['to'];
        }
        if (!empty($filters['unit_id'])) {
            $sql .= ' AND a.unit_id = :uid';
            $params['uid'] = (int) $filters['unit_id'];
        }
        if (!empty($filters['direction']) && in_array($filters['direction'], ['in', 'out'], true)) {
            $sql .= ' AND a.direction = :dir';
            $params['dir'] = $filters['direction'];
        }
        if (!empty($filters['result']) && in_array($filters['result'], ['granted', 'denied'], true)) {
            $sql .= ' AND a.result = :res';
            $params['res'] = $filters['result'];
        }
        if (!empty($filters['type'])) {
            $type = (string) $filters['type'];
            if ($type === 'visitor') {
                $sql .= ' AND a.visitor_id IS NOT NULL';
            } elseif ($type === 'resident') {
                $sql .= ' AND a.user_id IS NOT NULL AND a.visitor_id IS NULL';
            }
        }

        $sql .= ' ORDER BY a.id DESC LIMIT :__limit OFFSET :__offset';
        $stmt = $this->pdo->prepare($sql);
        foreach ($params as $k => $v) {
            $stmt->bindValue($k, $v);
        }
        $stmt->bindValue('__limit', $limit, \PDO::PARAM_INT);
        $stmt->bindValue('__offset', $offset, \PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function findInCondo(int $id, int $condominiumId): ?array
    {
        $stmt = $this->pdo->prepare(
            'SELECT a.*, v.name AS visitor_name, u.name AS user_name,
                    un.block, un.number AS unit_number
             FROM access_logs a
             LEFT JOIN visitors v ON v.id = a.visitor_id
             LEFT JOIN users u    ON u.id = a.user_id
             LEFT JOIN units un   ON un.id = a.unit_id
             WHERE a.id = :id AND a.condominium_id = :cid
             LIMIT 1'
        );
        $stmt->execute(['id' => $id, 'cid' => $condominiumId]);
        $row = $stmt->fetch();
        return $row ?: null;
    }
}
