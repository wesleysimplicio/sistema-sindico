<?php

declare(strict_types=1);

namespace App\Controllers\Api;

use App\Core\Response;

final class HealthController
{
    public static function index(): void
    {
        Response::json([
            'status' => 'ok',
            'app'    => 'sistema-sindico',
            'time'   => gmdate('c'),
        ]);
    }
}
