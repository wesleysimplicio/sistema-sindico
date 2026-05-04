<?php

declare(strict_types=1);

namespace App\Controllers\Api;

use App\Core\Auth;
use App\Core\Response;
use App\Repositories\CommonAreaRepository;

final class CommonAreaController
{
    public function index(): void
    {
        $cid = Auth::condominiumId();
        if ($cid === null) {
            Response::error('Condominio nao definido.', 422);
            return;
        }
        $items = (new CommonAreaRepository())->listByCondominium($cid);
        Response::json($items, 200, ['count' => count($items)]);
    }
}
