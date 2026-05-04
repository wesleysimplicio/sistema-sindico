<?php

declare(strict_types=1);

namespace App\Repositories;

final class VehicleRepository extends BaseRepository
{
    protected string $table = 'vehicles';

    public function allByUnit(int $condoId, int $unitId): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT * FROM vehicles
             WHERE condominium_id = :cid AND unit_id = :uid
             ORDER BY plate ASC'
        );
        $stmt->execute(['cid' => $condoId, 'uid' => $unitId]);
        return $stmt->fetchAll();
    }

    public function findScoped(int $condoId, int $unitId, int $id): ?array
    {
        $stmt = $this->pdo->prepare(
            'SELECT * FROM vehicles
             WHERE id = :id AND condominium_id = :cid AND unit_id = :uid
             LIMIT 1'
        );
        $stmt->execute(['id' => $id, 'cid' => $condoId, 'uid' => $unitId]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function createForUnit(int $condoId, int $unitId, array $data): int
    {
        $payload = array_merge($data, [
            'condominium_id' => $condoId,
            'unit_id'        => $unitId,
        ]);
        return $this->create($payload);
    }
}
