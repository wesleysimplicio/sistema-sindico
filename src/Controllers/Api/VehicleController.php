<?php

declare(strict_types=1);

namespace App\Controllers\Api;

use App\Core\Auth;
use App\Core\Request;
use App\Core\Response;
use App\Repositories\VehicleRepository;
use PDOException;

final class VehicleController
{
    private const TYPES = ['car', 'motorcycle', 'bike', 'other'];

    public function index(array $params): void
    {
        $condoId = (int) ($params['c'] ?? 0);
        $unitId  = (int) ($params['u'] ?? 0);
        if (!self::guardRead($condoId)) {
            return;
        }
        $items = (new VehicleRepository())->allByUnit($condoId, $unitId);
        Response::json($items, 200, ['count' => count($items)]);
    }

    public function store(array $params): void
    {
        $condoId = (int) ($params['c'] ?? 0);
        $unitId  = (int) ($params['u'] ?? 0);
        if (!self::guardWrite($condoId, $unitId)) {
            return;
        }

        $plate = strtoupper(trim((string) Request::input('plate', '')));
        if ($plate === '') {
            Response::error('Placa obrigatoria.', 422);
            return;
        }

        $type = (string) Request::input('vehicle_type', 'car');
        if (!in_array($type, self::TYPES, true)) {
            $type = 'car';
        }

        $data = [
            'plate'        => $plate,
            'brand'        => self::nullableStr(Request::input('brand')),
            'model'        => self::nullableStr(Request::input('model')),
            'color'        => self::nullableStr(Request::input('color')),
            'vehicle_type' => $type,
            'parking_spot' => self::nullableStr(Request::input('parking_spot')),
            'resident_id'  => self::nullableInt(Request::input('resident_id')),
        ];

        try {
            $id = (new VehicleRepository())->createForUnit($condoId, $unitId, $data);
        } catch (PDOException $e) {
            if ($e->getCode() === '23000') {
                Response::error('Placa ja cadastrada neste condominio.', 409);
                return;
            }
            throw $e;
        }
        Response::json(['id' => $id], 201);
    }

    public function update(array $params): void
    {
        $condoId = (int) ($params['c'] ?? 0);
        $unitId  = (int) ($params['u'] ?? 0);
        $id      = (int) ($params['vid'] ?? 0);
        if (!self::guardWrite($condoId, $unitId)) {
            return;
        }
        $repo = new VehicleRepository();
        $existing = $repo->findScoped($condoId, $unitId, $id);
        if ($existing === null) {
            Response::error('Veiculo nao encontrado.', 404);
            return;
        }

        $body = Request::all();
        $allowed = ['plate', 'brand', 'model', 'color', 'vehicle_type', 'parking_spot', 'resident_id'];
        $patch = [];
        foreach ($allowed as $field) {
            if (array_key_exists($field, $body)) {
                $value = $body[$field];
                if ($field === 'plate' && is_string($value)) {
                    $value = strtoupper(trim($value));
                }
                if ($field === 'vehicle_type' && !in_array((string) $value, self::TYPES, true)) {
                    continue;
                }
                $patch[$field] = $value;
            }
        }
        if (empty($patch)) {
            Response::error('Nada para atualizar.', 422);
            return;
        }

        try {
            $repo->update($id, $patch);
        } catch (PDOException $e) {
            if ($e->getCode() === '23000') {
                Response::error('Placa ja cadastrada neste condominio.', 409);
                return;
            }
            throw $e;
        }
        Response::json(['updated' => true]);
    }

    public function destroy(array $params): void
    {
        $condoId = (int) ($params['c'] ?? 0);
        $unitId  = (int) ($params['u'] ?? 0);
        $id      = (int) ($params['vid'] ?? 0);
        if (!self::guardWrite($condoId, $unitId)) {
            return;
        }
        $repo = new VehicleRepository();
        if ($repo->findScoped($condoId, $unitId, $id) === null) {
            Response::error('Veiculo nao encontrado.', 404);
            return;
        }
        $repo->delete($id);
        Response::json(['deleted' => true]);
    }

    private static function guardRead(int $condoId): bool
    {
        $user = Auth::user();
        if ($user === null) {
            Response::error('Nao autenticado.', 401);
            return false;
        }
        if (!self::canAccessCondo($user, $condoId)) {
            Response::error('Forbidden', 403);
            return false;
        }
        return true;
    }

    private static function guardWrite(int $condoId, int $unitId): bool
    {
        $user = Auth::user();
        if ($user === null) {
            Response::error('Nao autenticado.', 401);
            return false;
        }
        if (!self::canAccessCondo($user, $condoId)) {
            Response::error('Forbidden', 403);
            return false;
        }
        $role = $user['role'] ?? null;
        if ($role === 'porteiro') {
            Response::error('Sem permissao.', 403);
            return false;
        }
        if ($role === 'morador') {
            $userUnit = isset($user['unit_id']) ? (int) $user['unit_id'] : 0;
            if ($userUnit !== $unitId) {
                Response::error('Sem permissao.', 403);
                return false;
            }
        }
        return true;
    }

    private static function canAccessCondo(array $user, int $condoId): bool
    {
        if (($user['role'] ?? null) === 'admin') {
            return true;
        }
        $userCondo = isset($user['condominium_id']) ? (int) $user['condominium_id'] : 0;
        return $userCondo === $condoId;
    }

    private static function nullableStr(mixed $v): ?string
    {
        if ($v === null) {
            return null;
        }
        $v = trim((string) $v);
        return $v === '' ? null : $v;
    }

    private static function nullableInt(mixed $v): ?int
    {
        if ($v === null || $v === '') {
            return null;
        }
        return (int) $v;
    }
}
