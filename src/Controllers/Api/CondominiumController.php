<?php

declare(strict_types=1);

namespace App\Controllers\Api;

use App\Core\Auth;
use App\Core\Response;
use App\Repositories\CondominiumRepository;

final class CondominiumController
{
    public function index(): void
    {
        $cid = Auth::condominiumId();
        $repo = new CondominiumRepository();
        if ($cid !== null) {
            $row = $repo->find($cid);
            $list = $row ? [$row] : [];
        } else {
            $list = $repo->all();
        }
        Response::json($list, 200, ['count' => count($list)]);
    }

    public function show(array $params): void
    {
        $id = (int) ($params['id'] ?? 0);
        $row = (new CondominiumRepository())->find($id);
        if ($row === null) {
            Response::error('Condominio nao encontrado.', 404);
            return;
        }
        Response::json($row);
    }
}
