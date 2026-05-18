<?php

declare(strict_types=1);

namespace App\Controllers\Api;

use App\Core\Auth;
use App\Core\Response;
use App\Repositories\AdoptionMetricsRepository;

final class AdoptionMetricsController
{
    public function index(): void
    {
        $user = Auth::user();
        if ($user === null) {
            Response::error('Nao autenticado.', 401);
            return;
        }

        if ((string) ($user['role'] ?? '') !== 'admin') {
            Response::error('Sem permissao.', 403);
            return;
        }

        $condominiumId = Auth::condominiumId();
        if ($condominiumId === null || $condominiumId <= 0) {
            Response::error('Condominio nao definido.', 422);
            return;
        }

        $metrics = (new AdoptionMetricsRepository())->summaryForCondominium($condominiumId);
        Response::json($metrics);
    }
}
