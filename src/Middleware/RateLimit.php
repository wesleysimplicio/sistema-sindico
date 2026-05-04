<?php

declare(strict_types=1);

namespace App\Middleware;

use App\Core\Database;
use App\Core\Request;
use App\Core\Response;
use PDO;
use Throwable;

/**
 * Sliding-window rate limiter backed by `rate_limits` table.
 *
 * Usage (call BEFORE running controller logic):
 *
 *   if (!RateLimit::enforce('login', 10, 900, $ip . '|' . strtolower($email))) {
 *       return; // 429 already sent
 *   }
 *
 * Buckets in use:
 *   login           — 10 / 900s (IP+email)
 *   forgot-password — 3  / 3600s (IP+identifier)
 *   verify-code     — 5  / 900s (IP+identifier)
 *   twofa-verify    — 5  / 900s (IP+email)
 *   webhook         — 60 / 60s  (IP)
 */
final class RateLimit
{
    public static function enforce(string $bucket, int $max, int $windowSec, string $key): bool
    {
        if ($max <= 0 || $windowSec <= 0) {
            return true;
        }
        $pdo = self::pdo();
        if ($pdo === null) {
            // Fail-open if DB unreachable — auth still requires valid creds; logged elsewhere.
            return true;
        }
        $hash = hash('sha256', $bucket . '|' . $key);
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
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            return true; // fail-open on storage error
        }

        $remaining = max(0, $max - $count);
        if (!headers_sent()) {
            header('X-RateLimit-Limit: ' . $max);
            header('X-RateLimit-Remaining: ' . $remaining);
        }

        if ($count > $max) {
            if (!headers_sent()) {
                header('Retry-After: ' . $windowSec);
            }
            Response::error('Muitas tentativas. Tente novamente mais tarde.', 429);
            return false;
        }
        return true;
    }

    public static function ipKey(string $extra = ''): string
    {
        $ip = Request::ip();
        return $extra === '' ? $ip : $ip . '|' . $extra;
    }

    private static function pdo(): ?PDO
    {
        try {
            $config = require dirname(__DIR__, 2) . '/config/app.php';
            return Database::connection($config['db']);
        } catch (Throwable $e) {
            return null;
        }
    }
}
