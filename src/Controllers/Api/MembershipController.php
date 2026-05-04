<?php

declare(strict_types=1);

namespace App\Controllers\Api;

use App\Core\Auth;
use App\Core\Response;
use App\Repositories\MembershipRepository;

final class MembershipController
{
    /**
     * GET /api/memberships
     *
     * Returns all condominiums the authenticated user belongs to,
     * with their role per condominium.
     *
     * Query params:
     *   ?is_active=1   – only active memberships (default: all)
     *   ?is_active=0   – only inactive memberships
     */
    public function index(): void
    {
        $userId = Auth::id();
        if ($userId === null) {
            Response::error('Nao autenticado.', 401);
            return;
        }

        $isActive = null;
        if (isset($_GET['is_active']) && $_GET['is_active'] !== '') {
            $isActive = (bool) (int) $_GET['is_active'];
        }

        $rows = (new MembershipRepository())->listByUser($userId, $isActive);

        $data = array_map(static fn(array $row): array => [
            'id'           => (int) $row['id'],
            'role'         => $row['role'],
            'is_active'    => (bool) $row['is_active'],
            'unit_id'      => $row['unit_id'] !== null ? (int) $row['unit_id'] : null,
            'unit'         => $row['unit_id'] !== null ? [
                'block'  => $row['unit_block'],
                'number' => $row['unit_number'],
                'floor'  => $row['unit_floor'],
            ] : null,
            'condominium'  => [
                'id'       => (int) $row['condominium_id'],
                'name'     => $row['condominium_name'],
                'address'  => $row['condominium_address'],
                'city'     => $row['condominium_city'],
                'state'    => $row['condominium_state'],
                'logo_url' => $row['condominium_logo_url'],
            ],
            'created_at'   => $row['created_at'],
            'updated_at'   => $row['updated_at'],
        ], $rows);

        Response::json($data, 200, ['count' => count($data)]);
    }
}
