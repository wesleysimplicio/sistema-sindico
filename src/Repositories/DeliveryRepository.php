<?php

declare(strict_types=1);

namespace App\Repositories;

final class DeliveryRepository extends BaseRepository
{
    protected string $table = 'deliveries';

    public function listByCondominium(int $condominiumId, ?string $status = null, ?int $unitId = null): array
    {
        $sql = 'SELECT d.*, u.name AS resident_name, un.block, un.number AS unit_number,
                       rb.name AS received_by_name, wu.name AS withdrawn_user_name
                FROM deliveries d
                LEFT JOIN users u ON u.id = d.resident_id
                LEFT JOIN units un ON un.id = d.unit_id
                LEFT JOIN users rb ON rb.id = d.received_by_id
                LEFT JOIN users wu ON wu.id = d.withdrawn_user_id
                WHERE d.condominium_id = :cid';
        $params = ['cid' => $condominiumId];
        if ($status !== null) {
            $sql .= ' AND d.status = :status';
            $params['status'] = $status;
        }
        if ($unitId !== null) {
            $sql .= ' AND d.unit_id = :uid';
            $params['uid'] = $unitId;
        }
        $sql .= ' ORDER BY d.received_at DESC';
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function findInCondo(int $id, int $condominiumId): ?array
    {
        $stmt = $this->pdo->prepare(
            'SELECT * FROM deliveries WHERE id = :id AND condominium_id = :cid LIMIT 1'
        );
        $stmt->execute(['id' => $id, 'cid' => $condominiumId]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function findLatestForUnit(int $condoId, int $unitId): ?array
    {
        $stmt = $this->pdo->prepare(
            'SELECT id, sender, courier, status, received_at
             FROM deliveries
             WHERE condominium_id = :cid AND unit_id = :uid
             ORDER BY received_at DESC
             LIMIT 1'
        );
        $stmt->execute(['cid' => $condoId, 'uid' => $unitId]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function listByResident(int $residentId, int $condominiumId): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT * FROM deliveries
             WHERE resident_id = :rid AND condominium_id = :cid
             ORDER BY received_at DESC'
        );
        $stmt->execute(['rid' => $residentId, 'cid' => $condominiumId]);
        return $stmt->fetchAll();
    }

    public function markWithdrawn(int $id, string $withdrawnByName, ?int $withdrawnUserId = null): bool
    {
        $stmt = $this->pdo->prepare(
            "UPDATE deliveries
             SET status = 'retirada',
                 withdrawn_at = NOW(),
                 withdrawn_by = :name,
                 withdrawn_user_id = :wuid
             WHERE id = :id"
        );
        return $stmt->execute([
            'id'   => $id,
            'name' => $withdrawnByName,
            'wuid' => $withdrawnUserId,
        ]);
    }
}
