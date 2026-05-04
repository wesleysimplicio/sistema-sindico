<?php

declare(strict_types=1);

namespace App\Repositories;

final class NotificationPreferenceRepository extends BaseRepository
{
    protected string $table = 'notification_preferences';

    public const CHANNELS = ['inapp', 'push', 'email'];
    public const EVENTS   = [
        'notice',
        'maintenance',
        'incident',
        'delivery',
        'visitor',
        'access',
        'booking',
        'message',
    ];

    /**
     * @return array<int,array{channel:string,event:string,enabled:bool}>
     */
    public function listForUser(int $userId): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT pref_key, enabled FROM notification_preferences WHERE user_id = :uid'
        );
        $stmt->execute(['uid' => $userId]);
        $rows = $stmt->fetchAll();

        $stored = [];
        foreach ($rows as $r) {
            $stored[(string) $r['pref_key']] = (int) $r['enabled'] === 1;
        }

        $out = [];
        foreach (self::CHANNELS as $c) {
            foreach (self::EVENTS as $e) {
                $key = $c . ':' . $e;
                $out[] = [
                    'channel' => $c,
                    'event'   => $e,
                    'enabled' => $stored[$key] ?? true,
                ];
            }
        }
        return $out;
    }

    /**
     * @param array<int,array{channel?:string,event?:string,enabled?:bool|int|string}> $matrix
     */
    public function setForUser(int $userId, array $matrix): int
    {
        $valid = [];
        foreach ($matrix as $row) {
            if (!is_array($row)) {
                continue;
            }
            $c = (string) ($row['channel'] ?? '');
            $e = (string) ($row['event']   ?? '');
            if (!in_array($c, self::CHANNELS, true) || !in_array($e, self::EVENTS, true)) {
                continue;
            }
            $enabled = $row['enabled'] ?? true;
            if (is_string($enabled)) {
                $enabled = $enabled === '1' || strtolower($enabled) === 'true';
            }
            $valid[$c . ':' . $e] = (int) (bool) $enabled;
        }

        $this->pdo->beginTransaction();
        try {
            $del = $this->pdo->prepare('DELETE FROM notification_preferences WHERE user_id = :uid');
            $del->execute(['uid' => $userId]);

            $ins = $this->pdo->prepare(
                'INSERT INTO notification_preferences (user_id, pref_key, enabled)
                 VALUES (:uid, :k, :en)'
            );
            $count = 0;
            foreach ($valid as $k => $en) {
                $ins->execute(['uid' => $userId, 'k' => $k, 'en' => $en]);
                $count++;
            }
            $this->pdo->commit();
            return $count;
        } catch (\Throwable $e) {
            $this->pdo->rollBack();
            throw $e;
        }
    }

    public function isEnabled(int $userId, string $channel, string $event): bool
    {
        if (!in_array($channel, self::CHANNELS, true) || !in_array($event, self::EVENTS, true)) {
            return false;
        }
        $stmt = $this->pdo->prepare(
            'SELECT enabled FROM notification_preferences
             WHERE user_id = :uid AND pref_key = :k LIMIT 1'
        );
        $stmt->execute(['uid' => $userId, 'k' => $channel . ':' . $event]);
        $row = $stmt->fetch();
        if ($row === false) {
            return true;
        }
        return (int) $row['enabled'] === 1;
    }
}
