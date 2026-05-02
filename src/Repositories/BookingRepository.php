<?php

declare(strict_types=1);

namespace App\Repositories;

final class BookingRepository extends BaseRepository
{
    protected string $table = 'bookings';

    public function listByCondominium(int $condominiumId, ?string $status = null): array
    {
        $sql = 'SELECT b.*, ca.name AS area_name, u.name AS resident_name
                FROM bookings b
                LEFT JOIN common_areas ca ON ca.id = b.common_area_id
                LEFT JOIN users u ON u.id = b.resident_id
                WHERE b.condominium_id = :cid';
        $params = ['cid' => $condominiumId];
        if ($status !== null) {
            $sql .= ' AND b.status = :status';
            $params['status'] = $status;
        }
        $sql .= ' ORDER BY b.starts_at DESC';
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function listByResident(int $residentId): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT b.*, ca.name AS area_name
             FROM bookings b
             LEFT JOIN common_areas ca ON ca.id = b.common_area_id
             WHERE b.resident_id = :uid
             ORDER BY b.starts_at DESC'
        );
        $stmt->execute(['uid' => $residentId]);
        return $stmt->fetchAll();
    }

    public function hasConflict(int $commonAreaId, string $startsAt, string $endsAt, ?int $ignoreId = null): bool
    {
        $sql = "SELECT COUNT(*) AS c FROM bookings
                WHERE common_area_id = :area
                  AND status IN ('solicitado','aprovado')
                  AND NOT (ends_at <= :s OR starts_at >= :e)";
        $params = ['area' => $commonAreaId, 's' => $startsAt, 'e' => $endsAt];
        if ($ignoreId !== null) {
            $sql .= ' AND id <> :ignore';
            $params['ignore'] = $ignoreId;
        }
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return ((int) ($stmt->fetch()['c'] ?? 0)) > 0;
    }
}
