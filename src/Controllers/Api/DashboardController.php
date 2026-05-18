<?php

declare(strict_types=1);

namespace App\Controllers\Api;

use App\Core\Auth;
use App\Core\Database;
use App\Core\Response;
use App\Repositories\AdoptionMetricsRepository;
use App\Repositories\NoticeRepository;
use PDO;

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

    private function pdo(): PDO
    {
        $config = require dirname(__DIR__, 3) . '/config/app.php';
        return Database::connection($config['db']);
    }

    private function moradorPayload(array $user, int $condominiumId): array
    {
        $pdo = $this->pdo();
        $userId = (int) $user['id'];
        $unitId = isset($user['unit_id']) && $user['unit_id'] !== null ? (int) $user['unit_id'] : null;

        // Notices unread proxy: count all condo notices (no notice_reads table yet).
        $stmt = $pdo->prepare('SELECT COUNT(*) AS c FROM notices WHERE condominium_id = :cid');
        $stmt->execute(['cid' => $condominiumId]);
        $noticesUnread = (int) ($stmt->fetch()['c'] ?? 0);

        $stmt = $pdo->prepare(
            "SELECT COUNT(*) AS c FROM maintenance_requests
             WHERE requester_id = :uid
               AND condominium_id = :cid
               AND status IN ('aberto','em_andamento')"
        );
        $stmt->execute(['uid' => $userId, 'cid' => $condominiumId]);
        $maintenanceOpenMine = (int) ($stmt->fetch()['c'] ?? 0);

        $deliveriesPendingMine = 0;
        if ($unitId !== null) {
            $stmt = $pdo->prepare(
                'SELECT COUNT(*) AS c FROM deliveries
                 WHERE unit_id = :uid AND condominium_id = :cid AND withdrawn_at IS NULL'
            );
            $stmt->execute(['uid' => $unitId, 'cid' => $condominiumId]);
            $deliveriesPendingMine = (int) ($stmt->fetch()['c'] ?? 0);
        }

        $recentNotices = (new NoticeRepository())->listByCondominium($condominiumId, 5);

        return [
            'role'     => 'morador',
            'counters' => [
                'notices_unread'          => $noticesUnread,
                'maintenance_open_mine'   => $maintenanceOpenMine,
                'deliveries_pending_mine' => $deliveriesPendingMine,
            ],
            'lists' => [
                'recent_notices' => $recentNotices,
            ],
        ];
    }

    private function sindicoPayload(int $condominiumId): array
    {
        $pdo = $this->pdo();
        $adoptionMetrics = (new AdoptionMetricsRepository())->summaryForCondominium($condominiumId);

        $stmt = $pdo->prepare(
            "SELECT COUNT(*) AS c FROM maintenance_requests
             WHERE condominium_id = :cid AND status IN ('aberto','em_andamento')"
        );
        $stmt->execute(['cid' => $condominiumId]);
        $maintenanceOpen = (int) ($stmt->fetch()['c'] ?? 0);

        $stmt = $pdo->prepare(
            'SELECT COUNT(*) AS c FROM deliveries
             WHERE condominium_id = :cid AND received_at >= CURDATE()'
        );
        $stmt->execute(['cid' => $condominiumId]);
        $deliveriesToday = (int) ($stmt->fetch()['c'] ?? 0);

        $noticesRecent = (new NoticeRepository())->listByCondominium($condominiumId, 5);

        $stmt = $pdo->prepare(
            'SELECT id, name, document, unit_id, status, expected_at, qr_token, created_at
             FROM visitors
             WHERE condominium_id = :cid
             ORDER BY created_at DESC
             LIMIT 5'
        );
        $stmt->execute(['cid' => $condominiumId]);
        $visitorsRecent = $stmt->fetchAll();

        return [
            'role'     => 'sindico',
            'counters' => [
                'maintenance_open' => $maintenanceOpen,
                'deliveries_today' => $deliveriesToday,
            ],
            'lists' => [
                'notices_recent'  => $noticesRecent,
                'visitors_recent' => $visitorsRecent,
            ],
            'metrics' => [
                'adoption' => $adoptionMetrics,
            ],
        ];
    }

    private function porteiroPayload(int $condominiumId): array
    {
        $pdo = $this->pdo();

        $stmt = $pdo->prepare(
            'SELECT COUNT(*) AS c FROM deliveries
             WHERE condominium_id = :cid AND received_at >= CURDATE()'
        );
        $stmt->execute(['cid' => $condominiumId]);
        $deliveriesTodayCount = (int) ($stmt->fetch()['c'] ?? 0);

        $stmt = $pdo->prepare(
            "SELECT COUNT(*) AS c FROM visitors
             WHERE condominium_id = :cid AND status = 'previsto'"
        );
        $stmt->execute(['cid' => $condominiumId]);
        $visitorsExpected = (int) ($stmt->fetch()['c'] ?? 0);

        return [
            'role'     => 'porteiro',
            'counters' => [
                'deliveries_today_count' => $deliveriesTodayCount,
                'visitors_expected'      => $visitorsExpected,
            ],
            'lists' => [
                'recent_access' => [],
            ],
        ];
    }
}
