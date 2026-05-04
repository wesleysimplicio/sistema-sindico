<?php

declare(strict_types=1);

namespace App\Controllers\Api;

use App\Core\Auth;
use App\Core\Response;
use App\Repositories\CondominiumRepository;
use App\Repositories\UnitRepository;

final class ProfileController
{
    public function show(): void
    {
        $u = Auth::user();
        if ($u === null) {
            Response::error('Nao autenticado.', 401);
            return;
        }
        $unit  = isset($u['unit_id']) && $u['unit_id'] !== null
            ? (new UnitRepository())->find((int) $u['unit_id'])
            : null;
        $condo = isset($u['condominium_id']) && $u['condominium_id'] !== null
            ? (new CondominiumRepository())->find((int) $u['condominium_id'])
            : null;
        Response::json([
            'id'           => (int) $u['id'],
            'name'         => $u['name'],
            'email'        => $u['email'],
            'phone'        => $u['phone']      ?? null,
            'role'         => $u['role'],
            'avatar_url'   => $u['avatar_url'] ?? null,
            'unit'         => $unit,
            'condominium'  => $condo,
        ]);
    }
}
