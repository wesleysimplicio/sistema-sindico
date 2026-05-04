<?php

declare(strict_types=1);

namespace App\Controllers\Api;

use App\Core\Auth;
use App\Core\Response;
use App\Repositories\DeliveryRepository;
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

        $role          = (string) ($user['role'] ?? 'morador');
        $condominiumId = isset($user['condominium_id']) ? (int) $user['condominium_id'] : 0;

        if ($condominiumId <= 0) {
            Response::json([
                'role'     => $role,
                'counters' => [],
                'lists'    => [],
            ]);
            return;
        }

        $payload = match ($role) {
            'morador'  => $this->moradorPayload($user, $condominiumId),
            'sindico'  => $this->sindicoPayload($condominiumId),
            'porteiro' => $this->porteiroPayload($condominiumId),
            default    => $this->sindicoPayload($condominiumId),
        };

        Response::json($payload);
    }

    private function moradorPayload(array $user, int $condominiumId): array
    {
        $unitId = isset($user['unit_id']) && $user['unit_id'] !== null ? (int) $user['unit_id'] : null;

        $noticesCount     = (new NoticeRepository())->count(['condominium_id' => $condominiumId]);
        $maintenanceCount = $unitId !== null
            ? (new MaintenanceRepository())->count(['unit_id' => $unitId, 'status' => 'aberto'])
            : 0;
        $deliveriesCount  = $unitId !== null
            ? (new DeliveryRepository())->count(['unit_id' => $unitId, 'status' => 'aguardando'])
            : 0;

        return [
            'role'     => 'morador',
            'counters' => [
                'notices_count'           => $noticesCount,
                'open_maintenance_count'  => $maintenanceCount,
                'pending_deliveries_count' => $deliveriesCount,
            ],
            'lists' => [],
        ];
    }

    private function sindicoPayload(int $condominiumId): array
    {
        $maintenanceOpen = (new MaintenanceRepository())->count([
            'condominium_id' => $condominiumId,
            'status'         => 'aberto',
        ]);

        $noticesRecent  = (new NoticeRepository())->listByCondominium($condominiumId, 5);
        $visitorsRecent = (new VisitorRepository())->listByCondominium($condominiumId, null, 5);

        $deliveryRepo   = new DeliveryRepository();
        $deliveriesToday = $deliveryRepo->countToday($condominiumId);

        return [
            'role'     => 'sindico',
            'counters' => [
                'open_maintenance_count' => $maintenanceOpen,
                'deliveries_today_count' => $deliveriesToday,
            ],
            'lists' => [
                'notices_recent'  => $noticesRecent,
                'visitors_recent' => $visitorsRecent,
            ],
        ];
    }

    private function porteiroPayload(int $condominiumId): array
    {
        $deliveryRepo  = new DeliveryRepository();
        $visitorRepo   = new VisitorRepository();

        $deliveriesToday   = $deliveryRepo->listToday($condominiumId);
        $visitorsExpected  = $visitorRepo->listExpected($condominiumId);
        $recentAccessEvents = $visitorRepo->listRecentAccessEvents($condominiumId, 10);

        return [
            'role'     => 'porteiro',
            'counters' => [
                'deliveries_today_count'  => count($deliveriesToday),
                'visitors_expected_count' => count($visitorsExpected),
            ],
            'lists' => [
                'deliveries_today'    => $deliveriesToday,
                'visitors_expected'   => $visitorsExpected,
                'recent_access_events' => $recentAccessEvents,
            ],
        ];
    }
}
