<?php

declare(strict_types=1);

namespace App\Controllers\Api;

use App\Core\Auth;
use App\Core\Request;
use App\Core\Response;
use App\Core\StoragePath;
use App\Repositories\AuditLogRepository;
use App\Repositories\NoticeRepository;

final class NoticeController
{
    private const SCOPES     = ['all', 'block', 'unit', 'role'];
    private const CATEGORIES = ['geral', 'urgente', 'manutencao', 'financeiro', 'evento'];
    private const ROLES      = ['admin', 'sindico', 'porteiro', 'morador', 'inquilino'];

    public function index(): void
    {
        $cid  = Auth::condominiumId();
        $user = Auth::user();
        if ($cid === null || $user === null) {
            Response::error('Nao autenticado.', 401);
            return;
        }
        $role = (string) ($user['role'] ?? 'morador');
        $repo = new NoticeRepository();

        if (in_array($role, ['admin', 'sindico'], true) && Request::input('all')) {
            $items = $repo->listAdmin($cid, 100);
        } else {
            $items = $repo->listForUser(
                $cid,
                (int) $user['id'],
                $user['block'] ?? null,
                isset($user['unit_id']) ? (int) $user['unit_id'] : null,
                $role,
                50
            );
        }
        Response::json($items, 200, ['count' => count($items)]);
    }

    public function show(array $params): void
    {
        $cid  = Auth::condominiumId();
        $user = Auth::user();
        if ($cid === null || $user === null) {
            Response::error('Nao autenticado.', 401);
            return;
        }
        $id = (int) ($params['id'] ?? 0);
        $repo = new NoticeRepository();
        $row = $repo->findWithAttachments($id, $cid);
        if ($row === null) {
            Response::error('Aviso nao encontrado.', 404);
            return;
        }
        if (!$this->visibleToUser($row, $user)) {
            Response::error('Sem permissao.', 403);
            return;
        }
        $repo->markRead($id, (int) $user['id']);
        Response::json($row);
    }

    public function store(): void
    {
        $cid  = Auth::condominiumId();
        $uid  = Auth::id();
        $role = Auth::role();
        if ($cid === null || $uid === null) {
            Response::error('Nao autenticado.', 401);
            return;
        }
        if (!in_array($role, ['admin', 'sindico'], true)) {
            Response::error('Apenas sindico/admin pode publicar.', 403);
            return;
        }
        $title = trim((string) Request::input('title', ''));
        $body  = trim((string) Request::input('body', ''));
        if ($title === '' || $body === '') {
            Response::error('Titulo e corpo obrigatorios.', 422);
            return;
        }

        $category = (string) Request::input('category', 'geral');
        if (!in_array($category, self::CATEGORIES, true)) {
            $category = 'geral';
        }

        $scope = (string) Request::input('scope', 'all');
        if (!in_array($scope, self::SCOPES, true)) {
            Response::error('Escopo invalido.', 422, ['allowed' => self::SCOPES]);
            return;
        }
        $scopeBlock  = null;
        $scopeUnitId = null;
        $scopeRole   = null;
        if ($scope === 'block') {
            $scopeBlock = trim((string) Request::input('scope_block', ''));
            if ($scopeBlock === '') {
                Response::error('scope_block obrigatorio.', 422);
                return;
            }
        } elseif ($scope === 'unit') {
            $scopeUnitId = (int) Request::input('scope_unit_id', 0);
            if ($scopeUnitId <= 0) {
                Response::error('scope_unit_id obrigatorio.', 422);
                return;
            }
        } elseif ($scope === 'role') {
            $scopeRole = (string) Request::input('scope_role', '');
            if (!in_array($scopeRole, self::ROLES, true)) {
                Response::error('scope_role invalido.', 422, ['allowed' => self::ROLES]);
                return;
            }
        }

        $repo = new NoticeRepository();
        $id = $repo->create([
            'condominium_id' => $cid,
            'author_id'      => $uid,
            'title'          => $title,
            'body'           => $body,
            'category'       => $category,
            'pinned'         => Request::input('pinned') ? 1 : 0,
            'published_at'   => date('Y-m-d H:i:s'),
            'scope'          => $scope,
            'scope_block'    => $scopeBlock,
            'scope_unit_id'  => $scopeUnitId,
            'scope_role'     => $scopeRole,
        ]);

        $attachments = Request::input('attachments');
        if (is_array($attachments)) {
            foreach ($attachments as $att) {
                if (is_array($att) && !empty($att['file_path'])) {
                    $repo->addAttachment($id, $att);
                }
            }
        }

        (new AuditLogRepository())->record(
            $uid,
            $cid,
            'notice.created',
            'notice',
            $id,
            ['title' => $title, 'scope' => $scope, 'category' => $category],
            Request::ip()
        );

        Response::json(['id' => $id], 201);
    }

