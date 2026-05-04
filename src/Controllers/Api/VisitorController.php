<?php

declare(strict_types=1);

namespace App\Controllers\Api;

use App\Core\Auth;
use App\Core\Request;
use App\Core\Response;
use App\Repositories\VisitorRepository;

final class VisitorController
{
    private const STATUSES = ['previsto', 'liberado', 'dentro', 'saiu', 'expirado', 'negado'];

    public function index(): void
    {
        $cid = Auth::condominiumId();
        if ($cid === null) {
            Response::error('Condominio nao definido.', 422);
            return;
        }
        $status = $_GET['status'] ?? null;
        $items = (new VisitorRepository())->listByCondominium($cid, is_string($status) ? $status : null);
        Response::json($items, 200, ['count' => count($items)]);
    }

    public function mine(): void
    {
        $uid = Auth::id();
        if ($uid === null) {
            Response::error('Nao autenticado.', 401);
            return;
        }
        $items = (new VisitorRepository())->listByHost($uid);
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
        $name = trim((string) Request::input('name', ''));
        if ($name === '') {
            Response::error('Nome obrigatorio.', 422);
            return;
        }
        $expectedAt = (string) Request::input('expected_at', date('Y-m-d H:i:s', time() + 3600));
        $token = bin2hex(random_bytes(16));
        $id = (new VisitorRepository())->create([
            'condominium_id' => $cid,
            'unit_id'        => $user['unit_id'] ?? null,
            'host_id'        => $uid,
            'name'           => $name,
            'document'       => (string) Request::input('document', ''),
            'phone'          => (string) Request::input('phone', ''),
            'qr_token'       => $token,
            'expected_at'    => $expectedAt,
            'status'         => 'previsto',
            'notes'          => (string) Request::input('notes', ''),
        ]);
        Response::json(['id' => $id, 'qr_token' => $token], 201);
    }

    public function updateStatus(array $params): void
    {
        $role = Auth::role();
        if (!in_array($role, ['admin', 'sindico', 'porteiro'], true)) {
            Response::error('Sem permissao.', 403);
            return;
        }
        $id = (int) ($params['id'] ?? 0);
        $status = (string) Request::input('status', '');
        if (!in_array($status, self::STATUSES, true)) {
            Response::error('Status invalido.', 422, ['allowed' => self::STATUSES]);
            return;
        }
        $ok = (new VisitorRepository())->setStatus($id, $status);
        Response::json(['updated' => $ok]);
    }

    public function byQr(array $params): void
    {
        $token = (string) ($params['token'] ?? '');
        $row = (new VisitorRepository())->findByQr($token);
        if ($row === null) {
            Response::error('QR invalido.', 404);
            return;
        }
        Response::json($row);
    }
}
