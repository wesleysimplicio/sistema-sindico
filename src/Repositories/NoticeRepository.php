<?php

declare(strict_types=1);

namespace App\Repositories;

final class NoticeRepository extends BaseRepository
{
    protected string $table = 'notices';

    public function listByCondominium(int $condominiumId, int $limit = 50): array
    {
        $sql = 'SELECT n.*, u.name AS author_name
                FROM notices n
                LEFT JOIN users u ON u.id = n.author_id
                WHERE n.condominium_id = :cid
                ORDER BY n.pinned DESC, n.published_at DESC, n.id DESC
                LIMIT ' . (int) $limit;
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['cid' => $condominiumId]);
        return $stmt->fetchAll();
    }

    /**
     * Count notices for a condominium.
     * Used as an unread proxy until notice_reads tracking is implemented (S4-02).
     */
    public function countByCondominium(int $condominiumId): int
    {
        $stmt = $this->pdo->prepare('SELECT COUNT(*) AS c FROM notices WHERE condominium_id = :cid');
        $stmt->execute(['cid' => $condominiumId]);
        return (int) ($stmt->fetch()['c'] ?? 0);
    }
}
