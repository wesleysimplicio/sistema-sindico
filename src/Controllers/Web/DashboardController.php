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

final class DashboardController
{
    public function index(): void
    {
        $cid  = Auth::condominiumId();
        $user = Auth::user();

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
}
