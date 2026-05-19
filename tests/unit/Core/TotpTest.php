<?php

declare(strict_types=1);

namespace Tests\Core;

use App\Core\Totp;
use PHPUnit\Framework\TestCase;

final class TotpTest extends TestCase
{
    public function test_generate_secret_has_expected_shape(): void
    {
        $secret = Totp::generateSecret();

        self::assertMatchesRegularExpression('/^[A-Z2-7]+$/', $secret);
        self::assertGreaterThanOrEqual(32, strlen($secret));
    }

    public function test_verify_accepts_current_code_for_known_secret(): void
    {
        $secret = 'JBSWY3DPEHPK3PXP';
        $code = $this->currentTotpCode($secret);

        self::assertTrue(Totp::verify($secret, $code));
        self::assertFalse(Totp::verify($secret, '000000'));
    }

    private function currentTotpCode(string $secret): string
    {
        $key = $this->base32Decode($secret);
        $counter = (int) floor(time() / 30);
        $bin = pack('N*', 0) . pack('N*', $counter);
        $hmac = hash_hmac('sha1', $bin, $key, true);
        $offset = ord($hmac[strlen($hmac) - 1]) & 0x0F;
        $value = ((ord($hmac[$offset]) & 0x7F) << 24)
            | ((ord($hmac[$offset + 1]) & 0xFF) << 16)
            | ((ord($hmac[$offset + 2]) & 0xFF) << 8)
            | (ord($hmac[$offset + 3]) & 0xFF);

        return str_pad((string) ($value % 1000000), 6, '0', STR_PAD_LEFT);
    }

    private function base32Decode(string $b32): string
    {
        $alphabet = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
        $bits = '';
        foreach (str_split($b32) as $char) {
            $bits .= str_pad((string) decbin((int) strpos($alphabet, $char)), 5, '0', STR_PAD_LEFT);
        }
        $output = '';
        foreach (str_split($bits, 8) as $byte) {
            if (strlen($byte) === 8) {
                $output .= chr((int) bindec($byte));
            }
        }
        return $output;
    }
}
