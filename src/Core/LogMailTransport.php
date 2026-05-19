<?php

declare(strict_types=1);

namespace App\Core;

final class LogMailTransport implements MailTransport
{
    public function __construct(
        private readonly string $logPath,
        private readonly bool $debugRevealCode = false,
    ) {
    }

    public function send(MailMessage $message): MailReceipt
    {
        $dir = dirname($this->logPath);
        if (!is_dir($dir)) {
            mkdir($dir, 0775, true);
        }

        // Intentionally omit the plaintext body/code from logs.
        $line = json_encode([
            'timestamp' => gmdate('c'),
            'driver' => 'log',
            'to' => $message->to,
            'subject' => $message->subject,
            'preview' => 'local-debug-response-only',
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        file_put_contents($this->logPath, ($line ?: '{}') . PHP_EOL, FILE_APPEND);

        return new MailReceipt(
            driver: 'log',
            delivered: true,
            debugCode: $this->debugRevealCode ? $this->extractCode($message->text) : null,
        );
    }

    private function extractCode(string $text): ?string
    {
        if (preg_match('/\b(\d{6})\b/', $text, $matches) === 1) {
            return $matches[1];
        }
        return null;
    }
}
