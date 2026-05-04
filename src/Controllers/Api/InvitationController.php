<?php

declare(strict_types=1);

namespace App\Controllers\Api;

use App\Core\Auth;
use App\Core\Request;
use App\Core\Response;
use App\Repositories\AuditLogRepository;
use App\Repositories\InvitationRepository;
use App\Repositories\UnitRepository;

final class InvitationController
{
    private const STATUSES = ['draft', 'active', 'done', 'cancelled'];

    public function index(): void
    {
        $cid = Auth::condominiumId();
        if ($cid === null) {
            Response::error('Condominio nao definido.', 422);
            return;
        }
        $role = Auth::role();
        $hostFilter = null;
        $mine = (string) Request::input('mine', '');
        if ($mine === '1' || in_array($role, ['morador'], true)) {
            $hostFilter = Auth::id();
        }
        $items = (new InvitationRepository())->listByCondominium($cid, $hostFilter);
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
        $row = (new InvitationRepository())->findInCondo($id, $cid);
        if ($row === null) {
            Response::error('Convite nao encontrado.', 404);
            return;
        }
        Response::json($row);
    }

    public function store(): void
    {
        $cid = Auth::condominiumId();
        $uid = Auth::id();
        if ($cid === null || $uid === null) {
            Response::error('Nao autenticado.', 401);
            return;
        }
        $title = trim((string) Request::input('title', ''));
        $unitId = (int) Request::input('unit_id', 0);
        $startsAt = (string) Request::input('starts_at', '');
        $endsAtRaw = Request::input('ends_at');
        $endsAt = is_string($endsAtRaw) && $endsAtRaw !== '' ? $endsAtRaw : null;
        $notesRaw = Request::input('notes');
        $notes = is_string($notesRaw) && $notesRaw !== '' ? $notesRaw : null;

        if ($title === '' || $unitId <= 0 || $startsAt === '') {
            Response::error('title, unit_id e starts_at sao obrigatorios.', 422);
            return;
        }
        if (!self::isValidDatetime($startsAt)) {
            Response::error('starts_at deve ser Y-m-d H:i:s.', 422);
            return;
        }
        if ($endsAt !== null) {
            if (!self::isValidDatetime($endsAt)) {
                Response::error('ends_at deve ser Y-m-d H:i:s.', 422);
                return;
            }
            if (strtotime($endsAt) < strtotime($startsAt)) {
                Response::error('ends_at deve ser >= starts_at.', 422);
                return;
            }
        }
        $unit = (new UnitRepository())->findInCondo($unitId, $cid);
        if ($unit === null) {
            Response::error('Unidade invalida.', 422);
            return;
        }
        $id = (new InvitationRepository())->create([
            'condominium_id' => $cid,
            'unit_id'        => $unitId,
            'host_user_id'   => $uid,
            'title'          => $title,
            'starts_at'      => $startsAt,
            'ends_at'        => $endsAt,
            'notes'          => $notes,
            'status'         => 'active',
        ]);
        (new AuditLogRepository())->record(
            $uid,
            $cid,
            'invitation.created',
            'invitation',
            $id,
            ['title' => $title, 'starts_at' => $startsAt],
            Request::ip()
        );
        Response::json(['id' => $id], 201);
    }

    public function update(array $params): void
    {
        $cid = Auth::condominiumId();
        $uid = Auth::id();
        if ($cid === null || $uid === null) {
            Response::error('Nao autenticado.', 401);
            return;
        }
        $id  = (int) ($params['id'] ?? 0);
        $repo = new InvitationRepository();
        $row = $repo->findInCondo($id, $cid);
        if ($row === null) {
            Response::error('Convite nao encontrado.', 404);
            return;
        }
        $role = Auth::role();
        if ((int) $row['host_user_id'] !== $uid && !in_array($role, ['admin', 'sindico'], true)) {
            Response::error('Sem permissao.', 403);
            return;
        }
        if (in_array($row['status'], ['done', 'cancelled'], true) && !in_array($role, ['admin', 'sindico'], true)) {
            Response::error('Convite finalizado nao pode ser alterado.', 409);
            return;
        }
        $allowed = ['title', 'starts_at', 'ends_at', 'notes', 'status'];
        $payload = [];
        foreach ($allowed as $field) {
            $val = Request::input($field);
            if ($val === null) {
                continue;
            }
            if ($field === 'status' && !in_array((string) $val, self::STATUSES, true)) {
                Response::error('Status invalido.', 422, ['allowed' => self::STATUSES]);
                return;
            }
            if ($field === 'starts_at' && ($val === '' || $val === null)) {
                Response::error('starts_at nao pode ser vazio.', 422);
                return;
            }
            if ($field === 'title' && trim((string) $val) === '') {
                Response::error('title nao pode ser vazio.', 422);
                return;
            }
            if (in_array($field, ['starts_at', 'ends_at'], true) && $val !== null && $val !== '') {
                if (!self::isValidDatetime((string) $val)) {
                    Response::error("$field deve ser Y-m-d H:i:s.", 422);
                    return;
                }
            }
            $payload[$field] = $val === '' ? null : $val;
        }
        if (empty($payload)) {
            Response::error('Nenhum campo para atualizar.', 422);
            return;
        }
        $repo->update($id, $payload);
        (new AuditLogRepository())->record(
            $uid,
            $cid,
            'invitation.updated',
            'invitation',
            $id,
            $payload,
            Request::ip()
        );
        Response::json(['updated' => true]);
    }

    public function destroy(array $params): void
    {
        $cid = Auth::condominiumId();
        $uid = Auth::id();
        if ($cid === null || $uid === null) {
            Response::error('Nao autenticado.', 401);
            return;
        }
        $id  = (int) ($params['id'] ?? 0);
        $repo = new InvitationRepository();
        $row = $repo->findInCondo($id, $cid);
        if ($row === null) {
            Response::error('Convite nao encontrado.', 404);
            return;
        }
        $role = Auth::role();
        if ((int) $row['host_user_id'] !== $uid && !in_array($role, ['admin', 'sindico'], true)) {
            Response::error('Sem permissao.', 403);
            return;
        }
        $repo->delete($id);
        (new AuditLogRepository())->record(
            $uid,
            $cid,
            'invitation.deleted',
            'invitation',
            $id,
            null,
            Request::ip()
        );
        Response::json(['deleted' => true]);
    }

    private static function isValidDatetime(string $value): bool
    {
        $dt = \DateTime::createFromFormat('Y-m-d H:i:s', $value);
        return $dt !== false && $dt->format('Y-m-d H:i:s') === $value;
    }
}
