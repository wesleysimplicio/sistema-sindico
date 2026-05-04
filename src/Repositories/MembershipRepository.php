<?php

declare(strict_types=1);

namespace App\Repositories;

final class MembershipRepository extends BaseRepository
{
    protected string $table = 'memberships';

    /**
     * Return all memberships for a user, optionally filtered by is_active.
     * Each row is enriched with condominium name and unit block/number.
     *
     * @param int       $userId
     * @param bool|null $isActive null = all, true = active only, false = inactive only
     * @return array<int, array<string, mixed>>
     */
    public function listByUser(int $userId, ?bool $isActive = null): array
    {
        $sql = 'SELECT
                    m.id,
                    m.user_id,
                    m.condominium_id,
                    m.unit_id,
                    m.role,
                    m.is_active,
                    m.created_at,
                    m.updated_at,
                    c.name          AS condominium_name,
                    c.address       AS condominium_address,
                    c.city          AS condominium_city,
                    c.state         AS condominium_state,
                    c.logo_url      AS condominium_logo_url,
                    u.block         AS unit_block,
                    u.number        AS unit_number,
                    u.floor         AS unit_floor
                FROM memberships m
                INNER JOIN condominiums c ON c.id = m.condominium_id
                LEFT  JOIN units        u ON u.id = m.unit_id
                WHERE m.user_id = :user_id';

        $params = ['user_id' => $userId];

        if ($isActive !== null) {
            $sql .= ' AND m.is_active = :is_active';
            $params['is_active'] = $isActive ? 1 : 0;
        }

        $sql .= ' ORDER BY m.id ASC';

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }
}
