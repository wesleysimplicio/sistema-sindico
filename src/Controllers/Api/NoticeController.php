<?php

declare(strict_types=1);

namespace App\Controllers\Api;

use App\Core\Response;
use App\Repositories\NoticeRepository;

final class NoticeController
{
    public static function index(): void
    {
        $items = (new NoticeRepository())->listByCondominium(1);
        Response::json($items, 200, ['count' => count($items)]);
    }
}
