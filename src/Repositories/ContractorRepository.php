<?php

declare(strict_types=1);

namespace App\Repositories;

final class ContractorRepository extends BaseRepository
{
    protected string $table = 'contractors';

    public function allByUnit(int $condoId, int $unitId): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT * FROM contractors
             WHERE condominium_id = :cid AND unit_id = :uid
             ORDER BY created_at DESC'
        );
        $stmt->execute(['cid' => $condoId, 'uid' => $unitId]);
        return $stmt->fetchAll();
    }

    public function findScoped(int $condoId, int $unitId, int $id): ?array
    {
        $stmt = $this->pdo->prepare(
            'SELECT * FROM contractors
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

    /**
     * Soft-sweep: bulk-update rows whose access window has closed.
     * Idempotent; safe to call once per controller GET.
     */
    public function markExpired(int $condoId): int
    {
        $stmt = $this->pdo->prepare(
            "UPDATE contractors
             SET status = 'expired'
             WHERE condominium_id = :cid
               AND access_ends_at IS NOT NULL
               AND access_ends_at < CURDATE()
               AND status NOT IN ('expired','revoked')"
        );
        $stmt->execute(['cid' => $condoId]);
        return $stmt->rowCount();
    }

    public function setStatus(int $id, string $status): bool
    {
        $stmt = $this->pdo->prepare(
            'UPDATE contractors SET status = :status WHERE id = :id'
        );
        return $stmt->execute(['id' => $id, 'status' => $status]);
    }
}
