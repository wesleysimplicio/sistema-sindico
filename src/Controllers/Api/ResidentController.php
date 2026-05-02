<?php

declare(strict_types=1);

namespace App\Controllers\Api;

use App\Core\Response;
use App\Repositories\UserRepository;

final class ResidentController
{
    public static function index(): void
    {
        $items = (new UserRepository())->listByCondominium(1, 'morador');
        Response::json($items, 200, ['count' => count($items)]);
    }
}
