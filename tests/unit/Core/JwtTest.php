<?php

declare(strict_types=1);

namespace Tests\Core;

use App\Core\Jwt;
use PHPUnit\Framework\TestCase;
use RuntimeException;

final class JwtTest extends TestCase
{
    public function test_encode_and_decode_round_trip(): void
    {
        $secret = '12345678901234567890123456789012';
        $token = Jwt::encode(['sub' => 42], $secret, 3600);

        $payload = Jwt::decode($token, $secret);

        self::assertIsArray($payload);
        self::assertSame(42, $payload['sub']);
    }

    public function test_encode_rejects_short_secret(): void
    {
        $this->expectException(RuntimeException::class);

        Jwt::encode(['sub' => 42], 'short');
    }
}
