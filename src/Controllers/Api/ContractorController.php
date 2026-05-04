<?php

declare(strict_types=1);

namespace App\Controllers\Api;

use App\Core\Auth;
use App\Core\Request;
use App\Core\Response;
use App\Repositories\ContractorRepository;
use DateTimeImmutable;

final class ContractorController
{
    public function index(array $params): void
    {
        $condoId = (int) ($params['c'] ?? 0);
        $unitId  = (int) ($params['u'] ?? 0);
        if (!self::guardRead($condoId)) {
            return;
        }
        $repo = new ContractorRepository();
        $repo->markExpired($condoId);
        $rows = $repo->allByUnit($condoId, $unitId);
        $rows = array_map(static fn(array $r) => self::project($r), $rows);
        Response::json($rows, 200, ['count' => count($rows)]);
    }

    public function store(array $params): void
    {
        $condoId = (int) ($params['c'] ?? 0);
        $unitId  = (int) ($params['u'] ?? 0);
        if (!self::guardWrite($condoId, $unitId)) {
            return;
        }
        $name = trim((string) Request::input('full_name', ''));
        if ($name === '') {
            Response::error('Nome obrigatorio.', 422);
            return;
        }
        $startsAt = self::nullableStr(Request::input('access_starts_at'));
        $endsAt   = self::nullableStr(Request::input('access_ends_at'));
        $window = self::validateDateWindow($startsAt, $endsAt);
        if ($window === null) {
            return; // 422 already emitted
        }
        $data = [
            'full_name'        => $name,
            'document'         => self::nullableStr(Request::input('document')),
            'service_type'     => self::nullableStr(Request::input('service_type')),
            'access_starts_at' => $startsAt,
            'access_ends_at'   => $endsAt,
            'status'           => 'pending',
        ];
        $id = (new ContractorRepository())->createForUnit($condoId, $unitId, $data);
        Response::json(['id' => $id], 201);
    }

    public function update(array $params): void
    {
        $condoId = (int) ($params['c'] ?? 0);
        $unitId  = (int) ($params['u'] ?? 0);
        $id      = (int) ($params['id'] ?? 0);
        if (!self::guardWrite($condoId, $unitId)) {
            return;
        }
        $repo = new ContractorRepository();
        if ($repo->findScoped($condoId, $unitId, $id) === null) {
            Response::error('Prestador nao encontrado.', 404);
            return;
        }
        $body = Request::all();
        $allowed = ['full_name', 'document', 'service_type', 'access_starts_at', 'access_ends_at'];
        $patch = [];
        foreach ($allowed as $field) {
            if (array_key_exists($field, $body)) {
                $patch[$field] = $body[$field];
            }
        }
        if (empty($patch)) {
            Response::error('Nada para atualizar.', 422);
            return;
        }

        $hasStart = array_key_exists('access_starts_at', $patch);
        $hasEnd   = array_key_exists('access_ends_at', $patch);
        if ($hasStart || $hasEnd) {
            $existing = $repo->findScoped($condoId, $unitId, $id);
            $startsAt = $hasStart ? self::nullableStr($patch['access_starts_at']) : ($existing['access_starts_at'] ?? null);
            $endsAt   = $hasEnd   ? self::nullableStr($patch['access_ends_at'])   : ($existing['access_ends_at']   ?? null);
            if (self::validateDateWindow($startsAt, $endsAt) === null) {
                return; // 422 already emitted
            }
            if ($hasStart) {
                $patch['access_starts_at'] = $startsAt;
            }
            if ($hasEnd) {
                $patch['access_ends_at'] = $endsAt;
            }
        }

        $repo->update($id, $patch);
        Response::json(['updated' => true]);
    }

    public function changeStatus(array $params): void
    {
        $condoId = (int) ($params['c'] ?? 0);
        $unitId  = (int) ($params['u'] ?? 0);
        $id      = (int) ($params['id'] ?? 0);

        $user = Auth::user();
        if ($user === null) {
            Response::error('Nao autenticado.', 401);
            return;
        }
        $role = $user['role'] ?? null;
        if (!in_array($role, ['admin', 'sindico'], true)) {
            Response::error('Sem permissao.', 403);
            return;
        }
        if (!self::canAccessCondo($user, $condoId)) {
            Response::error('Forbidden', 403);
            return;
        }

        $repo = new ContractorRepository();
        $current = $repo->findScoped($condoId, $unitId, $id);
        if ($current === null) {
            Response::error('Prestador nao encontrado.', 404);
            return;
        }

        $action = (string) Request::input('action', '');
        $currentStatus = (string) $current['status'];

        if ($action === 'approve') {
            if ($currentStatus !== 'pending') {
                Response::error('Transicao invalida.', 422, ['from' => $currentStatus, 'action' => $action]);
                return;
            }
            $repo->setStatus($id, 'approved');
            Response::json(['status' => 'approved']);
            return;
        }
        if ($action === 'revoke') {
            if (!in_array($currentStatus, ['pending', 'approved'], true)) {
                Response::error('Transicao invalida.', 422, ['from' => $currentStatus, 'action' => $action]);
                return;
            }
            $repo->setStatus($id, 'revoked');
            Response::json(['status' => 'revoked']);
            return;
        }
        Response::error('Acao invalida.', 422, ['allowed' => ['approve', 'revoke']]);
    }

