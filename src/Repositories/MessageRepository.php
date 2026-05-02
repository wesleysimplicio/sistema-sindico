<?php

declare(strict_types=1);

namespace App\Repositories;

final class MessageRepository extends BaseRepository
{
    protected string $table = 'messages';

    public function listByCondominium(int $condominiumId, ?string $channel = null): array
    {
        $sql = 'SELECT m.*, uf.name AS from_name, ut.name AS to_name
                FROM messages m
                LEFT JOIN users uf ON uf.id = m.from_user_id
                LEFT JOIN users ut ON ut.id = m.to_user_id
                WHERE m.condominium_id = :cid';
        $params = ['cid' => $condominiumId];
        if ($channel !== null) {
            $sql .= ' AND m.channel = :channel';
            $params['channel'] = $channel;
        }
        $sql .= ' ORDER BY m.created_at DESC';
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function listForUser(int $userId): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT m.*, uf.name AS from_name, ut.name AS to_name
             FROM messages m
             LEFT JOIN users uf ON uf.id = m.from_user_id
             LEFT JOIN users ut ON ut.id = m.to_user_id
             WHERE m.from_user_id = :uid OR m.to_user_id = :uid
             ORDER BY m.created_at DESC'
        );
        $stmt->execute(['uid' => $userId]);
        return $stmt->fetchAll();
    }

    public function markRead(int $id): bool
    {
        $stmt = $this->pdo->prepare('UPDATE messages SET read_at = NOW() WHERE id = :id');
        return $stmt->execute(['id' => $id]);
    }
}
