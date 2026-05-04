<?php

declare(strict_types=1);

namespace App\Controllers\Api;

use App\Core\Auth;
use App\Core\Request;
use App\Core\Response;
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
        $items = (new DeliveryRepository())->listByCondominium($cid, is_string($status) ? $status : null);
        Response::json($items, 200, ['count' => count($items)]);
    }

    public function mine(): void
    {
        $uid = Auth::id();
        if ($uid === null) {
            Response::error('Nao autenticado.', 401);
            return;
        }
        $items = (new DeliveryRepository())->listByResident($uid);
        Response::json($items, 200, ['count' => count($items)]);
    }

    public function store(): void
    {
        $cid = Auth::condominiumId();
        $role = Auth::role();
        if ($cid === null) {
            Response::error('Condominio nao definido.', 422);
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
        $id = (new DeliveryRepository())->create([
            'condominium_id' => $cid,
            'unit_id'        => (int) Request::input('unit_id', 0) ?: null,
            'resident_id'    => $residentId,
            'sender'         => (string) Request::input('sender', ''),
            'courier'        => (string) Request::input('courier', ''),
            'tracking_code'  => (string) Request::input('tracking_code', ''),
            'description'    => (string) Request::input('description', ''),
            'status'         => 'aguardando',
        ]);
        Response::json(['id' => $id], 201);
    }

    public function withdraw(array $params): void
    {
        $user = Auth::user();
        if ($user === null) {
            Response::error('Nao autenticado.', 401);
            return;
        }
        $id = (int) ($params['id'] ?? 0);
        $ok = (new DeliveryRepository())->markWithdrawn($id, (string) $user['name']);
        Response::json(['updated' => $ok]);
    }
}
