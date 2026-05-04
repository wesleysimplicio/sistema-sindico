<?php

declare(strict_types=1);

namespace App\Controllers\Api;

use App\Core\Auth;
use App\Core\Request;
use App\Core\Response;
use App\Core\Database;
use App\Repositories\AuditLogRepository;
use App\Repositories\LoginInvitationRepository;
use App\Repositories\MembershipRepository;
use App\Repositories\UnitRepository;
use App\Repositories\UserRepository;

final class LoginInvitationController
{
    private const ROLES        = ['sindico', 'morador', 'porteiro'];
    private const TTL_SECONDS  = 259200; // 72h

    public function index(): void
    {
        $cid  = Auth::condominiumId();
        $role = Auth::role();
        if ($cid === null) {
            Response::error('Nao autenticado.', 401);
            return;
        }
        if (!in_array($role, ['admin', 'sindico'], true)) {
            Response::error('Sem permissao.', 403);
            return;
        }
        $acceptedParam = Request::input('accepted');
        $accepted = null;
        if ($acceptedParam === '1' || $acceptedParam === 'true') {
            $accepted = true;
        } elseif ($acceptedParam === '0' || $acceptedParam === 'false') {
            $accepted = false;
        }
        $items = (new LoginInvitationRepository())->listByCondominium($cid, $accepted);
        Response::json($items, 200, ['count' => count($items)]);
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
            Response::error('Sem permissao.', 403);
            return;
        }
        $fullName = trim((string) Request::input('full_name', ''));
        $email    = trim((string) Request::input('email', ''));
        $phone    = trim((string) Request::input('phone', ''));
        $document = trim((string) Request::input('document', ''));
        $newRole  = (string) Request::input('role', '');
        $unitId   = (int) Request::input('unit_id', 0);

        if ($fullName === '' || $newRole === '') {
            Response::error('full_name e role sao obrigatorios.', 422);
            return;
        }
        if (!in_array($newRole, self::ROLES, true)) {
            Response::error('Role invalido.', 422, ['allowed' => self::ROLES]);
            return;
        }
        if ($email === '' && $phone === '') {
            Response::error('email ou phone obrigatorio.', 422);
            return;
        }
        if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            Response::error('email invalido.', 422);
            return;
        }
        if ($unitId > 0) {
            $unit = (new UnitRepository())->findInCondo($unitId, $cid);
            if ($unit === null) {
                Response::error('Unidade invalida.', 422);
                return;
            }
        }

        $rawToken  = bin2hex(random_bytes(32));
        $expiresAt = date('Y-m-d H:i:s', time() + self::TTL_SECONDS);

        $repo = new LoginInvitationRepository();
        $id = $repo->createInvite([
            'condominium_id'     => $cid,
            'unit_id'            => $unitId > 0 ? $unitId : null,
            'email'              => $email !== '' ? $email : null,
            'phone'              => $phone !== '' ? $phone : null,
            'full_name'          => $fullName,
            'document'           => $document !== '' ? $document : null,
            'role'               => $newRole,
            'token'              => $rawToken,
            'expires_at'         => $expiresAt,
            'created_by_user_id' => $uid,
        ]);
        (new AuditLogRepository())->record(
            $uid,
            $cid,
            'login_invitation.created',
            'login_invitation',
            $id,
            ['email' => $email, 'role' => $newRole, 'unit_id' => $unitId],
            Request::ip()
        );
        Response::json([
            'id'         => $id,
            'token'      => $rawToken,
            'expires_at' => $expiresAt,
            'accept_url' => '/aceitar/' . $rawToken,
        ], 201);
    }

    public function destroy(array $params): void
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
        $ok = (new LoginInvitationRepository())->deleteIfPending($id, $cid);
        if (!$ok) {
            Response::error('Convite nao encontrado ou ja aceito.', 404);
            return;
        }
        (new AuditLogRepository())->record(
            $uid,
            $cid,
            'login_invitation.deleted',
            'login_invitation',
            $id,
            null,
            Request::ip()
        );
        Response::json(['deleted' => true]);
    }

    public function accept(array $params): void
    {
        $token    = (string) ($params['token'] ?? '');
        $password = (string) Request::input('password', '');

        if ($token === '') {
            Response::error('Token invalido.', 422);
            return;
        }
        if (strlen($password) < 8) {
            Response::error('Senha deve ter pelo menos 8 caracteres.', 422);
            return;
        }
        if (!preg_match('/[A-Za-z]/', $password) || !preg_match('/[0-9]/', $password)) {
            Response::error('Senha deve conter letras e numeros.', 422);
            return;
        }

        $invRepo = new LoginInvitationRepository();
        $config  = require dirname(__DIR__, 3) . '/config/app.php';
        $pdo     = Database::connection($config['db']);

        try {
            $pdo->beginTransaction();
            $invite = $invRepo->findByToken($token);
            if ($invite === null) {
                $pdo->rollBack();
                Response::error('Convite invalido ou expirado.', 404);
                return;
            }

            $cid       = (int) $invite['condominium_id'];
            $unitId    = isset($invite['unit_id']) && $invite['unit_id'] !== null ? (int) $invite['unit_id'] : null;
            $email     = $invite['email'] ?? null;
            $synthetic = false;
            $document  = $invite['document'] ?? null;
            $userRepo  = new UserRepository();

            if ($email !== null && $email !== '') {
                $existing = $userRepo->findByEmail((string) $email);
                if ($existing !== null) {
                    $pdo->rollBack();
                    Response::error('Email ja cadastrado.', 409);
                    return;
                }
            } else {
                $email     = 'invite_' . $invite['id'] . '_' . bin2hex(random_bytes(4)) . '@local.invalid';
                $synthetic = true;
            }

            $hash   = password_hash($password, PASSWORD_BCRYPT);
            $userId = $userRepo->create([
                'condominium_id' => $cid,
                'unit_id'        => $unitId,
                'name'           => $invite['full_name'],
                'email'          => $email,
                'password_hash'  => $hash,
                'role'           => $invite['role'],
                'phone'          => $invite['phone'] ?? null,
                'document'       => $document,
                'active'         => 1,
            ]);

            (new MembershipRepository())->create([
                'user_id'        => $userId,
                'condominium_id' => $cid,
                'unit_id'        => $unitId,
                'role'           => $invite['role'],
                'is_active'      => 1,
            ]);

            $accepted = $invRepo->markAccepted((int) $invite['id']);
            if (!$accepted) {
                $pdo->rollBack();
                Response::error('Convite ja foi aceito.', 409);
                return;
            }

            (new AuditLogRepository())->record(
                $userId,
                $cid,
                'login_invitation.accepted',
                'login_invitation',
                (int) $invite['id'],
                ['user_id' => $userId, 'role' => $invite['role']],
                Request::ip()
            );

            $pdo->commit();
        } catch (\Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            Response::error('Erro ao aceitar convite.', 500);
            return;
        }

        Response::json([
            'accepted' => true,
            'user_id'  => $userId,
        ] + ($synthetic ? [] : ['email' => $email]), 201);
    }
}
