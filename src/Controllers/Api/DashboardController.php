<?php

declare(strict_types=1);

namespace App\Controllers\Api;

use App\Core\Auth;
use App\Core\Response;
use App\Repositories\MaintenanceRepository;
use App\Repositories\NoticeRepository;
use App\Repositories\VisitorRepository;

final class DashboardController
{
    public function index(): void
    {
        $user = Auth::user();
        if ($user === null) {
            Response::error('Nao autenticado.', 401);
            return;
        }

        $role           = (string) ($user['role'] ?? 'morador');
        $condominiumId  = isset($user['condominium_id']) ? (int) $user['condominium_id'] : 0;

        if ($condominiumId <= 0) {
            Response::json([
                'role'     => $role,
                'counters' => [],
                'lists'    => [],
            ]);
            return;
        }

        $payload = match ($role) {
            'sindico', 'admin' => $this->sindicoPayload($condominiumId),
            default            => $this->defaultPayload($role, $condominiumId),
        };

        Response::json($payload);
    }

    private function sindicoPayload(int $condominiumId): array
    {
        $maintenanceRepo = new MaintenanceRepository();
        $noticeRepo      = new NoticeRepository();
        $visitorRepo     = new VisitorRepository();

        $maintenanceOpen  = $maintenanceRepo->countOpen($condominiumId);
        $noticesRecent    = $noticeRepo->countRecent($condominiumId);
        $visitorsRecent   = $visitorRepo->countRecent($condominiumId);

        $recentNotices  = $noticeRepo->listByCondominium($condominiumId, 5);
        $recentVisitors = $visitorRepo->listRecent($condominiumId, 5);

        return [
            'role'     => 'sindico',
            'counters' => [
                'maintenance_open' => $maintenanceOpen,
                'notices_recent'   => $noticesRecent,
                'visitors_recent'  => $visitorsRecent,
            ],
            'lists' => [
                'recent_notices'  => $recentNotices,
                'recent_visitors' => $recentVisitors,
            ],
        ];
    }

    private function defaultPayload(string $role, int $condominiumId): array
    {
        $recentNotices = (new NoticeRepository())->listByCondominium($condominiumId, 5);

        return [
            'role'     => $role,
            'counters' => [],
            'lists'    => [
                'recent_notices' => $recentNotices,
            ],
        ];
    }
}
