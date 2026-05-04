<?php

declare(strict_types=1);

namespace App\Repositories;

final class WebhookNonceRepository extends BaseRepository
{
    protected string $table = 'webhook_nonces';

    /**
     * Atomically claim a nonce. Returns true if newly inserted (first sighting),
     * false if it already existed (replay).
     */
    public function claim(string $signatureHash, int $expiresAtUnix): bool
    {
        if (strlen($signatureHash) !== 64) {
            throw new \InvalidArgumentException('signature_hash must be 64 hex chars.');
        }
        $stmt = $this->pdo->prepare(
            'INSERT IGNORE INTO webhook_nonces (signature_hash, expires_at)
             VALUES (:h, :exp)'
        );
        $stmt->execute([
            'h'   => $signatureHash,
            'exp' => date('Y-m-d H:i:s', $expiresAtUnix),
        ]);
        return $stmt->rowCount() === 1;
    }

    /**
     * Best-effort cleanup. Called opportunistically; not required for correctness.
     */
    public function purgeExpired(): int
    {
        $stmt = $this->pdo->prepare('DELETE FROM webhook_nonces WHERE expires_at < NOW()');
        $stmt->execute();
        return $stmt->rowCount();
    }
}
