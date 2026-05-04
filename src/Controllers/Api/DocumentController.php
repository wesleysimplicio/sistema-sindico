<?php

declare(strict_types=1);

namespace App\Controllers\Api;

use App\Core\Auth;
use App\Core\Request;
use App\Core\Response;
use App\Core\StoragePath;
use App\Repositories\AuditLogRepository;
use App\Repositories\DocumentFolderRepository;
use App\Repositories\DocumentRepository;

final class DocumentController
{
    private const SIGNED_TTL = 600; // 10 min

    public function index(): void
    {
        $cid = Auth::condominiumId();
        if ($cid === null) {
            Response::error('Condominio nao definido.', 422);
            return;
        }
        $cat      = $_GET['category'] ?? null;
        $folderId = isset($_GET['folder_id']) && $_GET['folder_id'] !== '' ? (int) $_GET['folder_id'] : null;

        $repo = new DocumentRepository();
        if (array_key_exists('folder_id', $_GET)) {
            $items = $repo->listInFolder($cid, $folderId);
        } else {
            $items = $repo->listByCondominium($cid, is_string($cat) ? $cat : null);
        }
        Response::json($items, 200, ['count' => count($items)]);
    }

    public function show(array $params): void
    {
        $cid = Auth::condominiumId();
        if ($cid === null) {
            Response::error('Condominio nao definido.', 422);
            return;
        }
        $row = (new DocumentRepository())->findInCondo((int) ($params['id'] ?? 0), $cid);
        if ($row === null) {
            Response::error('Documento nao encontrado.', 404);
            return;
        }
        Response::json($row);
    }

    public function store(): void
    {
        $cid  = Auth::condominiumId();
        $uid  = Auth::id();
        $role = Auth::role();
        if ($cid === null || $uid === null) {
            Response::error('Nao autenticado.', 401);
            return;
        }
        if (!in_array($role, ['admin', 'sindico'], true)) {
            Response::error('Sem permissao.', 403);
            return;
        }
        $title    = trim((string) Request::input('title', ''));
        $filePath = trim((string) Request::input('file_path', ''));
        if ($title === '' || $filePath === '') {
            Response::error('Titulo e file_path obrigatorios.', 422);
            return;
        }
        if (!StoragePath::isSafeRelative($filePath)) {
            Response::error('file_path invalido.', 422);
            return;
        }
        $folderId = Request::input('folder_id');
        $folderId = $folderId !== null && $folderId !== '' ? (int) $folderId : null;
        if ($folderId !== null) {
            $folder = (new DocumentFolderRepository())->findInCondo($folderId, $cid);
            if ($folder === null) {
                Response::error('Pasta nao encontrada.', 404);
                return;
            }
        }
        $id = (new DocumentRepository())->create([
            'condominium_id' => $cid,
            'uploaded_by'    => $uid,
            'title'          => $title,
            'description'    => (string) Request::input('description', '') ?: null,
            'file_path'      => $filePath,
            'category'       => (string) Request::input('category', 'geral'),
            'size_bytes'     => Request::input('size_bytes'),
            'mime_type'      => Request::input('mime_type'),
            'folder_id'      => $folderId,
        ]);
        (new AuditLogRepository())->record(
            $uid,
            $cid,
            'document.uploaded',
            'document',
            $id,
            ['title' => $title, 'folder_id' => $folderId],
            Request::ip()
        );
        Response::json(['id' => $id], 201);
    }

    public function signedUrl(array $params): void
    {
        $cid = Auth::condominiumId();
        if ($cid === null) {
            Response::error('Nao autenticado.', 401);
            return;
        }
        $id = (int) ($params['id'] ?? 0);
        $row = (new DocumentRepository())->findInCondo($id, $cid);
        if ($row === null) {
            Response::error('Documento nao encontrado.', 404);
            return;
        }
        $exp = time() + self::SIGNED_TTL;
        $token = self::sign($id, $exp);
        Response::json([
            'document_id' => $id,
            'expires_at'  => gmdate('c', $exp),
            'token'       => $token,
            'url'         => '/api/documents/' . $id . '/download?token=' . urlencode($token),
        ]);
    }

    public function download(array $params): void
    {
        $cid = Auth::condominiumId();
        $uid = Auth::id();
        if ($cid === null || $uid === null) {
            Response::error('Nao autenticado.', 401);
            return;
        }
        $id    = (int) ($params['id'] ?? 0);
        $token = (string) ($_GET['token'] ?? '');
        if ($token === '' || !self::verify($id, $token)) {
            Response::error('Token invalido ou expirado.', 403);
            return;
        }
        $row = (new DocumentRepository())->findInCondo($id, $cid);
        if ($row === null) {
            Response::error('Documento nao encontrado.', 404);
            return;
        }
        $path = $this->resolvePath((string) $row['file_path']);
        if ($path === null || !is_file($path)) {
            Response::error('Arquivo ausente.', 404);
            return;
        }
        (new AuditLogRepository())->record(
            $uid,
            $cid,
            'document.downloaded',
            'document',
            $id,
            null,
            Request::ip()
        );
        $rawMime = (string) ($row['mime_type'] ?? 'application/octet-stream');
        $mime    = preg_match('/^[a-zA-Z0-9!#$&^_.+\-\/]+$/', $rawMime) === 1
            ? $rawMime
            : 'application/octet-stream';
        $rawName  = (string) ($row['title'] ?? 'document');
        $safeName = preg_replace('/[\r\n\t"\\\\]+/', '_', $rawName) ?? 'document';
        $encoded  = rawurlencode($rawName);
        header('Content-Type: ' . $mime);
        header(
            "Content-Disposition: attachment; filename=\"{$safeName}\"; filename*=UTF-8''{$encoded}"
        );
        header('Content-Length: ' . filesize($path));
        readfile($path);
    }

