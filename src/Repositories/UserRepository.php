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

    public function findByDocument(string $document): ?array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM users WHERE document = :doc LIMIT 1');
        $stmt->execute(['doc' => $document]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function updatePassword(int $id, string $passwordHash): void
    {
        $stmt = $this->pdo->prepare(
            'UPDATE users SET password_hash = :ph, updated_at = NOW() WHERE id = :id'
        );
        $stmt->execute(['ph' => $passwordHash, 'id' => $id]);
    }

    /**
     * Append the given hash to password_history and prune entries older than
     * the last 10, keeping the table from growing unbounded.
     */
    public function pushPasswordHistory(int $userId, string $passwordHash): void
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO password_history (user_id, password_hash) VALUES (:uid, :ph)'
        );
        $stmt->execute(['uid' => $userId, 'ph' => $passwordHash]);

        // Keep only the 10 most recent entries per user
        $prune = $this->pdo->prepare(
            'DELETE FROM password_history
             WHERE user_id = :uid
               AND id NOT IN (
                   SELECT id FROM (
                       SELECT id FROM password_history
                       WHERE user_id = :uid2
                       ORDER BY created_at DESC
                       LIMIT 10
                   ) AS keep
               )'
        );
        $prune->execute(['uid' => $userId, 'uid2' => $userId]);
    }

    public function touchLogin(int $id): void
    {
        $stmt = $this->pdo->prepare('UPDATE users SET last_login_at = NOW() WHERE id = :id');
        $stmt->execute(['id' => $id]);
    }
}
