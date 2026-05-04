<?php

declare(strict_types=1);

namespace App\Repositories;

final class IncidentTypeRepository extends BaseRepository
{
    protected string $table = 'incident_types';

    public function listByCondominium(int $condominiumId): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT id, condominium_id, name, description, created_at
             FROM incident_types
             WHERE condominium_id = :cid
             ORDER BY name ASC'
        );
        $stmt->execute(['cid' => $condominiumId]);
        return $stmt->fetchAll();
    }

    public function findInCondo(int $id, int $condominiumId): ?array
    {
        $stmt = $this->pdo->prepare(
            'SELECT * FROM incident_types WHERE id = :id AND condominium_id = :cid LIMIT 1'
        );
        $stmt->execute(['id' => $id, 'cid' => $condominiumId]);
        $row = $stmt->fetch();
        return $row ?: null;
    }
}
