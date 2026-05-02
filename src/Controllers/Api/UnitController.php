<?php

declare(strict_types=1);

namespace App\Controllers\Api;

use App\Core\Response;

final class UnitController
{
    public static function index(): void
    {
        Response::json([
            ['id' => 1, 'condominium_id' => 1, 'identifier' => 'Apto 101', 'block' => 'A', 'floor' => 1],
            ['id' => 2, 'condominium_id' => 1, 'identifier' => 'Apto 102', 'block' => 'A', 'floor' => 1],
        ], 200, ['count' => 2]);
    }
}
