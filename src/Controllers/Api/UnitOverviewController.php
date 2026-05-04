<?php

declare(strict_types=1);

namespace App\Controllers\Api;

use App\Core\Auth;
use App\Core\Response;
use App\Repositories\ContractorRepository;
use App\Repositories\DeliveryRepository;
use App\Repositories\PorterNoteRepository;
use App\Repositories\ResidentRepository;
use App\Repositories\UnitRepository;
use App\Repositories\VehicleRepository;
use App\Repositories\VisitorRepository;

final class UnitOverviewController
{
    public function show(array $params): void
    {
        $condoId = (int) ($params['c'] ?? 0);
        $unitId  = (int) ($params['u'] ?? 0);

        $user = Auth::user();
        if ($user === null) {
            Response::error('Nao autenticado.', 401);
            return;
        }
        if (!self::canSeeCondo($user, $condoId)) {
            Response::error('Forbidden', 403);
            return;
        }

        $unit = (new UnitRepository())->find($unitId);
        if ($unit === null || (int) $unit['condominium_id'] !== $condoId) {
            Response::error('Unidade nao encontrada.', 404);
            return;
        }

        $contractorRepo = new ContractorRepository();
        $contractorRepo->markExpired($condoId);
        $contractors = array_map(
            static fn(array $c) => self::projectContractor($c),
            $contractorRepo->allByUnit($condoId, $unitId)
        );

        $residents = (new ResidentRepository())->allByUnit($condoId, $unitId);
        $vehicles  = (new VehicleRepository())->allByUnit($condoId, $unitId);

        $lastVisitor  = self::lastVisitorForUnit($condoId, $unitId);
        $lastDelivery = self::lastDeliveryForUnit($condoId, $unitId);
        $porterNotes  = (new PorterNoteRepository())->lastForUnit($condoId, $unitId, 5);

        Response::json([
            'unit'          => $unit,
            'residents'     => $residents,
            'vehicles'      => $vehicles,
            'contractors'   => $contractors,
            'last_visitor'  => $lastVisitor,
            'last_delivery' => $lastDelivery,
            'porter_notes'  => $porterNotes,
        ]);
    }

    private static function canSeeCondo(array $user, int $condoId): bool
    {
        $role = $user['role'] ?? null;
        if ($role === 'admin') {
            return true;
        }
        $userCondo = isset($user['condominium_id']) ? (int) $user['condominium_id'] : 0;
        return $userCondo === $condoId;
    }

    private static function projectContractor(array $row): array
    {
        $today = date('Y-m-d');
        $endsAt = $row['access_ends_at'] ?? null;
        $status = (string) $row['status'];
        if ($endsAt !== null && $endsAt < $today && !in_array($status, ['expired', 'revoked'], true)) {
            $row['status'] = 'expired';
        }
        return $row;
    }

    private static function lastVisitorForUnit(int $condoId, int $unitId): ?array
    {
        return (new VisitorRepository())->findLatestForUnit($condoId, $unitId);
    }

    private static function lastDeliveryForUnit(int $condoId, int $unitId): ?array
    {
        return (new DeliveryRepository())->findLatestForUnit($condoId, $unitId);
    }
}
