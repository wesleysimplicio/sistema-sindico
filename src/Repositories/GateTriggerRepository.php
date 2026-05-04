<?php

declare(strict_types=1);

namespace App\Repositories;

final class GateTriggerRepository extends BaseRepository
{
    protected string $table = 'gate_triggers';

    public function listByCondominium(int $condominiumId): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT id, condominium_id, name, endpoint_url, timeout_ms, enabled, created_at, updated_at
             FROM gate_triggers
             WHERE condominium_id = :cid
             ORDER BY name ASC'
        );
        $stmt->execute(['cid' => $condominiumId]);
        return $stmt->fetchAll();
    }

    /**
     * Returns the trigger including auth_token and endpoint_url for outbound device call.
     * Use existsInCondo() when only an existence check is needed.
     */
    public function findInCondo(int $id, int $condominiumId): ?array
    {
        $stmt = $this->pdo->prepare(
            'SELECT id, condominium_id, name, endpoint_url, auth_token, timeout_ms, enabled,
                    created_at, updated_at
             FROM gate_triggers WHERE id = :id AND condominium_id = :cid LIMIT 1'
        );
        $stmt->execute(['id' => $id, 'cid' => $condominiumId]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function existsInCondo(int $id, int $condominiumId): bool
    {
        $stmt = $this->pdo->prepare(
            'SELECT 1 FROM gate_triggers WHERE id = :id AND condominium_id = :cid LIMIT 1'
        );
        $stmt->execute(['id' => $id, 'cid' => $condominiumId]);
        return (bool) $stmt->fetchColumn();
    }
}
