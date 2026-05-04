<?php

declare(strict_types=1);

namespace App\Repositories;

final class LoginInvitationRepository extends BaseRepository
{
    protected string $table = 'login_invitations';

    public function createInvite(array $data): int
    {
        return $this->create($data);
    }

    public function findByToken(string $token): ?array
    {
        $stmt = $this->pdo->prepare(
            'SELECT * FROM login_invitations WHERE token = :t LIMIT 1'
        );
        $stmt->execute(['t' => $token]);
        $row = $stmt->fetch();
        return $row ?: null;
    }
}
