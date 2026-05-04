<?php

declare(strict_types=1);

namespace App\Controllers\Api;

use App\Core\Auth;
use App\Core\Response;
use App\Repositories\PaymentRepository;

final class PaymentController
{
    public function index(): void
    {
        $cid = Auth::condominiumId();
        if ($cid === null) {
            Response::error('Condominio nao definido.', 422);
            return;
        }
        $status = $_GET['status'] ?? null;
        $items = (new PaymentRepository())->listByCondominium($cid, is_string($status) ? $status : null);
        Response::json($items, 200, ['count' => count($items)]);
    }

    public function mine(): void
    {
        $uid = Auth::id();
        if ($uid === null) {
            Response::error('Nao autenticado.', 401);
            return;
        }
        $items = (new PaymentRepository())->listByResident($uid);
        Response::json($items, 200, ['count' => count($items)]);
    }

    public function summary(): void
    {
        $cid = Auth::condominiumId();
        if ($cid === null) {
            Response::error('Condominio nao definido.', 422);
            return;
        }
        $rows = (new PaymentRepository())->summaryByCondominium($cid);
        Response::json($rows);
    }

    public function markPaid(array $params): void
    {
        $role = Auth::role();
        if (!in_array($role, ['admin', 'sindico'], true)) {
            Response::error('Sem permissao.', 403);
            return;
        }
        $id = (int) ($params['id'] ?? 0);
        $ok = (new PaymentRepository())->markPaid($id);
        Response::json(['updated' => $ok]);
    }
}
