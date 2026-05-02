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
}
