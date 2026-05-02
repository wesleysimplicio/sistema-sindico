<?php

declare(strict_types=1);

namespace App\Controllers\Api;

use App\Core\Response;
use App\Repositories\MaintenanceRepository;

final class MaintenanceController
{
    public static function index(): void
    {
        $items = (new MaintenanceRepository())->listByCondominium(1);
        Response::json($items, 200, ['count' => count($items)]);
    }
}
