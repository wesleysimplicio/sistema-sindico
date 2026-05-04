<?php

declare(strict_types=1);

namespace App\Controllers\Api;

use App\Core\Auth;
use App\Core\Jwt;
use App\Core\Request;
use App\Core\Response;
use App\Repositories\MembershipRepository;
use App\Repositories\UnitRepository;

final class MembershipController
{
    /**
     * GET /api/memberships
     * Returns all active condominiums this user belongs to, with their role in each.
     */
    public function index(): void
    {
        $uid = Auth::id();
        if ($uid === null) {
            Response::error('Nao autenticado.', 401);
            return;
        }

        $memberships = (new MembershipRepository())->listByUser($uid);
        Response::json($memberships, 200, ['count' => count($memberships)]);
    }

    /**
     * GET /api/memberships/{condoId}/units
     * Returns units inside the given condo that are linked to the authenticated user.
     */
    public function units(array $params): void
    {
        $uid = Auth::id();
        if ($uid === null) {
            Response::error('Nao autenticado.', 401);
            return;
        }

        $condoId = (int) ($params['condoId'] ?? 0);
        if ($condoId === 0) {
            Response::error('condominio_id invalido.', 422);
            return;
        }

        $repo = new MembershipRepository();

        $membership = $repo->findMembership($uid, $condoId);
        if ($membership === null) {
            Response::error('Sem acesso a este condominio.', 403);
            return;
        }

        $units = $repo->listUserUnitsInCondo($uid, $condoId);
        Response::json($units, 200, ['count' => count($units)]);
    }

    /**
     * POST /api/memberships/select
     * Selects condo + optional unit and reissues a scoped JWT.
     * Body: { condominium_id: int, unit_id?: int }
     */
    public function select(): void
    {
        $uid = Auth::id();
        if ($uid === null) {
            Response::error('Nao autenticado.', 401);
            return;
        }

        $condominiumId = (int) Request::input('condominium_id', 0);
        if ($condominiumId === 0) {
            Response::error('condominium_id obrigatorio.', 422);
            return;
        }

        $repo = new MembershipRepository();

        $membership = $repo->findMembership($uid, $condominiumId);
        if ($membership === null) {
            Response::error('Sem acesso a este condominio.', 403);
            return;
        }

        $unitId = null;
        $rawUnit = Request::input('unit_id');
        if ($rawUnit !== null && $rawUnit !== '') {
            $unitId = (int) $rawUnit;
            $unit = (new UnitRepository())->find($unitId);
            if ($unit === null || (int) $unit['condominium_id'] !== $condominiumId) {
                Response::error('Unidade invalida para este condominio.', 422);
                return;
            }
        }

        $secret = (string) (getenv('JWT_SECRET') ?: 'change-me-in-prod');
        $token = Jwt::encode([
            'sub'  => $uid,
            'role' => $membership['role'],
            'cid'  => $condominiumId,
            'uid'  => $unitId,
        ], $secret, 86400 * 7);

        Response::json([
            'token'          => $token,
            'expires_in'     => 86400 * 7,
            'condominium_id' => $condominiumId,
            'unit_id'        => $unitId,
            'role'           => $membership['role'],
        ], 200);
    }
}
