<?php

declare(strict_types=1);

namespace App\Controllers\Api;

use App\Core\Response;

/**
 * Auth scaffold. Real authentication (password hashing, sessions, JWT)
 * will be implemented after persistence wiring.
 */
final class AuthController
{
    public static function login(): void
    {
        $payload = self::readJsonBody();
        $email = isset($payload['email']) ? (string) $payload['email'] : '';

        if ($email === '') {
            Response::error('Campo email obrigatorio.', 422, ['field' => 'email']);
            return;
        }

        Response::json([
            'token' => 'stub-token-' . bin2hex(random_bytes(8)),
            'user'  => [
                'id'    => 1,
                'name'  => 'Sindico Exemplo',
                'email' => $email,
                'role'  => 'sindico',
            ],
        ]);
    }

    public static function logout(): void
    {
        Response::json(['logged_out' => true]);
    }

    /** @return array<string, mixed> */
    private static function readJsonBody(): array
    {
        $raw = file_get_contents('php://input') ?: '';
        if ($raw === '') {
            return [];
        }
        $decoded = json_decode($raw, true);
        return is_array($decoded) ? $decoded : [];
    }
}
