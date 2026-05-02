<?php

declare(strict_types=1);

namespace App\Core;

final class Jwt
{
    public static function encode(array $payload, string $secret, int $ttlSeconds = 86400): string
    {
        $header = ['alg' => 'HS256', 'typ' => 'JWT'];
        $now = time();
        $payload = array_merge($payload, [
            'iat' => $now,
            'exp' => $now + $ttlSeconds,
        ]);
        $h = self::b64(json_encode($header, JSON_UNESCAPED_SLASHES));
        $p = self::b64(json_encode($payload, JSON_UNESCAPED_SLASHES));
        $sig = self::b64(hash_hmac('sha256', "$h.$p", $secret, true));
        return "$h.$p.$sig";
    }

    public static function decode(string $token, string $secret): ?array
    {
        $parts = explode('.', $token);
        if (count($parts) !== 3) {
            return null;
        }
        [$h, $p, $sig] = $parts;
        $expected = self::b64(hash_hmac('sha256', "$h.$p", $secret, true));
        if (!hash_equals($expected, $sig)) {
            return null;
        }
        $payload = json_decode(self::b64decode($p), true);
        if (!is_array($payload)) {
            return null;
        }
        if (isset($payload['exp']) && $payload['exp'] < time()) {
            return null;
        }
        return $payload;
    }

    private static function b64(string $data): string
    {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }

    private static function b64decode(string $data): string
    {
        $pad = strlen($data) % 4;
        if ($pad) {
            $data .= str_repeat('=', 4 - $pad);
        }
        return base64_decode(strtr($data, '-_', '+/')) ?: '';
    }
}