    public function addAttachment(array $params): void
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
        $repo = new NoticeRepository();
        if ($repo->findInCondo($id, $cid) === null) {
            Response::error('Aviso nao encontrado.', 404);
            return;
        }
        $filePath = trim((string) Request::input('file_path', ''));
        if ($filePath === '') {
            Response::error('file_path obrigatorio.', 422);
            return;
        }
        if (!StoragePath::isSafeRelative($filePath)) {
            Response::error('file_path invalido.', 422);
            return;
        }
        $aid = $repo->addAttachment($id, [
            'file_path'     => $filePath,
            'original_name' => Request::input('original_name'),
            'mime_type'     => Request::input('mime_type'),
            'size_bytes'    => Request::input('size_bytes'),
        ]);
        (new AuditLogRepository())->record(
            $uid,
            $cid,
            'notice.attachment_added',
            'notice',
            $id,
            ['attachment_id' => $aid],
            Request::ip()
        );
        Response::json(['id' => $aid], 201);
    }

    public function markRead(array $params): void
    {
        $cid  = Auth::condominiumId();
        $user = Auth::user();
        if ($cid === null || $user === null) {
            Response::error('Nao autenticado.', 401);
            return;
        }
        $id = (int) ($params['id'] ?? 0);
        $repo = new NoticeRepository();
        $row = $repo->findInCondo($id, $cid);
        if ($row === null) {
            Response::error('Aviso nao encontrado.', 404);
            return;
        }
        if (!$this->visibleToUser($row, $user)) {
            Response::error('Sem permissao.', 403);
            return;
        }
        $created = $repo->markRead($id, (int) $user['id']);
        if ($created) {
            (new AuditLogRepository())->record(
                (int) $user['id'],
                $cid,
                'notice.read',
                'notice',
                $id,
                null,
                Request::ip()
            );
        }
        Response::json(['marked' => $created]);
    }

    public function unreadCount(): void
    {
        $cid  = Auth::condominiumId();
        $user = Auth::user();
        if ($cid === null || $user === null) {
            Response::error('Nao autenticado.', 401);
            return;
        }
        $count = (new NoticeRepository())->unreadCountForUser(
            $cid,
            (int) $user['id'],
            $user['block'] ?? null,
            isset($user['unit_id']) ? (int) $user['unit_id'] : null,
            (string) ($user['role'] ?? 'morador')
        );
        Response::json(['unread' => $count]);
    }

    private function visibleToUser(array $notice, array $user): bool
    {
        $scope = (string) ($notice['scope'] ?? 'all');
        $role  = (string) ($user['role'] ?? '');
        if (in_array($role, ['admin', 'sindico'], true)) {
            return true;
        }
        if ($scope === 'all') {
            return true;
        }
        if ($scope === 'block') {
            return ($notice['scope_block'] ?? null) === ($user['block'] ?? null);
        }
        if ($scope === 'unit') {
            return (int) ($notice['scope_unit_id'] ?? 0) === (int) ($user['unit_id'] ?? 0);
        }
        if ($scope === 'role') {
            return (string) ($notice['scope_role'] ?? '') === $role;
        }
        return false;
    }
}
