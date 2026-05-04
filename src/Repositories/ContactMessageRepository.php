<?php

declare(strict_types=1);

namespace App\Repositories;

use PDO;

final class ContactMessageRepository extends BaseRepository
{
    protected string $table = 'contact_messages';

    public const STATUSES = ['new', 'read', 'replied'];

    public function create(array $data): int
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO contact_messages
                (condominium_id, user_id, name, email, subject, body, ip, status)
             VALUES (:cid, :uid, :name, :email, :subject, :body, :ip, "new")'
        );
        $stmt->execute([
            'cid'     => (int) $data['condominium_id'],
            'uid'     => isset($data['user_id']) ? (int) $data['user_id'] : null,
            'name'    => substr((string) $data['name'], 0, 120),
            'email'   => isset($data['email']) ? substr((string) $data['email'], 0, 150) : null,
            'subject' => substr((string) $data['subject'], 0, 200),
            'body'    => (string) $data['body'],
            'ip'      => isset($data['ip']) ? substr((string) $data['ip'], 0, 45) : null,
        ]);
        return (int) $this->pdo->lastInsertId();
    }

    public function listForCondominium(int $condoId, ?string $status, int $page, int $limit): array
    {
        $page   = max(1, $page);
        $limit  = max(1, min(200, $limit));
        $offset = ($page - 1) * $limit;

        $sql = 'SELECT id, condominium_id, user_id, name, email, subject, body, status,
                       reply, replied_at, replied_by, ip, created_at
                FROM contact_messages
                WHERE condominium_id = :cid';
        $params = ['cid' => $condoId];
        if ($status !== null && in_array($status, self::STATUSES, true)) {
            $sql .= ' AND status = :st';
            $params['st'] = $status;
        }
        $sql .= ' ORDER BY id DESC LIMIT :limit OFFSET :offset';

        $stmt = $this->pdo->prepare($sql);
        foreach ($params as $k => $v) {
            $stmt->bindValue(':' . $k, $v);
        }
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function findInCondominium(int $id, int $condoId): ?array
    {
        $stmt = $this->pdo->prepare(
            'SELECT * FROM contact_messages WHERE id = :id AND condominium_id = :cid LIMIT 1'
        );
        $stmt->execute(['id' => $id, 'cid' => $condoId]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function markRead(int $id, int $condoId): bool
    {
        $stmt = $this->pdo->prepare(
            'UPDATE contact_messages SET status = "read"
             WHERE id = :id AND condominium_id = :cid AND status = "new"'
        );
        $stmt->execute(['id' => $id, 'cid' => $condoId]);
        return $stmt->rowCount() > 0;
    }

    public function reply(int $id, int $condoId, int $repliedBy, string $reply): bool
    {
        $stmt = $this->pdo->prepare(
            'UPDATE contact_messages
             SET status = "replied",
                 reply = :reply,
                 replied_by = :rb,
                 replied_at = NOW()
             WHERE id = :id AND condominium_id = :cid'
        );
        $stmt->execute([
            'id'    => $id,
            'cid'   => $condoId,
            'reply' => $reply,
            'rb'    => $repliedBy,
        ]);
        return $stmt->rowCount() > 0;
    }

    public function unreadCount(int $condoId): int
    {
        $stmt = $this->pdo->prepare(
            'SELECT COUNT(*) AS c FROM contact_messages
             WHERE condominium_id = :cid AND status = "new"'
        );
        $stmt->execute(['cid' => $condoId]);
        $row = $stmt->fetch();
        return (int) ($row['c'] ?? 0);
    }
}
