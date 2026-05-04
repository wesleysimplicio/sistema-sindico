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
}
