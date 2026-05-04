<?php

declare(strict_types=1);

namespace App\Repositories;

final class VisitorRepository extends BaseRepository
{
    protected string $table = 'visitors';

    public function listByCondominium(int $condominiumId, ?string $status = null, ?int $limit = null): array
    {
        $sql = 'SELECT v.*, u.name AS host_name, un.block, un.number AS unit_number
                FROM visitors v
                LEFT JOIN users u ON u.id = v.host_id
                LEFT JOIN units un ON un.id = v.unit_id
                WHERE v.condominium_id = :cid';
        $params = ['cid' => $condominiumId];
        if ($status !== null) {
            $sql .= ' AND v.status = :status';
            $params['status'] = $status;
        }
        $sql .= ' ORDER BY v.expected_at DESC';
        if ($limit !== null) {
            $sql .= ' LIMIT ' . (int) $limit;
        }
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function listExpected(int $condominiumId): array
    {
        return $this->listByCondominium($condominiumId, 'previsto');
    }

    public function listRecentAccessEvents(int $condominiumId, int $limit = 10): array
    {
        $sql = "SELECT v.id, v.name, v.document, v.status, v.expected_at,
                       v.entered_at, v.exited_at, v.created_at,
                       u.name AS host_name, un.block, un.number AS unit_number
                FROM visitors v
                LEFT JOIN users u ON u.id = v.host_id
                LEFT JOIN units un ON un.id = v.unit_id
                WHERE v.condominium_id = :cid
                  AND v.status IN ('dentro','saiu')
                ORDER BY COALESCE(v.exited_at, v.entered_at) DESC
                LIMIT " . (int) $limit;
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['cid' => $condominiumId]);
        return $stmt->fetchAll();
    }

    public function findByQr(string $qrToken): ?array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM visitors WHERE qr_token = :token LIMIT 1');
        $stmt->execute(['token' => $qrToken]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function listByHost(int $hostId): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT * FROM visitors WHERE host_id = :uid ORDER BY expected_at DESC'
        );
        $stmt->execute(['uid' => $hostId]);
        return $stmt->fetchAll();
    }

    public function setStatus(int $id, string $status): bool
    {
        $col = match ($status) {
            'dentro' => 'entered_at',
            'saiu'   => 'exited_at',
            default  => null,
        };
        $sql = $col === null
            ? 'UPDATE visitors SET status = :status WHERE id = :id'
            : "UPDATE visitors SET status = :status, $col = NOW() WHERE id = :id";
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute(['id' => $id, 'status' => $status]);
    }
}
