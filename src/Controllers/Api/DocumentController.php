<?php

declare(strict_types=1);

namespace App\Controllers\Api;

use App\Core\Auth;
use App\Core\Response;
use App\Repositories\DocumentRepository;

final class DocumentController
{
    public function index(): void
    {
        $cid = Auth::condominiumId();
        if ($cid === null) {
            Response::error('Condominio nao definido.', 422);
            return;
        }
        $cat = $_GET['category'] ?? null;
        $items = (new DocumentRepository())->listByCondominium($cid, is_string($cat) ? $cat : null);
        Response::json($items, 200, ['count' => count($items)]);
    }
}
