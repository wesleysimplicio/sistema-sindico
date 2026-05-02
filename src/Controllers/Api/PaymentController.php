<?php

declare(strict_types=1);

namespace App\Controllers\Api;

use App\Core\Response;
use App\Repositories\PaymentRepository;

final class PaymentController
{
    public static function index(): void
    {
        $items = (new PaymentRepository())->listByCondominium(1);
        Response::json($items, 200, ['count' => count($items)]);
    }
}
