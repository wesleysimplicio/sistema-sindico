<?php

declare(strict_types=1);

namespace App\Core;

use App\Repositories\UserRepository;

final class Auth
{
    private static ?array $user = null;
    private static ?string $jti = null;

    public static function setJti(?string $jti): void
    {
        self::$jti = $jti;
    }

    public static function jti(): ?string
    {
        return self::$jti;
    }

    public static function user(): ?array
    {
        if (self::$user !== null) {
            return self::$user;
        }
        $id = Session::get('user_id');
        if ($id === null) {
            return null;
        }
        return self::$user = (new UserRepository())->find((int) $id);
    }

    public static function check(): bool
    {
        return self::user() !== null;
    }

    public static function login(array $user): void
    {
        Session::start();
        session_regenerate_id(true);
        Session::put('user_id', (int) $user['id']);
        self::$user = $user;
    }

    public static function logout(): void
    {
        Session::flush();
        self::$user = null;
    }

    public static function attempt(string $email, string $password): ?array
    {
        $user = (new UserRepository())->findByEmail($email);
        if ($user === null) {
            return null;
        }
        if (!password_verify($password, $user['password_hash'])) {
            return null;
        }
        self::login($user);
        return $user;
    }

    public static function id(): ?int
    {
        $u = self::user();
        return $u ? (int) $u['id'] : null;
    }

    public static function role(): ?string
    {
        $u = self::user();
        return $u['role'] ?? null;
    }

    public static function condominiumId(): ?int
    {
        $u = self::user();
        return $u && isset($u['condominium_id']) ? (int) $u['condominium_id'] : null;
    }

    public static function setApiUser(array $user): void
    {
        self::$user = $user;
    }
}
