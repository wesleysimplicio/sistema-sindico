<?php

declare(strict_types=1);

namespace App\Core;

use PDO;
use Throwable;

final class MySQLRateLimitStore implements RateLimitStore
{
    public function __construct(private readonly array $dbConfig)
    {
    }

    public function increment(string $bucket, string $key, int $windowSec): int
    {
        $pdo = Database::connection($this->dbConfig);
        $hash = self::hashKey($bucket, $key);

        try {
            $pdo->beginTransaction();
            $sel = $pdo->prepare(
                'SELECT id, count, UNIX_TIMESTAMP(window_start) AS ts
                 FROM rate_limits
                 WHERE bucket = :b AND key_hash = :k
                 FOR UPDATE'
            );
            $sel->execute(['b' => $bucket, 'k' => $hash]);
            $row = $sel->fetch(PDO::FETCH_ASSOC);
            $now = time();

            if ($row === false) {
                $ins = $pdo->prepare(
                    'INSERT INTO rate_limits (bucket, key_hash, count, window_start)
                     VALUES (:b, :k, 1, FROM_UNIXTIME(:ts))'
                );
                $ins->execute(['b' => $bucket, 'k' => $hash, 'ts' => $now]);
                $count = 1;
            } else {
                $age = $now - (int) $row['ts'];
                if ($age >= $windowSec) {
                    $upd = $pdo->prepare(
                        'UPDATE rate_limits
                         SET count = 1, window_start = FROM_UNIXTIME(:ts)
                         WHERE id = :id'
                    );
                    $upd->execute(['ts' => $now, 'id' => (int) $row['id']]);
                    $count = 1;
                } else {
                    $upd = $pdo->prepare(
                        'UPDATE rate_limits SET count = count + 1 WHERE id = :id'
                    );
                    $upd->execute(['id' => (int) $row['id']]);
                    $count = ((int) $row['count']) + 1;
                }
            }

            $pdo->commit();
            return $count;
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }

            throw $e;
        }
    }

    private static function hashKey(string $bucket, string $key): string
    {
        return hash('sha256', $bucket . '|' . $key);
    }
}
