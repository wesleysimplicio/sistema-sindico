<?php

declare(strict_types=1);

namespace App\Controllers\Api;

use App\Core\Auth;
use App\Core\Request;
use App\Core\Response;
use App\Repositories\NotificationPreferenceRepository;

final class NotificationPreferenceController
{
    public function index(): void
    {
        $uid = Auth::id();
        if ($uid === null) {
            Response::error('Nao autenticado.', 401);
            return;
        }
        $items = (new NotificationPreferenceRepository())->listForUser($uid);
        Response::json($items, 200, [
            'count'    => count($items),
            'channels' => NotificationPreferenceRepository::CHANNELS,
            'events'   => NotificationPreferenceRepository::EVENTS,
        ]);
    }

    public function update(): void
    {
        $uid = Auth::id();
        if ($uid === null) {
            Response::error('Nao autenticado.', 401);
            return;
        }
        $matrix = Request::input('preferences', null);
        if (!is_array($matrix)) {
            Response::error('Campo preferences obrigatorio (array).', 422);
            return;
        }
        $written = (new NotificationPreferenceRepository())->setForUser($uid, $matrix);
        Response::json(['updated' => $written]);
    }
}
