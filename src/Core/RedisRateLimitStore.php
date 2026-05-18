<?php

declare(strict_types=1);

namespace App\Core;

final class RedisRateLimitStore implements RateLimitStore
{
    private const SCRIPT = <<<'LUA'
local key = KEYS[1]
local ttl = tonumber(ARGV[1])
local current = redis.call('INCR', key)
if redis.call('TTL', key) < 0 then
  redis.call('EXPIRE', key, ttl)
end
return current
LUA;

    public function __construct(
        private readonly string $redisUrl,
        private readonly string $prefix = 'sistema-sindico:rate-limit'
    ) {
    }

    public function increment(string $bucket, string $key, int $windowSec): int
    {
        $client = new RedisClient($this->redisUrl);
        $result = $client->command([
            'EVAL',
            self::SCRIPT,
            '1',
            $this->keyFor($bucket, $key),
            (string) $windowSec,
        ]);

        return (int) $result;
    }

    private function keyFor(string $bucket, string $key): string
    {
        return sprintf(
            '%s:%s:%s',
            $this->prefix,
            $bucket,
            hash('sha256', $bucket . '|' . $key)
        );
    }
}
