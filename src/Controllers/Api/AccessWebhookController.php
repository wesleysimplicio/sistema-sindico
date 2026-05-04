<?php

declare(strict_types=1);

namespace App\Controllers\Api;

use App\Core\Response;
use App\Middleware\RateLimit;
use App\Repositories\AccessLogRepository;
use App\Repositories\CondominiumRepository;
use App\Repositories\WebhookNonceRepository;

final class AccessWebhookController
{
    private const MAX_CLOCK_SKEW = 300; // seconds
    private const DIRECTIONS     = ['in', 'out'];
    private const RESULTS        = ['granted', 'denied'];

    public function ingest(): void
    {
        if (!RateLimit::enforce('webhook', 60, 60, RateLimit::ipKey())) {
            return;
        }

        $secret = (string) ($_ENV['ACCESS_WEBHOOK_SECRET'] ?? getenv('ACCESS_WEBHOOK_SECRET') ?: '');
        if ($secret === '' || $secret === 'change-me') {
            Response::error('Webhook secret nao configurado.', 503);
            return;
        }

        $rawBody = (string) file_get_contents('php://input');
        if ($rawBody === '' || strlen($rawBody) > 16384) {
            Response::error('Payload invalido.', 400);
            return;
        }

        $signatureHeader = (string) ($_SERVER['HTTP_X_SIGNATURE'] ?? '');
        $timestampHeader = (string) ($_SERVER['HTTP_X_TIMESTAMP'] ?? '');
        if ($signatureHeader === '' || $timestampHeader === '') {
            Response::error('Headers de assinatura ausentes.', 401);
            return;
        }

        $ts = (int) $timestampHeader;
        if ($ts <= 0 || abs(time() - $ts) > self::MAX_CLOCK_SKEW) {
            Response::error('Timestamp fora da janela permitida.', 401);
            return;
        }

        $expected = 'sha256=' . hash_hmac('sha256', $timestampHeader . '.' . $rawBody, $secret);
        if (!hash_equals($expected, $signatureHeader)) {
            Response::error('Assinatura invalida.', 401);
            return;
        }

        // Replay protection: claim signature hash with TTL = clock-skew window.
        $nonceHash = hash('sha256', $signatureHeader);
        $nonceRepo = new WebhookNonceRepository();
        if (!$nonceRepo->claim($nonceHash, $ts + self::MAX_CLOCK_SKEW)) {
            Response::error('Webhook ja processado.', 409);
            return;
        }
        // Best-effort cleanup; never blocks ingestion.
        if (random_int(0, 99) === 0) {
            try { $nonceRepo->purgeExpired(); } catch (\Throwable) { /* noop */ }
        }

        $payload = json_decode($rawBody, true);
        if (!is_array($payload)) {
            Response::error('JSON invalido.', 400);
            return;
        }

        $cid = isset($payload['condominium_id']) ? (int) $payload['condominium_id'] : 0;
        if ($cid <= 0) {
            Response::error('condominium_id obrigatorio.', 422);
            return;
        }
        if ((new CondominiumRepository())->find($cid) === null) {
            Response::error('Condominio nao encontrado.', 404);
            return;
        }

        $direction = (string) ($payload['direction'] ?? '');
        if (!in_array($direction, self::DIRECTIONS, true)) {
            Response::error('Direction invalido.', 422, ['allowed' => self::DIRECTIONS]);
            return;
        }

        $result = (string) ($payload['result'] ?? 'granted');
        if (!in_array($result, self::RESULTS, true)) {
            Response::error('Result invalido.', 422, ['allowed' => self::RESULTS]);
            return;
        }

        $userId    = isset($payload['user_id'])    && $payload['user_id']    !== '' ? (int) $payload['user_id']    : null;
        $visitorId = isset($payload['visitor_id']) && $payload['visitor_id'] !== '' ? (int) $payload['visitor_id'] : null;
        $unitId    = isset($payload['unit_id'])    && $payload['unit_id']    !== '' ? (int) $payload['unit_id']    : null;

        $reason = isset($payload['reason']) ? mb_substr((string) $payload['reason'], 0, 500) : null;
        if ($reason === '') {
            $reason = null;
        }

        $photoUrl = isset($payload['photo_url']) ? (string) $payload['photo_url'] : null;
        if ($photoUrl !== null && $photoUrl !== '') {
            if (!filter_var($photoUrl, FILTER_VALIDATE_URL) || !preg_match('#^https://#i', $photoUrl)) {
                Response::error('photo_url deve ser https valido.', 422);
                return;
            }
            $photoUrl = mb_substr($photoUrl, 0, 500);
        } else {
            $photoUrl = null;
        }

        $id = (new AccessLogRepository())->record(
            $cid,
            $userId,
            $visitorId,
            $unitId,
            $direction,
            $result,
            $reason,
            $photoUrl
        );

        Response::json(['id' => $id, 'accepted' => true], 201);
    }
}
