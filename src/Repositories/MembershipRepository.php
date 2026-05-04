<?php

declare(strict_types=1);

namespace App\Repositories;

final class MembershipRepository extends BaseRepository
{
    protected string $table = 'memberships';

    public function listForUser(int $userId): array
    {
        $sql = 'SELECT m.id, m.condominium_id, c.name AS condominium_name,
                       m.role, m.unit_id, un.block, un.number AS unit_number
                FROM memberships m
                INNER JOIN condominiums c ON c.id = m.condominium_id
                LEFT JOIN units un ON un.id = m.unit_id
                WHERE m.user_id = :uid AND m.is_active = 1
                ORDER BY m.id ASC';
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['uid' => $userId]);
        return $stmt->fetchAll();
    }

    public function findActive(int $userId, int $condominiumId): ?array
    {
        $sql = 'SELECT * FROM memberships
                WHERE user_id = :uid AND condominium_id = :cid AND is_active = 1
                ORDER BY id ASC LIMIT 1';
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['uid' => $userId, 'cid' => $condominiumId]);
        $row = $stmt->fetch();
        return $row ?: null;
    }
}
