<?php

declare(strict_types=1);

namespace App\Repositories;

final class MaintenanceRepository extends BaseRepository
{
    protected string $table = 'maintenance_requests';

    public function listByCondominium(int $condominiumId, ?string $status = null, ?string $priority = null, ?int $unitId = null): array
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
        if ($priority !== null) {
            $sql .= ' AND m.priority = :priority';
            $params['priority'] = $priority;
        }
        if ($unitId !== null) {
            $sql .= ' AND m.unit_id = :uid';
            $params['uid'] = $unitId;
        }
        $sql .= ' ORDER BY m.created_at DESC';
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function listByUser(int $userId, int $condominiumId): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT * FROM maintenance_requests
             WHERE requester_id = :uid AND condominium_id = :cid
             ORDER BY created_at DESC'
        );
        $stmt->execute(['uid' => $userId, 'cid' => $condominiumId]);
        return $stmt->fetchAll();
    }

    public function findInCondo(int $id, int $condominiumId): ?array
    {
        $stmt = $this->pdo->prepare(
            'SELECT * FROM maintenance_requests WHERE id = :id AND condominium_id = :cid LIMIT 1'
        );
        $stmt->execute(['id' => $id, 'cid' => $condominiumId]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function setStatus(int $id, string $status): bool
    {
        $stmt = $this->pdo->prepare(
            'UPDATE maintenance_requests SET status = :status, updated_at = NOW() WHERE id = :id'
        );
        return $stmt->execute(['id' => $id, 'status' => $status]);
    }
}
