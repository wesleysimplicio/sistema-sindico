<?php

declare(strict_types=1);

namespace App\Repositories;

final class VisitorRepository extends BaseRepository
{
    protected string $table = 'visitors';

    public function listByCondominium(int $condominiumId, ?string $status = null): array
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
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function findLatestForUnit(int $condoId, int $unitId): ?array
    {
        $stmt = $this->pdo->prepare(
            'SELECT id, name, document, status, expected_at, created_at
             FROM visitors
             WHERE condominium_id = :cid AND unit_id = :uid
             ORDER BY created_at DESC
             LIMIT 1'
        );
        $stmt->execute(['cid' => $condoId, 'uid' => $unitId]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function findValidByQr(string $qrToken, int $condominiumId): ?array
    {
        $stmt = $this->pdo->prepare(
            'SELECT id, condominium_id, unit_id, host_id, name, document, status,
                    expected_at, qr_expires_at, photo_url
             FROM visitors
             WHERE qr_token = :token
               AND condominium_id = :cid
               AND (qr_expires_at IS NULL OR qr_expires_at > NOW())
             LIMIT 1'
        );
        $stmt->execute(['token' => $qrToken, 'cid' => $condominiumId]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function refreshQr(int $id, string $token, string $expiresAt): bool
    {
        $stmt = $this->pdo->prepare(
            'UPDATE visitors
             SET qr_token = :token, qr_expires_at = :exp
             WHERE id = :id'
        );
        return $stmt->execute(['id' => $id, 'token' => $token, 'exp' => $expiresAt]);
    }

    public function listHistory(int $condominiumId, int $limit = 100): array
    {
        $limit = max(1, min(500, $limit));
        $stmt = $this->pdo->prepare(
            'SELECT v.id, v.name, v.document, v.status, v.expected_at, v.entered_at,
                    v.exited_at, v.photo_url, v.created_at,
                    un.block, un.number AS unit_number
             FROM visitors v
             LEFT JOIN units un ON un.id = v.unit_id
             WHERE v.condominium_id = :cid
               AND v.status IN ("saiu", "expirado", "negado")
             ORDER BY COALESCE(v.exited_at, v.created_at) DESC
             LIMIT ' . $limit
        );
        $stmt->execute(['cid' => $condominiumId]);
        return $stmt->fetchAll();
    }

    public function listByHost(int $hostId, int $condominiumId, int $limit = 200): array
    {
        $limit = max(1, min(500, $limit));
        $stmt = $this->pdo->prepare(
            'SELECT * FROM visitors
             WHERE host_id = :uid AND condominium_id = :cid
             ORDER BY expected_at DESC
             LIMIT ' . $limit
        );
        $stmt->execute(['uid' => $hostId, 'cid' => $condominiumId]);
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
