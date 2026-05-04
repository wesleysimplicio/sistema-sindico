<?php

declare(strict_types=1);

namespace App\Repositories;

final class MaintenanceAttachmentRepository extends BaseRepository
{
    protected string $table = 'maintenance_attachments';

    public function listForRequest(int $requestId): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT id, file_path, original_name, mime_type, size_bytes, uploaded_by, created_at
             FROM maintenance_attachments WHERE request_id = :rid ORDER BY id ASC'
        );
        $stmt->execute(['rid' => $requestId]);
        return $stmt->fetchAll();
    }

    public function add(int $requestId, array $data, ?int $uploadedBy = null): int
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO maintenance_attachments (request_id, file_path, original_name, mime_type, size_bytes, uploaded_by)
             VALUES (:rid, :path, :name, :mime, :size, :uid)'
        );
        $stmt->execute([
            'rid'  => $requestId,
            'path' => $data['file_path'],
            'name' => $data['original_name'] ?? null,
            'mime' => $data['mime_type']     ?? null,
            'size' => $data['size_bytes']    ?? null,
            'uid'  => $uploadedBy,
        ]);
        return (int) $this->pdo->lastInsertId();
    }
}
