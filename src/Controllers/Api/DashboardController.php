<?php

declare(strict_types=1);

namespace App\Controllers\Api;

use App\Core\Auth;
use App\Core\Response;
use App\Repositories\DeliveryRepository;
use App\Repositories\MaintenanceRepository;
use App\Repositories\NoticeRepository;

/**
 * GET /api/dashboard
 *
 * Returns role-scoped counters for the authenticated user's home dashboard.
 * Currently implements the morador view (S1-06).
 */
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
            ]);
            return;
        }

        $payload = match ($role) {
            'morador'  => $this->moradorPayload($user, $condominiumId),
            default    => $this->moradorPayload($user, $condominiumId),
        };

        Response::json($payload);
    }

    /**
     * Dashboard counters scoped to the resident's own unit / requests.
     *
     * counters:
     *   notices_unread          – total condo notices (proxy until notice_reads exists, S4-02)
     *   maintenance_open_mine   – open/in-progress requests submitted by this user
     *   deliveries_pending_mine – deliveries awaiting pickup at this unit
     */
    private function moradorPayload(array $user, int $condominiumId): array
    {
        $userId = (int) $user['id'];
        $unitId = isset($user['unit_id']) && $user['unit_id'] !== null
            ? (int) $user['unit_id']
            : null;

        $noticesUnread = (new NoticeRepository())->countByCondominium($condominiumId);

        $maintenanceOpenMine = (new MaintenanceRepository())
            ->countOpenByRequester($userId, $condominiumId);

        $deliveriesPendingMine = $unitId !== null
            ? (new DeliveryRepository())->countPendingByUnit($unitId, $condominiumId)
            : 0;

        return [
            'role'     => 'morador',
            'counters' => [
                'notices_unread'          => $noticesUnread,
                'maintenance_open_mine'   => $maintenanceOpenMine,
                'deliveries_pending_mine' => $deliveriesPendingMine,
            ],
        ];
    }
}
