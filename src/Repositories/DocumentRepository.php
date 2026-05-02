<?php

declare(strict_types=1);

namespace App\Repositories;

final class DocumentRepository extends BaseRepository
{
    protected string $table = 'documents';

    public function listByCondominium(int $condominiumId, ?string $category = null): array
    {
        $sql = 'SELECT d.*, u.name AS uploader_name
                FROM documents d
                LEFT JOIN users u ON u.id = d.uploaded_by
                WHERE d.condominium_id = :cid';
        $params = ['cid' => $condominiumId];
        if ($category !== null) {
            $sql .= ' AND d.category = :cat';
            $params['cat'] = $category;
        }
        $sql .= ' ORDER BY d.created_at DESC';
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }
}
