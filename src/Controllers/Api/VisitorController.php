<?php

declare(strict_types=1);

namespace App\Controllers\Api;

use App\Core\Auth;
use App\Core\Request;
use App\Core\Response;
use App\Repositories\AccessLogRepository;
use App\Repositories\AuditLogRepository;
use App\Repositories\VisitorRepository;

final class VisitorController
{
    private const STATUSES   = ['previsto', 'liberado', 'dentro', 'saiu', 'expirado', 'negado'];
    private const QR_TTL_SEC = 600; // 10 minutes

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
        $cid = Auth::condominiumId();
        $uid = Auth::id();
        if ($cid === null || $uid === null) {
            Response::error('Nao autenticado.', 401);
            return;
        }
        $items = (new VisitorRepository())->listByHost($uid, $cid);
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
        if (!self::isValidDatetime($expectedAt)) {
            Response::error('expected_at deve ser Y-m-d H:i:s.', 422);
            return;
        }
        $photoUrl = trim((string) Request::input('photo_url', ''));
        if ($photoUrl !== '' && !self::isValidPhotoUrl($photoUrl)) {
            Response::error('photo_url invalida.', 422);
            return;
        }
        $token     = bin2hex(random_bytes(16));
        $qrExpires = date('Y-m-d H:i:s', time() + self::QR_TTL_SEC);
        $document  = trim((string) Request::input('document', ''));
        $phone     = trim((string) Request::input('phone', ''));
        $notes     = trim((string) Request::input('notes', ''));
        $repo      = new VisitorRepository();
        $id = $repo->create([
            'condominium_id' => $cid,
            'unit_id'        => $user['unit_id'] ?? null,
            'host_id'        => $uid,
            'name'           => $name,
            'document'       => $document !== '' ? $document : null,
            'phone'          => $phone !== '' ? $phone : null,
            'qr_token'       => $token,
            'qr_expires_at'  => $qrExpires,
            'expected_at'    => $expectedAt,
            'status'         => 'previsto',
            'notes'          => $notes !== '' ? $notes : null,
            'photo_url'      => $photoUrl !== '' ? $photoUrl : null,
        ]);
        (new AuditLogRepository())->record(
            $uid,
            $cid,
            'visitor.created',
            'visitor',
            $id,
            ['name' => $name, 'expected_at' => $expectedAt],
            Request::ip()
        );
        Response::json([
            'id'            => $id,
            'qr_token'      => $token,
            'qr_expires_at' => $qrExpires,
        ], 201);
    }

    public function qrFor(array $params): void
    {
        $cid = Auth::condominiumId();
        $uid = Auth::id();
        if ($cid === null || $uid === null) {
            Response::error('Nao autenticado.', 401);
            return;
        }
        $id = (int) ($params['id'] ?? 0);
        if ($id <= 0) {
            Response::error('Visitante invalido.', 422);
            return;
        }
        $repo = new VisitorRepository();
        $row = $repo->find($id);
        if ($row === null || (int) $row['condominium_id'] !== $cid) {
            Response::error('Visitante nao encontrado.', 404);
            return;
        }
        $role = Auth::role();
        $isHost = (int) ($row['host_id'] ?? 0) === $uid;
        if (!$isHost && !in_array($role, ['admin', 'sindico', 'porteiro'], true)) {
            Response::error('Sem permissao.', 403);
            return;
        }
        $token   = bin2hex(random_bytes(16));
        $expires = date('Y-m-d H:i:s', time() + self::QR_TTL_SEC);
        $repo->refreshQr($id, $token, $expires);
        (new AuditLogRepository())->record(
            $uid,
            $cid,
            'visitor.qr_issued',
            'visitor',
            $id,
            ['expires_at' => $expires],
            Request::ip()
        );
        Response::json([
            'visitor_id'    => $id,
            'qr_token'      => $token,
            'qr_expires_at' => $expires,
            'ttl_seconds'   => self::QR_TTL_SEC,
        ]);
    }

    public function checkIn(array $params): void
    {
        $this->transition($params, 'in');
    }

    public function checkOut(array $params): void
    {
        $this->transition($params, 'out');
    }

    private function transition(array $params, string $direction): void
    {
        $cid = Auth::condominiumId();
        $uid = Auth::id();
        $role = Auth::role();
        if ($cid === null || $uid === null) {
            Response::error('Nao autenticado.', 401);
            return;
        }
        if (!in_array($role, ['admin', 'sindico', 'porteiro'], true)) {
            Response::error('Sem permissao.', 403);
            return;
        }
        $id = (int) ($params['id'] ?? 0);
        if ($id <= 0) {
            Response::error('Visitante invalido.', 422);
            return;
        }
        $repo = new VisitorRepository();
        $row  = $repo->find($id);
        if ($row === null || (int) $row['condominium_id'] !== $cid) {
            Response::error('Visitante nao encontrado.', 404);
            return;
        }
        if ($direction === 'in') {
            if ($row['status'] === 'dentro') {
                Response::error('Visitante ja esta dentro.', 409);
                return;
            }
            $newStatus = 'dentro';
            $action    = 'visitor.checkin';
        } else {
            if ($row['status'] !== 'dentro') {
                Response::error('Visitante nao esta dentro.', 409);
                return;
            }
            $newStatus = 'saiu';
            $action    = 'visitor.checkout';
        }
        $repo->setStatus($id, $newStatus);
        (new AccessLogRepository())->record(
            $cid,
            null,
            $id,
            isset($row['unit_id']) ? (int) $row['unit_id'] : null,
            $direction,
            'granted',
            'porter_manual',
            $row['photo_url'] ?? null
        );
        (new AuditLogRepository())->record(
            $uid,
            $cid,
            $action,
            'visitor',
            $id,
            ['status' => $newStatus],
            Request::ip()
        );
        Response::json([
            'visitor_id' => $id,
            'status'     => $newStatus,
        ]);
    }

    public function history(): void
    {
        $cid = Auth::condominiumId();
        if ($cid === null) {
            Response::error('Condominio nao definido.', 422);
            return;
        }
        $items = (new VisitorRepository())->listHistory($cid);
        Response::json($items, 200, ['count' => count($items)]);
    }

    private static function isValidDatetime(string $value): bool
    {
        $dt = \DateTime::createFromFormat('Y-m-d H:i:s', $value);
        return $dt !== false && $dt->format('Y-m-d H:i:s') === $value;
    }

    private static function isValidPhotoUrl(string $url): bool
    {
        if (strlen($url) > 2048) {
            return false;
        }
        $parts = parse_url($url);
        if ($parts === false || !isset($parts['scheme'], $parts['host'])) {
            return false;
        }
        if (strtolower($parts['scheme']) !== 'https') {
            return false;
        }
        $host = strtolower($parts['host']);
        if ($host === '' || $host === 'localhost') {
            return false;
        }
        // Block raw IPs in private/reserved ranges to mitigate SSRF if URL is ever fetched server-side.
        if (filter_var($host, FILTER_VALIDATE_IP)) {
            $flags = FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE;
            return filter_var($host, FILTER_VALIDATE_IP, $flags) !== false;
        }
        return true;
    }

    public function updateStatus(array $params): void
    {
        $cid  = Auth::condominiumId();
        $uid  = Auth::id();
        $role = Auth::role();
        if ($cid === null || $uid === null) {
            Response::error('Nao autenticado.', 401);
            return;
        }
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
        $repo = new VisitorRepository();
        $row  = $repo->find($id);
        if ($row === null || (int) $row['condominium_id'] !== $cid) {
            Response::error('Visitante nao encontrado.', 404);
            return;
        }
        $ok = $repo->setStatus($id, $status);
        if (in_array($status, ['dentro', 'saiu'], true)) {
            (new AccessLogRepository())->record(
                $cid,
                null,
                $id,
                isset($row['unit_id']) ? (int) $row['unit_id'] : null,
                $status === 'dentro' ? 'in' : 'out',
                'granted',
                'porter_manual',
                $row['photo_url'] ?? null
            );
        }
        (new AuditLogRepository())->record(
            $uid,
            $cid,
            'visitor.status_changed',
            'visitor',
            $id,
            ['status' => $status],
            Request::ip()
        );
        Response::json(['updated' => $ok, 'status' => $status]);
    }

    public function byQr(array $params): void
    {
        $cid = Auth::condominiumId();
        if ($cid === null) {
            Response::error('Nao autenticado.', 401);
            return;
        }
        $token = (string) ($params['token'] ?? '');
        $row = (new VisitorRepository())->findValidByQr($token, $cid);
        if ($row === null) {
            Response::error('QR invalido ou expirado.', 404);
            return;
        }
        Response::json($row);
    }
}
