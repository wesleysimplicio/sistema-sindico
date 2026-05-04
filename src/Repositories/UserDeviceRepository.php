<?php

declare(strict_types=1);

namespace App\Repositories;

final class UserDeviceRepository extends BaseRepository
{
    protected string $table = 'user_devices';

    public const PLATFORMS = ['ios', 'android', 'web'];

    public function listForUser(int $userId): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT id, user_id, platform, device_name, last_used_at, revoked_at, created_at
             FROM user_devices
             WHERE user_id = :uid
             ORDER BY id DESC'
        );
        $stmt->execute(['uid' => $userId]);
        return $stmt->fetchAll();
    }

    /**
     * Upsert by FCM token. If token already stored, refresh metadata + clear revoked_at.
     * If token belonged to another user, the binding migrates to the current user.
     */
    public function register(int $userId, string $platform, string $fcmToken, ?string $deviceName): int
    {
        if (!in_array($platform, self::PLATFORMS, true)) {
            throw new \InvalidArgumentException('Invalid platform.');
        }
        if ($fcmToken === '' || strlen($fcmToken) > 255) {
            throw new \InvalidArgumentException('Invalid fcm_token.');
        }

        $find = $this->pdo->prepare(
            'SELECT id FROM user_devices WHERE fcm_token = :t LIMIT 1'
        );
        $find->execute(['t' => $fcmToken]);
        $existing = $find->fetch();

        if ($existing) {
            $upd = $this->pdo->prepare(
                'UPDATE user_devices
                    SET user_id      = :uid,
                        platform     = :plat,
                        device_name  = :dn,
                        revoked_at   = NULL,
                        last_used_at = NOW()
                  WHERE id = :id'
            );
            $upd->execute([
                'uid'  => $userId,
                'plat' => $platform,
                'dn'   => $deviceName,
                'id'   => (int) $existing['id'],
            ]);
            return (int) $existing['id'];
        }

        $ins = $this->pdo->prepare(
            'INSERT INTO user_devices (user_id, platform, fcm_token, device_name, last_used_at)
             VALUES (:uid, :plat, :t, :dn, NOW())'
        );
        $ins->execute([
            'uid'  => $userId,
            'plat' => $platform,
            't'    => $fcmToken,
            'dn'   => $deviceName,
        ]);
        return (int) $this->pdo->lastInsertId();
    }

    public function revoke(int $id, int $userId): bool
    {
        $stmt = $this->pdo->prepare(
            'UPDATE user_devices SET revoked_at = NOW()
             WHERE id = :id AND user_id = :uid AND revoked_at IS NULL'
        );
        $stmt->execute(['id' => $id, 'uid' => $userId]);
        return $stmt->rowCount() > 0;
    }
}
