<?php

declare(strict_types=1);

namespace App\Controllers\Api;

use App\Core\Auth;
use App\Core\Request;
use App\Core\Response;
use App\Repositories\PasswordHistoryRepository;
use App\Repositories\UserRepository;

final class MeController
{
    public function show(): void
    {
        $user = Auth::user();
        if ($user === null) {
            Response::error('Nao autenticado.', 401);
            return;
        }
        Response::json(['user' => self::publicUser($user)]);
    }

    public function update(): void
    {
        $user = Auth::user();
        if ($user === null) {
            Response::error('Nao autenticado.', 401);
            return;
        }

        $fields = [];
        $name = Request::input('name');
        if ($name !== null) {
            $name = trim((string) $name);
            if ($name === '') {
                Response::error('Nome nao pode ser vazio.', 422);
                return;
            }
            $fields['name'] = $name;
        }
        $phone = Request::input('phone');
        if ($phone !== null) {
            $fields['phone'] = trim((string) $phone) ?: null;
        }
        $avatarUrl = Request::input('avatar_url');
        if ($avatarUrl !== null) {
            $fields['avatar_url'] = trim((string) $avatarUrl) ?: null;
        }

        if (empty($fields)) {
            Response::error('Nenhum campo para atualizar.', 422);
            return;
        }

        $repo = new UserRepository();
        $repo->updateProfile((int) $user['id'], $fields);

        $updated = $repo->find((int) $user['id']);
        Response::json(['user' => self::publicUser($updated ?? $user)]);
    }

    public function changePassword(): void
    {
        $user = Auth::user();
        if ($user === null) {
            Response::error('Nao autenticado.', 401);
            return;
        }

        $currentPassword = (string) Request::input('current_password', '');
        $newPassword      = (string) Request::input('new_password', '');

        if ($currentPassword === '' || $newPassword === '') {
            Response::error('current_password e new_password sao obrigatorios.', 422);
            return;
        }

        if (!password_verify($currentPassword, (string) $user['password_hash'])) {
            Response::error('Senha atual incorreta.', 422);
            return;
        }

        if (strlen($newPassword) < 8) {
            Response::error('A nova senha deve ter pelo menos 8 caracteres.', 422);
            return;
        }

        $newHash = password_hash($newPassword, PASSWORD_BCRYPT);

        $repo = new UserRepository();
        $repo->updatePassword((int) $user['id'], $newHash);

        (new PasswordHistoryRepository())->append((int) $user['id'], $newHash);

        Response::json(['password_changed' => true]);
    }

    private static function publicUser(array $u): array
    {
        return [
            'id'             => (int) $u['id'],
            'name'           => $u['name'],
            'email'          => $u['email'],
            'role'           => $u['role'],
            'condominium_id' => isset($u['condominium_id']) ? (int) $u['condominium_id'] : null,
            'unit_id'        => isset($u['unit_id']) ? (int) $u['unit_id'] : null,
            'phone'          => $u['phone'] ?? null,
            'avatar_url'     => $u['avatar_url'] ?? null,
        ];
    }
}
