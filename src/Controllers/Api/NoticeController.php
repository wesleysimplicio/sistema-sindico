<?php

declare(strict_types=1);

namespace App\Controllers\Api;

use App\Core\Auth;
use App\Core\Request;
use App\Core\Response;
use App\Repositories\NoticeRepository;

final class NoticeController
{
    public function index(): void
    {
        $cid = Auth::condominiumId();
        if ($cid === null) {
            Response::error('Condominio nao definido.', 422);
            return;
        }
        $items = (new NoticeRepository())->listByCondominium($cid);
        Response::json($items, 200, ['count' => count($items)]);
    }

    public function store(): void
    {
        $cid = Auth::condominiumId();
        $uid = Auth::id();
        $role = Auth::role();
        if ($cid === null || $uid === null) {
            Response::error('Nao autenticado.', 401);
            return;
        }
        if (!in_array($role, ['admin', 'sindico'], true)) {
            Response::error('Apenas sindico/admin pode publicar.', 403);
            return;
        }
        $title = trim((string) Request::input('title', ''));
        $body  = trim((string) Request::input('body', ''));
        if ($title === '' || $body === '') {
            Response::error('Titulo e corpo obrigatorios.', 422);
            return;
        }
        $id = (new NoticeRepository())->create([
            'condominium_id' => $cid,
            'author_id'      => $uid,
            'title'          => $title,
            'body'           => $body,
            'category'       => (string) Request::input('category', 'geral'),
            'pinned'         => Request::input('pinned') ? 1 : 0,
            'published_at'   => date('Y-m-d H:i:s'),
        ]);
        Response::json(['id' => $id], 201);
    }

    public function show(array $params): void
    {
        $row = (new NoticeRepository())->find((int) ($params['id'] ?? 0));
        if ($row === null) {
            Response::error('Aviso nao encontrado.', 404);
            return;
        }
        Response::json($row);
    }
}
