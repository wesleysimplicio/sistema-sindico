<?php

declare(strict_types=1);

namespace App\Controllers\Api;

use App\Core\Auth;
use App\Core\Request;
use App\Core\Response;
use App\Repositories\AuditLogRepository;
use App\Repositories\InvitationGuestRepository;
use App\Repositories\InvitationRepository;

final class InvitationGuestController
{
    private const STATUSES = ['expected', 'arrived', 'no_show'];

    public function index(array $params): void
    {
        $cid = Auth::condominiumId();
        if ($cid === null) {
            Response::error('Nao autenticado.', 401);
            return;
        }
        $invitationId = (int) ($params['id'] ?? 0);
        $invitation = (new InvitationRepository())->findInCondo($invitationId, $cid);
        if ($invitation === null) {
            Response::error('Convite nao encontrado.', 404);
            return;
        }
        $items = (new InvitationGuestRepository())->listByInvitation($invitationId);
        Response::json($items, 200, ['count' => count($items)]);
    }

    public function store(array $params): void
    {
        $cid = Auth::condominiumId();
        $uid = Auth::id();
        if ($cid === null || $uid === null) {
            Response::error('Nao autenticado.', 401);
            return;
        }
        $invitationId = (int) ($params['id'] ?? 0);
        $invitation = (new InvitationRepository())->findInCondo($invitationId, $cid);
        if ($invitation === null) {
            Response::error('Convite nao encontrado.', 404);
            return;
        }
        $role = Auth::role();
        if ((int) $invitation['host_user_id'] !== $uid && !in_array($role, ['admin', 'sindico'], true)) {
            Response::error('Sem permissao.', 403);
            return;
        }
        $fullName = trim((string) Request::input('full_name', ''));
        if ($fullName === '') {
            Response::error('full_name obrigatorio.', 422);
            return;
        }
        $document = trim((string) Request::input('document', ''));
        $id = (new InvitationGuestRepository())->create([
            'invitation_id' => $invitationId,
            'full_name'     => $fullName,
            'document'      => $document !== '' ? $document : null,
            'status'        => 'expected',
        ]);
        (new AuditLogRepository())->record(
            $uid,
            $cid,
            'invitation.guest_added',
            'invitation_guest',
            $id,
            ['invitation_id' => $invitationId, 'full_name' => $fullName],
            Request::ip()
        );
        Response::json(['id' => $id], 201);
    }

    public function updateStatus(array $params): void
    {
        $cid = Auth::condominiumId();
        $uid = Auth::id();
        if ($cid === null || $uid === null) {
            Response::error('Nao autenticado.', 401);
            return;
        }
        $invitationId = (int) ($params['id'] ?? 0);
        $guestId      = (int) ($params['gid'] ?? 0);
        $invitation = (new InvitationRepository())->findInCondo($invitationId, $cid);
        if ($invitation === null) {
            Response::error('Convite nao encontrado.', 404);
            return;
        }
        $role = Auth::role();
        $isHost = (int) $invitation['host_user_id'] === $uid;
        if (!$isHost && !in_array($role, ['admin', 'sindico', 'porteiro'], true)) {
            Response::error('Sem permissao.', 403);
            return;
        }
        $status = (string) Request::input('status', '');
        if (!in_array($status, self::STATUSES, true)) {
            Response::error('Status invalido.', 422, ['allowed' => self::STATUSES]);
            return;
        }
        $repo = new InvitationGuestRepository();
        $guest = $repo->findInInvitation($guestId, $invitationId);
        if ($guest === null) {
            Response::error('Convidado nao encontrado.', 404);
            return;
        }
        $repo->setStatus($guestId, $status);
        (new AuditLogRepository())->record(
            $uid,
            $cid,
            $status === 'arrived' ? 'invitation.guest_arrived' : 'invitation.guest_status',
            'invitation_guest',
            $guestId,
            ['invitation_id' => $invitationId, 'status' => $status],
            Request::ip()
        );
        Response::json(['updated' => true, 'status' => $status]);
    }

    public function destroy(array $params): void
    {
        $cid = Auth::condominiumId();
        $uid = Auth::id();
        if ($cid === null || $uid === null) {
            Response::error('Nao autenticado.', 401);
            return;
        }
        $invitationId = (int) ($params['id'] ?? 0);
        $guestId      = (int) ($params['gid'] ?? 0);
        $invitation = (new InvitationRepository())->findInCondo($invitationId, $cid);
        if ($invitation === null) {
            Response::error('Convite nao encontrado.', 404);
            return;
        }
        $role = Auth::role();
        if ((int) $invitation['host_user_id'] !== $uid && !in_array($role, ['admin', 'sindico'], true)) {
            Response::error('Sem permissao.', 403);
            return;
        }
        $repo = new InvitationGuestRepository();
        $guest = $repo->findInInvitation($guestId, $invitationId);
        if ($guest === null) {
            Response::error('Convidado nao encontrado.', 404);
            return;
        }
        $repo->delete($guestId);
        (new AuditLogRepository())->record(
            $uid,
            $cid,
            'invitation.guest_removed',
            'invitation_guest',
            $guestId,
            ['invitation_id' => $invitationId],
            Request::ip()
        );
        Response::json(['deleted' => true]);
    }
}
