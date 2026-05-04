<?php

declare(strict_types=1);

namespace App\Controllers\Api;

use App\Core\Auth;
use App\Core\Request;
use App\Core\Response;
use App\Repositories\MessageRepository;

final class MessageController
{
    private const CHANNELS = ['sindico', 'portaria', 'suporte', 'direto'];

    public function index(): void
    {
        $cid = Auth::condominiumId();
        if ($cid === null) {
            Response::error('Condominio nao definido.', 422);
            return;
        }
        $channel = $_GET['channel'] ?? null;
        $items = (new MessageRepository())->listByCondominium($cid, is_string($channel) ? $channel : null);
        Response::json($items, 200, ['count' => count($items)]);
    }

    public function inbox(): void
    {
        $uid = Auth::id();
        if ($uid === null) {
            Response::error('Nao autenticado.', 401);
            return;
        }
        $items = (new MessageRepository())->listForUser($uid);
        Response::json($items, 200, ['count' => count($items)]);
    }

    public function store(): void
    {
        $cid = Auth::condominiumId();
        $uid = Auth::id();
        if ($cid === null || $uid === null) {
            Response::error('Nao autenticado.', 401);
            return;
        }
        $body = trim((string) Request::input('body', ''));
        if ($body === '') {
            Response::error('Mensagem vazia.', 422);
            return;
        }
        $channel = (string) Request::input('channel', 'sindico');
        if (!in_array($channel, self::CHANNELS, true)) {
            $channel = 'sindico';
        }
        $id = (new MessageRepository())->create([
            'condominium_id' => $cid,
            'from_user_id'   => $uid,
            'to_user_id'     => (int) Request::input('to_user_id', 0) ?: null,
            'subject'        => (string) Request::input('subject', ''),
            'body'           => $body,
            'channel'        => $channel,
        ]);
        Response::json(['id' => $id], 201);
    }

    public function read(array $params): void
    {
        $id = (int) ($params['id'] ?? 0);
        $ok = (new MessageRepository())->markRead($id);
        Response::json(['updated' => $ok]);
    }
}
