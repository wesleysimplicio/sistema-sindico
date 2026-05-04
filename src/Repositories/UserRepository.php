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

    public function findByDocumentOrEmail(?string $document, ?string $email): ?array
    {
        $document = $document !== null ? trim($document) : '';
        $email    = $email !== null ? trim($email) : '';
        if ($document === '' && $email === '') {
            return null;
        }

        $sql = 'SELECT * FROM users WHERE 1=0';
        $params = [];
        if ($document !== '') {
            $sql .= ' OR document = :document';
            $params['document'] = $document;
        }
        if ($email !== '') {
            $sql .= ' OR email = :email';
            $params['email'] = $email;
        }
        $sql .= ' LIMIT 1';

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function updateProfile(int $id, array $fields): bool
    {
        $allowed = ['name', 'phone', 'avatar_url'];
        $data = [];
        foreach ($allowed as $key) {
            if (array_key_exists($key, $fields) && $fields[$key] !== null) {
                $data[$key] = $fields[$key];
            }
        }
        if (empty($data)) {
            return false;
        }
        return $this->update($id, $data);
    }

    public function setPassword(int $id, string $passwordHash): bool
    {
        $stmt = $this->pdo->prepare(
            'UPDATE users
             SET password_hash = :hash,
                 password_changed_at = NOW(),
                 updated_at = NOW()
             WHERE id = :id'
        );
        return $stmt->execute(['id' => $id, 'hash' => $passwordHash]);
    }
}
