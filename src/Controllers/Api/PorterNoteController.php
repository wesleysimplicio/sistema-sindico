<?php

declare(strict_types=1);

namespace App\Controllers\Api;

use App\Core\Auth;
use App\Core\Request;
use App\Core\Response;
use App\Repositories\PorterNoteRepository;

final class PorterNoteController
{
    public function index(array $params): void
    {
        $condoId = (int) ($params['c'] ?? 0);
        $user = Auth::user();
        if ($user === null) {
            Response::error('Nao autenticado.', 401);
            return;
        }
        if (!self::canAccessCondo($user, $condoId)) {
            Response::error('Forbidden', 403);
            return;
        }
        $role = $user['role'] ?? null;
        if (!in_array($role, ['porteiro', 'sindico', 'admin'], true)) {
            Response::error('Sem permissao.', 403);
            return;
        }
        $unitId = isset($_GET['unit_id']) && $_GET['unit_id'] !== '' ? (int) $_GET['unit_id'] : null;
        $rows = (new PorterNoteRepository())->listByCondominium($condoId, $unitId);
        Response::json($rows, 200, ['count' => count($rows)]);
    }

    public function store(array $params): void
    {
        $condoId = (int) ($params['c'] ?? 0);
        $user = Auth::user();
        if ($user === null) {
            Response::error('Nao autenticado.', 401);
            return;
        }
        if (!self::canAccessCondo($user, $condoId)) {
            Response::error('Forbidden', 403);
            return;
        }
        $role = $user['role'] ?? null;
        if (!in_array($role, ['porteiro', 'sindico', 'admin'], true)) {
            Response::error('Sem permissao.', 403);
            return;
        }
        $body = trim((string) Request::input('body', ''));
        if ($body === '') {
            Response::error('Conteudo obrigatorio.', 422);
            return;
        }
        if (strlen($body) > 2000) {
            Response::error('Conteudo excede 2000 caracteres.', 422);
            return;
        }
        $unitIdRaw = Request::input('unit_id');
        $unitId = ($unitIdRaw === null || $unitIdRaw === '') ? null : (int) $unitIdRaw;

        $id = (new PorterNoteRepository())->create([
            'condominium_id' => $condoId,
            'unit_id'        => $unitId,
            'author_user_id' => (int) $user['id'],
            'body'           => $body,
        ]);
        Response::json(['id' => $id], 201);
    }

    private static function canAccessCondo(array $user, int $condoId): bool
    {
        if (($user['role'] ?? null) === 'admin') {
            return true;
        }
        $userCondo = isset($user['condominium_id']) ? (int) $user['condominium_id'] : 0;
        return $userCondo === $condoId;
    }
}
