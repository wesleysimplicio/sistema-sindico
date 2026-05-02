<?php

declare(strict_types=1);

namespace App\Repositories;

final class CommonAreaRepository extends BaseRepository
{
    protected string $table = 'common_areas';

    public function listByCondominium(int $condominiumId): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT * FROM common_areas WHERE condominium_id = :cid ORDER BY name ASC'
        );
        $stmt->execute(['cid' => $condominiumId]);
        return $stmt->fetchAll();
    }
}
