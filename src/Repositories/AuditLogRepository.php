<?php

declare(strict_types=1);

namespace App\Repositories;

final class AuditLogRepository extends BaseRepository
{
    protected string $table = 'audit_logs';

    public function record(
        ?int $userId,
        ?int $condominiumId,
        string $action,
        ?string $entity = null,
        ?int $entityId = null,
        ?array $payload = null,
        ?string $ip = null
    ): int {
        $stmt = $this->pdo->prepare(
            'INSERT INTO audit_logs (user_id, condominium_id, action, entity, entity_id, payload, ip, created_at)
             VALUES (:uid, :cid, :action, :entity, :eid, :payload, :ip, NOW())'
        );
        $stmt->execute([
            'uid'     => $userId,
            'cid'     => $condominiumId,
            'action'  => $action,
            'entity'  => $entity,
            'eid'     => $entityId,
            'payload' => $payload === null ? null : json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            'ip'      => $ip,
        ]);
        return (int) $this->pdo->lastInsertId();
    }

    public function listByCondominium(int $condominiumId, int $limit = 200): array
    {
        $limit = max(1, min(1000, $limit));
        $stmt = $this->pdo->prepare(
            'SELECT id, user_id, condominium_id, action, entity, entity_id, payload, ip, created_at
             FROM audit_logs
             WHERE condominium_id = :cid
             ORDER BY id DESC
             LIMIT ' . $limit
        );
        $stmt->execute(['cid' => $condominiumId]);
        $rows = $stmt->fetchAll();
        foreach ($rows as &$row) {
            if (isset($row['payload']) && is_string($row['payload']) && $row['payload'] !== '') {
                $decoded = json_decode($row['payload'], true);
                if ($decoded !== null) {
                    $row['payload'] = $decoded;
                }
            }
        }
        unset($row);
        return $rows;
    }
}
