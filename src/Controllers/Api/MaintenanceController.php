<?php

declare(strict_types=1);

namespace App\Controllers\Api;

use App\Core\Auth;
use App\Core\Request;
use App\Core\Response;
use App\Repositories\AuditLogRepository;
use App\Repositories\MaintenanceAttachmentRepository;
use App\Repositories\MaintenanceCommentRepository;
use App\Repositories\MaintenanceRepository;

final class MaintenanceController
{
    private const STATUSES   = ['aberto', 'em_andamento', 'aguardando', 'concluido', 'cancelado'];
    private const PRIORITIES = ['baixa', 'media', 'alta', 'urgente'];

    public function index(): void
    {
        $cid = Auth::condominiumId();
        if ($cid === null) {
            Response::error('Condominio nao definido.', 422);
            return;
        }
        $status   = $_GET['status']   ?? null;
        $priority = $_GET['priority'] ?? null;
        $unitId   = isset($_GET['unit_id']) && $_GET['unit_id'] !== '' ? (int) $_GET['unit_id'] : null;
        $items = (new MaintenanceRepository())->listByCondominium(
            $cid,
            is_string($status)   ? $status   : null,
            is_string($priority) ? $priority : null,
            $unitId
        );
        Response::json($items, 200, ['count' => count($items)]);
    }

    public function mine(): void
    {
        $uid = Auth::id();
        $cid = Auth::condominiumId();
        if ($uid === null || $cid === null) {
            Response::error('Nao autenticado.', 401);
            return;
        }
        $items = (new MaintenanceRepository())->listByUser($uid, $cid);
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
        $row = (new MaintenanceRepository())->findInCondo($id, $cid);
        if ($row === null) {
            Response::error('Solicitacao nao encontrada.', 404);
            return;
        }
        $row['attachments'] = (new MaintenanceAttachmentRepository())->listForRequest($id);
        $row['comments']    = (new MaintenanceCommentRepository())->listForRequest($id);
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
        $priority = (string) Request::input('priority', 'media');
        if (!in_array($priority, self::PRIORITIES, true)) {
            $priority = 'media';
        }
        $id = (new MaintenanceRepository())->create([
            'condominium_id' => $cid,
            'unit_id'        => $user['unit_id'] ?? null,
            'requester_id'   => $uid,
            'title'          => $title,
            'description'    => (string) Request::input('description', ''),
            'category'       => (string) Request::input('category', 'geral'),
            'priority'       => $priority,
            'status'         => 'aberto',
        ]);
        (new AuditLogRepository())->record(
            $uid,
            $cid,
            'maintenance.created',
            'maintenance',
            $id,
            ['title' => $title, 'priority' => $priority],
            Request::ip()
        );
        Response::json(['id' => $id], 201);
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
        if (!in_array($role, ['admin', 'sindico'], true)) {
            Response::error('Sem permissao.', 403);
            return;
        }
        $id = (int) ($params['id'] ?? 0);
        $status = (string) Request::input('status', '');
        if (!in_array($status, self::STATUSES, true)) {
            Response::error('Status invalido.', 422, ['allowed' => self::STATUSES]);
            return;
        }
        $repo = new MaintenanceRepository();
        $row = $repo->findInCondo($id, $cid);
        if ($row === null) {
            Response::error('Solicitacao nao encontrada.', 404);
            return;
        }
        $previous = (string) ($row['status'] ?? '');
        $ok = $repo->setStatus($id, $status);

        $note = trim((string) Request::input('note', ''));
        $body = '[status] ' . $previous . ' -> ' . $status . ($note !== '' ? ' | ' . $note : '');
        (new MaintenanceCommentRepository())->add($id, $uid, $body);

        (new AuditLogRepository())->record(
            $uid,
            $cid,
            'maintenance.status_changed',
            'maintenance',
            $id,
            ['from' => $previous, 'to' => $status],
            Request::ip()
        );
        Response::json(['updated' => $ok, 'status' => $status]);
    }

    public function addAttachment(array $params): void
    {
        $cid  = Auth::condominiumId();
        $uid  = Auth::id();
        if ($cid === null || $uid === null) {
            Response::error('Nao autenticado.', 401);
            return;
        }
        $id = (int) ($params['id'] ?? 0);
        $repo = new MaintenanceRepository();
        $row = $repo->findInCondo($id, $cid);
        if ($row === null) {
            Response::error('Solicitacao nao encontrada.', 404);
            return;
        }
        $role = Auth::role();
        $isOwner = (int) ($row['requester_id'] ?? 0) === $uid;
        if (!$isOwner && !in_array($role, ['admin', 'sindico'], true)) {
            Response::error('Sem permissao.', 403);
            return;
        }
        $filePath = trim((string) Request::input('file_path', ''));
        if ($filePath === '') {
            Response::error('file_path obrigatorio.', 422);
            return;
        }
        $aid = (new MaintenanceAttachmentRepository())->add($id, [
            'file_path'     => $filePath,
            'original_name' => Request::input('original_name'),
            'mime_type'     => Request::input('mime_type'),
            'size_bytes'    => Request::input('size_bytes'),
        ], $uid);
        (new AuditLogRepository())->record(
            $uid,
            $cid,
            'maintenance.attachment_added',
            'maintenance',
            $id,
            ['attachment_id' => $aid],
            Request::ip()
        );
        Response::json(['id' => $aid], 201);
    }

    public function comments(array $params): void
    {
        $cid = Auth::condominiumId();
        if ($cid === null) {
            Response::error('Nao autenticado.', 401);
            return;
        }
        $id = (int) ($params['id'] ?? 0);
        if ((new MaintenanceRepository())->findInCondo($id, $cid) === null) {
            Response::error('Solicitacao nao encontrada.', 404);
            return;
        }
        $items = (new MaintenanceCommentRepository())->listForRequest($id);
        Response::json($items, 200, ['count' => count($items)]);
    }

    public function addComment(array $params): void
    {
        $cid  = Auth::condominiumId();
        $uid  = Auth::id();
        if ($cid === null || $uid === null) {
            Response::error('Nao autenticado.', 401);
            return;
        }
        $id = (int) ($params['id'] ?? 0);
        $row = (new MaintenanceRepository())->findInCondo($id, $cid);
        if ($row === null) {
            Response::error('Solicitacao nao encontrada.', 404);
            return;
        }
        $body = trim((string) Request::input('body', ''));
        if ($body === '') {
            Response::error('Mensagem vazia.', 422);
            return;
        }
        $role = Auth::role();
        $isOwner = (int) ($row['requester_id'] ?? 0) === $uid;
        if (!$isOwner && !in_array($role, ['admin', 'sindico'], true)) {
            Response::error('Sem permissao.', 403);
            return;
        }
        $cmtId = (new MaintenanceCommentRepository())->add($id, $uid, $body);
        (new AuditLogRepository())->record(
            $uid,
            $cid,
            'maintenance.comment_added',
            'maintenance',
            $id,
            ['comment_id' => $cmtId],
            Request::ip()
        );
        Response::json(['id' => $cmtId], 201);
    }
}
