<?php

declare(strict_types=1);

namespace App\Controllers\Api;

use App\Core\Auth;
use App\Core\Response;
use App\Repositories\UserRepository;

final class ResidentController
{
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
}