    public function destroy(array $params): void
    {
        $condoId = (int) ($params['c'] ?? 0);
        $unitId  = (int) ($params['u'] ?? 0);
        $id      = (int) ($params['id'] ?? 0);
        if (!self::guardWrite($condoId, $unitId)) {
            return;
        }
        $repo = new ContractorRepository();
        if ($repo->findScoped($condoId, $unitId, $id) === null) {
            Response::error('Prestador nao encontrado.', 404);
            return;
        }
        $repo->delete($id);
        Response::json(['deleted' => true]);
    }

    private static function project(array $row): array
    {
        $today = date('Y-m-d');
        $endsAt = $row['access_ends_at'] ?? null;
        $status = (string) $row['status'];
        if ($endsAt !== null && $endsAt < $today && !in_array($status, ['expired', 'revoked'], true)) {
            $row['status'] = 'expired';
        }
        return $row;
    }

    private static function guardRead(int $condoId): bool
    {
        $user = Auth::user();
        if ($user === null) {
            Response::error('Nao autenticado.', 401);
            return false;
        }
        if (!self::canAccessCondo($user, $condoId)) {
            Response::error('Forbidden', 403);
            return false;
        }
        return true;
    }

    private static function guardWrite(int $condoId, int $unitId): bool
    {
        $user = Auth::user();
        if ($user === null) {
            Response::error('Nao autenticado.', 401);
            return false;
        }
        if (!self::canAccessCondo($user, $condoId)) {
            Response::error('Forbidden', 403);
            return false;
        }
        $role = $user['role'] ?? null;
        if ($role === 'porteiro') {
            Response::error('Sem permissao.', 403);
            return false;
        }
        if ($role === 'morador') {
            $userUnit = isset($user['unit_id']) ? (int) $user['unit_id'] : 0;
            if ($userUnit !== $unitId) {
                Response::error('Sem permissao.', 403);
                return false;
            }
        }
        return true;
    }

    private static function canAccessCondo(array $user, int $condoId): bool
    {
        if (($user['role'] ?? null) === 'admin') {
            return true;
        }
        $userCondo = isset($user['condominium_id']) ? (int) $user['condominium_id'] : 0;
        return $userCondo === $condoId;
    }

    private static function nullableStr(mixed $v): ?string
    {
        if ($v === null) {
            return null;
        }
        $v = trim((string) $v);
        return $v === '' ? null : $v;
    }

    /**
     * Returns [DateTimeImmutable|null start, DateTimeImmutable|null end] on success
     * or null on validation failure (after emitting 422 via Response::error).
     * Both must be null OR both provided. Non-parseable, end < start, window > 365d -> 422.
     */
    private static function validateDateWindow(?string $start, ?string $end): ?array
    {
        if ($start === null && $end === null) {
            return [null, null];
        }
        if ($start === null || $end === null) {
            Response::error('Datas de acesso devem ser fornecidas em par.', 422);
            return null;
        }
        $startDt = DateTimeImmutable::createFromFormat('!Y-m-d', $start);
        $endDt   = DateTimeImmutable::createFromFormat('!Y-m-d', $end);
        if ($startDt === false || $startDt->format('Y-m-d') !== $start) {
            Response::error('Data de inicio invalida.', 422, ['field' => 'access_starts_at']);
            return null;
        }
        if ($endDt === false || $endDt->format('Y-m-d') !== $end) {
            Response::error('Data de fim invalida.', 422, ['field' => 'access_ends_at']);
            return null;
        }
        if ($endDt < $startDt) {
            Response::error('Data de fim anterior ao inicio.', 422);
            return null;
        }
        $diffDays = (int) $startDt->diff($endDt)->format('%a');
        if ($diffDays > 365) {
            Response::error('Janela de acesso excede 365 dias.', 422, ['days' => $diffDays]);
            return null;
        }
        return [$startDt, $endDt];
    }
}
