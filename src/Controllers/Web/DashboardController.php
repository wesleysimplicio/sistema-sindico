<?php

declare(strict_types=1);

namespace App\Controllers\Web;

use App\Core\Auth;
use App\Core\View;
use App\Repositories\CondominiumRepository;
use App\Repositories\DeliveryRepository;
use App\Repositories\MaintenanceRepository;
use App\Repositories\NoticeRepository;
use App\Repositories\PaymentRepository;
use App\Repositories\UnitRepository;
use App\Repositories\UserRepository;
use App\Repositories\VisitorRepository;

final class DashboardController
{
    public function index(): void
    {
        $user = Auth::user();
        $role = Auth::role() ?? 'morador';

        match ($role) {
            'morador'  => $this->renderMorador($user),
            'porteiro' => $this->renderPorteiro($user),
            default    => $this->renderSindico($user),
        };
    }

    private function renderSindico(?array $user): void
    {
        $cid = Auth::condominiumId();

        $stats = [
            'residents'          => 0,
            'units'              => 0,
            'open_maintenance'   => 0,
            'pending_payments'   => 0,
            'pending_deliveries' => 0,
        ];
        $notices  = [];
        $condo    = null;
        $payments = [];

        if ($cid !== null) {
            $condo = (new CondominiumRepository())->find($cid);

            $stats['residents']          = count((new UserRepository())->listByCondominium($cid, 'morador'));
            $stats['units']              = count((new UnitRepository())->listByCondominium($cid));
            $stats['open_maintenance']   = count((new MaintenanceRepository())->listByCondominium($cid, 'aberto'));
            $stats['pending_payments']   = count((new PaymentRepository())->listByCondominium($cid, 'pendente'));
            $stats['pending_deliveries'] = count((new DeliveryRepository())->listByCondominium($cid, 'aguardando'));

            $notices  = array_slice((new NoticeRepository())->listByCondominium($cid, 5), 0, 5);
            $payments = (new PaymentRepository())->summaryByCondominium($cid);
        }

        View::render('modules/dashboard', [
            'title'    => 'Dashboard | Sistema Sindico',
            'active'   => 'dashboard',
            'user'     => $user,
            'condo'    => $condo,
            'stats'    => $stats,
            'notices'  => $notices,
            'payments' => $payments,
        ]);
    }

    private function renderMorador(?array $user): void
    {
        $cid = Auth::condominiumId();
        $uid = Auth::id();

        $condo          = null;
        $notices        = [];
        $maintenance    = [];
        $deliveries     = [];
        $openMaint      = 0;
        $pendingDeliv   = 0;

        if ($cid !== null) {
            $condo = (new CondominiumRepository())->find($cid);
            $notices = array_slice((new NoticeRepository())->listByCondominium($cid, 5), 0, 5);
        }

        if ($uid !== null) {
            $maintenance  = array_slice((new MaintenanceRepository())->listByUser($uid), 0, 5);
            $deliveries   = array_slice((new DeliveryRepository())->listByResident($uid),  0, 5);
            $openMaint    = count(array_filter(
                (new MaintenanceRepository())->listByUser($uid),
                static fn($m) => $m['status'] === 'aberto'
            ));
            $pendingDeliv = count(array_filter(
                (new DeliveryRepository())->listByResident($uid),
                static fn($d) => $d['status'] === 'aguardando'
            ));
        }

        View::render('modules/dashboard_morador', [
            'title'       => 'Início | Sistema Síndico',
            'active'      => 'dashboard',
            'user'        => $user,
            'condo'       => $condo,
            'notices'     => $notices,
            'maintenance' => $maintenance,
            'deliveries'  => $deliveries,
            'openMaint'   => $openMaint,
            'pendingDeliv' => $pendingDeliv,
        ]);
    }

    private function renderPorteiro(?array $user): void
    {
        $cid = Auth::condominiumId();

        $condo            = null;
        $deliveriesToday  = [];
        $expectedVisitors = [];

        if ($cid !== null) {
            $condo            = (new CondominiumRepository())->find($cid);
            $deliveriesToday  = (new DeliveryRepository())->listTodayByCondominium($cid);
            $expectedVisitors = array_slice(
                (new VisitorRepository())->listByCondominium($cid, 'esperado'),
                0,
                10
            );
        }

        View::render('modules/dashboard_porteiro', [
            'title'            => 'Portaria | Sistema Síndico',
            'active'           => 'dashboard',
            'user'             => $user,
            'condo'            => $condo,
            'deliveriesToday'  => $deliveriesToday,
            'expectedVisitors' => $expectedVisitors,
        ]);
    }
}
