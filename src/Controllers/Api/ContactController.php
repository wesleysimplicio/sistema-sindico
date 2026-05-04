<?php

declare(strict_types=1);

namespace App\Controllers\Api;

use App\Core\Auth;
use App\Core\Request;
use App\Core\Response;
use App\Repositories\ContactMessageRepository;
use App\Repositories\NotificationRepository;
use App\Repositories\UserRepository;

final class ContactController
{
    public function store(): void
    {
        $u = Auth::user();
        if ($u === null) {
            Response::error('Nao autenticado.', 401);
            return;
        }
        $cid = isset($u['condominium_id']) ? (int) $u['condominium_id'] : 0;
        if ($cid <= 0) {
            Response::error('Usuario sem condominio vinculado.', 422);
            return;
        }

        $subject = trim((string) Request::input('subject', ''));
        $body    = trim((string) Request::input('body', ''));
        $email   = trim((string) Request::input('email', (string) ($u['email'] ?? '')));
        $name    = trim((string) Request::input('name', (string) ($u['name'] ?? '')));

        if ($subject === '' || strlen($subject) > 200) {
            Response::error('Assunto invalido (1-200 caracteres).', 422);
            return;
        }
        if ($body === '' || strlen($body) > 5000) {
            Response::error('Mensagem invalida (1-5000 caracteres).', 422);
            return;
        }
        if ($name === '') {
            Response::error('Nome obrigatorio.', 422);
            return;
        }
        if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            Response::error('Email invalido.', 422);
            return;
        }

        $id = (new ContactMessageRepository())->create([
            'condominium_id' => $cid,
            'user_id'        => (int) $u['id'],
            'name'           => $name,
            'email'          => $email !== '' ? $email : null,
            'subject'        => $subject,
            'body'           => $body,
            'ip'             => Request::ip(),
        ]);

        $sindicos = (new UserRepository())->listByCondominium($cid, 'sindico');
        $sindicoIds = array_map(static fn($s) => (int) $s['id'], $sindicos);
        if ($sindicoIds !== []) {
            (new NotificationRepository())->pushBulk(
                $sindicoIds,
                $cid,
                'contact',
                'Nova mensagem: ' . substr($subject, 0, 80),
                substr($body, 0, 200),
                'contact_message',
                $id
            );
        }

        Response::json(['id' => $id], 201);
    }

    public function index(): void
    {
        $u = Auth::user();
        if ($u === null || !in_array($u['role'] ?? '', ['admin', 'sindico'], true)) {
            Response::error('Acesso restrito a sindico.', 403);
            return;
        }
        $cid = (int) ($u['condominium_id'] ?? 0);
        if ($cid <= 0) {
            Response::error('Sem condominio vinculado.', 422);
            return;
        }

        $status = isset($_GET['status']) ? (string) $_GET['status'] : null;
        $page   = isset($_GET['page'])   ? (int) $_GET['page']   : 1;
        $limit  = isset($_GET['limit'])  ? (int) $_GET['limit']  : 50;

        $repo  = new ContactMessageRepository();
        $items = $repo->listForCondominium($cid, $status, $page, $limit);
        Response::json($items, 200, [
            'count'        => count($items),
            'unread_count' => $repo->unreadCount($cid),
            'page'         => max(1, $page),
            'limit'        => max(1, min(200, $limit)),
            'statuses'     => ContactMessageRepository::STATUSES,
        ]);
    }

    public function show(array $params): void
    {
        $u = Auth::user();
        if ($u === null || !in_array($u['role'] ?? '', ['admin', 'sindico'], true)) {
            Response::error('Acesso restrito a sindico.', 403);
            return;
        }
        $cid = (int) ($u['condominium_id'] ?? 0);
        $id  = (int) ($params['id'] ?? 0);

        $repo = new ContactMessageRepository();
        $row  = $repo->findInCondominium($id, $cid);
        if ($row === null) {
            Response::error('Mensagem nao encontrada.', 404);
            return;
        }
        if (($row['status'] ?? '') === 'new') {
            $repo->markRead($id, $cid);
            $row['status'] = 'read';
        }
        Response::json($row);
    }

    public function update(array $params): void
    {
        $u = Auth::user();
        if ($u === null || !in_array($u['role'] ?? '', ['admin', 'sindico'], true)) {
            Response::error('Acesso restrito a sindico.', 403);
            return;
        }
        $cid = (int) ($u['condominium_id'] ?? 0);
        $id  = (int) ($params['id'] ?? 0);

        $reply  = trim((string) Request::input('reply', ''));
        $action = (string) Request::input('action', $reply !== '' ? 'reply' : 'mark_read');

        $repo = new ContactMessageRepository();
        $row  = $repo->findInCondominium($id, $cid);
        if ($row === null) {
            Response::error('Mensagem nao encontrada.', 404);
            return;
        }

        if ($action === 'reply') {
            if ($reply === '' || strlen($reply) > 5000) {
                Response::error('Resposta invalida (1-5000 caracteres).', 422);
                return;
            }
            $repo->reply($id, $cid, (int) $u['id'], $reply);
            if (!empty($row['user_id'])) {
                (new NotificationRepository())->push(
                    (int) $row['user_id'],
                    $cid,
                    'contact_reply',
                    'Resposta: ' . substr((string) $row['subject'], 0, 80),
                    substr($reply, 0, 200),
                    'contact_message',
                    $id
                );
            }
            Response::json(['replied' => true]);
            return;
        }

        if ($action === 'mark_read') {
            $repo->markRead($id, $cid);
            Response::json(['marked_read' => true]);
            return;
        }

        Response::error('Acao invalida.', 422, ['allowed' => ['reply', 'mark_read']]);
    }
}
