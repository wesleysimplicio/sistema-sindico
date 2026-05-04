<?php

declare(strict_types=1);

namespace App\Controllers\Api;

use App\Core\Auth;
use App\Core\Response;
use App\Repositories\AccessLogRepository;

final class AccessLogController
{
    public function index(): void
    {
        $cid = Auth::condominiumId();
        if ($cid === null) {
            Response::error('Nao autenticado.', 401);
            return;
        }

        $filters = [
            'from'      => $_GET['from']      ?? null,
            'to'        => $_GET['to']        ?? null,
            'unit_id'   => $_GET['unit_id']   ?? null,
            'direction' => $_GET['direction'] ?? null,
            'result'    => $_GET['result']    ?? null,
            'type'      => $_GET['type']      ?? null,
            'page'      => $_GET['page']      ?? 1,
            'limit'     => $_GET['limit']     ?? 50,
        ];
        if (!empty($filters['from']) && !$this->isValidDate((string) $filters['from'])) {
            Response::error('Formato de data invalido em from.', 422);
            return;
        }
        if (!empty($filters['to']) && !$this->isValidDate((string) $filters['to'])) {
            Response::error('Formato de data invalido em to.', 422);
            return;
        }

        $items = (new AccessLogRepository())->listWithFilters($cid, $filters);
        Response::json($items, 200, [
            'count' => count($items),
            'page'  => (int) $filters['page'],
            'limit' => (int) $filters['limit'],
        ]);
    }

    public function show(array $params): void
    {
        $cid = Auth::condominiumId();
        if ($cid === null) {
            Response::error('Nao autenticado.', 401);
            return;
        }
        $id = (int) ($params['id'] ?? 0);
        $row = (new AccessLogRepository())->findInCondo($id, $cid);
        if ($row === null) {
            Response::error('Registro nao encontrado.', 404);
            return;
        }
        Response::json($row);
    }

    private function isValidDate(string $value): bool
    {
        if ($value === '') {
            return true;
        }
        $ts = strtotime($value);
        return $ts !== false;
    }
}
