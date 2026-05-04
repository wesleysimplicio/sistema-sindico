<?php

declare(strict_types=1);

namespace App\Controllers\Api;

use App\Core\Auth;
use App\Core\Request;
use App\Core\Response;
use App\Repositories\CondominiumRepository;
use App\Repositories\PasswordHistoryRepository;
use App\Repositories\UnitRepository;
use App\Repositories\UserRepository;

final class ProfileController
{
    private const MIN_PASSWORD_LEN = 8;

    public function show(): void
    {
        $u = Auth::user();
        if ($u === null) {
            Response::error('Nao autenticado.', 401);
            return;
        }
        $unit  = isset($u['unit_id']) && $u['unit_id'] !== null
            ? (new UnitRepository())->find((int) $u['unit_id'])
            : null;
        $condo = isset($u['condominium_id']) && $u['condominium_id'] !== null
            ? (new CondominiumRepository())->find((int) $u['condominium_id'])
            : null;
        Response::json([
            'id'           => (int) $u['id'],
            'name'         => $u['name'],
            'email'        => $u['email'],
            'phone'        => $u['phone']      ?? null,
            'role'         => $u['role'],
            'avatar_url'   => $u['avatar_url'] ?? null,
            'locale'       => $u['locale']     ?? 'pt-BR',
            'unit'         => $unit,
            'condominium'  => $condo,
        ]);
    }

    public function update(): void
    {
        $u = Auth::user();
        if ($u === null) {
            Response::error('Nao autenticado.', 401);
            return;
        }

        $fields = [];
        foreach (['name', 'phone', 'avatar_url'] as $key) {
            $value = Request::input($key);
            if ($value === null) {
                continue;
            }
            if (!is_string($value)) {
                Response::error("Campo {$key} invalido.", 422);
                return;
            }
            $trimmed = trim($value);
            if ($trimmed === '') {
                Response::error("Campo {$key} nao pode ser vazio.", 422);
                return;
            }
            $fields[$key] = $trimmed;
        }

        if (empty($fields)) {
            Response::error('Nenhum campo para atualizar.', 422);
            return;
        }

        $repo = new UserRepository();
        $repo->updateProfile((int) $u['id'], $fields);
        $fresh = $repo->find((int) $u['id']);

        Response::json([
            'id'         => (int) $fresh['id'],
            'name'       => $fresh['name'],
            'email'      => $fresh['email'],
            'phone'      => $fresh['phone']      ?? null,
            'role'       => $fresh['role'],
            'avatar_url' => $fresh['avatar_url'] ?? null,
        ]);
    }

    public function changePassword(): void
    {
        $u = Auth::user();
        if ($u === null) {
            Response::error('Nao autenticado.', 401);
            return;
        }

        $current = (string) Request::input('current_password', '');
        $next = (string) Request::input('new_password', '');

        if ($current === '' || $next === '') {
            Response::error('current_password e new_password sao obrigatorios.', 422);
            return;
        }
        if (strlen($next) < self::MIN_PASSWORD_LEN) {
            Response::error('Senha deve ter ao menos ' . self::MIN_PASSWORD_LEN . ' caracteres.', 422);
            return;
        }
        if (!password_verify($current, (string) $u['password_hash'])) {
            Response::error('Senha atual incorreta.', 401);
            return;
        }

        $userId = (int) $u['id'];
        $history = new PasswordHistoryRepository();
        if ($history->matchesAnyRecent($userId, $next, 5)) {
            Response::error('Nao reutilize uma das ultimas 5 senhas.', 422);
            return;
        }
        if (password_verify($next, (string) $u['password_hash'])) {
            Response::error('A nova senha deve ser diferente da atual.', 422);
            return;
        }

        $oldHash = (string) $u['password_hash'];
        $newHash = password_hash($next, PASSWORD_DEFAULT);

        (new UserRepository())->setPassword($userId, $newHash);
        if ($oldHash !== '') {
            $history->append($userId, $oldHash);
        }

        Response::json(['message' => 'Senha atualizada.']);
    }
}
