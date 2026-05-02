<?php

declare(strict_types=1);

namespace App\Repositories;

final class UserRepository extends BaseRepository
{
    protected string $table = 'users';

    public function findByEmail(string $email): ?array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM users WHERE email = :email LIMIT 1');
        $stmt->execute(['email' => $email]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function listByCondominium(int $condominiumId, ?string $role = null): array
    {
        $sql = 'SELECT u.*, un.block, un.number AS unit_number
                FROM users u
                LEFT JOIN units un ON un.id = u.unit_id
                WHERE u.condominium_id = :cid';
        $params = ['cid' => $condominiumId];
        if ($role !== null) {
            $sql .= ' AND u.role = :role';
            $params['role'] = $role;
        }
        $sql .= ' ORDER BY u.name ASC';
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function touchLogin(int $id): void
    {
        $stmt = $this->pdo->prepare('UPDATE users SET last_login_at = NOW() WHERE id = :id');
        $stmt->execute(['id' => $id]);
    }
}
