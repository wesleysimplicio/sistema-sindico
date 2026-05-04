<?php

declare(strict_types=1);

namespace App\Controllers\Api;

use App\Core\Auth;
use App\Core\Request;
use App\Core\Response;
use App\Repositories\AuditLogRepository;
use App\Repositories\GateTriggerLogRepository;
use App\Repositories\GateTriggerRepository;

final class GateTriggerController
{
    public function index(): void
    {
        $cid = Auth::condominiumId();
        if ($cid === null) {
            Response::error('Nao autenticado.', 401);
            return;
        }
        $items = (new GateTriggerRepository())->listByCondominium($cid);
        foreach ($items as &$row) {
            unset($row['auth_token']);
        }
        Response::json($items, 200, ['count' => count($items)]);
    }

    public function fire(array $params): void
    {
        $cid  = Auth::condominiumId();
        $uid  = Auth::id();
        $role = Auth::role();
        if ($cid === null || $uid === null) {
            Response::error('Nao autenticado.', 401);
            return;
        }
        if (!in_array($role, ['admin', 'sindico', 'porteiro'], true)) {
            Response::error('Sem permissao para acionar portao.', 403);
            return;
        }
        $id = (int) ($params['id'] ?? 0);
        $row = (new GateTriggerRepository())->findInCondo($id, $cid);
        if ($row === null) {
            Response::error('Acionador nao encontrado.', 404);
            return;
        }
        if ((int) ($row['enabled'] ?? 0) !== 1) {
            Response::error('Acionador desabilitado.', 409);
            return;
        }

        $endpoint  = (string) ($row['endpoint_url'] ?? '');
        $timeoutMs = (int)    ($row['timeout_ms']   ?? 5000);
        $authToken = (string) ($row['auth_token']   ?? '');

        $start = microtime(true);
        [$httpStatus, $error] = $this->callDevice($endpoint, $authToken, $timeoutMs);
        $durationMs = (int) ((microtime(true) - $start) * 1000);

        $result = ($error === null && $httpStatus !== null && $httpStatus >= 200 && $httpStatus < 300)
            ? 'success'
            : 'failure';

        $sanitizedError = $error !== null ? self::sanitizeCurlError($error) : null;

        $logId = (new GateTriggerLogRepository())->record(
            $id,
            $uid,
            $result,
            $httpStatus,
            $durationMs,
            $sanitizedError
        );

        (new AuditLogRepository())->record(
            $uid,
            $cid,
            'gate.fired',
            'gate_trigger',
            $id,
            ['result' => $result, 'http_status' => $httpStatus, 'duration_ms' => $durationMs],
            Request::ip()
        );

        Response::json([
            'log_id'      => $logId,
            'result'      => $result,
            'http_status' => $httpStatus,
            'duration_ms' => $durationMs,
            'error'       => $sanitizedError,
        ], $result === 'success' ? 200 : 502);
    }

    /**
     * Strip URLs and credential-looking tokens from cURL error strings before
     * persisting/returning them, so endpoint_url query params or auth_token leakage
     * via misconfiguration cannot reach gate_trigger_logs or API consumers.
     */
    private static function sanitizeCurlError(string $error): string
    {
        $error = preg_replace('#https?://\S+#i', '<url>', $error) ?? $error;
        $error = preg_replace('/[A-Za-z0-9_\-]{32,}/', '<token>', $error) ?? $error;
        return mb_substr($error, 0, 500);
    }

    public function logs(array $params): void
    {
        $cid = Auth::condominiumId();
        if ($cid === null) {
            Response::error('Nao autenticado.', 401);
            return;
        }
        $id = (int) ($params['id'] ?? 0);
        if (!(new GateTriggerRepository())->existsInCondo($id, $cid)) {
            Response::error('Acionador nao encontrado.', 404);
            return;
        }
        $limit = isset($_GET['limit']) ? (int) $_GET['limit'] : 50;
        $items = (new GateTriggerLogRepository())->listForTrigger($id, $limit);
        Response::json($items, 200, ['count' => count($items)]);
    }

    /**
     * @return array{0:?int,1:?string}
     */
    private function callDevice(string $endpoint, string $authToken, int $timeoutMs): array
    {
        if ($endpoint === '' || !preg_match('/^https?:\/\//i', $endpoint)) {
            return [null, 'invalid endpoint_url'];
        }
        $parts = parse_url($endpoint);
        if ($parts === false || empty($parts['host'])) {
            return [null, 'invalid endpoint_url'];
        }
        $host   = (string) $parts['host'];
        $scheme = strtolower((string) ($parts['scheme'] ?? 'http'));
        $port   = isset($parts['port']) ? (int) $parts['port'] : ($scheme === 'https' ? 443 : 80);

        // Resolve host once and validate against private/loopback/link-local/CGNAT/etc.
        $ip = self::resolveHost($host);
        if ($ip === null) {
            return [null, 'dns resolve failed'];
        }
        if (!self::isPublicIp($ip)) {
            return [null, 'endpoint resolves to disallowed address'];
        }
        if (!function_exists('curl_init')) {
            return [null, 'curl extension missing'];
        }
        $ch = curl_init($endpoint);
        if ($ch === false) {
            return [null, 'curl init failed'];
        }
        $headers = ['Content-Type: application/json'];
        if ($authToken !== '') {
            $headers[] = 'Authorization: Bearer ' . $authToken;
        }
        curl_setopt_array($ch, [
            CURLOPT_POST              => true,
            CURLOPT_POSTFIELDS        => json_encode(['action' => 'open']),
            CURLOPT_HTTPHEADER        => $headers,
            CURLOPT_RETURNTRANSFER    => true,
            CURLOPT_TIMEOUT_MS        => max(500, min(30000, $timeoutMs)),
            CURLOPT_CONNECTTIMEOUT_MS => max(500, min(10000, $timeoutMs)),
            CURLOPT_FOLLOWLOCATION    => false,
            CURLOPT_SSL_VERIFYPEER    => true,
            CURLOPT_SSL_VERIFYHOST    => 2,
            CURLOPT_PROTOCOLS         => CURLPROTO_HTTP | CURLPROTO_HTTPS,
            CURLOPT_REDIR_PROTOCOLS   => CURLPROTO_HTTPS,
            // Pin DNS to the validated IP so a rebind between resolve and connect cannot redirect to private space.
            CURLOPT_RESOLVE           => [sprintf('%s:%d:%s', $host, $port, $ip)],
        ]);
        curl_exec($ch);
        $err  = curl_errno($ch) !== 0 ? curl_error($ch) : null;
        $code = curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
        curl_close($ch);
        return [$code > 0 ? (int) $code : null, $err];
    }

    private static function resolveHost(string $host): ?string
    {
        // Already an IP literal? Use as-is.
        if (filter_var($host, FILTER_VALIDATE_IP)) {
            return $host;
        }
        // Strip IPv6 brackets if any.
        $clean = trim($host, '[]');
        if (filter_var($clean, FILTER_VALIDATE_IP)) {
            return $clean;
        }
        $resolved = @gethostbyname($host);
        if ($resolved === '' || $resolved === $host) {
            return null;
        }
        return $resolved;
    }

    private static function isPublicIp(string $ip): bool
    {
        return (bool) filter_var(
            $ip,
            FILTER_VALIDATE_IP,
            FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE
        );
    }
}
