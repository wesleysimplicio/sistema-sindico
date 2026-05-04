<?php

declare(strict_types=1);

namespace App\Controllers\Api;

use App\Core\Auth;
use App\Core\Response;
use App\Repositories\DeliveryRepository;
use App\Repositories\MaintenanceRepository;
use App\Repositories\NoticeRepository;
use App\Repositories\UnitRepository;
use App\Repositories\UserRepository;
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

        $role = (string) ($user['role'] ?? 'morador');
        $cid  = isset($user['condominium_id']) ? (int) $user['condominium_id'] : 0;

        if ($cid <= 0) {
            Response::json([
                'role'      => $role,
                'counters'  => [],
                'shortcuts' => [],
                'lists'     => [],
            ]);
            return;
        }

        $payload = match ($role) {
            'morador'  => $this->moradorPayload($user, $cid),
            'porteiro' => $this->porteiroPayload($cid),
            default    => $this->sindicoPayload($cid),
        };

        Response::json(array_merge(['role' => $role], $payload));
    }

    private function moradorPayload(array $user, int $cid): array
    {
        $uid         = (int) $user['id'];
        $maintenance = (new MaintenanceRepository())->listByUser($uid);
        $deliveries  = (new DeliveryRepository())->listByResident($uid);
        $notices     = (new NoticeRepository())->listByCondominium($cid, 5);

        $openMaint      = count(array_filter($maintenance, static fn($m) => $m['status'] === 'aberto'));
        $pendingDeliv   = count(array_filter($deliveries,  static fn($d) => $d['status'] === 'aguardando'));

        return [
            'counters' => [
                'notices'            => count($notices),
                'open_maintenance'   => $openMaint,
                'pending_deliveries' => $pendingDeliv,
            ],
            'shortcuts' => [
                ['label' => 'Avisos',     'action' => 'notices'],
                ['label' => 'Manutenção', 'action' => 'maintenance'],
                ['label' => 'Encomendas', 'action' => 'deliveries'],
                ['label' => 'Visitantes', 'action' => 'visitors'],
                ['label' => 'Documentos', 'action' => 'documents'],
                ['label' => 'Reservas',   'action' => 'bookings'],
            ],
            'lists' => [
                'notices'     => $notices,
                'maintenance' => array_slice($maintenance, 0, 5),
                'deliveries'  => array_slice($deliveries,  0, 5),
            ],
        ];
    }

    private function sindicoPayload(int $cid): array
    {
        $openMaint = count((new MaintenanceRepository())->listByCondominium($cid, 'aberto'));
        $residents = count((new UserRepository())->listByCondominium($cid, 'morador'));
        $units     = count((new UnitRepository())->listByCondominium($cid));
        $notices   = (new NoticeRepository())->listByCondominium($cid, 5);
        $visitors  = array_slice((new VisitorRepository())->listByCondominium($cid, 'esperado'), 0, 5);

        return [
            'counters' => [
                'residents'        => $residents,
                'units'            => $units,
                'open_maintenance' => $openMaint,
            ],
            'shortcuts' => [
                ['label' => 'Manutenção', 'action' => 'maintenance'],
                ['label' => 'Avisos',     'action' => 'notices'],
                ['label' => 'Moradores',  'action' => 'residents'],
                ['label' => 'Visitantes', 'action' => 'visitors'],
                ['label' => 'Documentos', 'action' => 'documents'],
                ['label' => 'Pagamentos', 'action' => 'payments'],
            ],
            'lists' => [
                'notices'  => $notices,
                'visitors' => $visitors,
            ],
        ];
    }

    private function porteiroPayload(int $cid): array
    {
        $deliveriesToday   = (new DeliveryRepository())->listTodayByCondominium($cid);
        $expectedVisitors  = array_slice(
            (new VisitorRepository())->listByCondominium($cid, 'esperado'),
            0,
            10
        );

        return [
            'counters' => [
                'deliveries_today'  => count($deliveriesToday),
                'expected_visitors' => count($expectedVisitors),
            ],
            'shortcuts' => [
                ['label' => 'Encomendas',  'action' => 'deliveries'],
                ['label' => 'Visitantes',  'action' => 'visitors'],
                ['label' => 'Acessos',     'action' => 'access_logs'],
                ['label' => 'Ocorrencias', 'action' => 'incidents'],
            ],
            'lists' => [
                'deliveries_today'  => $deliveriesToday,
                'expected_visitors' => $expectedVisitors,
            ],
        ];
    }
}
