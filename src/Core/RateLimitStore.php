<?php

declare(strict_types=1);

namespace App\Core;

interface RateLimitStore
{
    public function increment(string $bucket, string $key, int $windowSec): int;
}
