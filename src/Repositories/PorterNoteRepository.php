<?php

declare(strict_types=1);

namespace App\Repositories;

final class PorterNoteRepository extends BaseRepository
{
    protected string $table = 'porter_notes';

    public function listByCondominium(int $condoId, ?int $unitId = null, int $limit = 200): array
    {
        $sql = 'SELECT pn.*, u.name AS author_name
                FROM porter_notes pn
                LEFT JOIN users u ON u.id = pn.author_user_id
                WHERE pn.condominium_id = :cid';
        $params = ['cid' => $condoId];
        if ($unitId !== null) {
            $sql .= ' AND pn.unit_id = :uid';
            $params['uid'] = $unitId;
        }
        $sql .= ' ORDER BY pn.created_at DESC LIMIT ' . (int) $limit;
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function lastForUnit(int $condoId, int $unitId, int $limit = 5): array
    {
        $sql = 'SELECT pn.*, u.name AS author_name
                FROM porter_notes pn
                LEFT JOIN users u ON u.id = pn.author_user_id
                WHERE pn.condominium_id = :cid AND pn.unit_id = :uid
                ORDER BY pn.created_at DESC
                LIMIT ' . (int) $limit;
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['cid' => $condoId, 'uid' => $unitId]);
        return $stmt->fetchAll();
    }
}
