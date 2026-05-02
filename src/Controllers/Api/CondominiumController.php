<?php

declare(strict_types=1);

namespace App\Controllers\Api;

use App\Core\Response;

final class CondominiumController
{
    public static function index(): void
    {
        Response::json([
            [
                'id'           => 1,
                'name'         => 'Residencial Exemplo',
                'cnpj'         => '00.000.000/0001-00',
                'address'      => 'Rua Exemplo, 100 - Centro',
                'units_count'  => 24,
            ],
        ], 200, ['count' => 1]);
    }
}
