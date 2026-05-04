<?php

declare(strict_types=1);

namespace App\Repositories;

final class DocumentRepository extends BaseRepository
{
    protected string $table = 'documents';

    public function listByCondominium(int $condominiumId, ?string $category = null, ?int $folderId = null): array
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
        if ($folderId !== null) {
            $sql .= ' AND d.folder_id = :fid';
            $params['fid'] = $folderId;
        }
        $sql .= ' ORDER BY d.created_at DESC';
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function listInFolder(int $condominiumId, ?int $folderId): array
    {
        if ($folderId === null) {
            $stmt = $this->pdo->prepare(
                'SELECT d.*, u.name AS uploader_name
                 FROM documents d
                 LEFT JOIN users u ON u.id = d.uploaded_by
                 WHERE d.condominium_id = :cid AND d.folder_id IS NULL
                 ORDER BY d.created_at DESC'
            );
            $stmt->execute(['cid' => $condominiumId]);
        } else {
            $stmt = $this->pdo->prepare(
                'SELECT d.*, u.name AS uploader_name
                 FROM documents d
                 LEFT JOIN users u ON u.id = d.uploaded_by
                 WHERE d.condominium_id = :cid AND d.folder_id = :fid
                 ORDER BY d.created_at DESC'
            );
            $stmt->execute(['cid' => $condominiumId, 'fid' => $folderId]);
        }
        return $stmt->fetchAll();
    }

    public function findInCondo(int $id, int $condominiumId): ?array
    {
        $stmt = $this->pdo->prepare(
            'SELECT * FROM documents WHERE id = :id AND condominium_id = :cid LIMIT 1'
        );
        $stmt->execute(['id' => $id, 'cid' => $condominiumId]);
        $row = $stmt->fetch();
        return $row ?: null;
    }
}
