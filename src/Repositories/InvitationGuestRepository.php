<?php

declare(strict_types=1);

namespace App\Repositories;

final class InvitationGuestRepository extends BaseRepository
{
    protected string $table = 'invitation_guests';

    private const STATUSES = ['expected', 'arrived', 'no_show'];

    public function listByInvitation(int $invitationId): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT id, invitation_id, full_name, document, status, arrived_at, created_at
             FROM invitation_guests
             WHERE invitation_id = :iid
             ORDER BY full_name ASC'
        );
        $stmt->execute(['iid' => $invitationId]);
        return $stmt->fetchAll();
    }

    public function findInInvitation(int $id, int $invitationId): ?array
    {
        $stmt = $this->pdo->prepare(
            'SELECT * FROM invitation_guests WHERE id = :id AND invitation_id = :iid LIMIT 1'
        );
        $stmt->execute(['id' => $id, 'iid' => $invitationId]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function setStatus(int $id, string $status): bool
    {
        if (!in_array($status, self::STATUSES, true)) {
            throw new \InvalidArgumentException('Invalid guest status.');
        }
        $sql = $status === 'arrived'
            ? 'UPDATE invitation_guests SET status = :status, arrived_at = NOW() WHERE id = :id'
            : 'UPDATE invitation_guests SET status = :status WHERE id = :id';
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute(['id' => $id, 'status' => $status]);
    }
}
