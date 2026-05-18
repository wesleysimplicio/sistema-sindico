<?php

declare(strict_types=1);

namespace App\Repositories;

final class AdoptionMetricsRepository extends BaseRepository
{
    protected string $table = 'api_tokens';

    private const KNOWN_ROLES = ['admin', 'sindico', 'morador', 'porteiro'];

    public function summaryForCondominium(int $condominiumId): array
    {
        $mauByRole = array_fill_keys(self::KNOWN_ROLES, 0);
        foreach ($this->activeUsersByRole30d($condominiumId) as $role => $count) {
            $mauByRole[$role] = $count;
        }

        $samples = $this->visitRegistrationSamples($condominiumId);

        return [
            'active_users_30d' => $this->activeUsers30d($condominiumId),
            'mau_by_role' => $mauByRole,
            'avg_visit_registration_seconds_p50' => self::percentile($samples, 0.50),
            'avg_visit_registration_seconds_p95' => self::percentile($samples, 0.95),
        ];
    }

    private function activeUsers30d(int $condominiumId): int
    {
        $stmt = $this->pdo->prepare(
            'SELECT COUNT(DISTINCT t.user_id) AS total
             FROM api_tokens t
             INNER JOIN users u ON u.id = t.user_id
             WHERE u.condominium_id = :cid
               AND u.active = 1
               AND t.last_used_at IS NOT NULL
               AND t.last_used_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)'
        );
        $stmt->execute(['cid' => $condominiumId]);

        return (int) ($stmt->fetch()['total'] ?? 0);
    }

    /**
     * @return array<string,int>
     */
    private function activeUsersByRole30d(int $condominiumId): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT u.role, COUNT(DISTINCT t.user_id) AS total
             FROM api_tokens t
             INNER JOIN users u ON u.id = t.user_id
             WHERE u.condominium_id = :cid
               AND u.active = 1
               AND t.last_used_at IS NOT NULL
               AND t.last_used_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
             GROUP BY u.role'
        );
        $stmt->execute(['cid' => $condominiumId]);

        $out = [];
        foreach ($stmt->fetchAll() as $row) {
            $role = (string) ($row['role'] ?? '');
            if (!in_array($role, self::KNOWN_ROLES, true)) {
                continue;
            }
            $out[$role] = (int) ($row['total'] ?? 0);
        }

        return $out;
    }

    /**
     * @return list<int>
     */
    private function visitRegistrationSamples(int $condominiumId): array
    {
        $stmt = $this->pdo->prepare(
            "SELECT TIMESTAMPDIFF(SECOND, created_logs.created_at, qr_logs.qr_issued_at) AS elapsed_seconds
             FROM (
               SELECT entity_id, MIN(created_at) AS created_at
               FROM audit_logs
               WHERE condominium_id = :cid_created
                 AND entity = 'visitor'
                 AND action = 'visitor.created'
                 AND entity_id IS NOT NULL
               GROUP BY entity_id
             ) AS created_logs
             INNER JOIN (
               SELECT entity_id, MIN(created_at) AS qr_issued_at
               FROM audit_logs
               WHERE condominium_id = :cid_qr
                 AND entity = 'visitor'
                 AND action = 'visitor.qr_issued'
                 AND entity_id IS NOT NULL
               GROUP BY entity_id
             ) AS qr_logs
               ON qr_logs.entity_id = created_logs.entity_id
             WHERE qr_logs.qr_issued_at >= created_logs.created_at
             ORDER BY elapsed_seconds ASC"
        );
        $stmt->execute([
            'cid_created' => $condominiumId,
            'cid_qr' => $condominiumId,
        ]);

        $values = [];
        foreach ($stmt->fetchAll() as $row) {
            $seconds = isset($row['elapsed_seconds']) ? (int) $row['elapsed_seconds'] : null;
            if ($seconds === null || $seconds < 0) {
                continue;
            }
            $values[] = $seconds;
        }

        return $values;
    }

    /**
     * @param list<int> $values
     */
    private static function percentile(array $values, float $percentile): ?int
    {
        if ($values === []) {
            return null;
        }

        sort($values, SORT_NUMERIC);
        $position = (int) ceil($percentile * count($values)) - 1;
        $position = max(0, min(count($values) - 1, $position));

        return $values[$position];
    }
}