    public function folderIndex(): void
    {
        $cid = Auth::condominiumId();
        if ($cid === null) {
            Response::error('Nao autenticado.', 401);
            return;
        }
        $parentId = isset($_GET['parent_id']) && $_GET['parent_id'] !== '' ? (int) $_GET['parent_id'] : null;
        $items = (new DocumentFolderRepository())->listInCondo($cid, $parentId);
        Response::json($items, 200, ['count' => count($items)]);
    }

    public function folderShow(array $params): void
    {
        $cid = Auth::condominiumId();
        if ($cid === null) {
            Response::error('Nao autenticado.', 401);
            return;
        }
        $row = (new DocumentFolderRepository())->findInCondo((int) ($params['id'] ?? 0), $cid);
        if ($row === null) {
            Response::error('Pasta nao encontrada.', 404);
            return;
        }
        Response::json($row);
    }

    public function folderStore(): void
    {
        $cid  = Auth::condominiumId();
        $uid  = Auth::id();
        $role = Auth::role();
        if ($cid === null || $uid === null) {
            Response::error('Nao autenticado.', 401);
            return;
        }
        if (!in_array($role, ['admin', 'sindico'], true)) {
            Response::error('Sem permissao.', 403);
            return;
        }
        $name = trim((string) Request::input('name', ''));
        if ($name === '') {
            Response::error('Nome obrigatorio.', 422);
            return;
        }
        $parentId = Request::input('parent_id');
        $parentId = $parentId !== null && $parentId !== '' ? (int) $parentId : null;
        $repo = new DocumentFolderRepository();
        if ($parentId !== null && $repo->findInCondo($parentId, $cid) === null) {
            Response::error('Pasta pai nao encontrada.', 404);
            return;
        }
        $id = $repo->create([
            'condominium_id' => $cid,
            'parent_id'      => $parentId,
            'name'           => $name,
            'created_by'     => $uid,
        ]);
        (new AuditLogRepository())->record(
            $uid,
            $cid,
            'document.folder_created',
            'document_folder',
            $id,
            ['name' => $name, 'parent_id' => $parentId],
            Request::ip()
        );
        Response::json(['id' => $id], 201);
    }

    public function folderDestroy(array $params): void
    {
        $cid  = Auth::condominiumId();
        $uid  = Auth::id();
        $role = Auth::role();
        if ($cid === null || $uid === null) {
            Response::error('Nao autenticado.', 401);
            return;
        }
        if (!in_array($role, ['admin', 'sindico'], true)) {
            Response::error('Sem permissao.', 403);
            return;
        }
        $id = (int) ($params['id'] ?? 0);
        $repo = new DocumentFolderRepository();
        if ($repo->findInCondo($id, $cid) === null) {
            Response::error('Pasta nao encontrada.', 404);
            return;
        }
        $ok = $repo->delete($id);
        (new AuditLogRepository())->record(
            $uid,
            $cid,
            'document.folder_deleted',
            'document_folder',
            $id,
            null,
            Request::ip()
        );
        Response::json(['deleted' => $ok]);
    }

    private static function secret(): string
    {
        $secret = (string) ($_ENV['JWT_SECRET'] ?? getenv('JWT_SECRET') ?: '');
        if ($secret === '' || $secret === 'change-me') {
            throw new \RuntimeException('JWT_SECRET not configured.');
        }
        return $secret;
    }

    private static function sign(int $docId, int $exp): string
    {
        $payload = $docId . '|' . $exp;
        $sig = hash_hmac('sha256', $payload, self::secret());
        return rtrim(strtr(base64_encode($payload . '|' . $sig), '+/', '-_'), '=');
    }

    private static function verify(int $docId, string $token): bool
    {
        $padded = str_pad($token, (int) (ceil(strlen($token) / 4) * 4), '=', STR_PAD_RIGHT);
        $raw = base64_decode(strtr($padded, '-_', '+/'), true);
        if ($raw === false) {
            return false;
        }
        $parts = explode('|', $raw);
        if (count($parts) !== 3) {
            return false;
        }
        [$id, $exp, $sig] = $parts;
        if ((int) $id !== $docId || (int) $exp < time()) {
            return false;
        }
        $expected = hash_hmac('sha256', $id . '|' . $exp, self::secret());
        return hash_equals($expected, $sig);
    }

    private function resolvePath(string $relative): ?string
    {
        return StoragePath::resolve($relative);
    }
}
