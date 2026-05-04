<?php

declare(strict_types=1);

namespace App\Controllers\Api;

use App\Core\Auth;
use App\Core\Response;
use App\Repositories\NotificationRepository;

final class NotificationController
{
    public function index(): void
    {
        $uid = Auth::id();
        if ($uid === null) {
            Response::error('Nao autenticado.', 401);
            return;
        }
        $page   = isset($_GET['page'])   ? (int) $_GET['page']   : 1;
        $limit  = isset($_GET['limit'])  ? (int) $_GET['limit']  : 50;
        $unread = isset($_GET['unread']) ? (string) $_GET['unread'] : null;
        $repo   = new NotificationRepository();
        $items  = $repo->listForUser($uid, $page, $limit, $unread);
        Response::json($items, 200, [
            'count'        => count($items),
            'unread_count' => $repo->unreadCount($uid),
            'page'         => max(1, $page),
            'limit'        => max(1, min(200, $limit)),
        ]);
    }

    public function unreadCount(): void
    {
        $uid = Auth::id();
        if ($uid === null) {
            Response::error('Nao autenticado.', 401);
            return;
        }
        Response::json(['unread_count' => (new NotificationRepository())->unreadCount($uid)]);
    }

    public function read(array $params): void
    {
        $uid = Auth::id();
        if ($uid === null) {
            Response::error('Nao autenticado.', 401);
            return;
        }
        $id = (int) ($params['id'] ?? 0);
        $ok = (new NotificationRepository())->markRead($id, $uid);
        Response::json(['marked' => $ok]);
    }

    public function readAll(): void
    {
        $uid = Auth::id();
        if ($uid === null) {
            Response::error('Nao autenticado.', 401);
            return;
        }
        $count = (new NotificationRepository())->markAllRead($uid);
        Response::json(['marked' => $count]);
    }
}
