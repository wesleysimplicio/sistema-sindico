<?php

declare(strict_types=1);

namespace App\Core;

/**
 * RFC 6238 TOTP (HMAC-SHA1, 6 digits, 30s step).
 * Stateless helper. No deps.
 */
final class Totp
{
    private const ALPHABET = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
    private const PERIOD   = 30;
    private const DIGITS   = 6;

    /** Generate a random base32 secret (default 20 bytes => 32-char base32). */
    public static function generateSecret(int $bytes = 20): string
    {
        $raw = random_bytes($bytes);
        return self::base32Encode($raw);
    }

    /** Build otpauth:// URL for QR code. */
    public static function otpauthUrl(string $secret, string $accountName, string $issuer): string
    {
        $label = rawurlencode($issuer) . ':' . rawurlencode($accountName);
        $params = http_build_query([
            'secret'    => $secret,
            'issuer'    => $issuer,
            'algorithm' => 'SHA1',
            'digits'    => self::DIGITS,
            'period'    => self::PERIOD,
        ]);
        return 'otpauth://totp/' . $label . '?' . $params;
    }

    /** Verify code with ±$window steps tolerance (default ±1 = ±30s). */
    public static function verify(string $secret, string $code, int $window = 1): bool
    {
        $code = preg_replace('/\D/', '', $code) ?? '';
        if (strlen($code) !== self::DIGITS) {
            return false;
        }
        $key = self::base32Decode($secret);
        if ($key === '') {
            return false;
        }
        $now = (int) floor(time() / self::PERIOD);
        for ($i = -$window; $i <= $window; $i++) {
            if (hash_equals(self::compute($key, $now + $i), $code)) {
                return true;
            }
        }
        return false;
    }

    private static function compute(string $key, int $counter): string
    {
        $bin = pack('N*', 0) . pack('N*', $counter);
        $hmac = hash_hmac('sha1', $bin, $key, true);
        $offset = ord($hmac[strlen($hmac) - 1]) & 0x0F;
        $code = ((ord($hmac[$offset]) & 0x7F) << 24)
              | ((ord($hmac[$offset + 1]) & 0xFF) << 16)
              | ((ord($hmac[$offset + 2]) & 0xFF) << 8)
              |  (ord($hmac[$offset + 3]) & 0xFF);
        $mod = (int) (10 ** self::DIGITS);
        return str_pad((string) ($code % $mod), self::DIGITS, '0', STR_PAD_LEFT);
    }

    private static function base32Encode(string $bin): string
    {
        $bits = '';
        foreach (str_split($bin) as $b) {
            $bits .= str_pad(decbin(ord($b)), 8, '0', STR_PAD_LEFT);
        }
        $out = '';
        foreach (str_split($bits, 5) as $chunk) {
            if (strlen($chunk) < 5) {
                $chunk = str_pad($chunk, 5, '0');
            }
            $out .= self::ALPHABET[bindec($chunk)];
        }
        return $out;
    }

    private static function base32Decode(string $b32): string
    {
        $b32 = strtoupper(preg_replace('/[^A-Z2-7]/', '', $b32) ?? '');
        if ($b32 === '') {
            return '';
        }
        $bits = '';
        for ($i = 0, $n = strlen($b32); $i < $n; $i++) {
            $idx = strpos(self::ALPHABET, $b32[$i]);
            if ($idx === false) {
                return '';
            }
            $bits .= str_pad(decbin($idx), 5, '0', STR_PAD_LEFT);
        }
        $out = '';
        foreach (str_split($bits, 8) as $byte) {
            if (strlen($byte) === 8) {
                $out .= chr(bindec($byte));
            }
        }
        return $out;
    }
}
