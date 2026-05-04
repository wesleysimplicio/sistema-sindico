<?php

declare(strict_types=1);

namespace App\Controllers\Api;

use App\Core\Auth;
use App\Core\Request;
use App\Core\Response;
use App\Repositories\AuditLogRepository;
use App\Repositories\DeliveryRepository;

final class DeliveryController
{
    public function index(): void
    {
        $cid = Auth::condominiumId();
        if ($cid === null) {
            Response::error('Condominio nao definido.', 422);
            return;
        }
        $status = $_GET['status'] ?? null;
        $unitId = isset($_GET['unit_id']) && $_GET['unit_id'] !== '' ? (int) $_GET['unit_id'] : null;
        $items = (new DeliveryRepository())->listByCondominium(
            $cid,
            is_string($status) ? $status : null,
            $unitId
        );
        Response::json($items, 200, ['count' => count($items)]);
    }

    public function mine(): void
    {
        $uid = Auth::id();
        $cid = Auth::condominiumId();
        if ($uid === null || $cid === null) {
            Response::error('Nao autenticado.', 401);
            return;
        }
        $items = (new DeliveryRepository())->listByResident($uid, $cid);
        Response::json($items, 200, ['count' => count($items)]);
    }

    public function show(array $params): void
    {
        $cid = Auth::condominiumId();
        if ($cid === null) {
            Response::error('Nao autenticado.', 401);
            return;
        }
        $row = (new DeliveryRepository())->findInCondo((int) ($params['id'] ?? 0), $cid);
        if ($row === null) {
            Response::error('Encomenda nao encontrada.', 404);
            return;
        }
        Response::json($row);
    }

    public function store(): void
    {
        $cid  = Auth::condominiumId();
        $uid  = Auth::id();
        $role = Auth::role();
        if ($cid === null || $uid === null) {
            Response::error('Nao autenticado.', 401);
            return;
        }
        if (!in_array($role, ['admin', 'sindico', 'porteiro'], true)) {
            Response::error('Sem permissao.', 403);
            return;
        }
        $residentId = (int) Request::input('resident_id', 0);
        if ($residentId <= 0) {
            Response::error('resident_id obrigatorio.', 422);
            return;
        }
        $unitId = (int) Request::input('unit_id', 0) ?: null;
        $locker = trim((string) Request::input('locker_code', '')) ?: null;

        $id = (new DeliveryRepository())->create([
            'condominium_id'  => $cid,
            'unit_id'         => $unitId,
            'resident_id'     => $residentId,
            'sender'          => (string) Request::input('sender', ''),
            'courier'         => (string) Request::input('courier', ''),
            'tracking_code'   => (string) Request::input('tracking_code', ''),
            'description'     => (string) Request::input('description', ''),
            'status'          => 'aguardando',
            'locker_code'     => $locker,
            'received_by_id'  => $uid,
            'photo_url'       => (string) Request::input('photo_url', '') ?: null,
        ]);
        (new AuditLogRepository())->record(
            $uid,
            $cid,
            'delivery.received',
            'delivery',
            $id,
            ['resident_id' => $residentId, 'locker_code' => $locker],
            Request::ip()
        );
        Response::json(['id' => $id], 201);
    }

    public function withdraw(array $params): void
    {
        $cid  = Auth::condominiumId();
        $user = Auth::user();
        if ($cid === null || $user === null) {
            Response::error('Nao autenticado.', 401);
            return;
        }
        $id = (int) ($params['id'] ?? 0);
        $repo = new DeliveryRepository();
        $row = $repo->findInCondo($id, $cid);
        if ($row === null) {
            Response::error('Encomenda nao encontrada.', 404);
            return;
        }
        if ((string) ($row['status'] ?? '') === 'retirada') {
            Response::error('Encomenda ja retirada.', 409);
            return;
        }
        $role = Auth::role();
        $uid  = (int) $user['id'];
        $isResident = (int) ($row['resident_id'] ?? 0) === $uid;
        if (!$isResident && !in_array($role, ['admin', 'sindico', 'porteiro'], true)) {
            Response::error('Sem permissao.', 403);
            return;
        }

        $withdrawnUserId = (int) Request::input('withdrawn_user_id', 0) ?: null;
        if ($withdrawnUserId === null && $isResident) {
            $withdrawnUserId = $uid;
        }
        $name = (string) Request::input('withdrawn_by', $user['name'] ?? '');
        $ok = $repo->markWithdrawn($id, $name !== '' ? $name : (string) $user['name'], $withdrawnUserId);

        (new AuditLogRepository())->record(
            $uid,
            $cid,
            'delivery.withdrawn',
            'delivery',
            $id,
            ['withdrawn_user_id' => $withdrawnUserId, 'name' => $name],
            Request::ip()
        );
        Response::json(['updated' => $ok]);
    }
}
