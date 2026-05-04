<?php

declare(strict_types=1);

namespace App\Repositories;

final class DeliveryRepository extends BaseRepository
{
    protected string $table = 'deliveries';

    public function listByCondominium(int $condominiumId, ?string $status = null): array
    {
        $sql = 'SELECT d.*, u.name AS resident_name, un.block, un.number AS unit_number
                FROM deliveries d
                LEFT JOIN users u ON u.id = d.resident_id
                LEFT JOIN units un ON un.id = d.unit_id
                WHERE d.condominium_id = :cid';
        $params = ['cid' => $condominiumId];
        if ($status !== null) {
            $sql .= ' AND d.status = :status';
            $params['status'] = $status;
        }
        $sql .= ' ORDER BY d.received_at DESC';
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function listByResident(int $residentId): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT * FROM deliveries WHERE resident_id = :rid ORDER BY received_at DESC'
        );
        $stmt->execute(['rid' => $residentId]);
        return $stmt->fetchAll();
    }

    public function countToday(int $condominiumId): int
    {
        $stmt = $this->pdo->prepare(
            'SELECT COUNT(*) AS c FROM deliveries
             WHERE condominium_id = :cid AND received_at >= CURDATE()'
        );
        $stmt->execute(['cid' => $condominiumId]);
        return (int) ($stmt->fetch()['c'] ?? 0);
    }

    public function listToday(int $condominiumId): array
    {
        $sql = 'SELECT d.*, u.name AS resident_name, un.block, un.number AS unit_number
                FROM deliveries d
                LEFT JOIN users u ON u.id = d.resident_id
                LEFT JOIN units un ON un.id = d.unit_id
                WHERE d.condominium_id = :cid AND d.received_at >= CURDATE()
                ORDER BY d.received_at DESC';
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['cid' => $condominiumId]);
        return $stmt->fetchAll();
    }

    public function markWithdrawn(int $id, string $withdrawnByName): bool
    {
        $stmt = $this->pdo->prepare(
            "UPDATE deliveries
             SET status = 'retirada', withdrawn_at = NOW(), withdrawn_by = :name
             WHERE id = :id"
        );
        return $stmt->execute(['id' => $id, 'name' => $withdrawnByName]);
    }
}
