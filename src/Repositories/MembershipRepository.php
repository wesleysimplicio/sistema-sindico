<?php

declare(strict_types=1);

namespace App\Repositories;

final class MembershipRepository extends BaseRepository
{
    protected string $table = 'memberships';

    /**
     * Return all active memberships for a user, joined with condominium info.
     */
    public function listByUser(int $userId): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT m.id, m.user_id, m.condominium_id, m.role, m.is_active,
                    c.name AS condominium_name, c.address AS condominium_address,
                    c.city AS condominium_city, c.logo_url AS condominium_logo_url
             FROM memberships m
             JOIN condominiums c ON c.id = m.condominium_id
             WHERE m.user_id = :uid AND m.is_active = 1
             ORDER BY c.name ASC'
        );
        $stmt->execute(['uid' => $userId]);
        return $stmt->fetchAll();
    }

    /**
     * Return a single active membership for (user, condo) pair.
     */
    public function findByUserAndCondominium(int $userId, int $condominiumId): ?array
    {
        $stmt = $this->pdo->prepare(
            'SELECT * FROM memberships
             WHERE user_id = :uid AND condominium_id = :cid AND is_active = 1
             LIMIT 1'
        );
        $stmt->execute(['uid' => $userId, 'cid' => $condominiumId]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    /**
     * Return units accessible to a user inside a condominium.
     * Uses users.unit_id as the primary source (residents table added in Sprint 2).
     */
    public function listUnitsByUserAndCondominium(int $userId, int $condominiumId): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT u.id, u.block, u.number, u.floor, u.type
             FROM units u
             JOIN users us ON us.unit_id = u.id AND us.id = :uid
             WHERE u.condominium_id = :cid
             ORDER BY u.block ASC, u.number ASC'
        );
        $stmt->execute(['uid' => $userId, 'cid' => $condominiumId]);
        return $stmt->fetchAll();
    }
}
