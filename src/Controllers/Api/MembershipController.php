<?php

declare(strict_types=1);

namespace App\Controllers\Api;

use App\Core\Auth;
use App\Core\Jwt;
use App\Core\Request;
use App\Core\Response;
use App\Repositories\MembershipRepository;
use App\Repositories\UnitRepository;
use App\Repositories\UserRepository;

/**
 * Handles multi-tenant membership queries and condo/unit selection.
 *
 * S1-03  GET  /api/memberships                    – list condos this user belongs to
 * S1-03  GET  /api/memberships/{condoId}/units    – list units accessible inside a condo
 * S1-04  POST /api/memberships/select             – reissue JWT scoped to chosen condo+unit
 */
final class MembershipController
{
    /**
     * GET /api/memberships
     * Returns all active memberships (with condominium info) for the authenticated user.
     */
    public function index(): void
    {
        $userId = Auth::id();
        if ($userId === null) {
            Response::error('Nao autenticado.', 401);
            return;
        }

        $memberships = (new MembershipRepository())->listByUser($userId);
        Response::json($memberships, 200, ['count' => count($memberships)]);
    }

    /**
     * GET /api/memberships/{condoId}/units
     * Returns units linked to the authenticated user inside the given condominium.
     *
     * @param array<string,string> $params Route params: condoId
     */
    public function listUnits(array $params = []): void
    {
        $userId = Auth::id();
        if ($userId === null) {
            Response::error('Nao autenticado.', 401);
            return;
        }

        $condoId = isset($params['condoId']) ? (int) $params['condoId'] : 0;
        if ($condoId === 0) {
            Response::error('Condominio invalido.', 422);
            return;
        }

        $repo = new MembershipRepository();

        // Verify user has an active membership in this condo.
        $membership = $repo->findByUserAndCondominium($userId, $condoId);
        if ($membership === null) {
            Response::error('Sem acesso a este condominio.', 403);
            return;
        }

        $units = $repo->listUnitsByUserAndCondominium($userId, $condoId);
        Response::json($units, 200, ['count' => count($units)]);
    }

    /**
     * POST /api/memberships/select
     * Validates membership, then reissues a JWT with condominium_id, unit_id, and role
     * so subsequent calls are tenant-scoped.
     *
     * Body: { condominium_id: int, unit_id?: int }
     */
    public function select(): void
    {
        $userId = Auth::id();
        if ($userId === null) {
            Response::error('Nao autenticado.', 401);
            return;
        }

        $condoId = (int) Request::input('condominium_id', 0);
        if ($condoId === 0) {
            Response::error('condominium_id obrigatorio.', 422);
            return;
        }

        $unitIdRaw = Request::input('unit_id');
        $unitId    = ($unitIdRaw !== null && $unitIdRaw !== '') ? (int) $unitIdRaw : null;

        $repo       = new MembershipRepository();
        $membership = $repo->findByUserAndCondominium($userId, $condoId);
        if ($membership === null) {
            Response::error('Sem acesso a este condominio.', 403);
            return;
        }

        // If a unit was requested, verify it belongs to this condominium.
        if ($unitId !== null) {
            $unit = (new UnitRepository())->find($unitId);
            if ($unit === null || (int) $unit['condominium_id'] !== $condoId) {
                Response::error('Unidade invalida para este condominio.', 422);
                return;
            }
        }

        $user = (new UserRepository())->find($userId);
        if ($user === null) {
            Response::error('Usuario nao encontrado.', 404);
            return;
        }

        $secret = (string) (getenv('JWT_SECRET') ?: 'change-me-in-prod');
        $token  = Jwt::encode([
            'sub'     => $userId,
            'role'    => $membership['role'],
            'cid'     => $condoId,
            'unit_id' => $unitId,
        ], $secret, 86400 * 7);

        Response::json([
            'token'          => $token,
            'expires_in'     => 86400 * 7,
            'condominium_id' => $condoId,
            'unit_id'        => $unitId,
            'role'           => $membership['role'],
        ]);
    }
}
