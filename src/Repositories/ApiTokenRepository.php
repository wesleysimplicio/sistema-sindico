<?php

declare(strict_types=1);

namespace App\Repositories;

final class ApiTokenRepository extends BaseRepository
{
    protected string $table = 'api_tokens';

    public static function hash(string $jti): string
    {
        return hash('sha256', $jti);
    }

    public function claim(
        int $userId,
        string $jti,
        ?string $device,
        ?string $ip,
        ?string $userAgent,
        int $ttlSeconds
    ): int {
        $hash = self::hash($jti);
        $stmt = $this->pdo->prepare(
            'INSERT INTO api_tokens (user_id, device, token_hash, ip, user_agent, expires_at, last_used_at)
             VALUES (:uid, :dev, :h, :ip, :ua, DATE_ADD(NOW(), INTERVAL :ttl SECOND), NOW())'
        );
        $stmt->execute([
            'uid' => $userId,
            'dev' => $device,
            'h'   => $hash,
            'ip'  => $ip,
            'ua'  => $userAgent !== null ? substr($userAgent, 0, 255) : null,
            'ttl' => $ttlSeconds,
        ]);
        return (int) $this->pdo->lastInsertId();
    }

    public function isActive(string $jti): bool
    {
        $hash = self::hash($jti);
        $stmt = $this->pdo->prepare(
            'SELECT id FROM api_tokens
             WHERE token_hash = :h
               AND revoked_at IS NULL
               AND (expires_at IS NULL OR expires_at > NOW())
             LIMIT 1'
        );
        $stmt->execute(['h' => $hash]);
        $row = $stmt->fetch();
        if ($row === false) {
            return false;
        }
        $upd = $this->pdo->prepare(
            'UPDATE api_tokens
             SET last_used_at = NOW()
             WHERE id = :id
               AND (last_used_at IS NULL OR last_used_at < DATE_SUB(NOW(), INTERVAL 1 MINUTE))'
        );
        $upd->execute(['id' => (int) $row['id']]);
        return true;
    }

    public function listForUser(int $userId, ?string $currentJti = null): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT id, device, ip, user_agent, last_used_at, expires_at, created_at, token_hash
             FROM api_tokens
             WHERE user_id = :uid AND revoked_at IS NULL
             ORDER BY id DESC'
        );
        $stmt->execute(['uid' => $userId]);
        $rows = $stmt->fetchAll();

        $currentHash = $currentJti !== null ? self::hash($currentJti) : null;
        $out = [];
        foreach ($rows as $r) {
            $out[] = [
                'id'           => (int) $r['id'],
                'device'       => $r['device'],
                'ip'           => $r['ip'],
                'user_agent'   => $r['user_agent'],
                'last_used_at' => $r['last_used_at'],
                'expires_at'   => $r['expires_at'],
                'created_at'   => $r['created_at'],
                'current'      => $currentHash !== null && hash_equals((string) $r['token_hash'], $currentHash),
            ];
        }
        return $out;
    }

    public function revokeByJti(string $jti, int $userId): bool
    {
        $stmt = $this->pdo->prepare(
            'UPDATE api_tokens SET revoked_at = NOW()
             WHERE token_hash = :h AND user_id = :uid AND revoked_at IS NULL'
        );
        $stmt->execute(['h' => self::hash($jti), 'uid' => $userId]);
        return $stmt->rowCount() > 0;
    }

    public function revoke(int $id, int $userId): bool
    {
        $stmt = $this->pdo->prepare(
            'UPDATE api_tokens SET revoked_at = NOW()
             WHERE id = :id AND user_id = :uid AND revoked_at IS NULL'
        );
        $stmt->execute(['id' => $id, 'uid' => $userId]);
        return $stmt->rowCount() > 0;
    }

    public function revokeAllExcept(int $userId, ?string $keepJti = null): int
    {
        $sql = 'UPDATE api_tokens SET revoked_at = NOW()
                WHERE user_id = :uid AND revoked_at IS NULL';
        $params = ['uid' => $userId];
        if ($keepJti !== null) {
            $sql .= ' AND token_hash <> :keep';
            $params['keep'] = self::hash($keepJti);
        }
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->rowCount();
    }
}
