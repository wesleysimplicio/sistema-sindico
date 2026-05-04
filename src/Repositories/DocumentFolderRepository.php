<?php

declare(strict_types=1);

namespace App\Repositories;

final class DocumentFolderRepository extends BaseRepository
{
    protected string $table = 'document_folders';

    public function listInCondo(int $condominiumId, ?int $parentId = null): array
    {
        if ($parentId === null) {
            $stmt = $this->pdo->prepare(
                'SELECT id, parent_id, name, created_by, created_at
                 FROM document_folders
                 WHERE condominium_id = :cid AND parent_id IS NULL
                 ORDER BY name ASC'
            );
            $stmt->execute(['cid' => $condominiumId]);
        } else {
            $stmt = $this->pdo->prepare(
                'SELECT id, parent_id, name, created_by, created_at
                 FROM document_folders
                 WHERE condominium_id = :cid AND parent_id = :pid
                 ORDER BY name ASC'
            );
            $stmt->execute(['cid' => $condominiumId, 'pid' => $parentId]);
        }
        return $stmt->fetchAll();
    }

    public function findInCondo(int $id, int $condominiumId): ?array
    {
        $stmt = $this->pdo->prepare(
            'SELECT * FROM document_folders WHERE id = :id AND condominium_id = :cid LIMIT 1'
        );
        $stmt->execute(['id' => $id, 'cid' => $condominiumId]);
        $row = $stmt->fetch();
        return $row ?: null;
    }
}
