<?php

declare(strict_types=1);

namespace App\Controllers\Api;

use App\Core\Auth;
use App\Core\Jwt;
use App\Core\Request;
use App\Core\Response;
use App\Repositories\CondominiumRepository;
use App\Repositories\MembershipRepository;
use App\Repositories\UnitRepository;

final class MembershipController
{
    private const TOKEN_TTL_SECONDS = 86400 * 7;

    public function index(): void
    {
        $user = Auth::user();
        if ($user === null) {
            Response::error('Nao autenticado.', 401);
            return;
        }

        $rows = (new MembershipRepository())->listForUser((int) $user['id']);
        $items = array_map([self::class, 'shapeMembership'], $rows);

        if (empty($items) && !empty($user['condominium_id'])) {
            $items[] = self::synthesizeMembership($user);
        }

        Response::json($items);
    }

    public function select(): void
    {
        $user = Auth::user();
        if ($user === null) {
            Response::error('Nao autenticado.', 401);
            return;
        }

        $condominiumId = (int) Request::input('condominium_id', 0);
        $unitIdInput = Request::input('unit_id');
        $unitId = ($unitIdInput === null || $unitIdInput === '') ? null : (int) $unitIdInput;

        if ($condominiumId <= 0) {
            Response::error('condominium_id e obrigatorio.', 422);
            return;
        }

        $repo = new MembershipRepository();
        $membership = $repo->findActive((int) $user['id'], $condominiumId);

        $role = null;
        if ($membership !== null) {
            $role = (string) $membership['role'];
        } elseif (isset($user['condominium_id']) && (int) $user['condominium_id'] === $condominiumId) {
            $role = (string) $user['role'];
        } else {
            Response::error('Membership nao encontrado para este condominio.', 403);
            return;
        }

        if ($unitId !== null) {
            $unit = (new UnitRepository())->find($unitId);
            if ($unit === null || (int) $unit['condominium_id'] !== $condominiumId) {
                Response::error('Unidade nao pertence ao condominio informado.', 422);
                return;
            }
        }

        $secret = (string) (getenv('JWT_SECRET') ?: 'change-me-in-prod');
        $token = Jwt::encode([
            'sub'  => (int) $user['id'],
            'role' => $role,
            'cid'  => $condominiumId,
            'uid'  => $unitId,
        ], $secret, self::TOKEN_TTL_SECONDS);

        Response::json([
            'token'      => $token,
            'expires_in' => self::TOKEN_TTL_SECONDS,
            'scope'      => [
                'condominium_id' => $condominiumId,
                'unit_id'        => $unitId,
                'role'           => $role,
            ],
        ]);
    }

    private static function shapeMembership(array $row): array
    {
        return [
            'id'                => (int) $row['id'],
            'condominium_id'    => (int) $row['condominium_id'],
            'condominium_name'  => $row['condominium_name'] ?? null,
            'role'              => $row['role'],
            'unit_id'           => isset($row['unit_id']) && $row['unit_id'] !== null
                ? (int) $row['unit_id']
                : null,
            'unit_label'        => self::unitLabel(
                $row['block'] ?? null,
                $row['unit_number'] ?? null,
                $row['unit_id'] ?? null
            ),
        ];
    }

    private static function synthesizeMembership(array $user): array
    {
        $unit = isset($user['unit_id']) && $user['unit_id'] !== null
            ? (new UnitRepository())->find((int) $user['unit_id'])
            : null;
        $condo = (new CondominiumRepository())->find((int) $user['condominium_id']);

        return [
            'id'                => 0,
            'condominium_id'    => (int) $user['condominium_id'],
            'condominium_name'  => $condo['name'] ?? null,
            'role'              => $user['role'],
            'unit_id'           => isset($user['unit_id']) && $user['unit_id'] !== null
                ? (int) $user['unit_id']
                : null,
            'unit_label'        => $unit !== null
                ? self::unitLabel($unit['block'] ?? null, $unit['number'] ?? null, $unit['id'])
                : null,
        ];
    }

    private static function unitLabel(?string $block, ?string $number, mixed $unitId): ?string
    {
        if (empty($unitId)) {
            return null;
        }
        if ($block !== null && $block !== '' && $number !== null && $number !== '') {
            return $block . '-' . $number;
        }
        return $number !== null && $number !== '' ? (string) $number : null;
    }
}
