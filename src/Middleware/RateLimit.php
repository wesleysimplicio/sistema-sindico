<?php

declare(strict_types=1);

namespace App\Middleware;

use App\Core\Request;
use App\Core\RateLimitStore;
use App\Core\RateLimitStoreFactory;
use App\Core\Response;
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
    private static ?RateLimitStore $store = null;

    public static function enforce(string $bucket, int $max, int $windowSec, string $key): bool
    {
        if ($max <= 0 || $windowSec <= 0) {
            return true;
        }

        try {
            $count = self::store()->increment($bucket, $key, $windowSec);
        } catch (Throwable $e) {
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

    private static function store(): RateLimitStore
    {
        return self::$store ??= RateLimitStoreFactory::make();
    }
}
