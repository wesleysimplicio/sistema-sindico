<?php

declare(strict_types=1);

namespace App\Repositories;

final class NoticeRepository extends BaseRepository
{
    protected string $table = 'notices';

    public function listForUser(int $condominiumId, int $userId, ?string $userBlock, ?int $userUnitId, string $userRole, int $limit = 50): array
    {
        $sql = 'SELECT n.*, u.name AS author_name,
                       (SELECT COUNT(*) FROM notice_reads r WHERE r.notice_id = n.id AND r.user_id = :uid) AS is_read
                FROM notices n
                LEFT JOIN users u ON u.id = n.author_id
                WHERE n.condominium_id = :cid
                  AND (
                       n.scope = "all"
                    OR (n.scope = "block" AND n.scope_block = :block)
                    OR (n.scope = "unit"  AND n.scope_unit_id = :unit)
                    OR (n.scope = "role"  AND n.scope_role = :role)
                  )
                ORDER BY n.pinned DESC, n.published_at DESC, n.id DESC
                LIMIT ' . (int) $limit;
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            'cid'   => $condominiumId,
            'uid'   => $userId,
            'block' => $userBlock,
            'unit'  => $userUnitId,
            'role'  => $userRole,
        ]);
        return $stmt->fetchAll();
    }

    public function listAdmin(int $condominiumId, int $limit = 100): array
    {
        $sql = 'SELECT n.*, u.name AS author_name,
                       (SELECT COUNT(*) FROM notice_reads r WHERE r.notice_id = n.id) AS read_count
                FROM notices n
                LEFT JOIN users u ON u.id = n.author_id
                WHERE n.condominium_id = :cid
                ORDER BY n.pinned DESC, n.published_at DESC, n.id DESC
                LIMIT ' . (int) $limit;
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['cid' => $condominiumId]);
        return $stmt->fetchAll();
    }

    public function findInCondo(int $id, int $condominiumId): ?array
    {
        $stmt = $this->pdo->prepare(
            'SELECT * FROM notices WHERE id = :id AND condominium_id = :cid LIMIT 1'
        );
        $stmt->execute(['id' => $id, 'cid' => $condominiumId]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function findWithAttachments(int $id, int $condominiumId): ?array
    {
        $row = $this->findInCondo($id, $condominiumId);
        if ($row === null) {
            return null;
        }
        $stmt = $this->pdo->prepare(
            'SELECT id, file_path, original_name, mime_type, size_bytes, created_at
             FROM notice_attachments WHERE notice_id = :id ORDER BY id ASC'
        );
        $stmt->execute(['id' => $id]);
        $row['attachments'] = $stmt->fetchAll();
        return $row;
    }

    public function addAttachment(int $noticeId, array $data): int
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO notice_attachments (notice_id, file_path, original_name, mime_type, size_bytes)
             VALUES (:nid, :path, :name, :mime, :size)'
        );
        $stmt->execute([
            'nid'  => $noticeId,
            'path' => $data['file_path'],
            'name' => $data['original_name'] ?? null,
            'mime' => $data['mime_type']     ?? null,
            'size' => $data['size_bytes']    ?? null,
        ]);
        return (int) $this->pdo->lastInsertId();
    }

    public function markRead(int $noticeId, int $userId): bool
    {
        $stmt = $this->pdo->prepare(
            'INSERT IGNORE INTO notice_reads (notice_id, user_id) VALUES (:nid, :uid)'
        );
        $stmt->execute(['nid' => $noticeId, 'uid' => $userId]);
        return $stmt->rowCount() > 0;
    }

    public function unreadCountForUser(int $condominiumId, int $userId, ?string $userBlock, ?int $userUnitId, string $userRole): int
    {
        $stmt = $this->pdo->prepare(
            'SELECT COUNT(*) AS c
             FROM notices n
             WHERE n.condominium_id = :cid
               AND (
                    n.scope = "all"
                 OR (n.scope = "block" AND n.scope_block = :block)
                 OR (n.scope = "unit"  AND n.scope_unit_id = :unit)
                 OR (n.scope = "role"  AND n.scope_role = :role)
               )
               AND NOT EXISTS (
                   SELECT 1 FROM notice_reads r WHERE r.notice_id = n.id AND r.user_id = :uid
               )'
        );
        $stmt->execute([
            'cid'   => $condominiumId,
            'uid'   => $userId,
            'block' => $userBlock,
            'unit'  => $userUnitId,
            'role'  => $userRole,
        ]);
        return (int) ($stmt->fetch()['c'] ?? 0);
    }

    public function listByCondominium(int $condominiumId, int $limit = 50): array
    {
        return $this->listAdmin($condominiumId, $limit);
    }
}
