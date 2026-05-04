<?php

declare(strict_types=1);

namespace App\Repositories;

final class MaintenanceRepository extends BaseRepository
{
    protected string $table = 'maintenance_requests';

    public function listByCondominium(int $condominiumId, ?string $status = null): array
    {
        $sql = 'SELECT m.*, u.name AS requester_name, un.block, un.number AS unit_number
                FROM maintenance_requests m
                LEFT JOIN users u ON u.id = m.requester_id
                LEFT JOIN units un ON un.id = m.unit_id
                WHERE m.condominium_id = :cid';
        $params = ['cid' => $condominiumId];
        if ($status !== null) {
            $sql .= ' AND m.status = :status';
            $params['status'] = $status;
        }
        $sql .= ' ORDER BY m.created_at DESC';
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function listByUser(int $userId): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT * FROM maintenance_requests WHERE requester_id = :uid ORDER BY created_at DESC'
        );
        $stmt->execute(['uid' => $userId]);
        return $stmt->fetchAll();
    }

    /**
     * Count open (aberto / em_andamento) requests submitted by a specific user in a condominium.
     */
    public function countOpenByRequester(int $userId, int $condominiumId): int
    {
        $stmt = $this->pdo->prepare(
            "SELECT COUNT(*) AS c FROM maintenance_requests
             WHERE requester_id = :uid
               AND condominium_id = :cid
               AND status IN ('aberto','em_andamento')"
        );
        $stmt->execute(['uid' => $userId, 'cid' => $condominiumId]);
        return (int) ($stmt->fetch()['c'] ?? 0);
    }

    public function setStatus(int $id, string $status): bool
    {
        $stmt = $this->pdo->prepare(
            'UPDATE maintenance_requests SET status = :status, updated_at = NOW() WHERE id = :id'
        );
        return $stmt->execute(['id' => $id, 'status' => $status]);
    }
}
