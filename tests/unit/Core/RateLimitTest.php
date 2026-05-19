<?php

declare(strict_types=1);

namespace Tests\Core;

use App\Core\RateLimitStore;
use App\Middleware\RateLimit;
use PHPUnit\Framework\TestCase;

final class RateLimitTest extends TestCase
{
    protected function tearDown(): void
    {
        RateLimit::setStoreForTests(null);
    }

    public function test_enforce_blocks_after_threshold(): void
    {
        RateLimit::setStoreForTests(new class implements RateLimitStore {
            private int $count = 0;

            public function increment(string $bucket, string $key, int $windowSec): int
            {
                return ++$this->count;
            }
        });

        ob_start();
        $first = RateLimit::enforce('forgot-password', 1, 60, '127.0.0.1|foo');
        $second = RateLimit::enforce('forgot-password', 1, 60, '127.0.0.1|foo');
        $output = (string) ob_get_clean();

        self::assertTrue($first);
        self::assertFalse($second);
        self::assertStringContainsString('rate_limited', $output);
    }
}
