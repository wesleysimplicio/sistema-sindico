<?php

declare(strict_types=1);

namespace App\Repositories;

final class InvitationRepository extends BaseRepository
{
    protected string $table = 'invitations';

    public function listByCondominium(int $condominiumId, ?int $hostUserId = null, int $limit = 100): array
    {
        $limit = max(1, min(500, $limit));
        $sql = 'SELECT i.id, i.condominium_id, i.unit_id, i.host_user_id, i.title,
                       i.starts_at, i.ends_at, i.notes, i.status, i.created_at, i.updated_at,
                       u.name AS host_name, un.block, un.number AS unit_number
                FROM invitations i
                LEFT JOIN users u ON u.id = i.host_user_id
                LEFT JOIN units un ON un.id = i.unit_id
                WHERE i.condominium_id = :cid';
        $params = ['cid' => $condominiumId];
        if ($hostUserId !== null) {
            $sql .= ' AND i.host_user_id = :uid';
            $params['uid'] = $hostUserId;
        }
        $sql .= ' ORDER BY i.starts_at DESC LIMIT ' . $limit;
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function findInCondo(int $id, int $condominiumId): ?array
    {
        $stmt = $this->pdo->prepare(
            'SELECT * FROM invitations WHERE id = :id AND condominium_id = :cid LIMIT 1'
        );
        $stmt->execute(['id' => $id, 'cid' => $condominiumId]);
        $row = $stmt->fetch();
        return $row ?: null;
    }
}
