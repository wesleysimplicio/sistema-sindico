<?php

declare(strict_types=1);

namespace App\Repositories;

final class CameraRepository extends BaseRepository
{
    protected string $table = 'cameras';

    public function listByCondominium(int $condominiumId): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT id, condominium_id, name, location, hls_path, enabled, created_at, updated_at
             FROM cameras
             WHERE condominium_id = :cid
             ORDER BY name ASC'
        );
        $stmt->execute(['cid' => $condominiumId]);
        return $stmt->fetchAll();
    }

    public function findInCondo(int $id, int $condominiumId): ?array
    {
        $stmt = $this->pdo->prepare(
            'SELECT id, condominium_id, name, location, hls_path, enabled, created_at, updated_at
             FROM cameras WHERE id = :id AND condominium_id = :cid LIMIT 1'
        );
        $stmt->execute(['id' => $id, 'cid' => $condominiumId]);
        $row = $stmt->fetch();
        return $row ?: null;
    }
}
