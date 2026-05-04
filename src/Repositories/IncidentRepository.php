<?php

declare(strict_types=1);

namespace App\Repositories;

final class IncidentRepository extends BaseRepository
{
    protected string $table = 'incidents';

    public function listByCondominium(int $condominiumId, ?string $status = null, ?int $typeId = null): array
    {
        $sql = 'SELECT i.id, i.condominium_id, i.incident_type_id, i.reporter_id, i.unit_id,
                       i.title, i.status, i.occurred_at, i.created_at, i.updated_at,
                       it.name AS type_name, u.name AS reporter_name, un.block, un.number AS unit_number
                FROM incidents i
                LEFT JOIN incident_types it ON it.id = i.incident_type_id
                LEFT JOIN users u ON u.id = i.reporter_id
                LEFT JOIN units un ON un.id = i.unit_id
                WHERE i.condominium_id = :cid';
        $params = ['cid' => $condominiumId];
        if ($status !== null) {
            $sql .= ' AND i.status = :status';
            $params['status'] = $status;
        }
        if ($typeId !== null) {
            $sql .= ' AND i.incident_type_id = :tid';
            $params['tid'] = $typeId;
        }
        $sql .= ' ORDER BY i.id DESC LIMIT 200';
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function findInCondo(int $id, int $condominiumId): ?array
    {
        $stmt = $this->pdo->prepare(
            'SELECT i.*, it.name AS type_name, u.name AS reporter_name,
                    un.block, un.number AS unit_number
             FROM incidents i
             LEFT JOIN incident_types it ON it.id = i.incident_type_id
             LEFT JOIN users u ON u.id = i.reporter_id
             LEFT JOIN units un ON un.id = i.unit_id
             WHERE i.id = :id AND i.condominium_id = :cid
             LIMIT 1'
        );
        $stmt->execute(['id' => $id, 'cid' => $condominiumId]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function setStatus(int $id, string $status, int $condominiumId): bool
    {
        $stmt = $this->pdo->prepare(
            'UPDATE incidents SET status = :status, updated_at = NOW()
             WHERE id = :id AND condominium_id = :cid'
        );
        $stmt->execute(['id' => $id, 'status' => $status, 'cid' => $condominiumId]);
        return $stmt->rowCount() > 0;
    }
}
