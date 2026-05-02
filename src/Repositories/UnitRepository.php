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
}
