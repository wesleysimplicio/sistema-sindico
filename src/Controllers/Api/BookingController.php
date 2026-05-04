<?php

declare(strict_types=1);

namespace App\Controllers\Api;

use App\Core\Auth;
use App\Core\Request;
use App\Core\Response;
use App\Repositories\BookingRepository;
use App\Repositories\CommonAreaRepository;

final class BookingController
{
    private const STATUSES = ['solicitado', 'aprovado', 'rejeitado', 'cancelado', 'concluido'];

    public function index(): void
    {
        $cid = Auth::condominiumId();
        if ($cid === null) {
            Response::error('Condominio nao definido.', 422);
            return;
        }
        $status = $_GET['status'] ?? null;
        $items = (new BookingRepository())->listByCondominium($cid, is_string($status) ? $status : null);
        Response::json($items, 200, ['count' => count($items)]);
    }

    public function mine(): void
    {
        $uid = Auth::id();
        if ($uid === null) {
            Response::error('Nao autenticado.', 401);
            return;
        }
        $items = (new BookingRepository())->listByResident($uid);
        Response::json($items, 200, ['count' => count($items)]);
    }

    public function store(): void
    {
        $cid = Auth::condominiumId();
        $uid = Auth::id();
        $user = Auth::user();
        if ($cid === null || $uid === null || $user === null) {
            Response::error('Nao autenticado.', 401);
            return;
        }
        $areaId = (int) Request::input('common_area_id', 0);
        $startsAt = (string) Request::input('starts_at', '');
        $endsAt   = (string) Request::input('ends_at', '');
        if ($areaId <= 0 || $startsAt === '' || $endsAt === '') {
            Response::error('common_area_id, starts_at, ends_at obrigatorios.', 422);
            return;
        }
        if (strtotime($startsAt) >= strtotime($endsAt)) {
            Response::error('Periodo invalido.', 422);
            return;
        }
        $repo = new BookingRepository();
        if ($repo->hasConflict($areaId, $startsAt, $endsAt)) {
            Response::error('Conflito de horario.', 409);
            return;
        }
        $area = (new CommonAreaRepository())->find($areaId);
        $status = ($area && (int) $area['requires_approval'] === 1) ? 'solicitado' : 'aprovado';
        $id = $repo->create([
            'condominium_id' => $cid,
            'common_area_id' => $areaId,
            'unit_id'        => $user['unit_id'] ?? null,
            'resident_id'    => $uid,
            'starts_at'      => $startsAt,
            'ends_at'        => $endsAt,
            'status'         => $status,
            'notes'          => (string) Request::input('notes', ''),
        ]);
        Response::json(['id' => $id, 'status' => $status], 201);
    }

    public function updateStatus(array $params): void
    {
        $role = Auth::role();
        if (!in_array($role, ['admin', 'sindico'], true)) {
            Response::error('Sem permissao.', 403);
            return;
        }
        $id = (int) ($params['id'] ?? 0);
        $status = (string) Request::input('status', '');
        if (!in_array($status, self::STATUSES, true)) {
            Response::error('Status invalido.', 422, ['allowed' => self::STATUSES]);
            return;
        }
        $ok = (new BookingRepository())->update($id, ['status' => $status]);
        Response::json(['updated' => $ok]);
    }
}
