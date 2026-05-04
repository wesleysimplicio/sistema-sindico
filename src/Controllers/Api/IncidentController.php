<?php

declare(strict_types=1);

namespace App\Controllers\Api;

use App\Core\Auth;
use App\Core\Request;
use App\Core\Response;
use App\Repositories\AuditLogRepository;
use App\Repositories\IncidentCommentRepository;
use App\Repositories\IncidentRepository;
use App\Repositories\IncidentTypeRepository;

final class IncidentController
{
    private const STATUSES = ['open', 'in_progress', 'resolved', 'closed'];

    public function index(): void
    {
        $cid = Auth::condominiumId();
        if ($cid === null) {
            Response::error('Nao autenticado.', 401);
            return;
        }
        $status = isset($_GET['status']) && in_array($_GET['status'], self::STATUSES, true)
            ? (string) $_GET['status']
            : null;
        $typeId = isset($_GET['type_id']) && $_GET['type_id'] !== '' ? (int) $_GET['type_id'] : null;
        $items = (new IncidentRepository())->listByCondominium($cid, $status, $typeId);
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
        $row = (new IncidentRepository())->findInCondo($id, $cid);
        if ($row === null) {
            Response::error('Ocorrencia nao encontrada.', 404);
            return;
        }
        $row['comments'] = (new IncidentCommentRepository())->listForIncident($id);
        Response::json($row);
    }

    public function store(): void
    {
        $cid  = Auth::condominiumId();
        $uid  = Auth::id();
        $user = Auth::user();
        if ($cid === null || $uid === null || $user === null) {
            Response::error('Nao autenticado.', 401);
            return;
        }
        $title = trim((string) Request::input('title', ''));
        if ($title === '') {
            Response::error('Titulo obrigatorio.', 422);
            return;
        }

        $typeId = Request::input('incident_type_id');
        $typeId = $typeId !== null && $typeId !== '' ? (int) $typeId : null;
        if ($typeId !== null && (new IncidentTypeRepository())->findInCondo($typeId, $cid) === null) {
            Response::error('Tipo de ocorrencia invalido.', 422);
            return;
        }

        $occurredAt = (string) Request::input('occurred_at', '');
        if ($occurredAt !== '' && strtotime($occurredAt) === false) {
            Response::error('Formato de occurred_at invalido.', 422);
            return;
        }

        $id = (new IncidentRepository())->create([
            'condominium_id'   => $cid,
            'incident_type_id' => $typeId,
            'reporter_id'      => $uid,
            'unit_id'          => $user['unit_id'] ?? null,
            'title'            => $title,
            'body'             => (string) Request::input('body', '') ?: null,
            'status'           => 'open',
            'occurred_at'      => $occurredAt !== '' ? date('Y-m-d H:i:s', (int) strtotime($occurredAt)) : null,
        ]);

        (new AuditLogRepository())->record(
            $uid,
            $cid,
            'incident.created',
            'incident',
            $id,
            ['title' => $title, 'incident_type_id' => $typeId],
            Request::ip()
        );
        Response::json(['id' => $id], 201);
    }

    public function update(array $params): void
    {
        $cid  = Auth::condominiumId();
        $uid  = Auth::id();
        $role = Auth::role();
        if ($cid === null || $uid === null) {
            Response::error('Nao autenticado.', 401);
            return;
        }
        if (!in_array($role, ['admin', 'sindico'], true)) {
            Response::error('Sem permissao.', 403);
            return;
        }
        $id = (int) ($params['id'] ?? 0);
        $repo = new IncidentRepository();
        $row = $repo->findInCondo($id, $cid);
        if ($row === null) {
            Response::error('Ocorrencia nao encontrada.', 404);
            return;
        }
        $status = (string) Request::input('status', '');
        if (!in_array($status, self::STATUSES, true)) {
            Response::error('Status invalido.', 422, ['allowed' => self::STATUSES]);
            return;
        }
        $previous = (string) ($row['status'] ?? '');
        $ok = $repo->setStatus($id, $status, $cid);

        $note = trim((string) Request::input('note', ''));
        $body = '[status] ' . $previous . ' -> ' . $status . ($note !== '' ? ' | ' . $note : '');
        (new IncidentCommentRepository())->add($id, $uid, $body);

        (new AuditLogRepository())->record(
            $uid,
            $cid,
            'incident.status_changed',
            'incident',
            $id,
            ['from' => $previous, 'to' => $status],
            Request::ip()
        );
        Response::json(['updated' => $ok, 'status' => $status]);
    }

    public function comments(array $params): void
    {
        $cid = Auth::condominiumId();
        if ($cid === null) {
            Response::error('Nao autenticado.', 401);
            return;
        }
        $id = (int) ($params['id'] ?? 0);
        if ((new IncidentRepository())->findInCondo($id, $cid) === null) {
            Response::error('Ocorrencia nao encontrada.', 404);
            return;
        }
        $items = (new IncidentCommentRepository())->listForIncident($id);
        Response::json($items, 200, ['count' => count($items)]);
    }

    public function addComment(array $params): void
    {
        $cid = Auth::condominiumId();
        $uid = Auth::id();
        if ($cid === null || $uid === null) {
            Response::error('Nao autenticado.', 401);
            return;
        }
        $id = (int) ($params['id'] ?? 0);
        if ((new IncidentRepository())->findInCondo($id, $cid) === null) {
            Response::error('Ocorrencia nao encontrada.', 404);
            return;
        }
        $body = trim((string) Request::input('body', ''));
        if ($body === '') {
            Response::error('Mensagem vazia.', 422);
            return;
        }
        $cmtId = (new IncidentCommentRepository())->add($id, $uid, $body);
        (new AuditLogRepository())->record(
            $uid,
            $cid,
            'incident.comment_added',
            'incident',
            $id,
            ['comment_id' => $cmtId],
            Request::ip()
        );
        Response::json(['id' => $cmtId], 201);
    }

    public function types(): void
    {
        $cid = Auth::condominiumId();
        if ($cid === null) {
            Response::error('Nao autenticado.', 401);
            return;
        }
        $items = (new IncidentTypeRepository())->listByCondominium($cid);
        Response::json($items, 200, ['count' => count($items)]);
    }

    public function storeType(): void
    {
        $cid  = Auth::condominiumId();
        $uid  = Auth::id();
        $role = Auth::role();
        if ($cid === null || $uid === null) {
            Response::error('Nao autenticado.', 401);
            return;
        }
        if (!in_array($role, ['admin', 'sindico'], true)) {
            Response::error('Sem permissao.', 403);
            return;
        }
        $name = trim((string) Request::input('name', ''));
        if ($name === '') {
            Response::error('Nome obrigatorio.', 422);
            return;
        }
        $id = (new IncidentTypeRepository())->create([
            'condominium_id' => $cid,
            'name'           => $name,
            'description'    => (string) Request::input('description', '') ?: null,
        ]);
        (new AuditLogRepository())->record(
            $uid,
            $cid,
            'incident_type.created',
            'incident_type',
            $id,
            ['name' => $name],
            Request::ip()
        );
        Response::json(['id' => $id], 201);
    }
}
