<?php

declare(strict_types=1);

namespace App\Repositories;

final class PaymentRepository extends BaseRepository
{
    protected string $table = 'payments';

    public function listByCondominium(int $condominiumId, ?string $status = null): array
    {
        $sql = 'SELECT p.*, u.name AS resident_name, un.block, un.number AS unit_number
                FROM payments p
                LEFT JOIN users u ON u.id = p.resident_id
                LEFT JOIN units un ON un.id = p.unit_id
                WHERE p.condominium_id = :cid';
        $params = ['cid' => $condominiumId];
        if ($status !== null) {
            $sql .= ' AND p.status = :status';
            $params['status'] = $status;
        }
        $sql .= ' ORDER BY p.due_date DESC';
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function listByResident(int $residentId): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT * FROM payments WHERE resident_id = :rid ORDER BY due_date DESC'
        );
        $stmt->execute(['rid' => $residentId]);
        return $stmt->fetchAll();
    }

    public function markPaid(int $id, int $condominiumId): bool
    {
        $stmt = $this->pdo->prepare(
            "UPDATE payments SET status = 'pago', paid_at = NOW()
             WHERE id = :id AND condominium_id = :cid"
        );
        $stmt->execute(['id' => $id, 'cid' => $condominiumId]);
        return $stmt->rowCount() > 0;
    }

    public function summaryByCondominium(int $condominiumId): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT status, COUNT(*) AS total, SUM(amount) AS amount
             FROM payments
             WHERE condominium_id = :cid
             GROUP BY status'
        );
        $stmt->execute(['cid' => $condominiumId]);
        return $stmt->fetchAll();
    }
}
