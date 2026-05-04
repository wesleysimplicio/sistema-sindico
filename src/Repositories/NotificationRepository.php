<?php

declare(strict_types=1);

namespace App\Repositories;

final class NotificationRepository extends BaseRepository
{
    protected string $table = 'notifications';

    public function listForUser(int $userId, int $page = 1, int $limit = 50, ?string $unread = null): array
    {
        $page  = max(1, $page);
        $limit = max(1, min(200, $limit));
        $offset = ($page - 1) * $limit;

        $sql = 'SELECT id, user_id, condominium_id, type, title, body,
                       related_entity, related_id, read_at, created_at
                FROM notifications
                WHERE user_id = :uid';
        $params = ['uid' => $userId];
        if ($unread === '1' || $unread === 'true') {
            $sql .= ' AND read_at IS NULL';
        }
        $sql .= ' ORDER BY id DESC LIMIT :__limit OFFSET :__offset';
        $stmt = $this->pdo->prepare($sql);
        foreach ($params as $k => $v) {
            $stmt->bindValue($k, $v, \PDO::PARAM_INT);
        }
        $stmt->bindValue('__limit', $limit, \PDO::PARAM_INT);
        $stmt->bindValue('__offset', $offset, \PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function unreadCount(int $userId): int
    {
        $stmt = $this->pdo->prepare(
            'SELECT COUNT(*) AS c FROM notifications WHERE user_id = :uid AND read_at IS NULL'
        );
        $stmt->execute(['uid' => $userId]);
        return (int) ($stmt->fetch()['c'] ?? 0);
    }

    public function markRead(int $id, int $userId): bool
    {
        $stmt = $this->pdo->prepare(
            'UPDATE notifications SET read_at = NOW()
             WHERE id = :id AND user_id = :uid AND read_at IS NULL'
        );
        $stmt->execute(['id' => $id, 'uid' => $userId]);
        return $stmt->rowCount() > 0;
    }

    public function markAllRead(int $userId): int
    {
        $stmt = $this->pdo->prepare(
            'UPDATE notifications SET read_at = NOW()
             WHERE user_id = :uid AND read_at IS NULL'
        );
        $stmt->execute(['uid' => $userId]);
        return $stmt->rowCount();
    }

    public function push(int $userId, ?int $condominiumId, string $type, string $title, ?string $body = null, ?string $relatedEntity = null, ?int $relatedId = null): int
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO notifications (user_id, condominium_id, type, title, body, related_entity, related_id)
             VALUES (:uid, :cid, :type, :title, :body, :rel_e, :rel_i)'
        );
        $stmt->execute([
            'uid'   => $userId,
            'cid'   => $condominiumId,
            'type'  => $type,
            'title' => $title,
            'body'  => $body,
            'rel_e' => $relatedEntity,
            'rel_i' => $relatedId,
        ]);
        return (int) $this->pdo->lastInsertId();
    }

    /**
     * Bulk-insert one notification per user_id in a single multi-row INSERT.
     * Returns the number of rows inserted. Skips when $userIds is empty.
     */
    public function pushBulk(
        array $userIds,
        ?int $condominiumId,
        string $type,
        string $title,
        ?string $body = null,
        ?string $relatedEntity = null,
        ?int $relatedId = null
    ): int {
        $userIds = array_values(array_unique(array_map('intval', $userIds)));
        $userIds = array_filter($userIds, static fn($v) => $v > 0);
        if ($userIds === []) {
            return 0;
        }
        $rows   = [];
        $params = [
            'cid'   => $condominiumId,
            'type'  => $type,
            'title' => $title,
            'body'  => $body,
            'rel_e' => $relatedEntity,
            'rel_i' => $relatedId,
        ];
        foreach ($userIds as $i => $uid) {
            $rows[] = "(:uid_$i, :cid, :type, :title, :body, :rel_e, :rel_i)";
            $params["uid_$i"] = $uid;
        }
        $sql = 'INSERT INTO notifications (user_id, condominium_id, type, title, body, related_entity, related_id) VALUES '
             . implode(', ', $rows);
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->rowCount();
    }
}
