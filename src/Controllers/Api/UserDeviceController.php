<?php

declare(strict_types=1);

namespace App\Controllers\Api;

use App\Core\Auth;
use App\Core\Request;
use App\Core\Response;
use App\Repositories\UserDeviceRepository;

final class UserDeviceController
{
    public function index(): void
    {
        $uid = Auth::id();
        if ($uid === null) {
            Response::error('Nao autenticado.', 401);
            return;
        }
        $items = (new UserDeviceRepository())->listForUser($uid);
        Response::json($items, 200, ['count' => count($items)]);
    }

    public function store(): void
    {
        $uid = Auth::id();
        if ($uid === null) {
            Response::error('Nao autenticado.', 401);
            return;
        }
        $platform = (string) Request::input('platform', '');
        $token    = trim((string) Request::input('fcm_token', ''));
        $name     = trim((string) Request::input('device_name', '')) ?: null;

        if (!in_array($platform, UserDeviceRepository::PLATFORMS, true)) {
            Response::error('Plataforma invalida.', 422, ['allowed' => UserDeviceRepository::PLATFORMS]);
            return;
        }
        if ($token === '' || strlen($token) > 255) {
            Response::error('fcm_token invalido.', 422);
            return;
        }
        try {
            $id = (new UserDeviceRepository())->register($uid, $platform, $token, $name);
        } catch (\InvalidArgumentException $e) {
            Response::error($e->getMessage(), 422);
            return;
        }
        Response::json(['id' => $id], 201);
    }

    public function destroy(array $params): void
    {
        $uid = Auth::id();
        if ($uid === null) {
            Response::error('Nao autenticado.', 401);
            return;
        }
        $id = (int) ($params['id'] ?? 0);
        $ok = (new UserDeviceRepository())->revoke($id, $uid);
        if (!$ok) {
            Response::error('Dispositivo nao encontrado.', 404);
            return;
        }
        Response::json(['revoked' => true]);
    }
}
