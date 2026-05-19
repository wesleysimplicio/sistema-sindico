<?php

declare(strict_types=1);

namespace App\Core;

use RuntimeException;

final class ResendMailTransport implements MailTransport
{
    public function __construct(
        private readonly string $apiKey,
        private readonly string $baseUrl = 'https://api.resend.com',
        private readonly int $timeoutSeconds = 10,
    ) {
    }

    public function send(MailMessage $message): MailReceipt
    {
        $payload = json_encode([
            'from' => $message->fromName !== null && $message->fromName !== ''
                ? sprintf('%s <%s>', $message->fromName, $message->from)
                : $message->from,
            'to' => [$message->to],
            'subject' => $message->subject,
            'text' => $message->text,
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        $context = stream_context_create([
            'http' => [
                'method' => 'POST',
                'header' => implode("\r\n", [
                    'Content-Type: application/json',
                    'Authorization: Bearer ' . $this->apiKey,
                ]),
                'content' => $payload ?: '{}',
                'timeout' => $this->timeoutSeconds,
                'ignore_errors' => true,
            ],
        ]);

        $response = @file_get_contents(rtrim($this->baseUrl, '/') . '/emails', false, $context);
        $statusLine = $http_response_header[0] ?? '';
        preg_match('/\s(\d{3})\s/', $statusLine, $matches);
        $status = isset($matches[1]) ? (int) $matches[1] : 0;

        if ($response === false || $status < 200 || $status >= 300) {
            throw new RuntimeException('Falha ao enviar email transacional pelo provedor configurado.');
        }

        $decoded = json_decode($response, true);
        $messageId = is_array($decoded) ? (string) ($decoded['id'] ?? '') : '';

        return new MailReceipt(
            driver: 'resend',
            delivered: true,
            messageId: $messageId !== '' ? $messageId : null,
        );
    }
}
