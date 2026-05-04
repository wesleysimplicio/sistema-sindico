<?php

declare(strict_types=1);

namespace App\Repositories;

final class MembershipRepository extends BaseRepository
{
    protected string $table = 'memberships';

    /**
     * List all active memberships for a user, joined with condominium details.
     */
    public function listByUser(int $userId): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT m.id, m.user_id, m.condominium_id, m.role, m.is_active,
                    c.name AS condominium_name, c.address, c.city, c.logo_url
             FROM memberships m
             JOIN condominiums c ON c.id = m.condominium_id
             WHERE m.user_id = :uid AND m.is_active = 1
             ORDER BY c.name ASC'
        );
        $stmt->execute(['uid' => $userId]);
        return $stmt->fetchAll();
    }

    /**
     * Find a single active membership for (user, condo) pair.
     * When the user holds multiple roles in the same condo the highest-privilege
     * role is returned (admin > sindico > porteiro > morador).
     */
    public function findMembership(int $userId, int $condominiumId): ?array
    {
        $stmt = $this->pdo->prepare(
            "SELECT * FROM memberships
             WHERE user_id = :uid AND condominium_id = :cid AND is_active = 1
             ORDER BY FIELD(role, 'admin', 'sindico', 'porteiro', 'morador') ASC
             LIMIT 1"
        );
        $stmt->execute(['uid' => $userId, 'cid' => $condominiumId]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    /**
     * List units in a condo that are linked to the given user.
     * Uses users.unit_id for now; will be extended to the residents table in Sprint 2.
     */
    public function listUserUnitsInCondo(int $userId, int $condominiumId): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT u.*
             FROM units u
             JOIN users usr ON usr.unit_id = u.id
             WHERE usr.id = :uid AND u.condominium_id = :cid
             ORDER BY u.block ASC, u.number ASC'
        );
        $stmt->execute(['uid' => $userId, 'cid' => $condominiumId]);
        return $stmt->fetchAll();
    }
}
