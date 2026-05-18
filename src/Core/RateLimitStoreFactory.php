<?php

declare(strict_types=1);

namespace App\Core;

final class RateLimitStoreFactory
{
    public static function make(): RateLimitStore
    {
        $config = require dirname(__DIR__, 2) . '/config/app.php';
        $driver = strtolower(trim((string) ($config['rate_limit']['driver'] ?? 'mysql')));

        if ($driver === 'redis') {
            $redisUrl = trim((string) ($config['rate_limit']['redis_url'] ?? ''));
            if ($redisUrl !== '') {
                return new RedisRateLimitStore(
                    $redisUrl,
                    (string) ($config['rate_limit']['redis_prefix'] ?? 'sistema-sindico:rate-limit')
                );
            }
        }

        return new MySQLRateLimitStore($config['db']);
    }
}
