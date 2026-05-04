<?php

declare(strict_types=1);

namespace App\Repositories;

final class GateTriggerLogRepository extends BaseRepository
{
    protected string $table = 'gate_trigger_logs';

    public function record(
        int $gateTriggerId,
        ?int $userId,
        string $result,
        ?int $httpStatus,
        ?int $durationMs,
        ?string $errorMessage = null
    ): int {
        if (!in_array($result, ['success', 'failure'], true)) {
            throw new \InvalidArgumentException('Invalid gate trigger result.');
        }
        $stmt = $this->pdo->prepare(
            'INSERT INTO gate_trigger_logs (gate_trigger_id, user_id, result, http_status, duration_ms, error_message)
             VALUES (:gtid, :uid, :res, :status, :dur, :err)'
        );
        $stmt->execute([
            'gtid'   => $gateTriggerId,
            'uid'    => $userId,
            'res'    => $result,
            'status' => $httpStatus,
            'dur'    => $durationMs,
            'err'    => $errorMessage,
        ]);
        return (int) $this->pdo->lastInsertId();
    }

    public function listForTrigger(int $gateTriggerId, int $limit = 50): array
    {
        $limit = max(1, min(500, $limit));
        $stmt = $this->pdo->prepare(
            'SELECT l.id, l.gate_trigger_id, l.user_id, l.result, l.http_status, l.duration_ms,
                    l.error_message, l.created_at, u.name AS user_name
             FROM gate_trigger_logs l
             LEFT JOIN users u ON u.id = l.user_id
             WHERE l.gate_trigger_id = :gtid
             ORDER BY l.id DESC
             LIMIT :lim'
        );
        $stmt->bindValue('gtid', $gateTriggerId, \PDO::PARAM_INT);
        $stmt->bindValue('lim', $limit, \PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }
}
