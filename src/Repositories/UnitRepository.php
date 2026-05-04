<?php

declare(strict_types=1);

namespace App\Repositories;

final class UnitRepository extends BaseRepository
{
    protected string $table = 'units';

    public function listByCondominium(int $condominiumId): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT * FROM units WHERE condominium_id = :cid ORDER BY block ASC, number ASC'
        );
        $stmt->execute(['cid' => $condominiumId]);
        return $stmt->fetchAll();
    }

    public function findInCondo(int $unitId, int $condoId): ?array
    {
        $stmt = $this->pdo->prepare(
            'SELECT id FROM units WHERE id = :u AND condominium_id = :c LIMIT 1'
        );
        $stmt->execute(['u' => $unitId, 'c' => $condoId]);
        $row = $stmt->fetch();
        return $row ?: null;
    }
}
