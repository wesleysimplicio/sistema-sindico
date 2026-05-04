<?php

declare(strict_types=1);

namespace App\Controllers\Api;

use App\Core\Auth;
use App\Core\Response;
use App\Repositories\CameraRepository;

final class CameraController
{
    private const STREAM_TTL = 600; // 10 min

    public function index(): void
    {
        $cid = Auth::condominiumId();
        if ($cid === null) {
            Response::error('Nao autenticado.', 401);
            return;
        }
        $items = (new CameraRepository())->listByCondominium($cid);
        Response::json($items, 200, ['count' => count($items)]);
    }

    public function show(array $params): void
    {
        $cid = Auth::condominiumId();
        if ($cid === null) {
            Response::error('Nao autenticado.', 401);
            return;
        }
        $id = (int) ($params['id'] ?? 0);
        $row = (new CameraRepository())->findInCondo($id, $cid);
        if ($row === null) {
            Response::error('Camera nao encontrada.', 404);
            return;
        }
        unset($row['rtsp_url']);
        Response::json($row);
    }

    public function stream(array $params): void
    {
        $cid = Auth::condominiumId();
        if ($cid === null) {
            Response::error('Nao autenticado.', 401);
            return;
        }
        $id = (int) ($params['id'] ?? 0);
        $row = (new CameraRepository())->findInCondo($id, $cid);
        if ($row === null) {
            Response::error('Camera nao encontrada.', 404);
            return;
        }
        if ((int) ($row['enabled'] ?? 0) !== 1) {
            Response::error('Camera desabilitada.', 409);
            return;
        }
        $hlsPath = (string) ($row['hls_path'] ?? '');
        if ($hlsPath === '') {
            Response::error('Camera sem hls_path configurado.', 422);
            return;
        }

        $exp = time() + self::STREAM_TTL;
        try {
            $token = self::sign($cid, $id, $exp);
        } catch (\RuntimeException) {
            Response::error('Servico de streaming indisponivel.', 503);
            return;
        }
        Response::json([
            'camera_id'  => $id,
            'expires_at' => gmdate('c', $exp),
            'token'      => $token,
            'hls_url'    => '/streams/' . ltrim($hlsPath, '/') . '?token=' . urlencode($token),
        ]);
    }

    private static function secret(): string
    {
        $secret = (string) ($_ENV['JWT_SECRET'] ?? getenv('JWT_SECRET') ?: '');
        if ($secret === '' || $secret === 'change-me') {
            throw new \RuntimeException('JWT_SECRET not configured.');
        }
        return $secret;
    }

    private static function sign(int $condominiumId, int $cameraId, int $exp): string
    {
        $payload = 'cam|' . $condominiumId . '|' . $cameraId . '|' . $exp;
        $sig = hash_hmac('sha256', $payload, self::secret());
        return rtrim(strtr(base64_encode($payload . '|' . $sig), '+/', '-_'), '=');
    }
}
