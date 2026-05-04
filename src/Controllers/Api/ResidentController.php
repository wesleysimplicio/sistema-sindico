<?php

declare(strict_types=1);

namespace App\Controllers\Api;

use App\Core\Auth;
use App\Core\Request;
use App\Core\Response;
use App\Repositories\LoginInvitationRepository;
use App\Repositories\ResidentRepository;
use App\Repositories\UserRepository;

final class ResidentController
{
    private const RELATIONSHIPS = ['owner', 'tenant', 'dependent', 'other'];

    /**
     * Legacy flat list: /api/residents (returns morador users for caller's condo).
     * Kept for backwards compat; sprint 2 prefers scoped unit endpoints below.
     */
    public function index(): void
    {
        $cid = Auth::condominiumId();
        if ($cid === null) {
            Response::error('Condominio nao definido.', 422);
            return;
        }
        $items = (new UserRepository())->listByCondominium($cid, 'morador');
        $items = array_map(static fn(array $u) => [
            'id'          => (int) $u['id'],
            'name'        => $u['name'],
            'email'       => $u['email'],
            'phone'       => $u['phone'] ?? null,
            'unit_id'     => isset($u['unit_id']) ? (int) $u['unit_id'] : null,
            'block'       => $u['block'] ?? null,
            'unit_number' => $u['unit_number'] ?? null,
            'active'      => (bool) ($u['active'] ?? false),
        ], $items);
        Response::json($items, 200, ['count' => count($items)]);
    }

    public function unitIndex(array $params): void
    {
        $condoId = (int) ($params['c'] ?? 0);
        $unitId  = (int) ($params['u'] ?? 0);
        if (!self::guardRead($condoId)) {
            return;
        }
        $items = (new ResidentRepository())->allByUnit($condoId, $unitId);
        Response::json($items, 200, ['count' => count($items)]);
    }

    public function unitStore(array $params): void
    {
        $condoId = (int) ($params['c'] ?? 0);
        $unitId  = (int) ($params['u'] ?? 0);
        if (!self::guardWrite($condoId, $unitId)) {
            return;
        }

        $fullName = trim((string) Request::input('full_name', ''));
        if ($fullName === '') {
            Response::error('Nome completo obrigatorio.', 422);
            return;
        }
        $relationship = (string) Request::input('relationship', 'owner');
        if (!in_array($relationship, self::RELATIONSHIPS, true)) {
            Response::error('Relacionamento invalido.', 422, ['allowed' => self::RELATIONSHIPS]);
            return;
        }

        $data = [
            'full_name'      => $fullName,
            'document'       => self::nullableStr(Request::input('document')),
            'birth_date'     => self::nullableStr(Request::input('birth_date')),
            'relationship'   => $relationship,
            'is_responsible' => (int) (bool) Request::input('is_responsible', 0),
        ];

        $residentId = (new ResidentRepository())->createForUnit($condoId, $unitId, $data);

        $invitation = null;
        $inviteLogin = (bool) Request::input('invite_login', false);
        $email = self::nullableStr(Request::input('email'));
        if ($inviteLogin && $email !== null) {
            $token = bin2hex(random_bytes(32));
            $expiresAt = date('Y-m-d H:i:s', time() + 7 * 24 * 3600);
            $author = Auth::user();
            $createdBy = $author !== null ? (int) $author['id'] : 0;
            $inviteId = (new LoginInvitationRepository())->createInvite([
                'condominium_id'      => $condoId,
                'unit_id'             => $unitId,
                'email'               => $email,
                'phone'               => self::nullableStr(Request::input('phone')),
                'full_name'           => $fullName,
                'document'            => $data['document'],
                'role'                => 'morador',
                'token'               => $token,
                'expires_at'          => $expiresAt,
                'created_by_user_id'  => $createdBy,
            ]);
            $invitation = [
                'id'         => $inviteId,
                'token'      => $token,
                'expires_at' => $expiresAt,
            ];
        }

        Response::json([
            'id'         => $residentId,
            'invitation' => $invitation,
        ], 201);
    }

    public function unitDestroy(array $params): void
    {
        $condoId = (int) ($params['c'] ?? 0);
        $unitId  = (int) ($params['u'] ?? 0);
        $rid     = (int) ($params['rid'] ?? 0);
        if (!self::guardWrite($condoId, $unitId)) {
            return;
        }
        $repo = new ResidentRepository();
        if ($repo->findScoped($condoId, $unitId, $rid) === null) {
            Response::error('Residente nao encontrado.', 404);
            return;
        }
        $repo->delete($rid);
        Response::json(['deleted' => true]);
    }

    private static function guardRead(int $condoId): bool
    {
        $user = Auth::user();
        if ($user === null) {
            Response::error('Nao autenticado.', 401);
            return false;
        }
        if (!self::canAccessCondo($user, $condoId)) {
            Response::error('Forbidden', 403);
            return false;
        }
        return true;
    }

    private static function guardWrite(int $condoId, int $unitId): bool
    {
        $user = Auth::user();
        if ($user === null) {
            Response::error('Nao autenticado.', 401);
            return false;
        }
        if (!self::canAccessCondo($user, $condoId)) {
            Response::error('Forbidden', 403);
            return false;
        }
        $role = $user['role'] ?? null;
        if ($role === 'porteiro') {
            Response::error('Sem permissao.', 403);
            return false;
        }
        if ($role === 'morador') {
            $userUnit = isset($user['unit_id']) ? (int) $user['unit_id'] : 0;
            if ($userUnit !== $unitId) {
                Response::error('Sem permissao.', 403);
                return false;
            }
        }
        return true;
    }

    private static function canAccessCondo(array $user, int $condoId): bool
    {
        if (($user['role'] ?? null) === 'admin') {
            return true;
        }
        $userCondo = isset($user['condominium_id']) ? (int) $user['condominium_id'] : 0;
        return $userCondo === $condoId;
    }

    private static function nullableStr(mixed $v): ?string
    {
        if ($v === null) {
            return null;
        }
        $v = trim((string) $v);
        return $v === '' ? null : $v;
    }
}
