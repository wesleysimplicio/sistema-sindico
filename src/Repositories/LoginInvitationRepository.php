<?php

declare(strict_types=1);

namespace App\Repositories;

final class LoginInvitationRepository extends BaseRepository
{
    protected string $table = 'login_invitations';

    public function createInvite(array $data): int
    {
        if (!isset($data['token']) || !is_string($data['token']) || $data['token'] === '') {
            throw new \InvalidArgumentException('createInvite requires a non-empty token.');
        }
        $data['token'] = hash('sha256', $data['token']);
        return $this->create($data);
    }

    public function findByToken(string $plainToken): ?array
    {
        $stmt = $this->pdo->prepare(
            'SELECT * FROM login_invitations
             WHERE token = :h
               AND accepted_at IS NULL
               AND expires_at > NOW()
             LIMIT 1'
        );
        $stmt->execute(['h' => hash('sha256', $plainToken)]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function markAccepted(int $id): bool
    {
        $stmt = $this->pdo->prepare(
            'UPDATE login_invitations
             SET accepted_at = NOW()
             WHERE id = :id AND accepted_at IS NULL'
        );
        $stmt->execute(['id' => $id]);
        return $stmt->rowCount() > 0;
    }

    public function listByCondominium(int $condominiumId, ?bool $accepted = null, int $limit = 200): array
    {
        $limit = max(1, min(500, $limit));
        $sql = 'SELECT id, condominium_id, unit_id, email, phone, full_name, document, role,
                       expires_at, accepted_at, created_by_user_id, created_at
                FROM login_invitations
                WHERE condominium_id = :cid';
        $params = ['cid' => $condominiumId];
        if ($accepted === true) {
            $sql .= ' AND accepted_at IS NOT NULL';
        } elseif ($accepted === false) {
            $sql .= ' AND accepted_at IS NULL';
        }
        $sql .= ' ORDER BY id DESC LIMIT ' . $limit;
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function findInCondo(int $id, int $condominiumId): ?array
    {
        $stmt = $this->pdo->prepare(
            'SELECT id, condominium_id, unit_id, email, phone, full_name, document, role,
                    expires_at, accepted_at, created_by_user_id, created_at
             FROM login_invitations
             WHERE id = :id AND condominium_id = :cid LIMIT 1'
        );
        $stmt->execute(['id' => $id, 'cid' => $condominiumId]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function deleteIfPending(int $id, int $condominiumId): bool
    {
        $stmt = $this->pdo->prepare(
            'DELETE FROM login_invitations
             WHERE id = :id AND condominium_id = :cid AND accepted_at IS NULL'
        );
        $stmt->execute(['id' => $id, 'cid' => $condominiumId]);
        return $stmt->rowCount() > 0;
    }
}
